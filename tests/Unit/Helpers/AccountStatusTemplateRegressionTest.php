<?php

declare(strict_types=1);

it('keeps account-status config wiring in unified and legacy member list templates', function (): void {
    $unified = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-list-unified.php');
    $legacy = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-list.php');
    $view = file_get_contents(dirname(__DIR__, 3) . '/templates-partials/members-view-unified.php');

    expect($unified)->toBeString()->not->toBeFalse();
    expect($legacy)->toBeString()->not->toBeFalse();
    expect($view)->toBeString()->not->toBeFalse();

    expect($unified)->toContain("\$account_status_config = is_array(\$ui_config['account_status'] ?? null)");
    expect($unified)->toContain("\$show_unconfirmed_label = (bool) (\$account_status_config['show_unconfirmed_label'] ?? true);");
    expect($unified)->toContain("\$unconfirmed_label = (string) (\$account_status_config['unconfirmed_label'] ?? __('Account not confirmed', 'wicket-acc'));");

    expect($legacy)->toContain("\$account_status_config = is_array(\$member_list_config['account_status'] ?? null)");
    expect($legacy)->toContain("\$show_account_status = (bool) (\$account_status_config['enabled'] ?? true);");
    expect($legacy)->toContain("<?php if (\$show_unconfirmed_label && \$unconfirmed_label !== '') : ?>");

    expect($view)->toContain("\$show_account_status = (bool) ((\$orgman_config['ui']['member_list']['account_status']['enabled'] ?? true));");
});
