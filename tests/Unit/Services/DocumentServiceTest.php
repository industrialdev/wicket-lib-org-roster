<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Services\DocumentService;

it('rejects invalid document file types', function (): void {
    $service = new DocumentService();

    $result = $service->upload_document('org-1', [
        'name' => 'payload.exe',
        'size' => 100,
        'tmp_name' => '/tmp/payload.exe',
    ]);

    expect(is_wp_error($result))->toBeTrue()
        ->and($result->get_error_code())->toBe('invalid_file_type');
});

it('rejects documents larger than configured max size', function (): void {
    Functions\when('size_format')->alias(fn (int $bytes): string => $bytes . ' bytes');

    $service = new DocumentService();

    $result = $service->upload_document('org-1', [
        'name' => 'document.pdf',
        'size' => (10 * 1024 * 1024) + 1,
        'tmp_name' => '/tmp/document.pdf',
    ]);

    expect(is_wp_error($result))->toBeTrue()
        ->and($result->get_error_code())->toBe('file_too_large');
});

it('rejects document deletion when org meta is missing', function (): void {
    $service = new DocumentService();

    $GLOBALS['__orgroster_posts'][123] = (object) [
        'ID' => 123,
        'post_type' => 'attachment',
    ];

    $result = $service->delete_document(123);

    expect(is_wp_error($result))->toBeTrue()
        ->and($result->get_error_code())->toBe('invalid_document');
});

it('deletes documents linked to an organization', function (): void {
    $service = new DocumentService();

    $GLOBALS['__orgroster_posts'][321] = (object) [
        'ID' => 321,
        'post_type' => 'attachment',
    ];
    $GLOBALS['__orgroster_post_meta'][321]['_org_management_org_id'] = 'org-1';
    $GLOBALS['__orgroster_delete_attachment_results'][321] = true;

    $result = $service->delete_document(321);

    expect($result)->toBeTrue();
});
