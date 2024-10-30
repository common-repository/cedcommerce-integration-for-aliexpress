<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    CIFA
 * @subpackage CIFA/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( is_array( $active_marketplaces ) && ! empty( $active_marketplaces ) ) {

	?>
	<div class="ced-marketplaces-heading-main-wrapper">
		<div class="ced-marketplaces-heading-wrapper">
			<h2><?php esc_html_e( 'Active Marketplaces', 'cedcommerce-integration-for-aliexpress' ); ?></h2>
		</div>
	</div>
	<div class="ced-marketplaces-card-view-wrapper">
		<?php
		foreach ( $active_marketplaces as $key => $value ) {
			$url = admin_url( 'admin.php?page=' . esc_attr( $value['menu_link'] ) );
			?>
			<div class="ced-marketplace-card <?php echo esc_attr( $value['name'] ); ?>">
				<a href="<?php echo esc_attr( $url ); ?>">
					<div class="thumbnail">
						<div class="thumb-img ced-padding-10">
							<img class="img-responsive center-block integration-icons" src="<?php echo esc_attr( $value['card_image_link'] ); ?>" width="100%" alt="how to sell on AliExpress marketplace">
						</div>
					</div>
					<div class="mp-label"><?php echo esc_attr( $value['name'] ); ?></div>
				</a>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}
?>
