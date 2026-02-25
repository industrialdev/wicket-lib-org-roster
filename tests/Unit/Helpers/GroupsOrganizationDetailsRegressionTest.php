<?php

declare(strict_types=1);

it('keeps groups organization-details actions gated via can_manage_group', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/organization-details.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("if (\$roster_mode === 'groups' && \$group_uuid !== '') {");
    expect($template)->toContain('$group_access = $group_service->can_manage_group($group_uuid, (string) $user_uuid);');
    expect($template)->toContain("\$can_edit_org = !empty(\$group_access['allowed']);");
    expect($template)->toContain("\$is_membership_manager = !empty(\$group_access['allowed']);");
    expect($template)->toContain("<?php esc_html_e('Manage Members', 'wicket-acc'); ?>");
});
