<?php

declare(strict_types=1);

it('keeps organization summary owner and renewal fallback rendering logic in template', function (): void {
    $template = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/organization-details.php');

    expect($template)->toBeString()->not->toBeFalse();
    expect($template)->toContain('$resolve_person_name = static function ($person): string {');
    expect($template)->toContain("if (\$owner_name === '' && !empty(\$membership_data['included']) && is_array(\$membership_data['included'])) {");
    expect($template)->toContain('$date_candidates = [');
    expect($template)->toContain("\$membership_data['data']['attributes']['ends_at'] ?? ''");
    expect($template)->toContain("\$membership_data['data']['attributes']['renewal_date'] ?? ''");
    expect($template)->toContain("\$membership_data['data']['attributes']['next_renewal_at'] ?? ''");
    expect($template)->toContain("Membership Owner-', 'wicket-acc') . ' ' . esc_html(\$owner_name !== '' ? \$owner_name : '—')");
    expect($template)->toContain("Renewal Date-', 'wicket-acc') . ' ' . esc_html(\$renewal_date !== '' ? \$renewal_date : '—')");
});
