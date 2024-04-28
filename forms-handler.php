<?php

/**
 * Plugin Name: Forms
 * Description: Forms handler
 * Author: Sidun Oleh
 */

use FormsHandler\FormsData;
use function FormsHandler\get_forms_settings;

defined('ABSPATH') or die;

/**
 * Plugin root
 */
const FORMS_HANDLER_ROOT = __DIR__;

/**
 * Composer autoloader
 */
require_once FORMS_HANDLER_ROOT . '/vendor/autoload.php';

/**
 * Create forms data table
 */
register_activation_hook(__FILE__, function () {
    require_once ABSPATH . '/wp-admin/includes/upgrade.php';

    global $wpdb;

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}forms_data (
        id         BIGINT(20)   UNSIGNED NOT NULL  AUTO_INCREMENT,
        form       VARCHAR(100)          NOT NULL,
        status     BOOLEAN               NOT NULL,
        data       JSON                  NOT NULL,
        created_at DATETIME              NOT NULL, 
        PRIMARY KEY(id)
    ) {$wpdb->get_charset_collate()}";
    
    dbDelta($sql);

    $wpdb->last_error and die($wpdb->last_error);
});

/**
 * reCAPTCHA
 */
add_action('wp_head', function () {
    if (! get_forms_settings('enable_recaptcha')) {
        return;
    }

    $siteKey = get_forms_settings('recaptcha_site_key');
    ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo $siteKey ?>">
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        grecaptcha.ready(validateCaptcha)

        const inputs = document.querySelectorAll('input')
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                document.querySelector('.grecaptcha-badge')
                    .classList
                    .add('show')
            })
        })
    })

    function validateCaptcha() {
        grecaptcha.execute('<?php echo $siteKey ?>', {
            action:'validate_captcha',
        }).then(token => {
            const recaptchaInput = document.createElement('input')
            recaptchaInput.type = 'hidden'
            recaptchaInput.name = 'recaptcha_response'
            recaptchaInput.value = token

            const forms = document.querySelectorAll('form')
            forms.forEach(form => form.appendChild(recaptchaInput.cloneNode()))
        }).catch(err => console.log(err))
    }
    </script>

    <style>
    .grecaptcha-badge:not(.show) {
        visibility: hidden !important;
        right: -300px !important;
        transition: all 0.5s linear !important; 
    }
    </style>
    <?php 
});

/**
 * Add menu page
 */
add_action('admin_menu', function () {
    add_menu_page(
        __('Forms'),
        __('Forms'),
        'forms_edit',
        'forms',
        fn () => require FORMS_HANDLER_ROOT . '/src/views/pages/index.php',
        'dashicons-forms',
        60
    );
});

/**
 * Add settings subpage
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'forms',
        __('Settings'),
        __('Settings'),
        'manage_options',
        'forms-settings',
        fn () => require FORMS_HANDLER_ROOT . '/src/views/pages/settings.php',
    );
});

/**
 * Add forms_edit cap and forms editor role
 */
add_action('init', function () {
    $role = add_role('forms_editor', __('Forms editor'));
    if ($role) {
        $role->add_cap('forms_edit');
        $role->add_cap('read');
    }

    $admin = get_role('administrator');
    if (! $admin->has_cap('forms_editor')) {
        $admin->add_cap('forms_edit');
    }
});

/**
 * Get forms data
 */
add_action('wp_ajax_get_forms_data', function () {
    if (
        ! $user = wp_get_current_user() or 
        ! $user->has_cap('forms_edit')
    ) {
        wp_send_json(['message' => 'Forbidden',], 403);
        wp_die();
    }

    $page = $_GET['page'] ?? 1;
    $size = $_GET['size'] ?? 15;

    $resposne = [];
    $resposne['data'] = FormsData::get($page, $size);
    $resposne['last_page'] = ceil(FormsData::total() / $size);

    wp_send_json($resposne);
    wp_die();
});

/**
 * Delete forms data
 */
add_action('wp_ajax_delete_forms_data', function () {
    if (
        ! $user = wp_get_current_user() or 
        ! $user->has_cap('forms_edit')
    ) {
        wp_send_json(['message' => 'Forbidden',], 403);
        wp_die();
    }
    
    $id = $_POST['id'] ?? 0;

    $result = FormsData::delete($id);

    wp_send_json(['success' => $result === false ? false : true,]);
    wp_die();
});

/**
 * Update settings
 */
add_action('wp_ajax_update_forms_handler_settings', function () {
    if (
        ! $user = wp_get_current_user() or 
        ! $user->has_cap('manage_options')
    ) {
        wp_send_json(['message' => 'Forbidden',], 403);
        wp_die();
    }
    
    $settings = $_POST['settings'] ?? [];

    update_option('forms_handler_settings', $settings);

    wp_redirect(wp_get_referer());
    wp_die();
});