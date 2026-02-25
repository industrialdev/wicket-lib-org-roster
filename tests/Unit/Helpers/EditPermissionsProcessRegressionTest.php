<?php

declare(strict_types=1);

it('keeps server-side role filtering in update-permissions processor', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/process/update-permissions.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$edit_permissions_config = \$config['edit_permissions_modal'] ?? [];");
    expect($template)->toContain("\$edit_allowed_roles = is_array(\$edit_permissions_config['allowed_roles'] ?? null)");
    expect($template)->toContain("\$edit_excluded_roles = is_array(\$edit_permissions_config['excluded_roles'] ?? null)");
    expect($template)->toContain('$roles = OrgManagement\\Helpers\\PermissionHelper::filter_role_submission(');
});

it('keeps prevent-owner-assignment enforced in update-permissions processor', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/process/update-permissions.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("if (!empty(\$config['permissions']['prevent_owner_assignment']))");
    expect($template)->toContain("\$edit_excluded_roles[] = 'membership_owner';");
});
