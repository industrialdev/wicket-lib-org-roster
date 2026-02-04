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
