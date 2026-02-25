<?php

declare(strict_types=1);

it('keeps unified member list remove policy gating and callout wiring', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-list-unified.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$show_remove_button_default = (bool) (\$ui_config['show_remove_button'] ?? true);");
    expect($template)->toContain("\$seat_limit_message = (string) (\$ui_config['seat_limit_message'] ?? __('All seats have been assigned. Please purchase additional seats to add more members.', 'wicket-acc'));");
    expect($template)->toContain("\$remove_policy_callout = is_array(\$ui_config['remove_policy_callout'] ?? null)");
    expect($template)->toContain("\$remove_policy_callout_placement = (string) (\$remove_policy_callout['placement'] ?? 'above_members');");
    expect($template)->toContain('&& !$show_remove_button');
    expect($template)->toContain("&& !empty(\$remove_policy_callout['enabled'])");
    expect($template)->toContain("&& !empty(\$remove_policy_callout['message'])");
    expect($template)->toContain("\$remove_policy_callout_placement === 'above_members'");
    expect($template)->toContain("\$remove_policy_callout_placement === 'below_members'");
    expect($template)->toContain('<?php echo esc_html($seat_limit_message); ?>');
});

it('keeps member view remove controls gated by config plus permissions', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-view-unified.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$show_remove_button_by_config = (bool) (\$member_list_config['show_remove_button'] ?? true);");
    expect($template)->toContain('$show_remove_button = $show_remove_button_by_config && OrgHelpers\\PermissionHelper::can_remove_members($org_uuid);');
    expect($template)->toContain('<?php if ($show_remove_button) : ?>');
    expect($template)->toContain('<dialog id="membersRemoveModal"');
});

it('keeps legacy members list remove controls and callout config wiring', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-list.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$show_remove_button_by_config = (bool) (\$member_list_config['show_remove_button'] ?? true);");
    expect($template)->toContain("\$seat_limit_message = (string) (\$member_list_config['seat_limit_message'] ?? __('All seats have been assigned. Please purchase additional seats to add more members.', 'wicket-acc'));");
    expect($template)->toContain("\$remove_policy_callout = is_array(\$member_list_config['remove_policy_callout'] ?? null)");
    expect($template)->toContain("\$remove_policy_callout_placement = (string) (\$remove_policy_callout['placement'] ?? 'above_members');");
    expect($template)->toContain('$show_remove_button = $show_remove_button_by_config && OrgHelpers\\PermissionHelper::can_remove_members($org_uuid);');
    expect($template)->toContain('$show_remove_policy_callout = (');
    expect($template)->toContain("\$show_remove_policy_callout && \$remove_policy_callout_placement === 'above_members'");
    expect($template)->toContain("\$show_remove_policy_callout && \$remove_policy_callout_placement === 'below_members'");
    expect($template)->toContain('<?php echo esc_html($seat_limit_message); ?>');
    expect($template)->toContain('<?php if ($show_remove_button): ?>');
    expect($template)->toContain('<dialog id="removeMemberModal"');
});
