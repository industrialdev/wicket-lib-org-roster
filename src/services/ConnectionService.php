<?php
/**
 * Connection Service for Org Management.
 */

declare(strict_types=1);

namespace OrgManagement\Services;

use DateTimeImmutable;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles membership and connection helpers against the Wicket API.
 */
class ConnectionService {

	/**
	 * Determine if a person belongs to a given organization membership.
	 *
	 * @param string $personUuid
	 * @param string $membershipUuid
	 * @return bool|WP_Error
	 */
	public function personHasMembership( string $personUuid, string $membershipUuid ) {
		if ( ! function_exists( 'wicket_api_client' ) ) {
			return new WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
		}

		try {
			$client   = wicket_api_client();
			$endpoint = '/organization_memberships/' . rawurlencode( $membershipUuid ) . '/person_memberships';
			$response = $client->get( $endpoint . '?page[number]=1&page[size]=100&include=person' );

			if ( empty( $response['data'] ) || ! is_array( $response['data'] ) ) {
				return false;
			}

			foreach ( $response['data'] as $member ) {
				$currentId = $member['relationships']['person']['data']['id'] ?? null;
				if ( $currentId === $personUuid ) {
					return true;
				}
			}
		} catch ( \Throwable $e ) {
			return new WP_Error( 'membership_lookup_failed', $e->getMessage() );
		}

		return false;
	}

	/**
	 * Ensure a person-to-organization connection exists.
	 *
	 * @param string $personUuid
	 * @param string $orgUuid
	 * @param array  $overrides Optional overrides including 'type' for relationship type
	 * @return true|WP_Error
	 */
	public function ensurePersonConnection( string $personUuid, string $orgUuid, array $overrides = [] ) {
		// Use relationship type from overrides if provided, otherwise use configured default
		$relationshipType = $overrides['type'] ?? \OrgManagement\Helpers\RelationshipHelper::get_default_relationship_type();

		// Remove 'type' from overrides since we're passing it as a separate parameter
		unset($overrides['type']);

		if ( ! function_exists( 'wicket_create_person_to_org_connection' ) ) {
			return new WP_Error( 'missing_dependency', 'Connection helper is unavailable.' );
		}

		$attributes = array_merge(
			[
				'connection_type' => 'person_to_organization',
				'starts_at'       => $this->currentStartDate(),
			],
			$overrides
		);

		$result = wicket_create_person_to_org_connection( $personUuid, $orgUuid, $relationshipType, true, $attributes );

		if ( false === $result ) {
			return new WP_Error( 'connection_failed', 'Failed to create organization connection.' );
		}

		if ( isset( $result['error'] ) && true === $result['error'] ) {
			$message = is_array( $result['message'] ?? null ) ? wp_json_encode( $result['message'] ) : ( $result['message'] ?? 'Failed to create organization connection.' );
			return new WP_Error( 'connection_failed', $message );
		}

		return true;
	}

	/**
	 * Current start date in site timezone.
	 *
	 * @return string
	 */
	private function currentStartDate(): string {
		$timezone = wp_timezone();
		$now      = new DateTimeImmutable( 'now', $timezone );

		return $now->setTime( 0, 0 )->format( 'c' );
	}

	/**
	 * Get person connections by UUID.
	 *
	 * @param string $person_uuid The person UUID
	 * @return array|false The person connections or false if not found
	 */
	public function getPersonConnectionsById( $person_uuid ) {
		if ( empty( $person_uuid ) || ! function_exists( 'wicket_api_client' ) ) {
			return false;
		}

		try {
			$client = wicket_api_client();
			$response = $client->get( 'people/' . rawurlencode( $person_uuid ) . '/connections?filter%5Bconnection_type_eq%5D=all&sort=-created_at' );

			if ( ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
				return false;
			}

			return $response;
		} catch ( \Throwable $e ) {
			error_log( '[ConnectionService] Failed to get person connections: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * End a person's relationship with an organization today.
	 *
	 * @param string $person_uuid The UUID of the person.
	 * @param string $relationship_id The ID of the relationship to end.
	 * @param string $org_id The ID of the organization.
	 * @return array|WP_Error The updated connection data or WP_Error on failure.
	 */
	public function endRelationshipToday( $person_uuid, $relationship_id, $org_id ) {
		if ( empty( $person_uuid ) || empty( $relationship_id ) || empty( $org_id ) ) {
			return new \WP_Error( 'invalid_params', 'Person UUID, relationship ID, and organization ID are required.' );
		}

		try {
			$client = wicket_api_client();

			// Get the current connection
			$connection = wicket_get_connection_by_id( $relationship_id );
			if ( ! $connection || empty( $connection['data'] ) ) {
				return new \WP_Error( 'connection_not_found', 'Connection not found.' );
			}

			// Prepare the update payload with end date set to today
			$connection_data = $connection['data'];
			$attributes = $connection_data['attributes'];

			// Legacy date format logic
			$ends_at = (new \DateTime('@' . strtotime(date('Y-m-d H:i:s', current_time('timestamp'))), wp_timezone()))->format('Y-m-d\T00:00:00-05:01');

			// Fix tags, if empty or null, make it an empty array
			$attributes['tags'] = !empty($attributes['tags']) ? $attributes['tags'] : [];
			if ($attributes['tags'] === null) {
				$attributes['tags'] = [];
			}

			// Ensure empty fields stay null
			$attributes['description']       = !empty($attributes['description']) ? $attributes['description'] : null;
			$attributes['custom_data_field'] = !empty($attributes['custom_data_field']) ? $attributes['custom_data_field'] : null;

			$update_payload = [
				'data' => [
					'type'          => $connection_data['type'],
					'id'            => $relationship_id,
					'attributes'    => [
						'type'              => $attributes['type'],
						'starts_at'         => $attributes['starts_at'],
						'ends_at'           => $ends_at,
						'description'       => $attributes['description'],
						'tags'              => $attributes['tags'],
						'custom_data_field' => $attributes['custom_data_field'],
					],
					'relationships' => [
						'from' => [
							'data' => [
								'type' => $connection_data['relationships']['from']['data']['type'],
								'id'   => $connection_data['relationships']['from']['data']['id'],
								'meta' => [
									'can_manage' => true,
									'can_update' => true
								]
							],
						],
						'to'   => [
							'data' => [
								'type' => $connection_data['relationships']['to']['data']['type'],
								'id'   => $connection_data['relationships']['to']['data']['id'],
							],
						],
					]
				]
			];

			// Update the connection
			$response = $client->patch( "connections/{$relationship_id}", ['json' => $update_payload] );

			if ( ! empty( $response['errors'] ) ) {
				error_log( "ConnectionService::end_relationship_today() - API error: " . json_encode( $response['errors'] ) );
				return new \WP_Error( 'api_error', 'Failed to end relationship: ' . ( $response['errors'][0]['detail'] ?? 'Unknown error' ) );
			}

			return $response;

		} catch ( \Exception $e ) {
			error_log( "ConnectionService::end_relationship_today() - Exception: " . $e->getMessage() );
			return new \WP_Error( 'end_relationship_exception', $e->getMessage() );
		}
	}

	/**
	 * Builds a payload for creating a new connection between a person and an organization.
	 *
	 * This method provides backward compatibility with the legacy function signature.
	 *
	 * @param string $person_id The UUID of the person to connect to the organization.
	 * @param string $org_id The UUID of the organization to connect the person to.
	 * @param string $connection_type The type of connection to create (e.g., 'person_to_organization').
     * @param string $type The specific type of relationship (e.g., 'Position').
     * @param string|null $description Optional relationship description.
     * @return array|WP_Error The connection payload or WP_Error on failure.
     */
    public function buildConnectionPayload( $person_id = null, $org_id = null, $connection_type = null, $type = null, $description = null ) {
        if ( empty( $person_id ) || empty( $org_id ) || empty( $connection_type ) || empty( $type ) ) {
            return new \WP_Error( 'invalid_params', 'Person ID, organization ID, connection type, and type are required.' );
        }

        try {
            $now_date = ( new \DateTime( '@' . strtotime( date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) ), wp_timezone() ) )->format( 'Y-m-d\T00:00:00-05:00' );

            $description = is_string( $description ) ? sanitize_textarea_field( $description ) : '';
            $payload = [
                'data' => [
                    'type'          => 'connections',
                    'attributes'    => [
                        'connection_type' => $connection_type,
                        'type'            => $type,
                        'starts_at'       => $now_date,
                    ],
                    'relationships' => [
                        'organization' => [
							'data' => [
								'id'   => $org_id,
								'type' => 'organizations',
							],
						],
						'person'       => [
							'data' => [
								'id'   => $person_id,
								'type' => 'people',
							],
						],
						'from'         => [
							'data' => [
								'id'   => $person_id,
								'type' => 'people',
							],
						],
						'to'           => [
							'data' => [
								'id'   => $org_id,
								'type' => 'organizations',
							],
						],
                    ],
                ],
            ];

            if ( $description !== '' ) {
                $payload['data']['attributes']['description'] = $description;
            }

            return $payload;

        } catch ( \Exception $e ) {
            error_log( "ConnectionService::buildConnectionPayload() - Exception: " . $e->getMessage() );
            return new \WP_Error( 'build_payload_exception', $e->getMessage() );
        }
    }

    /**
     * Update the relationship description for a person-to-organization connection.
     *
     * @param string $person_uuid The UUID of the person.
     * @param string $org_id The UUID of the organization.
     * @param string $description The new relationship description.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public function updateConnectionDescription( $person_uuid, $org_id, $description ) {
        if ( empty( $person_uuid ) || empty( $org_id ) ) {
            return new \WP_Error( 'invalid_params', 'Person UUID and organization ID are required.' );
        }

        if ( ! function_exists( 'wicket_api_client' ) ) {
            return new \WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
        }

        try {
            $connections = $this->getPersonConnectionsById( $person_uuid );

            if ( empty( $connections['data'] ) ) {
                return new \WP_Error( 'no_connection', 'No connection found for this person and organization.' );
            }

            $connection_ids = [];
            foreach ( $connections['data'] as $connection ) {
                if (
                    isset( $connection['relationships']['organization']['data']['id'] ) &&
                    $connection['relationships']['organization']['data']['id'] === $org_id &&
                    isset( $connection['attributes']['connection_type'] ) &&
                    $connection['attributes']['connection_type'] === 'person_to_organization'
                ) {
                    $connection_ids[] = $connection['id'];
                }
            }

            if ( empty( $connection_ids ) ) {
                return new \WP_Error( 'no_connection', 'No active person-to-organization connection found.' );
            }

            $client = wicket_api_client();
            $description = is_string( $description ) ? sanitize_textarea_field( $description ) : '';
            $description = $description !== '' ? $description : null;

            foreach ( $connection_ids as $connection_id ) {
                $connection = wicket_get_connection_by_id( $connection_id );

                if ( ! $connection || empty( $connection['data'] ) ) {
                    continue;
                }

                $connection_data = $connection['data'];
                $attributes = $connection_data['attributes'];

                if ( empty( $attributes['resource_type'] ) ) {
                    if ( ! empty( $connection_data['relationships']['organization']['data']['type'] ) ) {
                        $attributes['resource_type'] = $connection_data['relationships']['organization']['data']['type'];
                    } elseif ( ! empty( $connection_data['relationships']['to']['data']['type'] ) ) {
                        $attributes['resource_type'] = $connection_data['relationships']['to']['data']['type'];
                    } elseif ( ( $attributes['connection_type'] ?? '' ) === 'person_to_organization' ) {
                        $attributes['resource_type'] = 'organizations';
                    }
                }
                if ( empty( $attributes['resource_type'] ) ) {
                    $attributes['resource_type'] = 'organizations';
                }

                $attributes['tags'] = ! empty( $attributes['tags'] ) ? $attributes['tags'] : [];
                if ( $attributes['tags'] === null ) {
                    $attributes['tags'] = [];
                }

                $attributes['custom_data_field'] = ! empty( $attributes['custom_data_field'] ) ? $attributes['custom_data_field'] : null;

                $relationships = [
                    'from' => [
                        'data' => [
                            'type' => $connection_data['relationships']['from']['data']['type'],
                            'id'   => $connection_data['relationships']['from']['data']['id'],
                            'meta' => [
                                'can_manage' => true,
                                'can_update' => true
                            ]
                        ],
                    ],
                    'to'   => [
                        'data' => [
                            'type' => $connection_data['relationships']['to']['data']['type'],
                            'id'   => $connection_data['relationships']['to']['data']['id'],
                        ],
                    ],
                ];
                if ( ! empty( $connection_data['relationships']['organization']['data'] ) ) {
                    $relationships['organization'] = [
                        'data' => [
                            'type' => $connection_data['relationships']['organization']['data']['type'],
                            'id'   => $connection_data['relationships']['organization']['data']['id'],
                        ],
                    ];
                }
                if ( ! empty( $connection_data['relationships']['person']['data'] ) ) {
                    $relationships['person'] = [
                        'data' => [
                            'type' => $connection_data['relationships']['person']['data']['type'],
                            'id'   => $connection_data['relationships']['person']['data']['id'],
                        ],
                    ];
                }

                $update_payload = [
                    'data' => [
                        'type'          => $connection_data['type'],
                        'id'            => $connection_id,
                        'attributes'    => [
                            'connection_type'   => $attributes['connection_type'] ?? 'person_to_organization',
                            'resource_type'     => $attributes['resource_type'] ?? null,
                            'type'              => $attributes['type'],
                            'starts_at'         => $attributes['starts_at'],
                            'ends_at'           => $attributes['ends_at'] ?? null,
                            'description'       => $description,
                            'tags'              => $attributes['tags'],
                            'custom_data_field' => $attributes['custom_data_field'],
                        ],
                        'relationships' => $relationships
                    ]
                ];

                $response = $client->patch( "connections/{$connection_id}", ['json' => $update_payload] );

                if ( ! empty( $response['errors'] ) ) {
                    error_log( "ConnectionService::updateConnectionDescription() - API error: " . json_encode( $response['errors'] ) );
                    return new \WP_Error( 'api_error', 'Failed to update connection description: ' . ( $response['errors'][0]['detail'] ?? 'Unknown error' ) );
                }
            }

            return true;

        } catch ( \Exception $e ) {
            error_log( "ConnectionService::updateConnectionDescription() - Exception: " . $e->getMessage() );
            return new \WP_Error( 'update_connection_exception', $e->getMessage() );
        }
    }

	/**
	 * Creates a new connection in the API.
	 *
	 * This method provides backward compatibility with the legacy function signature.
	 *
	 * @param array $payload The connection payload to send to the API.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function createConnection( $payload ) {
		if ( empty( $payload ) || ! is_array( $payload ) ) {
			return new \WP_Error( 'invalid_params', 'Valid payload array is required.' );
		}

		if ( ! function_exists( 'wicket_api_client' ) ) {
			return new \WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
		}

		try {
			$client = wicket_api_client();
			$response = $client->post( 'connections', [ 'json' => $payload ] );

			// If we get here without an exception, the connection was created successfully
			return true;

		} catch ( \Exception $e ) {
			$error_message = $e->getMessage();

			// Try to extract more detailed error information from the response
			if ( method_exists( $e, 'getResponse' ) && $e->getResponse() ) {
				try {
					$error_body = json_decode( $e->getResponse()->getBody(), true );
					if ( isset( $error_body['errors'] ) && ! empty( $error_body['errors'] ) ) {
						$error_message = $error_body['errors'][0]['detail'] ?? $error_message;
					}
				} catch ( \Exception $json_error ) {
					// If we can't parse the JSON, just use the original error message
					error_log( "ConnectionService::createConnection() - JSON parse error: " . $json_error->getMessage() );
				}
			}

			error_log( "ConnectionService::createConnection() - Exception: " . $error_message );
			return new \WP_Error( 'connection_creation_failed', $error_message );
		}
	}

	/**
	 * Check if a person has a relationship with an organization.
	 *
	 * This method provides backward compatibility with the legacy function signature.
	 *
	 * @param string $person_uuid The UUID of the person.
	 * @param string $org_id The UUID of the organization.
	 * @return bool|WP_Error True if person has relationship, false if not, WP_Error on failure.
	 */
	public function personHasRelationship( $person_uuid, $org_id ) {
		if ( empty( $person_uuid ) || empty( $org_id ) ) {
			return new \WP_Error( 'invalid_params', 'Person UUID and organization ID are required.' );
		}

		if ( ! function_exists( 'wicket_api_client' ) ) {
			return new \WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
		}

		try {
			$client = wicket_api_client();
			$response = $client->get( "people/{$person_uuid}/connections?page[number]=1&page[size]=30&filter[connection_type_eq]=all&filter[active_true]=true&sort=" );

			if ( isset( $response['data'] ) && ! empty( $response['data'] ) ) {
				foreach ( $response['data'] as $connection ) {
					// Check type, connection type, and the organization ID within the relationships object
					if (
						$connection['type'] == 'connections' &&
						isset( $connection['attributes']['connection_type'] ) &&
						$connection['attributes']['connection_type'] == 'person_to_organization' &&
						isset( $connection['relationships']['organization']['data']['id'] ) &&
						$connection['relationships']['organization']['data']['id'] == $org_id
					) {
						return true;
					}
				}
			}

			return false;

		} catch ( \Exception $e ) {
			error_log( "ConnectionService::personHasRelationship() - Exception: " . $e->getMessage() );
			return new \WP_Error( 'relationship_check_failed', $e->getMessage() );
		}
	}

	/**
	 * Update the relationship type for a person-to-organization connection.
	 *
	 * @param string $person_uuid The UUID of the person.
	 * @param string $org_id The UUID of the organization.
	 * @param string $new_type The new relationship type.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function updateConnectionType( $person_uuid, $org_id, $new_type ) {
		if ( empty( $person_uuid ) || empty( $org_id ) || empty( $new_type ) ) {
			return new \WP_Error( 'invalid_params', 'Person UUID, organization ID, and new type are required.' );
		}

		if ( ! function_exists( 'wicket_api_client' ) ) {
			return new \WP_Error( 'missing_dependency', 'Wicket API client is unavailable.' );
		}

		try {
			// Get the person's active connections to this organization
			$connections = $this->getPersonConnectionsById( $person_uuid );

			if ( empty( $connections['data'] ) ) {
				return new \WP_Error( 'no_connection', 'No connection found for this person and organization.' );
			}

			// Find the matching connection(s)
			$connection_ids = [];
			foreach ( $connections['data'] as $connection ) {
				if (
					isset( $connection['relationships']['organization']['data']['id'] ) &&
					$connection['relationships']['organization']['data']['id'] === $org_id &&
					isset( $connection['attributes']['connection_type'] ) &&
					$connection['attributes']['connection_type'] === 'person_to_organization'
				) {
					$connection_ids[] = $connection['id'];
				}
			}

			if ( empty( $connection_ids ) ) {
				return new \WP_Error( 'no_connection', 'No active person-to-organization connection found.' );
			}

			$client = wicket_api_client();

			// Update each connection (usually just one, but handle multiple)
			foreach ( $connection_ids as $connection_id ) {
				// Get the full connection details
				$connection = wicket_get_connection_by_id( $connection_id );

				if ( ! $connection || empty( $connection['data'] ) ) {
					continue;
				}

				$connection_data = $connection['data'];
				$attributes = $connection_data['attributes'];

				// Update the type
				$attributes['type'] = $new_type;
				if ( empty( $attributes['resource_type'] ) ) {
					if ( ! empty( $connection_data['relationships']['organization']['data']['type'] ) ) {
						$attributes['resource_type'] = $connection_data['relationships']['organization']['data']['type'];
					} elseif ( ! empty( $connection_data['relationships']['to']['data']['type'] ) ) {
						$attributes['resource_type'] = $connection_data['relationships']['to']['data']['type'];
					} elseif ( ( $attributes['connection_type'] ?? '' ) === 'person_to_organization' ) {
						$attributes['resource_type'] = 'organizations';
					}
				}
				if ( empty( $attributes['resource_type'] ) ) {
					$attributes['resource_type'] = 'organizations';
				}

				// Fix tags, if empty or null, make it an empty array
				$attributes['tags'] = ! empty( $attributes['tags'] ) ? $attributes['tags'] : [];
				if ( $attributes['tags'] === null ) {
					$attributes['tags'] = [];
				}

				// Ensure empty fields stay null
				$attributes['description']       = ! empty( $attributes['description'] ) ? $attributes['description'] : null;
				$attributes['custom_data_field'] = ! empty( $attributes['custom_data_field'] ) ? $attributes['custom_data_field'] : null;

				$relationships = [
					'from' => [
						'data' => [
							'type' => $connection_data['relationships']['from']['data']['type'],
							'id'   => $connection_data['relationships']['from']['data']['id'],
							'meta' => [
								'can_manage' => true,
								'can_update' => true
							]
						],
					],
					'to'   => [
						'data' => [
							'type' => $connection_data['relationships']['to']['data']['type'],
							'id'   => $connection_data['relationships']['to']['data']['id'],
						],
					],
				];
				if ( ! empty( $connection_data['relationships']['organization']['data'] ) ) {
					$relationships['organization'] = [
						'data' => [
							'type' => $connection_data['relationships']['organization']['data']['type'],
							'id'   => $connection_data['relationships']['organization']['data']['id'],
						],
					];
				}
				if ( ! empty( $connection_data['relationships']['person']['data'] ) ) {
					$relationships['person'] = [
						'data' => [
							'type' => $connection_data['relationships']['person']['data']['type'],
							'id'   => $connection_data['relationships']['person']['data']['id'],
						],
					];
				}

				$update_payload = [
					'data' => [
						'type'          => $connection_data['type'],
						'id'            => $connection_id,
						'attributes'    => [
							'connection_type'   => $attributes['connection_type'] ?? 'person_to_organization',
							'resource_type'     => $attributes['resource_type'] ?? null,
							'type'              => $new_type,
							'starts_at'         => $attributes['starts_at'],
							'ends_at'           => $attributes['ends_at'] ?? null,
							'description'       => $attributes['description'],
							'tags'              => $attributes['tags'],
							'custom_data_field' => $attributes['custom_data_field'],
						],
						'relationships' => $relationships
					]
				];

				// Update the connection
				$response = $client->patch( "connections/{$connection_id}", ['json' => $update_payload] );

				if ( ! empty( $response['errors'] ) ) {
					error_log( "ConnectionService::updateConnectionType() - API error: " . json_encode( $response['errors'] ) );
					return new \WP_Error( 'api_error', 'Failed to update connection type: ' . ( $response['errors'][0]['detail'] ?? 'Unknown error' ) );
				}
			}

			return true;

		} catch ( \Exception $e ) {
			error_log( "ConnectionService::updateConnectionType() - Exception: " . $e->getMessage() );
			return new \WP_Error( 'update_connection_exception', $e->getMessage() );
		}
	}

}
