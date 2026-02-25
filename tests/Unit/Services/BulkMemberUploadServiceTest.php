<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use OrgManagement\Services\BulkMemberUploadService;

beforeEach(function (): void {
    $GLOBALS['__orgroster_bulk_options'] = [];
    $GLOBALS['__orgroster_bulk_scheduled'] = [];

    Functions\when('get_option')->alias(function ($key, $default = false) {
        return array_key_exists((string) $key, $GLOBALS['__orgroster_bulk_options'])
            ? $GLOBALS['__orgroster_bulk_options'][(string) $key]
            : $default;
    });
    Functions\when('update_option')->alias(function ($key, $value, $autoload = null): bool {
        $GLOBALS['__orgroster_bulk_options'][(string) $key] = $value;

        return true;
    });
    Functions\when('delete_option')->alias(function ($key): bool {
        unset($GLOBALS['__orgroster_bulk_options'][(string) $key]);

        return true;
    });
    Functions\when('wp_schedule_single_event')->alias(function ($timestamp, $hook, $args = []) {
        $GLOBALS['__orgroster_bulk_scheduled'][] = [
            'timestamp' => (int) $timestamp,
            'hook' => (string) $hook,
            'args' => is_array($args) ? $args : [],
        ];

        return true;
    });
    Functions\when('wp_generate_uuid4')->justReturn('11111111-2222-3333-4444-555555555555');
    Functions\when('sanitize_file_name')->alias(static fn ($value): string => (string) $value);
    Functions\when('esc_html')->alias(static fn ($value): string => (string) $value);
    Functions\when('sanitize_email')->alias(static fn ($email): string => strtolower(trim((string) $email)));
    Functions\when('is_email')->alias(static fn ($email): bool => filter_var((string) $email, FILTER_VALIDATE_EMAIL) !== false);
    Functions\when('wp_json_encode')->alias(static fn ($value): string => (string) json_encode($value));
});

afterEach(function (): void {
    unset($GLOBALS['__orgroster_bulk_options']);
    unset($GLOBALS['__orgroster_bulk_scheduled']);
});

it('queues bulk upload job with isolated option record and schedules first batch', function (): void {
    $tmp = tempnam(sys_get_temp_dir(), 'orgman-bulk-');
    file_put_contents($tmp, "First Name,Last Name,Email Address,Relationship Type,Roles\nJane,Doe,jane@example.com,Employee,member\nJohn,Smith,john@example.com,Employee,member\n");

    $service = new BulkMemberUploadService();
    $result = $service->enqueue_upload($tmp, 'members.csv', 'org-1', 'membership-1', 'direct');

    expect($result)->toBeArray();
    expect($result['job_id'] ?? null)->toBe('11111111222233334444555555555555');
    expect($result['total_records'] ?? null)->toBe(2);
    expect($result['batch_size'] ?? null)->toBe(25);

    $job_option_key = BulkMemberUploadService::JOB_OPTION_PREFIX . '11111111222233334444555555555555';
    $stored_job = $GLOBALS['__orgroster_bulk_options'][$job_option_key] ?? null;
    expect($stored_job)->toBeArray();
    expect($stored_job['file_name'] ?? null)->toBe('members.csv');
    expect($stored_job['file_sha256'] ?? '')->toBeString()->not->toBe('');
    expect($stored_job['status'] ?? null)->toBe('queued');
    expect($GLOBALS['__orgroster_bulk_scheduled'])->toHaveCount(1);
    expect($GLOBALS['__orgroster_bulk_scheduled'][0]['hook'] ?? null)->toBe(BulkMemberUploadService::CRON_HOOK);

    @unlink($tmp);
});

it('rejects duplicate active job when uploaded file hash already exists in queue', function (): void {
    $tmp = tempnam(sys_get_temp_dir(), 'orgman-bulk-');
    file_put_contents($tmp, "First Name,Last Name,Email Address,Relationship Type,Roles\nJane,Doe,jane@example.com,Employee,member\n");
    $hash = hash_file('sha256', $tmp);

    $existing_id = 'existingjob1';
    $GLOBALS['__orgroster_bulk_options'][BulkMemberUploadService::OPTION_KEY] = [$existing_id];
    $GLOBALS['__orgroster_bulk_options'][BulkMemberUploadService::JOB_OPTION_PREFIX . $existing_id] = [
        'id' => $existing_id,
        'status' => 'processing',
        'file_sha256' => $hash,
    ];

    $service = new BulkMemberUploadService();
    $result = $service->enqueue_upload($tmp, 'members.csv', 'org-1', 'membership-1', 'direct');

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('bulk_duplicate_active_job');
    expect($GLOBALS['__orgroster_bulk_scheduled'])->toHaveCount(0);

    @unlink($tmp);
});

it('processes one scheduled batch and keeps job queued when more rows remain', function (): void {
    $job_id = 'jobbatch1';
    $job_key = BulkMemberUploadService::JOB_OPTION_PREFIX . $job_id;
    $GLOBALS['__orgroster_bulk_options'][BulkMemberUploadService::OPTION_KEY] = [$job_id];
    $GLOBALS['__orgroster_bulk_options'][$job_key] = [
        'id' => $job_id,
        'status' => 'queued',
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'file_name' => 'members.csv',
        'file_sha256' => 'hash1',
        'org_uuid' => 'org-1',
        'membership_uuid' => 'membership-1',
        'roster_mode' => 'direct',
        'group_uuid' => '',
        'total_records' => 2,
        'processed' => 0,
        'added' => 0,
        'skipped' => 0,
        'failed' => 0,
        'next_offset' => 0,
        'batch_size' => 1,
        'error_snippets' => [],
        'seen_emails' => [],
        'rows' => [
            ['row_num' => 2, 'first_name' => '', 'last_name' => '', 'email' => '', 'roles_raw' => '', 'relationship_raw' => ''],
            ['row_num' => 3, 'first_name' => '', 'last_name' => '', 'email' => '', 'roles_raw' => '', 'relationship_raw' => ''],
        ],
    ];

    $service = new BulkMemberUploadService();
    $service->process_scheduled_job($job_id);

    $updated = $GLOBALS['__orgroster_bulk_options'][$job_key] ?? [];
    expect($updated['status'] ?? null)->toBe('queued');
    expect($updated['processed'] ?? null)->toBe(1);
    expect($updated['failed'] ?? null)->toBe(1);
    expect($updated['next_offset'] ?? null)->toBe(1);
    expect($GLOBALS['__orgroster_bulk_scheduled'])->toHaveCount(1);
});

it('marks job completed and clears heavy payload fields at the end', function (): void {
    $job_id = 'jobdone1';
    $job_key = BulkMemberUploadService::JOB_OPTION_PREFIX . $job_id;
    $GLOBALS['__orgroster_bulk_options'][BulkMemberUploadService::OPTION_KEY] = [$job_id];
    $GLOBALS['__orgroster_bulk_options'][$job_key] = [
        'id' => $job_id,
        'status' => 'queued',
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'file_name' => 'members.csv',
        'file_sha256' => 'hash2',
        'org_uuid' => 'org-1',
        'membership_uuid' => '',
        'roster_mode' => 'direct',
        'group_uuid' => '',
        'total_records' => 1,
        'processed' => 0,
        'added' => 0,
        'skipped' => 0,
        'failed' => 0,
        'next_offset' => 0,
        'batch_size' => 5,
        'error_snippets' => [],
        'seen_emails' => ['demo@example.com' => true],
        'rows' => [
            ['row_num' => 2, 'first_name' => '', 'last_name' => '', 'email' => '', 'roles_raw' => '', 'relationship_raw' => ''],
        ],
    ];

    $service = new BulkMemberUploadService();
    $service->process_scheduled_job($job_id);

    $updated = $GLOBALS['__orgroster_bulk_options'][$job_key] ?? [];
    expect($updated['status'] ?? null)->toBe('completed');
    expect($updated['processed'] ?? null)->toBe(1);
    expect($updated['failed'] ?? null)->toBe(1);
    expect($updated['rows'] ?? null)->toBe([]);
    expect($updated['seen_emails'] ?? null)->toBe([]);
    expect($GLOBALS['__orgroster_bulk_scheduled'])->toHaveCount(0);
});

it('keeps parallel jobs isolated in independent option rows', function (): void {
    $job_a_id = 'joba';
    $job_b_id = 'jobb';
    $job_a_key = BulkMemberUploadService::JOB_OPTION_PREFIX . $job_a_id;
    $job_b_key = BulkMemberUploadService::JOB_OPTION_PREFIX . $job_b_id;
    $shared = [
        'status' => 'queued',
        'created_at' => gmdate('c'),
        'updated_at' => gmdate('c'),
        'org_uuid' => 'org-1',
        'membership_uuid' => '',
        'roster_mode' => 'direct',
        'group_uuid' => '',
        'batch_size' => 1,
        'error_snippets' => [],
        'seen_emails' => [],
    ];

    $GLOBALS['__orgroster_bulk_options'][BulkMemberUploadService::OPTION_KEY] = [$job_a_id, $job_b_id];
    $GLOBALS['__orgroster_bulk_options'][$job_a_key] = array_merge($shared, [
        'id' => $job_a_id,
        'file_name' => 'a.csv',
        'file_sha256' => 'hash-a',
        'total_records' => 1,
        'processed' => 0,
        'added' => 0,
        'skipped' => 0,
        'failed' => 0,
        'next_offset' => 0,
        'rows' => [
            ['row_num' => 2, 'first_name' => '', 'last_name' => '', 'email' => '', 'roles_raw' => '', 'relationship_raw' => ''],
        ],
    ]);
    $GLOBALS['__orgroster_bulk_options'][$job_b_key] = array_merge($shared, [
        'id' => $job_b_id,
        'file_name' => 'b.csv',
        'file_sha256' => 'hash-b',
        'total_records' => 1,
        'processed' => 0,
        'added' => 0,
        'skipped' => 0,
        'failed' => 0,
        'next_offset' => 0,
        'rows' => [
            ['row_num' => 2, 'first_name' => '', 'last_name' => '', 'email' => '', 'roles_raw' => '', 'relationship_raw' => ''],
        ],
    ]);

    $service = new BulkMemberUploadService();
    $service->process_scheduled_job($job_a_id);

    $updated_a = $GLOBALS['__orgroster_bulk_options'][$job_a_key] ?? [];
    $updated_b = $GLOBALS['__orgroster_bulk_options'][$job_b_key] ?? [];

    expect($updated_a['status'] ?? null)->toBe('completed');
    expect($updated_b['status'] ?? null)->toBe('queued');
    expect($updated_b['processed'] ?? null)->toBe(0);
});

it('bounds batch size to supported min and max limits', function (): void {
    $service = new BulkMemberUploadService();
    $call_get_batch_size = Closure::bind(static function (BulkMemberUploadService $svc, array $config): int {
        return $svc->get_batch_size($config);
    }, null, BulkMemberUploadService::class);

    expect($call_get_batch_size($service, ['batch_size' => 0]))->toBe(1);
    expect($call_get_batch_size($service, ['batch_size' => 9999]))->toBe(500);
    expect($call_get_batch_size($service, ['batch_size' => 25]))->toBe(25);
});
