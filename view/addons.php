<div class="aws-content aws-addons">

	<?php
	$addons = array(
		'amazon-s3-and-cloudfront' => array(
			'title' => __( 'Amazon S3 and CloudFront', 'amazon-web-services' ),
			'path' => 'amazon-s3-and-cloudfront/wordpress-s3.php'
		)
	);

	foreach ( $addons as $slug => $addon ) :
		$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=600&amp;height=550' );
		?>

		<article class="addon">
			<h1><?php echo $addon['title']; ?></h1>
			<a class="thickbox" href="<?php echo $details_url; ?>"><?php _e( 'Details', 'amazon-web-services' ); ?></a> | 
			<?php
			if ( file_exists( WP_PLUGIN_DIR . '/' . $addon['path'] ) ) {
				echo '<span>' . _x( 'Installed', 'amazon-web-services' ) . '</span>';
			}
			else {
				echo '<a class="install-now" href="' . $this->get_plugin_install_url( $slug ) . '">' . __( 'Install Now', 'amazon-web-services' ) . '</a>';
			}
			?>
		</article>

		<?php
	endforeach;
	?>

</div>