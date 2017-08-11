<?php
$foogallery_url = 'http://foo.gallery?utm_source=foobox_free_plugin&utm_medium=foobox_free_link&utm_campaign=foobox_free_admin_getting_started';

$fs_instance = freemius( FOOBOX_BASE_SLUG );
$show_deactivation_message = false;

//if ( $fs_instance->check_if_free_version_activated() ) {
//	$fs_instance->try_deactivate_free_version();
//	$show_deactivation_message = true;
//}

$size     = 70;
$location = 'https://s3.amazonaws.com/foocdn/';
$demo_images = array(
	array(
			'src'  => '1.jpg',
			'title' => __( 'Your Image Title Goes Here', 'foobox-image-lightbox' ),
			'desc'  => __( 'You can have a nice long image description that goes here', 'foobox-image-lightbox' ),
	),
	array(
			'src'  => '2.jpg',
			'title' => __( 'Beach Sandcastle', 'foobox' ),
			'desc'  => __( 'HTML is also <a href=\'#\'>allowed</a> in your <em>descriptions</em>!', 'foobox-image-lightbox' )
	),
	array(
			'src'  => '3.jpg',
			'title' => __( 'Title With No Description', 'foobox-image-lightbox' ),
	),
	array(
			'src'  => '4.jpg',
			'desc'  => __( 'A caption with no title, and only a long description describing the image', 'foobox' )
	),
	array(
			'src'  => 'https://youtu.be/ofmzX1nI7SE',
			'title' => __( 'Video captions are awesome!', 'foobox-image-lightbox' ),
			'desc'  => __( 'Set a video caption title by adding a <code>data-caption-title</code> attribute to your link.<br />You can also set a caption description by adding a <code>data-caption-desc</code> attribute to your link.', 'foobox-image-lightbox' ),
			'thumb_src' => FOOBOX_PLUGIN_URL . 'img/youtube.png'
	),
	array(
			'src'  => 'https://vimeo.com/143456347',
			'title' => __( 'Captions for Vimeo vids are cool!', 'foobox-image-lightbox' ),
			'desc'  => __( 'Did you know that you can also include <strong>HTML</strong> in your captions!', 'foobox-image-lightbox' ),
			'thumb_src' => FOOBOX_PLUGIN_URL . 'img/vimeo.png'
	),
	array(
			'src'  => 'http://fooplugins.com',
			'title' => __( 'I am an iFrame caption', 'foobox-image-lightbox' ),
			'desc'  => __( 'This is an iFrame caption description.', 'foobox-image-lightbox' ),
			'thumb_src' => FOOBOX_PLUGIN_URL . 'img/iframe.png',
			'target' => 'foobox'
	),
	array(
			'src'  => '#foobox-inline',
			'title' => __( 'I am an HTML caption', 'foobox-image-lightbox' ),
			'desc'  => __( 'This is an HTML caption description.', 'foobox-image-lightbox' ),
			'thumb_src' => FOOBOX_PLUGIN_URL . 'img/inline.png',
			'target' => 'foobox',
			'data_width' => '600px'
	),
);
?>
	<h2 class="nav-tab-wrapper">
		<a class="nav-tab nav-tab-active" href="#getting-started">
			<?php _e( 'Getting Started', 'foobox-image-lightbox' ); ?>
		</a>
		<a class="nav-tab" href="#demo">
			<?php _e( 'Demo', 'foobox-image-lightbox' ); ?>
		</a>
	</h2>
	<div id="getting-started_tab" class="feature-section nav-container">
		<?php if ( $show_deactivation_message ) { ?>
		<div>
			<h2><?php _e( 'Congratulations! You\'re now running FooBox PRO!', 'foobox-image-lightbox' );?></h2>
			<p><?php _e( 'We’ve deactivated the free version of FooBox for you. Get started now by configuring your new FooBox PRO features...', 'foobox-image-lightbox' ); ?></p>
		</div>
		<?php } ?>
		<div>
			<h2><?php _e( 'Look &amp; Feel', 'foobox-image-lightbox' );?></h2>
			<p>
				<?php _e( 'FooBox has 3 awesome lightbox themes to choose from.', 'foobox-image-lightbox' );?>
				<strong><?php _e( 'Try them out below:', 'foobox-image-lightbox' );?></strong>
			</p>
			<p>
				<span class="dashicons dashicons-yes"></span>
				<a data-effect="fbx-effect-1" data-style="fbx-rounded" rel="fooboxtheme-rounded" href="<?php echo $location; ?>5.jpg" class="foobox"><?php _e( 'Rounded', 'foobox-image-lightbox' ); ?></a>
				<a style="display: none" rel="fooboxtheme-rounded" href="<?php echo $location; ?>8.jpg" class="foobox"></a>
				- <?php _e('The default FooBox theme. Original and sexy!', 'foobox-image-lightbox' ); ?>
			</p>
			<p>
				<span class="dashicons dashicons-yes"></span>
				<a data-effect="fbx-effect-2" data-style="fbx-metro" rel="fooboxtheme-metro" href="<?php echo $location; ?>6.jpg" class="foobox"><?php _e( 'Metro', 'foobox-image-lightbox' ); ?></a>
				<a style="display: none" rel="fooboxtheme-metro" href="<?php echo $location; ?>9.jpg" class="foobox"></a>
				- <?php _e('A metro-styled lightbox, great for showcasing galleries.', 'foobox-image-lightbox' ); ?></p>
			<p>
				<span class="dashicons dashicons-yes"></span>
				<a data-effect="fbx-effect-10" data-style="fbx-flat" rel="fooboxtheme-flat" href="<?php echo $location; ?>7.jpg" class="foobox"><?php _e( 'Flat', 'foobox-image-lightbox' ); ?></a>
				<a style="display: none" rel="fooboxtheme-flat" href="<?php echo $location; ?>10.jpg" class="foobox"></a>
				- <?php _e('A more modern and minimalistic lightbox theme.', 'foobox-image-lightbox' ); ?>
			</p>
			<p>
				<?php _e( 'The Rounded and Metro themes also allow you to change the color scheme, icon sets and loader icons.', 'foobox-image-lightbox' ); ?>
			</p>
		</div>
		<div>
			<h2><?php _e( 'Animations', 'foobox-image-lightbox' );?></h2>
			<p>
				<?php _e( 'You can animate FooBox in 12 different ways when it opens. This makes a great first impression on your visitors. See the animations in action by clicking the links inside the "Look &amp; Feel" section above.', 'foobox-image-lightbox' );?>
			</p>
		</div>
		<div>
			<h2><?php _e( 'Social Sharing', 'foobox-image-lightbox' );?></h2>
			<p>
				<?php _e( 'Easily add social sharing icons to FooBox lightbox so that your visitors can easily share your awesome media content!', 'foobox-image-lightbox' );?>
				<img style="display: block; margin: 0 auto;" src="data:image/png;base64,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">
			</p>
		</div>
		<div>
			<h2><?php _e( 'Plus so much more...', 'foobox-image-lightbox' );?></h2>
			<p>
				<?php _e( 'There are <strong>85+ settings</strong> at your disposal to make FooBox work exactly the way you want, including adding your own custom javascript and stylesheets, so that you can truly make FooBox your own.', 'foobox-image-lightbox' );?>
			</p>
			<p>
				<h4><?php printf( '<a href="%s">%s</a>', esc_url ( foobox_settings_url() . '#looknfeel' ), __( 'Change your FooBox settings now!', 'foobox-image-lightbox' ) ); ?></h4>
			</p>
		</div>
	</div>
	<div id="demo_tab" class="feature-section nav-container" style="display: none">
		<div id="foobox-inline" style="display: none">
			<div class="foobox-inline-content">
			<h3><?php _e( 'Inline HTML Content', 'foobox-image-lightbox' ); ?></h3>
			<p>Commodo error voluptate quia illum, esse commodi nonummy recusandae! Purus? Ea proident beatae ex! Deleniti vivamus. Feugiat irure excepteur condimentum cillum, architecto, erat facere. Magni mollit venenatis, ipsam. Eget pariatur.</p>
			<p>Suscipit eros eaque architecto sociis id condimentum minima amet, similique neque ab dolorem sapien est iusto facere voluptatum do faucibus exercitationem dapibus quisque aliquip nemo hic eu natus do porttitor.</p>
			</div>
		</div>
		<style>
			.about-wrap .feature-section p.demo-gallery {
				max-width: 32em;
			}
			.demo-gallery a
			{
				display: inline-block;
				float: left;
				margin: 10px;
				text-align: center;
				padding: 2px;
				border: 1px solid #9D9B8B;
				-webkit-box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
				-moz-box-shadow: 0 0 10px rgba(0,0,0,0.5);
				box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
			}

			.demo-gallery a img
			{
				padding: 2px;
				display: block;
			}

			.demo-gallery a:hover
			{
				opacity: 0.8;
			}

			.foobox-inline-content {
				padding: 20px;
			}
		</style>
		<p class="demo-gallery">
			<?php foreach ($demo_images as $demo_image) {
				if ( strpos( $demo_image['src'], 'http' ) !== false || strpos( $demo_image['src'], '#' ) !== false ) {
					$a_href = ' href="' .  $demo_image['src'] . '"';
				} else {
					$a_href = ' href="' . $location . $demo_image['src'] . '"';
				}
				if ( isset( $demo_image['target'] ) ) {
					$a_href .= ' target="' . $demo_image['target'] . '" ';
				}
				if ( isset( $demo_image['data_width'] ) ) {
					$a_href .= ' data-width="' . $demo_image['data_width'] . '" ';
				}
				$a_title = isset( $demo_image['title'] ) ? ' data-caption-title="' . $demo_image['title'] . '"' : '';
				$a_desc = isset( $demo_image['desc'] ) ? ' data-caption-desc="' . $demo_image['desc'] . '"' : '';

				if ( isset( $demo_image['thumb_src'] ) ) {
					$img_src = ' src="' . $demo_image['thumb_src'] . '"';
				} else {
					$img_src = ' src="' . $location . 'thumbs/' . $demo_image['src'] . '"';
				}

				$img_width = ' width="' . $size . '"';
				$img_height = ' height="' . $size . '"';

				?>
				<a<?php echo $a_href . $a_title . $a_desc; ?> class="foobox" rel="foobox"><img <?php echo $img_src . $img_width . $img_height; ?>/></a>
			<?php } ?>
		</p>
		<div style="clear:both"></div>
		<p style="text-align: center">
			<a target="_blank" href="https://pixabay.com"/><?php _e( 'images found on pixabay.com', 'foobox-image-lightbox' );?></a>
		</p>
		<?php if ( !class_exists( 'FooGallery_Plugin' ) ) { ?>
		<h2><?php _e( 'Looking for a Gallery Plugin?', 'foobox-image-lightbox' );?></h2>
		<p>
			<?php printf( __( 'Creating galleries has never been easier with our free %s plugin and our premium %s extension, both of which work beautifully with FooBox!', 'foobox-image-lightbox' ),
					'<strong><a target="_blank" href="' . $foogallery_url. '">FooGallery</a></strong>',
					'<strong><a target="_blank" href="http://fooplugins.com/plugins/foovideo?utm_source=fooboxfreeplugin&utm_medium=fooboxfreefoovideolink&utm_campaign=foobox_free_admin_notice">FooVideo</a></strong>'); ?>
		</p>
		<h4><?php printf( '<a href="https://wordpress.org/plugins/foogallery/" target="_blank">%s</a>', __( 'Download FooGallery', 'foobox-image-lightbox' ) ); ?></h4>
		<?php } ?>
	</div>
