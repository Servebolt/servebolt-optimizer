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
                <th><?php sb_e('Automatic version parameter'); ?></th>
                <th><?php sb_e('Controls'); ?></th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <th><?php sb_e('Blog ID'); ?></th>
                <th><?php sb_e('URL'); ?></th>
                <th><?php sb_e('Use native JS fallback'); ?></th>
                <th><?php sb_e('Automatic version parameter'); ?></th>
                <th><?php sb_e('Controls'); ?></th>
            </tr>
            </tfoot>
            <tbody>
            <?php foreach ( get_sites() as $site ) : ?>
                <tr>
                    <td><?php echo $site->blog_id; ?></td>
                    <td><?php echo $site->domain . $site->path; ?></td>
                    <td><?php echo $generalSettings->useNativeJsFallback($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
                    <td><?php echo $generalSettings->assetAutoVersion($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
                    <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-general-settings' ); ?>" class="button btn"><?php sb_e('Go to site settings'); ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php else : ?>

        <?php $settings = $generalSettings->getAllSettingsItems(); ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'sb-general-settings-options-page' ) ?>
            <?php do_settings_sections( 'sb-general-settings-options-page' ) ?>
            <table class="form-table" id="sb-nginx-fpc-form">
                <tr>
                    <th scope="row"><?php sb_e('Use native JS fallback'); ?></th>
                    <td>
                        <fieldset>
                            <?php
                                $overridden = $generalSettings->settingIsOverridden('use_native_js_fallback');
                                $checked = $generalSettings->useNativeJsFallback();
                            ?>
                            <legend class="screen-reader-text"><span><?php sb_e('Use native JS fallback'); ?></span></legend>
                            <label for="use_native_js_fallback">
                                <input name="<?php echo sb_get_option_name('use_native_js_fallback'); ?>" type="checkbox"<?php if ( $overridden ) echo ' disabled'; ?> id="use_native_js_fallback" value="1"<?php echo $checked ? ' checked' : ''; ?>>
                                <?php sb_e(sprintf('Using native JS for alerts, prompts and confirmations which would otherwise use third party library SweetAlert %s(prone to cause conflicts if SweetAlert is already used in the theme or in other plugins)%s.', '<em>', '</em>')); ?>
                                <?php if ( $overridden ): ?>
                                    <p><strong><?php sb_e(sprintf('Note: this setting is overridden by the constant "SERVEBOLT_USE_NATIVE_JS_FALLBACK" which is set to %s.', sb_display_value($checked))); ?></strong></p>
                                <?php endif; ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php sb_e('Add automatic version parameter to asset URLs'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php sb_e('Add automatic version parameter to asset URLs'); ?></span></legend>
                            <label for="asset_auto_version">
                                <input name="<?php echo sb_get_option_name('asset_auto_version'); ?>" type="checkbox" id="asset_auto_version" value="1"<?php echo sb_general_settings()->asset_auto_version() ? ' checked' : ''; ?>>
                                <?php sb_e('Check this if you want to add an automatic version parameter (used for automatic cache busting) to the URLs of the script and style-files on this site. This is useful when dealing with issues related to cache.'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php sb_e('Enable Cloudflare APO support?'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php sb_e('Whether Cloudflare APO-feature should be active for this site. Note that you need to use the Cloudflare proxy for this to work.'); ?></span></legend>
                            <label for="use_cloudflare_apo">
                                <input name="<?php echo sb_get_option_name('use_cloudflare_apo'); ?>" type="checkbox" id="use_cloudflare_apo" value="1"<?php echo sb_general_settings()->use_cloudflare_apo() ? ' checked' : ''; ?>>
                                <?php sb_e('Check this if you want the Cloudflare APO-feature to be active for this site. Note that you need to use the Cloudflare proxy for this to work.'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

            </table>
            <?php submit_button(); ?>

        </form>

    <?php endif; ?>

</div>
