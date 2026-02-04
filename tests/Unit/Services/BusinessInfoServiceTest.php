<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Services\BusinessInfoService;

it('sanitizes business info payload values against configured options', function (): void {
    Functions\when('wp_list_pluck')->alias(fn(array $list, string $field): array => array_map(
        static fn(array $item) => $item[$field] ?? null,
        $list
    ));

    $service = new BusinessInfoService();
    $sections = $service->get_sections_config();
    $section = $sections['company_attributes'];

    $payload = [
        'company_attributes' => [
            'women-owned',
            'invalid-value',
            'women-owned',
        ],
        'company_attributes_other' => 'Custom label',
    ];

    $result = (function (string $sectionKey, array $sectionConfig, array $payloadData) {
        return $this->sanitize_section_payload($sectionKey, $sectionConfig, $payloadData);
    })->call($service, 'company_attributes', $section, $payload);

    expect($result)->toBe([
        'attributes' => ['women-owned'],
        'attributesother' => 'Custom label',
    ]);
});

it('returns an error when updating sections without an org id', function (): void {
    $service = new BusinessInfoService();

    $result = $service->update_sections('', []);

    expect(is_wp_error($result))->toBeTrue()
        ->and($result->get_error_code())->toBe('missing_org');
});
