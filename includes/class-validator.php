<?php
/**
 * Kreaction Connect Validator
 *
 * Validates ACF field values before saving.
 * Returns detailed error messages for invalid data.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Kreaction_Validator {

    /**
     * Validation errors
     */
    private static $errors = [];

    /**
     * Validate ACF fields for a post
     *
     * @param array $fields Field values to validate [field_name => value]
     * @param string $post_type Post type slug
     * @return array|true True if valid, array of errors otherwise
     */
    public static function validate_acf_fields($fields, $post_type) {
        self::$errors = [];

        if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
            return true; // Can't validate without ACF
        }

        // Get field definitions for this post type
        $field_definitions = self::get_field_definitions($post_type);

        foreach ($fields as $field_name => $value) {
            if (!isset($field_definitions[$field_name])) {
                continue; // Unknown field, skip
            }

            $field_def = $field_definitions[$field_name];
            self::validate_field($field_name, $value, $field_def);
        }

        // Check required fields
        foreach ($field_definitions as $field_name => $field_def) {
            if (!empty($field_def['required']) && !isset($fields[$field_name])) {
                self::add_error($field_name, 'This field is required.');
            }
        }

        return empty(self::$errors) ? true : self::$errors;
    }

    /**
     * Validate a single field
     *
     * @param string $field_name Field name
     * @param mixed $value Field value
     * @param array $field_def Field definition
     */
    private static function validate_field($field_name, $value, $field_def) {
        $type = $field_def['type'] ?? 'text';

        // Check required
        if (!empty($field_def['required']) && self::is_empty($value)) {
            self::add_error($field_name, 'This field is required.');
            return;
        }

        // Skip validation if empty and not required
        if (self::is_empty($value)) {
            return;
        }

        // Type-specific validation
        switch ($type) {
            case 'email':
                if (!is_email($value)) {
                    self::add_error($field_name, 'Please enter a valid email address.');
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    self::add_error($field_name, 'Please enter a valid URL.');
                }
                break;

            case 'number':
            case 'range':
                if (!is_numeric($value)) {
                    self::add_error($field_name, 'Please enter a valid number.');
                } else {
                    $num = floatval($value);
                    if (isset($field_def['min']) && $field_def['min'] !== '' && $num < floatval($field_def['min'])) {
                        self::add_error($field_name, "Value must be at least {$field_def['min']}.");
                    }
                    if (isset($field_def['max']) && $field_def['max'] !== '' && $num > floatval($field_def['max'])) {
                        self::add_error($field_name, "Value must be at most {$field_def['max']}.");
                    }
                }
                break;

            case 'select':
            case 'radio':
            case 'button_group':
                if (!empty($field_def['choices']) && !array_key_exists($value, $field_def['choices'])) {
                    self::add_error($field_name, 'Please select a valid option.');
                }
                break;

            case 'checkbox':
                if (is_array($value) && !empty($field_def['choices'])) {
                    foreach ($value as $v) {
                        if (!array_key_exists($v, $field_def['choices'])) {
                            self::add_error($field_name, 'One or more selected options are invalid.');
                            break;
                        }
                    }
                }
                break;

            case 'date_picker':
                // Accept multiple common date formats
                $valid_formats = ['Ymd', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'F j, Y', 'j F Y'];
                $is_valid = false;
                foreach ($valid_formats as $format) {
                    if (self::validate_date($value, $format)) {
                        $is_valid = true;
                        break;
                    }
                }
                // Also accept if it's a valid strtotime parseable string
                if (!$is_valid && strtotime($value) !== false) {
                    $is_valid = true;
                }
                if (!$is_valid) {
                    self::add_error($field_name, 'Please enter a valid date.');
                }
                break;

            case 'date_time_picker':
                // Accept multiple datetime formats or strtotime parseable strings
                $valid_formats = ['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Ymd H:i:s', 'Ymd\THis'];
                $is_valid = false;
                foreach ($valid_formats as $format) {
                    if (self::validate_date($value, $format)) {
                        $is_valid = true;
                        break;
                    }
                }
                if (!$is_valid && strtotime($value) !== false) {
                    $is_valid = true;
                }
                if (!$is_valid) {
                    self::add_error($field_name, 'Please enter a valid date and time.');
                }
                break;

            case 'time_picker':
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)) {
                    self::add_error($field_name, 'Please enter a valid time.');
                }
                break;

            case 'color_picker':
                if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                    self::add_error($field_name, 'Please enter a valid hex color.');
                }
                break;

            case 'image':
            case 'file':
                if (!is_numeric($value)) {
                    self::add_error($field_name, 'Please select a valid file from the media library.');
                } else {
                    // Check attachment exists (more lenient than wp_get_attachment_url)
                    $attachment = get_post((int)$value);
                    if (!$attachment || $attachment->post_type !== 'attachment') {
                        self::add_error($field_name, 'Please select a valid file from the media library.');
                    }
                }
                break;

            case 'gallery':
                if (is_array($value)) {
                    foreach ($value as $attachment_id) {
                        if (!is_numeric($attachment_id)) {
                            self::add_error($field_name, 'One or more gallery items are invalid.');
                            break;
                        }
                        // Check attachment exists (more lenient than wp_get_attachment_url)
                        $attachment = get_post((int)$attachment_id);
                        if (!$attachment || $attachment->post_type !== 'attachment') {
                            self::add_error($field_name, 'One or more gallery items are invalid.');
                            break;
                        }
                    }
                }
                break;

            case 'post_object':
                if (!is_numeric($value) || !get_post((int)$value)) {
                    self::add_error($field_name, 'Please select a valid post.');
                }
                break;

            case 'relationship':
                if (is_array($value)) {
                    foreach ($value as $post_id) {
                        if (!is_numeric($post_id) || !get_post((int)$post_id)) {
                            self::add_error($field_name, 'One or more selected posts are invalid.');
                            break;
                        }
                    }
                }
                break;

            case 'text':
            case 'textarea':
            case 'wysiwyg':
                // Only validate maxlength if it's set and is a positive number
                if (isset($field_def['maxlength']) && $field_def['maxlength'] !== '' && (int)$field_def['maxlength'] > 0) {
                    if (strlen($value) > (int)$field_def['maxlength']) {
                        self::add_error($field_name, "Text must be at most {$field_def['maxlength']} characters.");
                    }
                }
                break;

            case 'repeater':
                if (is_array($value)) {
                    if (isset($field_def['min']) && count($value) < (int)$field_def['min']) {
                        self::add_error($field_name, "At least {$field_def['min']} items are required.");
                    }
                    if (isset($field_def['max']) && count($value) > (int)$field_def['max']) {
                        self::add_error($field_name, "At most {$field_def['max']} items are allowed.");
                    }
                }
                break;
        }
    }

    /**
     * Check if a value is considered empty
     *
     * @param mixed $value
     * @return bool
     */
    private static function is_empty($value) {
        if ($value === null || $value === '') {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }

    /**
     * Validate a date string against a format
     *
     * @param string $date Date string
     * @param string $format Date format
     * @return bool
     */
    private static function validate_date($date, $format) {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Add a validation error
     *
     * @param string $field_name Field name
     * @param string $message Error message
     */
    private static function add_error($field_name, $message) {
        if (!isset(self::$errors[$field_name])) {
            self::$errors[$field_name] = [];
        }
        self::$errors[$field_name][] = $message;
    }

    /**
     * Get field definitions for a post type
     *
     * @param string $post_type Post type slug
     * @return array Field definitions keyed by field name
     */
    private static function get_field_definitions($post_type) {
        $cache_key = "validator_fields_{$post_type}";
        $cached = Kreaction_Cache::get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $definitions = [];
        $field_groups = acf_get_field_groups(['post_type' => $post_type]);

        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['key']);
            if (!$fields) continue;

            foreach ($fields as $field) {
                $definitions[$field['name']] = $field;
            }
        }

        Kreaction_Cache::set($cache_key, $definitions, 3600); // Cache for 1 hour

        return $definitions;
    }

    /**
     * Sanitize field values before saving
     *
     * @param array $fields Field values
     * @param string $post_type Post type slug
     * @return array Sanitized field values
     */
    public static function sanitize_fields($fields, $post_type) {
        $field_definitions = self::get_field_definitions($post_type);
        $sanitized = [];

        foreach ($fields as $field_name => $value) {
            $field_def = $field_definitions[$field_name] ?? null;
            $type = $field_def['type'] ?? 'text';

            $sanitized[$field_name] = self::sanitize_value($value, $type);
        }

        return $sanitized;
    }

    /**
     * Sanitize a single value based on type
     *
     * @param mixed $value Value to sanitize
     * @param string $type Field type
     * @return mixed Sanitized value
     */
    private static function sanitize_value($value, $type) {
        switch ($type) {
            case 'text':
            case 'email':
            case 'url':
            case 'password':
                return sanitize_text_field($value);

            case 'textarea':
                return sanitize_textarea_field($value);

            case 'wysiwyg':
                return wp_kses_post($value);

            case 'number':
            case 'range':
                return is_numeric($value) ? floatval($value) : null;

            case 'true_false':
                return (bool)$value;

            case 'select':
            case 'radio':
            case 'button_group':
                return sanitize_text_field($value);

            case 'checkbox':
                return is_array($value) ? array_map('sanitize_text_field', $value) : [];

            case 'image':
            case 'file':
            case 'post_object':
                return is_numeric($value) ? (int)$value : null;

            case 'gallery':
            case 'relationship':
                return is_array($value) ? array_map('intval', $value) : [];

            case 'date_picker':
            case 'date_time_picker':
            case 'time_picker':
            case 'color_picker':
                return sanitize_text_field($value);

            case 'repeater':
            case 'group':
            case 'flexible_content':
                // Recursively sanitize complex fields
                return is_array($value) ? $value : [];

            default:
                return $value;
        }
    }
}
