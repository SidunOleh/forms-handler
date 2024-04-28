<?php 
$settings = get_option('forms_handler_settings', []); 
?>

<div class="wrap">

    <form 
        action="<?php echo admin_url('admin-ajax.php') ?>" 
        method="POST">

        <input type="hidden" name="action" value="update_forms_handler_settings">

        <table class="form-table" role="presentation">

            <tbody>
                <tr>
                    <th scope="row">
                        <label for="default_email">
                            <?php _e('Default e-mail') ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            name="settings[default_email]" 
                            type="text" 
                            id="default_email"
                            value="<?php echo $settings['default_email'] ?? '' ?>" 
                            class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enable_recaptcha">
                            <?php _e('Enable reCAPTCHA') ?>
                        </label>
                    </th>
                    <td>
                    <label for="enable_recaptcha">
                        <input 
                            name="settings[enable_recaptcha]" 
                            type="checkbox" 
                            id="enable_recaptcha"
                            <?php echo $settings['enable_recaptcha'] ?? '' ? 'checked' : '' ?>/>
                    </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="recaptcha_site_key">
                            <?php _e('reCAPTCHA site key') ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            name="settings[recaptcha_site_key]" 
                            type="text" 
                            id="recaptcha_site_key"
                            value="<?php echo $settings['recaptcha_site_key'] ?? '' ?>" 
                            class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="recaptcha_secret_key">
                            <?php _e('reCAPTCHA secret key') ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            name="settings[recaptcha_secret_key]" 
                            type="text" 
                            id="recaptcha_secret_key"
                            value="<?php echo $settings['recaptcha_secret_key'] ?? '' ?>" 
                            class="regular-text">
                    </td>
                </tr>
            </tbody>

        </table>

        <p class="submit">
            <input 
                type="submit" 
                name="submit" 
                id="submit" 
                class="button button-primary" 
                value="<?php _e('Save Changes') ?>">
        </p>

    </form>   
        
</div>