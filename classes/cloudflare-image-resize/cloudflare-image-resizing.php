<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class Cloudflare_Image_Resize
 *
 * This class integrates WordPress with Cloudflare Image Resizing service.
 * Note that you need to use Cloudflare Proxy for this to work. This is so that Cloudflare can "capture" the image, resize, cache and serve it to be displayed.
 *
 * Note that this will prevent certain image sizes from being created during an upload. The exceptions for this is cropped images (image sizes where the proportions have changed) or image sizes that have been specified with the filter "sb_optimizer_cf_image_resize_always_create_sizes".
 *
 * If you are adding this feature to your site with existing media files the you might want to regenerate all image files. A good plugin for this: https://wordpress.org/plugins/regenerate-thumbnails/
 */
class Cloudflare_Image_Resize {

	/**
	 * Max image width when resizing with Cloudflare.
	 *
	 * @var int
	 */
	private $max_width = 1920;

	/**
	 * Max image height when resizing with Cloudflare.
	 *
	 * @var int
	 */
	private $max_height = 1080;

	/**
	 * An array of sizes that should always be created.
	 *
	 * @var array
	 */
	private $always_create_sizes = [];

	/**
	 * Property to store original size array while we alter image sizes during image upload.
	 *
	 * @var array
	 */
	private $original_sizes = [];

	/**
	 * Initialize image resizing.
	 */
	public function init() {
		$this->init_image_resize();
		$this->init_image_upscale();
	}

	/**
	 * Register image resize hooks.
	 */
	public function init_image_resize() {

		// Alter srcset-attribute URLs
		if ( apply_filters('sb_optimizer_cf_image_resize_alter_srcset', true ) ) {
			add_filter( 'wp_calculate_image_srcset', [ $this, 'alter_srcset_image_urls' ] );
		}

		// Alter image src-attribute URL
		if ( apply_filters('sb_optimizer_cf_image_resize_alter_src', true ) ) {
			add_filter( 'wp_get_attachment_image_src', [ $this, 'alter_single_image_url' ] );
		}

		// Prevent certain image sizes to be created since we are using Cloudflare for resizing
		if ( apply_filters('sb_optimizer_cf_image_resize_alter_intermediate_sizes', true ) ) {
			add_filter( 'intermediate_image_sizes_advanced', [ $this, 'override_image_size_creation' ], 10, 2 );
		}

		return $this;

	}

	/**
	 * Initialize image upscaling when resizing to a cropped size (if the image is too small to fill the cropped proportions).
	 */
	public function init_image_upscale() {

		// Whether we should upscale images that are too small to fill the proportions of an image size
		if ( apply_filters('sb_optimizer_cf_image_resize_upscale_images', true ) ) {
			add_filter( 'image_resize_dimensions', [ $this, 'image_upscale' ], 10, 6 );
		}

		return $this;
	}

	/**
	 * Only resize images when needed.
	 *
	 * @param $sizes
	 * @param $image_meta
	 *
	 * @return array
	 */
	public function override_image_size_creation($sizes, $image_meta) {

		// Store the image sizes for later use
		$this->original_sizes = $sizes;

		// Re-add image sizes after file creation
		add_filter( 'wp_get_attachment_metadata', [ $this, 're_add_image_sizes' ] );

		// Determine which image sizes we should generate files for
		$uploaded_image_ratio = $image_meta['width'] / $image_meta['height'];
		$image_sizes_to_create = array_filter($sizes, function ($size, $key) use ($image_meta, $uploaded_image_ratio) {

			// Check if this is a size that we should always generate
			if ( in_array($key, (array) apply_filters('sb_optimizer_cf_image_resize_always_create_sizes', $this->always_create_sizes) ) ) {
				return true;
			}

			$image_size_ratio = $size['width'] / $size['height'];
			$uploaded_image_has_same_ratio_as_current_image_size = $uploaded_image_ratio == $image_size_ratio;
			$uploaded_image_is_bigger_than_current_image_size = $image_meta['width'] >= $size['width'] && $image_meta['height'] >= $size['height'];

			// If uploaded image has same the ratio as the original and it is bigger than the current size then we can downscale the original file with Cloudflare instead, therefore we dont need to generate the size
			if ( $uploaded_image_has_same_ratio_as_current_image_size && $uploaded_image_is_bigger_than_current_image_size ) {
				return false;
			}

			// If the image proportions are changed the we need to generate it (and later we can scale the size with Cloudflare using only width since the proportions of the image is correct)
			return $size['crop'];
		}, ARRAY_FILTER_USE_BOTH);

		return $image_sizes_to_create;

	}

	/**
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function re_add_image_sizes($data) {
		$file = substr($data['file'], strrpos($data['file'], '/') + 1);
		foreach ( $this->original_sizes as $key => $value ) {
			$this->original_sizes[$key] = $value;
			$this->original_sizes[$key]['file'] = $file;
		}
		$data['sizes'] = $this->original_sizes;
		return $data;
	}

	/**
	 * Get max width.
	 *
	 * @return mixed|void
	 */
	private function max_width() {
		return apply_filters('sb_optimizer_cf_image_resize_max_width', $this->max_width);
	}

	/**
	 * Get max height.
	 *
	 * @return mixed|void
	 */
	private function max_height() {
		return apply_filters('sb_optimizer_cf_image_resize_max_height', $this->max_height);
	}

	/**
	 * Alter image src-attribute.
	 *
	 * @param $image
	 *
	 * @return mixed
	 */
	public function alter_single_image_url($image) {

		list($url, $width, $height, $is_intermediate) = $image;

		$cf_params = [];

		// Set max dimensions
		if ( $width > $this->max_width() ) {
			$width = $this->max_width();
		}
		if ( $height > $this->max_height() ) {
			$cf_params['height'] = $this->max_height();
		}
		$cf_params['width'] = $width;

		$image[0] = $this->sb_build_image_url($url, $this->sb_default_cf_params($cf_params));

		return $image;
	}

	/**
	 * Alter srcset-attribute.
	 *
	 * @param $sources
	 *
	 * @return mixed
	 */
	public function alter_srcset_image_urls($sources) {
		foreach ( $sources as $key => $value ) {
			$descriptor = $value['descriptor'] === 'h' ? 'height' : 'width';
			$cf_params = $this->sb_default_cf_params([
				$descriptor => $value['value']
			]);
			$sources[$key]['url'] = $this->sb_build_image_url($value['url'], $cf_params);
		}
		return $sources;
	}

	/**
	 * Convert Cloudflare image resize parameter array to string.
	 *
	 * @param $cf_params
	 *
	 * @return string
	 */
	private function implode_parameters($cf_params) {
		$cf_param_array = [];
		foreach ( $cf_params as $key => $value ) {
			$cf_param_array[] = $key . '=' . $value;
		}
		return implode(',', $cf_params);
	}

	/**
	 * Build Cloudflare image resize URL.
	 *
	 * @param $url
	 * @param $cf_params
	 *
	 * @return string
	 */
	private function sb_build_image_url($url, $cf_params = []) {
		$url = wp_parse_url($url);
		$cdi_uri = '/cdn-cgi/image/';
		$cf_parameter_string = $this->implode_parameters($cf_params);
		$altered_url = $url['scheme'] . '://' . $url['host'] . $cdi_uri . $cf_parameter_string . $url['path'];
		return apply_filters('sb_optimizer_cf_image_resize_url', $altered_url, $url, $cdi_uri, $cf_params, $cf_parameter_string);
	}

	/**
	 * Get default Cloudflare image resize parameters.
	 *
	 * @param array $additional_params
	 *
	 * @return array
	 */
	private function sb_default_cf_params($additional_params = []) {
		$additional_params = apply_filters('sb_optimizer_cf_image_resize_default_params_additional', $additional_params);
		$default_params = apply_filters('sb_optimizer_cf_image_resize_default_params', [
			'quality' => '60',
			'format'  => 'auto',
			'onerror' => 'redirect',
		] );
		return apply_filters('sb_optimizer_cf_image_resize_default_params_concatenated', wp_parse_args($additional_params, $default_params) );
	}

	/**
	 * When generating cropped image sizes then upscale the image if the original is too small, so that we get the proportion specified in the image size.
	 *
	 * @param $default
	 * @param $orig_w
	 * @param $orig_h
	 * @param $new_w
	 * @param $new_h
	 * @param $crop
	 *
	 * @return array|null
	 */
	function image_upscale($default, $orig_w, $orig_h, $new_w, $new_h, $crop) {
		if ( ! $crop ) return $default; // Let the wordpress default function handle this

		$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

		$crop_w = round($new_w / $size_ratio);
		$crop_h = round($new_h / $size_ratio);

		$s_x = floor( ($orig_w - $crop_w) / 2 );
		$s_y = floor( ($orig_h - $crop_h) / 2 );

		return apply_filters('sb_optimizer_cf_image_resize_upscale_dimensions', [ 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h ] );
	}

}
