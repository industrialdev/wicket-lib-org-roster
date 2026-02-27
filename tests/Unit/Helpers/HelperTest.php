<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Helpers\Helper;

it('falls back to the provided path when my-account post is missing', function (): void {
    Functions\when('get_posts')->alias(fn (array $args): array => []);
    Functions\when('home_url')->alias(fn (string $path = ''): string => 'https://example.test' . $path);

    $url = Helper::get_my_account_page_url('organization-members', '/my-account/organization-members/');

    expect($url)->toBe('https://example.test/my-account/organization-members/');
});

it('shows member roles on cards by default', function (): void {
    expect(Helper::should_show_member_roles())->toBeTrue();
});

it('is aligned with shared config for member roles visibility', function (): void {
    $config = OrgManagement\Config\OrgManConfig::get();

    expect($config['ui']['member_card_fields']['roles']['enabled'] ?? null)->toBeTrue();
    expect(Helper::should_show_member_roles())->toBeTrue();
});
