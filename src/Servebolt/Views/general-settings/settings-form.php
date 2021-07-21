<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\displayValue; ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<?php $settings = $generalSettings->getAllSettingsItems(); ?>

<form method="post" action="options.php">
    <?php settings_fields( 'sb-general-settings-options-page' ) ?>
    <?php do_settings_sections( 'sb-general-settings-options-page' ) ?>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Enable Cloudflare APO support?', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Whether Cloudflare APO-feature should be active for this site. Note that you need to use the Cloudflare proxy for this to work.', 'servebolt-wp'); ?></span></legend>
                    <label for="use_cloudflare_apo">
                        <input name="<?php echo getOptionName('use_cloudflare_apo'); ?>" type="checkbox" id="use_cloudflare_apo" value="1"<?php echo $acdActive ? ' disabled' : ''; ?><?php echo $generalSettings->useCloudflareApo() ? ' checked' : ''; ?>>
                        <?php _e('Check this if you want the Cloudflare APO-feature to be active for this site. Note that you need to use the Cloudflare proxy for this to work.', 'servebolt-wp'); ?>
                        <?php if($acdActive) : ?>
                            <p><strong><?php _e('APO is not available when Accelerated Domains is active.', 'servebolt-wp'); ?></strong></p>
                        <?php endif; ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Use native JS fallback', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <?php
                    $overridden = $generalSettings->settingIsOverridden('use_native_js_fallback');
                    $checked = $generalSettings->useNativeJsFallback();
                    ?>
                    <legend class="screen-reader-text"><span><?php _e('Use native JS fallback', 'servebolt-wp'); ?></span></legend>
                    <label for="use_native_js_fallback">
                        <input name="<?php echo getOptionName('use_native_js_fallback'); ?>" type="checkbox"<?php if ( $overridden ) echo ' disabled'; ?> id="use_native_js_fallback" value="1"<?php echo $checked ? ' checked' : ''; ?>>
                        <?php _e(sprintf('Using native JS for alerts, prompts and confirmations which would otherwise use third party library SweetAlert %s(prone to cause conflicts if SweetAlert is already used in the theme or in other plugins)%s.', '<em>', '</em>')); ?>
                        <?php if ( $overridden ): ?>
                            <p><strong><?php _e(sprintf('Note: this setting is overridden by the constant "SERVEBOLT_USE_NATIVE_JS_FALLBACK" which is set to %s.', displayValue($checked))); ?></strong></p>
                        <?php endif; ?>
                    </label>
                </fieldset>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Add automatic version parameter to asset URLs', 'servebolt-wp'); ?></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span><?php _e('Add automatic version parameter to asset URLs', 'servebolt-wp'); ?></span></legend>
                    <label for="asset_auto_version">
                        <input name="<?php echo getOptionName('asset_auto_version'); ?>" type="checkbox" id="asset_auto_version" value="1"<?php echo $generalSettings->assetAutoVersion() ? ' checked' : ''; ?>>
                        <?php _e('Check this if you want to add an automatic version parameter (used for automatic cache busting) to the URLs of the script and style-files on this site. This is useful when dealing with issues related to cache. (NB! Can be slow on some installations)'); ?>
                    </label>
                </fieldset>
            </td>
        </tr>

    </table>
    <?php submit_button(); ?>

</form>
