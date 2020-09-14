<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap sb-content">
    <h1><?php sb_e('General settings'); ?></h1>

    <?php settings_errors(); ?>

    <br>

    <?php if ( is_network_admin() ) : ?>

        <table class="wp-list-table widefat striped">
            <thead>
            <tr>
                <th><?php sb_e('Blog ID'); ?></th>
                <th><?php sb_e('URL'); ?></th>
                <th><?php sb_e('Use native JS fallback'); ?></th>
                <th><?php sb_e('Controls'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <th><?php sb_e('Blog ID'); ?></th>
                <th><?php sb_e('URL'); ?></th>
                <th><?php sb_e('Use native JS fallback'); ?></th>
                <th><?php sb_e('Controls'); ?></th>
            </tr>
            </tfoot>
            <tbody>
            <?php foreach ( get_sites() as $site ) : ?>
                <tr>
                    <td><?php echo $site->blog_id; ?></td>
                    <td><?php echo $site->domain . $site->path; ?></td>
                    <td><?php echo sb_nginx_fpc()->fpc_is_active($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
                    <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-general-settings' ); ?>" class="button btn"><?php sb_e('Go to site settings'); ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php else : ?>

        <?php $settings = sb_general_settings()->get_all_settings_items(); ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'sb-general-settings-options-page' ) ?>
            <?php do_settings_sections( 'sb-general-settings-options-page' ) ?>
            <table class="form-table" id="sb-nginx-fpc-form">
                <tr>
                    <th scope="row">Use native JS fallback</th>
                    <td>
                        <fieldset>
                            <?php
                                $overridden = sb_general_settings()->setting_is_overridden('use_native_js_fallback');
                                $checked = sb_general_settings()->use_native_js_fallback();
                            ?>
                            <legend class="screen-reader-text"><span>Use native JS fallback</span></legend>
                            <label for="use_native_js_fallback">
                                <input name="<?php echo sb_get_option_name('use_native_js_fallback'); ?>" type="checkbox"<?php if ( $overridden ) echo ' disabled'; ?> id="use_native_js_fallback" value="1"<?php echo $checked ? ' checked' : ''; ?>>
                                Using native JS for alerts, prompts and confirmations which would otherwise use third party library SweetAlert <em>(prone to cause conflicts if SweetAlert is already used in the theme or in other plugins)</em>.
                                <?php if ( $overridden ): ?>
                                    <p><strong>Note: this setting is overridden by the constant "SERVEBOLT_USE_NATIVE_JS_FALLBACK" which is set to <?php sb_display_value($checked); ?>.</strong></p>
                                <?php endif; ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>

        </form>

    <?php endif; ?>

</div>
