<?php

declare(strict_types=1);

it('keeps groups landing wired to include all tagged roles for visibility', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/organization-list.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("'include_all_roles' => \$roster_mode === 'groups',");
    expect($template)->toContain('if ($roster_mode === \'groups\') {');
});

it('keeps single-group redirect behavior and multi-group list behavior', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/organization-list.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain('if ($groups_count === 1) {');
    expect($template)->toContain("\$redirect_args['group_uuid'] = (string) (\$single_group['group_uuid'] ?? '');");
    expect($template)->toContain("if ((string) (\$single_group['org_uuid'] ?? '') !== '') {");
    expect($template)->toContain('if ($groups_count === 0) {');
});

it('keeps group action links gated by can_manage flag only', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/organization-list.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain('$can_manage_group = !empty($group_item[\'can_manage\']);');
    expect($template)->toContain('<?php if ($can_manage_group) : ?>');
    expect($template)->toContain('Group Profile');
    expect($template)->toContain('Manage Members');
    expect($template)->not->toContain('No management access for this group role.');
});

it('keeps organization label resolution fallback to organization lookup and identifier', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/organization-list.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$group_org_label = (string) (\$group_item['org_name'] ?? '');");
    expect($template)->toContain("if (!empty(\$group_org_candidates) && function_exists('wicket_get_organization')) {");
    expect($template)->toContain("\$organization_response = wicket_get_organization(\$group_org_candidate);");
    expect($template)->toContain("\$group_org_label = (string) (\$group_item['org_identifier'] ?? '');");
});
