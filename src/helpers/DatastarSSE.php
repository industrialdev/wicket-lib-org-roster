<?php

/**
 * Shared Datastar SSE response utilities for Org Management modals.
 *
 * @package OrgManagement\Helpers
 */

namespace OrgManagement\Helpers;

use starfederation\datastar\ServerSentEventGenerator;
use starfederation\datastar\enums\ElementPatchMode;

if ( ! defined( 'ABSPATH' ) && ! defined( 'WICKET_ORGROSTER_DOINGTESTS' ) ) {
    exit;
}

/**
 * Static helper class for Datastar SSE operations.
 */
class DatastarSSE
{
    /**
     * Render a success message with countdown timer using Datastar SSE.
     *
     * @param string $message        The success message to display.
     * @param string $targetSelector The CSS selector for the target element.
     * @param array  $signalsToSet   Associative array of signal names and values to set (optional).
     * @param string $countdownId    The HTML element ID for the countdown timer (optional).
     * @return void
     */
    public static function renderSuccess(string $message, string $targetSelector, array $signalsToSet = [], string $countdownId = 'countdown'): void
    {
        $html = sprintf(
            '<div class="wt:bg-green-100 wt:border wt:border-green-400 wt:text-green-700 wt:px-4 wt:py-3 wt:rounded-sm wt:mb-4"><p><strong>%1$s</strong></p><p>%2$s</p><p class="wt:mt-2 wt:text-sm">%3$s <span id="%5$s">5</span> %4$s</p></div>',
            esc_html__('Success!', 'wicket-acc'),
            wp_kses_post($message),
            esc_html__('This page will reload in', 'wicket-acc'),
            esc_html__('seconds...', 'wicket-acc'),
            esc_attr($countdownId)
        );

        $generator = new ServerSentEventGenerator();
        $generator->sendHeaders();

        // Set specified signals
        if (!empty($signalsToSet)) {
            $generator->patchSignals($signalsToSet);
        }

        // Show the success message
        $generator->patchElements($html, [
            'selector' => $targetSelector,
            'mode' => ElementPatchMode::Inner
        ]);

        // Add countdown timer script
        $countdown_script = "
            let countdown = 5;
            const countdownEl = document.getElementById('" . esc_js($countdownId) . "');
            const timer = setInterval(() => {
                countdown--;
                if (countdownEl) {
                    countdownEl.textContent = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(timer);
                    window.location.reload();
                }
            }, 1000);
        ";

        $generator->executeScript($countdown_script);
    }

    /**
     * Render an error message using Datastar SSE.
     *
     * @param string $message        The error message to display.
     * @param string $targetSelector The CSS selector for the target element.
     * @param array  $signalsToSet   Associative array of signal names and values to set (optional).
     * @return void
     */
    public static function renderError(string $message, string $targetSelector, array $signalsToSet = []): void
    {
        $html = sprintf(
            '<div class="wt:bg-red-100 wt:border wt:border-red-400 wt:text-red-700 wt:px-4 wt:py-3 wt:rounded-sm wt:mb-4">%1$s</div>',
            esc_html($message)
        );

        $generator = new ServerSentEventGenerator();
        $generator->sendHeaders();

        // Set specified signals
        if (!empty($signalsToSet)) {
            $generator->patchSignals($signalsToSet);
        }

        // Show the error message
        $generator->patchElements($html, [
            'selector' => $targetSelector,
            'mode' => ElementPatchMode::Inner
        ]);
    }

    /**
     * Set multiple Datastar signals at once.
     *
     * @param array $signals Associative array of signal names and values.
     * @return void
     */
    public static function setSignals(array $signals): void
    {
        $generator = new ServerSentEventGenerator();
        $generator->sendHeaders();
        $generator->patchSignals($signals);
    }

    /**
     * Execute JavaScript via Datastar SSE.
     *
     * @param string $script The JavaScript code to execute.
     * @return void
     */
    public static function executeScript(string $script): void
    {
        $generator = new ServerSentEventGenerator();
        $generator->sendHeaders();
        $generator->executeScript($script);
    }
}