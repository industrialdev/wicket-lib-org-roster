<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Services\ConfigService;

it('detects additional seats form id by slug when form id is zero', function (): void {
    Functions\when('wicket_gf_get_form_id_by_slug')->alias(function (string $slug): int {
        return $slug === 'additional-seats' ? 55 : 0;
    });

    $service = new ConfigService();

    expect($service->get_additional_seats_form_id())->toBe(55);
});

it('includes membership cycle strategy configuration defaults', function (): void {
    $config = \OrgManagement\Config\get_config();

    expect($config['roster']['strategy'] ?? null)->toBeString();
    expect($config['membership_cycle'] ?? null)->toBeArray();
    expect($config['membership_cycle']['strategy_key'] ?? null)->toBe('membership_cycle');
    expect($config['membership_cycle']['member_management']['require_explicit_membership_uuid'] ?? null)->toBeTrue();
});

it('keeps membership cycle config limited to active keys', function (): void {
    $config = \OrgManagement\Config\get_config();
    $cycle = $config['membership_cycle'] ?? [];

    expect($cycle['permissions'] ?? [])->toHaveKeys([
        'add_roles',
        'remove_roles',
        'purchase_seats_roles',
        'prevent_owner_removal',
    ]);

    expect($cycle['permissions'] ?? [])->not->toHaveKeys([
        'view_roles',
        'bulk_upload_roles',
    ]);

    expect($cycle['member_management'] ?? [])->toHaveKey('require_explicit_membership_uuid');
    expect($cycle['member_management'] ?? [])->not->toHaveKeys([
        'duplicate_scope',
        'removal_mode',
        'removal_end_date_format',
    ]);

    expect($cycle)->not->toHaveKeys([
        'bulk_upload',
        'seats',
        'ui',
    ]);
});
