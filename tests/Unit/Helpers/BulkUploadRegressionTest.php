<?php

declare(strict_types=1);

it('keeps standalone bulk page strategy-aware with manageable groups selector and auto-redirect', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates/content-organization-members-bulk.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("if (\$roster_mode === 'groups') {");
    expect($template)->toContain('$groups_response = $group_service->get_manageable_groups($user_uuid, [');
    expect($template)->toContain("if (\$show_bulk_upload && \$group_uuid === '' && count(\$manageable_groups) === 1) {");
    expect($template)->toContain("\$redirect_args['group_uuid'] = (string) \$manageable_groups[0]['group_uuid'];");
    expect($template)->toContain('$group_access = $group_service->can_manage_group($group_uuid, $user_uuid);');
});

it('keeps bulk upload form posting group context for groups strategy', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-bulk-upload.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$group_uuid = isset(\$group_uuid) ? (string) \$group_uuid : '';");
    expect($template)->toContain("\$bulk_upload_dom_suffix_raw = \$org_uuid !== '' ? \$org_uuid : (\$group_uuid !== '' ? \$group_uuid : 'default');");
    expect($template)->toContain('<input type="hidden" name="group_uuid" value="<?php echo esc_attr($group_uuid); ?>">');
});

it('keeps process endpoint enforcing groups access checks and context routing', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/process/bulk-upload-members.php');
    $service = file_get_contents(dirname(__DIR__, 3) . '/src/Services/BulkMemberUploadService.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($service)->toBeString()->not->toBeFalse();
    expect($template)->toContain('$roster_mode = (string) $config_service->get_roster_mode();');
    expect($template)->toContain("if (\$roster_mode === 'groups') {");
    expect($template)->toContain('$group_access = $group_service->can_manage_group($group_uuid, $person_uuid);');
    expect($template)->toContain('$result = $bulk_upload_service->enqueue_upload(');
    expect($service)->toContain("\$context['group_uuid'] = \$group_uuid;");
    expect($service)->toContain("\$context['role'] = \$row_role;");
});

it('keeps non-groups bulk upload checks intact in processor', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/process/bulk-upload-members.php');
    $service = file_get_contents(dirname(__DIR__, 3) . '/src/Services/BulkMemberUploadService.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($service)->toBeString()->not->toBeFalse();
    expect($template)->toContain("if (\$roster_mode !== 'groups' && \$membership_uuid === '') {");
    expect($template)->toContain('if (!OrgManagement\\Helpers\\PermissionHelper::can_add_members($org_uuid)) {');
    expect($service)->toContain('$this->active_membership_exists($membership_uuid, $email)');
    expect($service)->toContain('$this->active_membership_exists_by_person($membership_uuid, $email)');
});
