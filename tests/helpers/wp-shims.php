<?php

declare(strict_types=1);

namespace OrgManagement\Config {
    if (!function_exists(__NAMESPACE__ . '\\__')) {
        function __($text, $domain = null)
        {
            return $text;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\apply_filters')) {
        function apply_filters($tag, $value = null)
        {
            return $value;
        }
    }
}

namespace OrgManagement\Services {
    if (!function_exists(__NAMESPACE__ . '\\__')) {
        function __($text, $domain = null)
        {
            if (function_exists('\\__')) {
                return \__($text, $domain);
            }

            return $text;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\apply_filters')) {
        function apply_filters($tag, $value = null)
        {
            if (function_exists('\\apply_filters')) {
                return \apply_filters($tag, $value);
            }

            return $value;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\sanitize_text_field')) {
        function sanitize_text_field($value): string
        {
            if (function_exists('\\sanitize_text_field')) {
                return \sanitize_text_field($value);
            }

            return is_scalar($value) ? (string) $value : '';
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\sanitize_textarea_field')) {
        function sanitize_textarea_field($value): string
        {
            if (function_exists('\\sanitize_textarea_field')) {
                return \sanitize_textarea_field($value);
            }

            return is_scalar($value) ? (string) $value : '';
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\sanitize_key')) {
        function sanitize_key($value): string
        {
            if (function_exists('\\sanitize_key')) {
                return \sanitize_key($value);
            }

            return is_scalar($value) ? strtolower((string) $value) : '';
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\sanitize_title')) {
        function sanitize_title($value): string
        {
            if (function_exists('\\sanitize_title')) {
                return \sanitize_title($value);
            }

            return is_scalar($value) ? strtolower((string) $value) : '';
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\wp_unslash')) {
        function wp_unslash($value)
        {
            if (function_exists('\\wp_unslash')) {
                return \wp_unslash($value);
            }

            return $value;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\wc_get_logger')) {
        function wc_get_logger()
        {
            return new class {
                public function debug(string $message, array $context = []): void {}

                public function info(string $message, array $context = []): void {}

                public function warning(string $message, array $context = []): void {}

                public function error(string $message, array $context = []): void {}

                public function critical(string $message, array $context = []): void {}
            };
        }
    }
}

namespace OrgManagement\Helpers {
    if (!function_exists(__NAMESPACE__ . '\\wc_get_logger')) {
        function wc_get_logger()
        {
            return new class {
                public function debug(string $message, array $context = []): void {}

                public function info(string $message, array $context = []): void {}

                public function warning(string $message, array $context = []): void {}

                public function error(string $message, array $context = []): void {}

                public function critical(string $message, array $context = []): void {}
            };
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\wp_die')) {
        function wp_die($message = ''): void
        {
            throw new \RuntimeException((string) $message);
        }
    }
}

namespace {
    if (!defined('MINUTE_IN_SECONDS')) {
        define('MINUTE_IN_SECONDS', 60);
    }

    if (!isset($GLOBALS['__orgroster_transients'])) {
        $GLOBALS['__orgroster_transients'] = [];
    }

    if (!isset($GLOBALS['__orgroster_posts'])) {
        $GLOBALS['__orgroster_posts'] = [];
    }

    if (!isset($GLOBALS['__orgroster_post_meta'])) {
        $GLOBALS['__orgroster_post_meta'] = [];
    }

    if (!isset($GLOBALS['__orgroster_delete_attachment_results'])) {
        $GLOBALS['__orgroster_delete_attachment_results'] = [];
    }

    if (!class_exists('WP_Error')) {
        class WP_Error
        {
            private string $code;
            private string $message;

            public function __construct(string $code = '', string $message = '')
            {
                $this->code = $code;
                $this->message = $message;
            }

            public function get_error_code(): string
            {
                return $this->code;
            }

            public function get_error_message(): string
            {
                return $this->message;
            }
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($value): bool
        {
            return $value instanceof WP_Error;
        }
    }

    if (!function_exists('sanitize_key')) {
        function sanitize_key($value): string
        {
            return is_scalar($value) ? strtolower((string) $value) : '';
        }
    }

    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($value): string
        {
            return is_scalar($value) ? (string) $value : '';
        }
    }

    if (!function_exists('sanitize_textarea_field')) {
        function sanitize_textarea_field($value): string
        {
            return is_scalar($value) ? (string) $value : '';
        }
    }

    if (!function_exists('sanitize_title')) {
        function sanitize_title($value): string
        {
            return is_scalar($value) ? strtolower((string) $value) : '';
        }
    }

    if (!function_exists('wp_unslash')) {
        function wp_unslash($value)
        {
            return $value;
        }
    }

    if (!function_exists('apply_filters')) {
        function apply_filters($tag, $value = null, ...$args)
        {
            $callbacks = $GLOBALS['__orgroster_filters'][$tag] ?? [];

            foreach ($callbacks as $callback) {
                if (is_callable($callback)) {
                    $value = $callback($value, ...$args);
                }
            }

            return $value;
        }
    }

    if (!function_exists('do_action')) {
        function do_action(...$args): void {}
    }

    if (!function_exists('wp_get_environment_type')) {
        function wp_get_environment_type(): string
        {
            return 'testing';
        }
    }

    if (!function_exists('get_transient')) {
        function get_transient($key)
        {
            return $GLOBALS['__orgroster_transients'][$key] ?? false;
        }
    }

    if (!function_exists('set_transient')) {
        function set_transient($key, $value, $expiration = 0): bool
        {
            $GLOBALS['__orgroster_transients'][$key] = $value;

            return true;
        }
    }

    if (!function_exists('delete_transient')) {
        function delete_transient($key): bool
        {
            unset($GLOBALS['__orgroster_transients'][$key]);

            return true;
        }
    }

    if (!function_exists('wicket_current_person_uuid')) {
        function wicket_current_person_uuid(): string
        {
            return $GLOBALS['__orgroster_current_person_uuid'] ?? 'person-1';
        }
    }

    if (!function_exists('get_post')) {
        function get_post($post_id)
        {
            return $GLOBALS['__orgroster_posts'][$post_id] ?? null;
        }
    }

    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key, $single = false)
        {
            return $GLOBALS['__orgroster_post_meta'][$post_id][$key] ?? '';
        }
    }

    if (!function_exists('wp_delete_attachment')) {
        function wp_delete_attachment($post_id, $force_delete = true)
        {
            if (array_key_exists($post_id, $GLOBALS['__orgroster_delete_attachment_results'])) {
                return $GLOBALS['__orgroster_delete_attachment_results'][$post_id];
            }

            return true;
        }
    }

    if (!function_exists('orgroster_test_reset_store')) {
        function orgroster_test_reset_store(): void
        {
            $GLOBALS['__orgroster_transients'] = [];
            $GLOBALS['__orgroster_posts'] = [];
            $GLOBALS['__orgroster_post_meta'] = [];
            $GLOBALS['__orgroster_delete_attachment_results'] = [];
            $GLOBALS['__orgroster_current_person_uuid'] = 'person-1';
            $GLOBALS['__orgroster_filters'] = [];
        }
    }
}
