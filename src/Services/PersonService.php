<?php

/**
 * Person Service for Org Management.
 */

declare(strict_types=1);

namespace WicketORM\Services;

use WP_Error;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides helpers for interacting with Wicket person records.
 */
class PersonService
{
    /**
     * Create or locate a person in Wicket and ensure key profile fields are updated.
     *
     * @param array $personData
     * @return string|WP_Error Person UUID or error.
     */
    public function createOrUpdatePerson(array $personData)
    {
        $firstName = sanitize_text_field($personData['first_name'] ?? '');
        $lastName = sanitize_text_field($personData['last_name'] ?? '');
        $email = sanitize_email($personData['email'] ?? '');

        if ('' === $firstName || '' === $lastName || '' === $email) {
            return new WP_Error('invalid_member_data', 'First name, last name, and a valid email are required.');
        }

        if (!function_exists('wicket_create_person')) {
            return new WP_Error('missing_dependency', 'Person creation helper function is unavailable.');
        }

        $person = $this->findPersonByEmail($email);
        if (!$person) {
            $person = wicket_create_person($firstName, $lastName, $email);

            if (!$person || (is_array($person) && isset($person['errors']))) {
                return new WP_Error('person_creation_failed', 'Failed to create the person in Wicket.');
            }
        }

        $personUuid = $this->extractPersonUuid($person);
        if (!$personUuid) {
            return new WP_Error('person_resolution_failed', 'Unable to resolve person identifier from Wicket.');
        }

        $this->maybeUpdatePersonProfile($personUuid, $personData);

        return $personUuid;
    }

    /**
     * Locate a person record by email.
     *
     * We first try the legacy helper for backwards compatibility, then fall back to
     * a direct API lookup to avoid duplicate person creation when an email exists but
     * is not flagged as the primary email in upstream data.
     *
     * @param string $email
     * @return mixed
     */
    private function findPersonByEmail(string $email)
    {
        if (function_exists('wicket_get_person_by_email')) {
            $person = wicket_get_person_by_email($email);
            if (!empty($person)) {
                return $person;
            }
        }

        if (!function_exists('wicket_api_client')) {
            return false;
        }

        try {
            $client = wicket_api_client();
            $response = $client->get('/people?filter[emails_address_eq]=' . rawurlencode($email));

            if (!empty($response['data'][0])) {
                return $response['data'][0];
            }
        } catch (\Throwable $e) {
            \Wicket()->log()->error('Fallback person lookup by email failed: ' . $e->getMessage(), ['source' => 'wicket-orgman']);
        }

        return false;
    }

    /**
     * Update optional profile attributes for the person.
     *
     * @param string $personUuid
     * @param array  $personData
     * @return void
     */
    private function maybeUpdatePersonProfile(string $personUuid, array $personData): void
    {
        $jobTitle = isset($personData['job_title']) ? sanitize_text_field((string) $personData['job_title']) : '';
        $phone = isset($personData['phone']) ? preg_replace('/[^0-9+]/', '', (string) $personData['phone']) : '';

        if ('' !== $jobTitle && function_exists('wicket_update_person')) {
            $update = wicket_update_person($personUuid, [
                'attributes' => ['job_title' => $jobTitle],
            ]);

            if (is_array($update) && ($update['success'] ?? false) !== true) {
                \Wicket()->log()->error('Failed to update person job title for ' . $personUuid, ['source' => 'wicket-orgman']);
            }
        }

        if ('' !== $phone && function_exists('wicket_create_person_phone')) {
            try {
                wicket_create_person_phone($personUuid, [
                    'data' => [
                        'type'       => 'phones',
                        'attributes' => [
                            'number' => $phone,
                            'type'   => 'work',
                        ],
                    ],
                ]);
            } catch (\Throwable $e) {
                \Wicket()->log()->error('Failed to attach phone to person ' . $personUuid . ' : ' . $e->getMessage(), ['source' => 'wicket-orgman']);
            }
        }
    }

    /**
     * Safely extract a UUID from Wicket responses.
     *
     * @param mixed $personResponse
     * @return string|null
     */
    private function extractPersonUuid($personResponse): ?string
    {
        if (is_array($personResponse)) {
            return $personResponse['id'] ?? $personResponse['data']['id'] ?? null;
        }

        if (is_object($personResponse)) {
            return $personResponse->id ?? null;
        }

        return null;
    }

    /**
     * Create or get a person by email address.
     *
     * This method provides backward compatibility with the legacy function signature.
     *
     * @param string $first_name The person's first name.
     * @param string $last_name The person's last name.
     * @param string $email The person's email address.
     * @param array  $extras Optional extra data to update on the person.
     * @return string|WP_Error The person UUID or WP_Error on failure.
     */
    public function createOrGetPerson($first_name, $last_name, $email, $extras = [])
    {
        if (empty($first_name) || empty($last_name) || empty($email)) {
            return new WP_Error('invalid_params', 'First name, last name, and email are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new WP_Error('invalid_email', 'Invalid email address format.');
        }

        // Build person data array for the existing method
        $person_data = [
            'first_name' => sanitize_text_field($first_name),
            'last_name'  => sanitize_text_field($last_name),
            'email'      => sanitize_email($email),
        ];

        // Add any extra data
        if (!empty($extras) && is_array($extras)) {
            // Map common extra fields
            if (isset($extras['job_title'])) {
                $person_data['job_title'] = sanitize_text_field($extras['job_title']);
            }
            if (isset($extras['phone'])) {
                $person_data['phone'] = sanitize_text_field($extras['phone']);
            }
            // Add any other extra fields as-is (they'll be handled by maybeUpdatePersonProfile if needed)
            foreach ($extras as $key => $value) {
                if (!isset($person_data[$key])) {
                    $person_data[$key] = $value;
                }
            }
        }

        return $this->createOrUpdatePerson($person_data);
    }
}
