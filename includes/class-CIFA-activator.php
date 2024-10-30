<?php
/**
 * Fired during plugin activation
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    CIFA
 * @subpackage CIFA/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    CIFA
 * @subpackage CIFA/includes
 */
class CIFA_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wp_filesystem;

		// Check if WP_Filesystem is available
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize the WP_Filesystem
		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}

		$content = '<?php $data = file_get_contents( "php://input" );
		file_put_contents( "CIFA_api_details.txt", $data );';

		$file = wp_upload_dir()['basedir'] . '/CIFA_woo_callback.php';

		$wp_filesystem->put_contents( $file, $content );
		chmod( $file, 0777 );
	}
}
