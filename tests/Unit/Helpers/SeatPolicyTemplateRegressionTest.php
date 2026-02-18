<?php

declare(strict_types=1);

it('keeps member list seat checks wired to effective max assignments resolver', function (): void {
    $unifiedTemplate = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-list-unified.php');
    $legacyTemplate = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-list.php');

    expect($unifiedTemplate)->toBeString()->not->toBeFalse();
    expect($legacyTemplate)->toBeString()->not->toBeFalse();
    expect($unifiedTemplate)->toContain("\$max_seats = \$membership_service->getEffectiveMaxAssignments(\$membership_data);");
    expect($legacyTemplate)->toContain("\$max_seats = \$membership_service->getEffectiveMaxAssignments(\$membership_data);");
});

it('keeps group seat checks wired to effective max assignments resolver', function (): void {
    $groupListTemplate = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/group-members-list.php');
    $addGroupMemberProcess = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/process/add-group-member.php');

    expect($groupListTemplate)->toBeString()->not->toBeFalse();
    expect($addGroupMemberProcess)->toBeString()->not->toBeFalse();
    expect($groupListTemplate)->toContain("\$max_seats = \$membership_service->getEffectiveMaxAssignments(\$membership_data);");
    expect($addGroupMemberProcess)->toContain("\$max_seats = \$membership_service->getEffectiveMaxAssignments(\$membership_data);");
});

it('keeps organization summary membership tier and seat max wiring through membership service helpers', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/organization-details.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$membership_name = \$membership_service->getMembershipTierName(\$membership_data);");
    expect($template)->toContain("\$max = \$membership_service->getEffectiveMaxAssignments(\$membership_data);");
});
