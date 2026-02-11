<?php

declare(strict_types=1);

it('includes a host-theme variable bridge with safe fallbacks', function (): void {
    $cssFile = dirname(__DIR__, 3) . '/public/css/modern-orgman-static.css';
    $css = file_get_contents($cssFile);

    expect($css)->toBeString()->not->toBeFalse();
    expect($css)->toContain('Theme variable bridge');
    expect($css)->toContain('--wicket-orgman-text-content: var(--text-content, #232a31);');
    expect($css)->toContain('--wicket-orgman-bg-interactive: var(--bg-interactive, #4c4c92);');
    expect($css)->toContain('--wicket-orgman-border-color: var(--border-light, #d9d9de);');
    expect($css)->toContain('--wicket-orgman-font-family-name: var(--font-family-name, proxima-nova, sans-serif);');
});

