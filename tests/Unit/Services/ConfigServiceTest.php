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

it('includes remove policy ui defaults for backward-compatible roster controls', function (): void {
    $config = \OrgManagement\Config\get_config();
    $member_list = $config['ui']['member_list'] ?? [];
    $callout = $member_list['remove_policy_callout'] ?? [];

    expect($member_list['show_remove_button'] ?? null)->toBeTrue();
    expect($member_list['seat_limit_message'] ?? null)->toBeString();
    expect($member_list['seat_limit_message'] ?? null)->not->toBe('');
    expect($callout)->toHaveKeys([
        'enabled',
        'placement',
        'title',
        'message',
        'email',
    ]);
    expect($callout['enabled'] ?? null)->toBeFalse();
    expect($callout['placement'] ?? null)->toBe('above_members');
    expect($callout['title'] ?? null)->toBe('Remove Members');
    expect($callout['email'] ?? null)->toBeString();
});

it('includes member edit activity guard default as disabled for backward compatibility', function (): void {
    $config = \OrgManagement\Config\get_config();

    expect($config['member_edit'] ?? [])->toHaveKey('require_active_membership_for_role_updates');
    expect($config['member_edit']['require_active_membership_for_role_updates'] ?? null)->toBeFalse();
});

it('keeps member card active-relationship filter disabled by default for backward compatibility', function (): void {
    $config = \OrgManagement\Config\get_config();

    expect($config['relationships'] ?? [])->toHaveKey('member_card_active_only');
    expect($config['relationships']['member_card_active_only'] ?? null)->toBeFalse();
});

it('includes tier-based seat policy mapping defaults as opt-in only', function (): void {
    $config = \OrgManagement\Config\get_config();
    $seatPolicy = $config['seat_policy'] ?? [];

    expect($seatPolicy)->toHaveKeys([
        'tier_max_assignments',
        'tier_name_case_sensitive',
    ]);
    expect($seatPolicy['tier_max_assignments'] ?? null)->toBeArray();
    expect($seatPolicy['tier_max_assignments'] ?? null)->toBe([]);
    expect($seatPolicy['tier_name_case_sensitive'] ?? null)->toBeFalse();
});
