<?php

declare(strict_types=1);

it('keeps unified member search wiring with query and membership scope', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-view-unified.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain('data-on:input__debounce.700ms');
    expect($template)->toContain('encodeURIComponent(');
    expect($template)->toContain('$searchQuery');
    expect($template)->toContain('$membership_query_fragment');
    expect($template)->toContain('data-on:click="<?php echo esc_attr($search_submit_action); ?>"');
});

it('keeps members-list endpoint flow available for search requests', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-list.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain("\$_GET['query']");
    expect($template)->toContain('$member_service->get_members(');
    expect($template)->toContain("include __DIR__ . '/members-list-unified.php';");
});
