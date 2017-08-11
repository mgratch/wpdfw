<?php
/*
FooBox PRO Media Lightbox
*/

if ( ! defined( 'FOOBOX_PLUGIN_URL' ) ) {
	define( 'FOOBOX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'FOOBOX_FILE' ) ) {
	define( 'FOOBOX_FILE', __FILE__ );
}

if ( ! defined( 'FOOBOX_PATH' ) ) {
	define( 'FOOBOX_PATH', plugin_dir_path( __FILE__ ) );
}

if (!class_exists('fooboxV2')) {

	// Includes
	require_once ( FOOBOX_PATH . 'includes/FooBox_Settings.php' );
	require_once ( FOOBOX_PATH . 'includes/FooBox_Script_Generator.php' );
	require_once ( FOOBOX_PATH . 'includes/wp_pluginbase.php' );
	require_once ( FOOBOX_PATH . 'includes/shortcodes.php' );
	require_once ( FOOBOX_PATH . 'includes/Foobox_Exclude.php' );
	require_once ( FOOBOX_PATH . 'includes/envira-support.php' );
	require_once ( FOOBOX_PATH . 'includes/class-foogallery-foobox-extension.php' );
	require_once ( FOOBOX_PATH . 'includes/foogallery_lightbox_admin_notice.php' );
	require_once ( FOOBOX_PATH . 'includes/fooboxshare/bootstrapper.php' );

	class fooboxV2 extends wp_pluginbase_v2_6_2 {

		const JS                   = 'foobox.min.js';
		const JS_DEBUG             = 'foobox.debug.js';
		const CSS                  = 'foobox.min.css';
        const CSS_NOIE7            = 'foobox.noie7.min.css';
		const FOOBOX_URL           = 'http://fooplugins.com/plugins/foobox/';
		const BECOME_AFFILIATE_URL = 'http://fooplugins.com/affiliate-program/';
		const AFFILIATE_PREFIX     = 'Powered by ';
		const DOCUMENTATION_URL    = 'http://fooplugins.link/fooboxdocs/';
		const ERROR_MSG            = 'Could not load the item';
		const DEBUG_DEFAULT        = false;
		const ERROR_IMG            = 'error.png';
		const UPDATE_URL           = 'http://fooplugins.com/api/foobox/check';
		const SUPPORT_URL		   = 'http://fooplugins.link/fooboxdocs/';


		function init() {
			$this->plugin_slug    = 'foobox';
			$this->plugin_title   = $this->lightbox_name();
			$this->plugin_version = FOOBOX_BASE_VERSION;

			//call base init
			parent::init();

			add_action('plugins_loaded', array($this, 'load_text_domain'));

			//register activation hook
			register_activation_hook( __FILE__, array( 'fooboxV2', 'activate' ) );

			if ( is_admin() ) {
				add_action('admin_head', array($this, 'admin_inline_content'));
				add_filter('foobox-settings_summary', array($this, 'admin_settings_summary'));
				add_filter('foobox-settings_title', array($this, 'admin_settings_title'));

				do_action('foobox-admin-init', $this);

				new FooBox_FooGallery_Lightbox_Admin_Notice();

				add_action( FOOBOX_ACTION_ADMIN_MENU_RENDER_GETTING_STARTED, array( $this, 'render_page_getting_started' ) );
				add_action( FOOBOX_ACTION_ADMIN_MENU_RENDER_SETTINGS, array( $this, 'render_page_settings' ) );

				add_filter( FOOBOX_FILTER_SUPPORT_MENU_URL, array( $this, 'override_support_forum_url' ) );

				add_filter( 'foobox_getting_started_title', array( $this, 'override_getting_started_title' ) );
				add_filter( 'foobox_getting_started_tagline', array( $this, 'override_getting_started_tagline' ) );

			} else {
				add_filter( 'fooboxshareurl', array($this, 'shorten_share_url') );

				if ($this->must_disable_other_lightboxes()) {
					add_action('wp_footer', array($this, 'disable_other_lightboxes'), 200);
				}
				new FooBox_AutoOpen_Shortcodes();

				add_filter( 'wp_get_attachment_link', array( $this, 'add_gallery_attachment_id_attribute' ), 10, 2 );
			}

			if ( class_exists( 'Envira_Gallery_Lite' ) ||
				class_exists( 'Envira_Gallery' ) ) {
				new Foobox_Envira_Lite_Support();
			}

			$GLOBALS['fooboxshare'] = new FooBoxShare();

			new Foobox_Exclude();
		}

		function plugin_title() {
			return $this->plugin_title;
		}

		function must_disable_other_lightboxes() {
			return $this->is_option_checked('deregister_others', true) ||
				( class_exists('Woocommerce') && $this->is_option_checked('override_woocommerce_lightbox', true) );
		}

		function lightbox_name() {
			return $this->apply_filters('foobox-name', 'FooBox');
		}

		function image_url() {
			return $this->plugin_url . 'img/';
		}

		function load_text_domain() {
			load_plugin_textdomain('foobox', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		}

		function add_gallery_attachment_id_attribute( $link, $id ) {
			return str_replace('<a href=', '<a data-attachment-id="'.$id.'" href=', $link);
		}

		function admin_settings_summary() {

			$html = __('For support, FAQ and demos please visit the <a href="%s" target="_blank">%s Knowledge Base</a>.', 'foobox');

			$summary = sprintf($html, self::SUPPORT_URL, $this->plugin_title);

			return apply_filters( 'foobox-settings-summary' , $summary );
		}

		function admin_settings_title() {
			$title = __('%s PRO Settings - v%s', 'foobox');

			return sprintf($title, $this->plugin_title, $this->plugin_version);
		}

		function is_nextgenv2_activated() {
			if ( defined('NEXTGEN_GALLERY_PLUGIN_VERSION') ) {
				return version_compare(NEXTGEN_GALLERY_PLUGIN_VERSION, '2.0.0') >= 0;
			}
			return false;
		}

		function admin_settings_init() {
			$load_settings = apply_filters( 'foobox-admin-settings-init-condition', true );

			if ( $load_settings ) {
				FooBox_Settings::admin_settings_init($this);
			}

			do_action( 'foobox-admin-settings-init', $this );
		}

		function admin_plugin_row_meta($links) {

			$links[] = sprintf('<a target="_blank" href="%s"><b>%s</b></a>', self::DOCUMENTATION_URL, __('Online Documentation', 'foobox'));

			return $links;
		}

		function custom_admin_settings_render($args = array()) {
			$type = '';

			extract($args);

			if ($type == 'debug_output') {
				echo '</td></tr><tr valign="top"><td colspan="2">';
				$this->render_debug_info();
			} else if ($type == 'colours') {
				$this->render_colour_options();
			} else if ($type == 'icons') {
				$this->render_icon_options();
			} else if ($type == 'loader') {
				$this->render_loader_options();
			} else if ($type == 'demo') {
				echo '</td></tr><tr valign="top"><td colspan="2">';
				$this->render_demo();
			}
		}

		function generate_javascript($debug = false) {
			return FooBox_Script_Generator::generate_javascript($this, $debug);
		}

		function render_for_archive() {
			if (is_admin()) return true;

			return !is_singular();
		}

		function render_colour_options() {
			$colour     = $this->get_option('colour', 'light');
			if ($colour == 'white') { $colour = 'light'; }
			$custom_colour     = $this->get_option('custom_colour', '#FFFFFF');
			$input_name = $this->plugin_slug . '[colour]';
			$custom_input_name = $this->plugin_slug . '[custom_colour]';
			?>
			<div class="hidden">
				<input name="<?php echo $input_name; ?>" id="rad_colour_default" <?php if ($colour == "light") {
					echo 'checked="checked"';
				} ?> type="radio" value="light" tabindex="1"/>
				<input name="<?php echo $input_name; ?>" id="rad_colour_pink" <?php if ($colour == "pink") {
					echo 'checked="checked"';
				} ?> type="radio" value="pink" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_colour_green" <?php if ($colour == "green") {
					echo 'checked="checked"';
				} ?> type="radio" value="green" tabindex="3"/>
				<input name="<?php echo $input_name; ?>" id="rad_colour_blue" <?php if ($colour == "blue") {
					echo 'checked="checked"';
				} ?> type="radio" value="blue" tabindex="4"/>
				<input name="<?php echo $input_name; ?>" id="rad_colour_black" <?php if ($colour == "dark") {
					echo 'checked="checked"';
				} ?> type="radio" value="dark" tabindex="5"/>
			</div>
			<div class="radio_selector">
				<label class="colours_radio" for="rad_colour_default"><a <?php if ($colour == "light") {
						echo 'class="selected"';
					} ?> style="background:#FFF" title="White"></a></label>
				<label class="colours_radio" for="rad_colour_pink"><a <?php if ($colour == "pink") {
						echo 'class="selected"';
					} ?> style="background:#df64b6" title="Pink"></a></label>
				<label class="colours_radio" for="rad_colour_green"><a <?php if ($colour == "green") {
						echo 'class="selected"';
					} ?> style="background:#339933" title="Green"></a></label>
				<label class="colours_radio" for="rad_colour_blue"><a <?php if ($colour == "blue") {
						echo 'class="selected"';
					} ?> style="background:#1b58b7" title="Blue"></a></label>
				<label class="colours_radio" for="rad_colour_black"><a <?php if ($colour == "dark") {
						echo 'class="selected"';
					} ?> style="background:#1b1b1b" title="Black"></a></label>
				<label style="display: none" class="colours_radio" for="rad_colour_custom"><a <?php if ($colour == "custom") {
						echo 'class="selected"';
					} ?> title="Custom">
						<input style="display: none" id="txt_colour_custom1" type="text" name="<?php echo $custom_input_name; ?>" class="foobox-colorpicker" size="10" value="<?php echo $custom_colour; ?>"/>
					</a>
				</label>
			</div>
		<?php
		}

		function render_icon_options() {
			$icon             = $this->get_option('icon', '0');
			$input_name       = $this->plugin_slug . '[icon]';

			if ($icon == 'default' || $icon == 'invert') { $icon = '0'; }
			else if ($icon == 'mini' || $icon == 'mini-invert') { $icon = '1'; }

			?>
			<div class="hidden">
				<input name="<?php echo $input_name; ?>" id="rad_icon_default" <?php if ($icon == "0") { echo 'checked="checked"'; } ?> type="radio" value="0" tabindex="1"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_1" <?php if ($icon == "1") { echo 'checked="checked"'; } ?> type="radio" value="1" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_2" <?php if ($icon == "2") { echo 'checked="checked"'; } ?> type="radio" value="2" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_3" <?php if ($icon == "3") { echo 'checked="checked"'; } ?> type="radio" value="3" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_4" <?php if ($icon == "4") { echo 'checked="checked"'; } ?> type="radio" value="4" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_5" <?php if ($icon == "5") { echo 'checked="checked"'; } ?> type="radio" value="5" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_6" <?php if ($icon == "6") { echo 'checked="checked"'; } ?> type="radio" value="6" tabindex="2"/>

				<input name="<?php echo $input_name; ?>" id="rad_icon_7" <?php if ($icon == "7") { echo 'checked="checked"'; } ?> type="radio" value="7" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_8" <?php if ($icon == "8") { echo 'checked="checked"'; } ?> type="radio" value="8" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_9" <?php if ($icon == "9") { echo 'checked="checked"'; } ?> type="radio" value="9" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_10" <?php if ($icon == "10") { echo 'checked="checked"'; } ?> type="radio" value="10" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_icon_11" <?php if ($icon == "11") { echo 'checked="checked"'; } ?> type="radio" value="11" tabindex="2"/>
			</div>
			<div class="radio_selector">
				<label class="icons_radio" for="rad_icon_default">
					<a class="fbx-arrows-0<?php if ($icon == "0") { echo ' selected';	} ?>" title="Default"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_1">
					<a class="fbx-arrows-1<?php if ($icon == "1") { echo ' selected';	} ?>" title="1"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_2">
					<a class="fbx-arrows-2<?php if ($icon == "2") { echo ' selected';	} ?>" title="2"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_3">
					<a class="fbx-arrows-3<?php if ($icon == "3") { echo ' selected';	} ?>" title="3"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_4">
					<a class="fbx-arrows-4<?php if ($icon == "4") { echo ' selected';	} ?>" title="4"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_5">
					<a class="fbx-arrows-5<?php if ($icon == "5") { echo ' selected';	} ?>" title="5"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_6">
					<a class="fbx-arrows-6<?php if ($icon == "6") { echo ' selected';	} ?>" title="6"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_7">
					<a class="fbx-arrows-7<?php if ($icon == "7") { echo ' selected';	} ?>" title="7"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_8">
					<a class="fbx-arrows-8<?php if ($icon == "8") { echo ' selected';	} ?>" title="8"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_9">
					<a class="fbx-arrows-9<?php if ($icon == "9") { echo ' selected';	} ?>" title="9"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_10">
					<a class="fbx-arrows-10<?php if ($icon == "10") { echo ' selected';	} ?>" title="10"><span class="fbx-next"></span></a>
				</label>
				<label class="icons_radio" for="rad_icon_11">
					<a class="fbx-arrows-11<?php if ($icon == "11") { echo ' selected';	} ?>" title="11"><span class="fbx-next"></span></a>
				</label>
			</div>
			<?php
		}

		function render_loader_options() {
			$loader           = $this->get_option('loader', '0');
			$input_name       = $this->plugin_slug . '[loader]';

			?>
			<div class="hidden">
				<input name="<?php echo $input_name; ?>" id="rad_loader_default" <?php if ($loader == "0") { echo 'checked="checked"'; } ?> type="radio" value="0" tabindex="1"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_2" <?php if ($loader == "2") { echo 'checked="checked"'; } ?> type="radio" value="2" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_3" <?php if ($loader == "3") { echo 'checked="checked"'; } ?> type="radio" value="3" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_4" <?php if ($loader == "4") { echo 'checked="checked"'; } ?> type="radio" value="4" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_5" <?php if ($loader == "5") { echo 'checked="checked"'; } ?> type="radio" value="5" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_6" <?php if ($loader == "6") { echo 'checked="checked"'; } ?> type="radio" value="6" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_7" <?php if ($loader == "7") { echo 'checked="checked"'; } ?> type="radio" value="7" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_8" <?php if ($loader == "8") { echo 'checked="checked"'; } ?> type="radio" value="8" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_9" <?php if ($loader == "9") { echo 'checked="checked"'; } ?> type="radio" value="9" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_10" <?php if ($loader == "10") { echo 'checked="checked"'; } ?> type="radio" value="10" tabindex="2"/>
				<input name="<?php echo $input_name; ?>" id="rad_loader_12" <?php if ($loader == "11") { echo 'checked="checked"'; } ?> type="radio" value="11" tabindex="2"/>
			</div>
			<div class="radio_selector">
				<label class="loaders_radio" for="rad_loader_default">
					<a class="fbx-admin-loader fbx-spinner-0<?php if ($loader == "0") { echo ' selected';	} ?>" title="Default"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_2">
					<a class="fbx-admin-loader fbx-spinner-2<?php if ($loader == "2") { echo ' selected';	} ?>" title="2"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_3">
					<a class="fbx-admin-loader fbx-spinner-3<?php if ($loader == "3") { echo ' selected';	} ?>" title="3"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_4">
					<a class="fbx-admin-loader fbx-spinner-4<?php if ($loader == "4") { echo ' selected';	} ?>" title="4"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_5">
					<a class="fbx-admin-loader fbx-spinner-5<?php if ($loader == "5") { echo ' selected';	} ?>" title="5"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_6">
					<a class="fbx-admin-loader fbx-spinner-6<?php if ($loader == "6") { echo ' selected';	} ?>" title="6"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_7">
					<a class="fbx-admin-loader fbx-spinner-7<?php if ($loader == "7") { echo ' selected';	} ?>" title="7"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_8">
					<a class="fbx-admin-loader fbx-spinner-8<?php if ($loader == "8") { echo ' selected';	} ?>" title="8"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_9">
					<a class="fbx-admin-loader fbx-spinner-9<?php if ($loader == "9") { echo ' selected';	} ?>" title="9"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_10">
					<a class="fbx-admin-loader fbx-spinner-10<?php if ($loader == "10") { echo ' selected';	} ?>" title="10"><div><span /></div></a>
				</label>
				<label class="loaders_radio" for="rad_loader_11">
					<a class="fbx-admin-loader fbx-spinner-11<?php if ($loader == "11") { echo ' selected';	} ?>" title="11"><div><span /></div></a>
				</label>
			</div>
		<?php
		}

		function render_debug_info() {

			echo '<strong>Javascript:<br /><pre>';

			echo htmlentities($this->generate_javascript(true));

			echo '</pre><br />Settings:<br /><pre>';

			echo htmlentities( print_r(get_option($this->plugin_slug), true) );

			echo '</pre>';
		}

		function render_demo() {
			require_once "includes/demo.php";
		}




		//does a check for WP inline script support. This does not work at the moment, so it was disabled by returning false
		function supports_wp_inline_scripts() {
			return false;
			//return function_exists( 'wp_add_inline_script1' );
		}

		function frontend_init() {
			$where = 'wp_head';

			if ($this->is_option_checked('scripts_in_footer')) {
				$where = 'wp_print_footer_scripts';
			}

			add_action( $where, array($this, 'inline_dynamic_js') );

			add_action( $where, array($this, 'inline_dynamic_css'), 100 );
		}

		function admin_print_styles() {
			parent::admin_print_styles();
			if ($this->check_admin_settings_page()) {
				$this->frontend_print_styles();
			}
			do_action('foobox_admin_print_styles');
		}

		function admin_print_scripts() {
			parent::admin_print_scripts();
			if ($this->check_admin_settings_page()) {
				if ($this->is_option_checked('enable_debug', self::DEBUG_DEFAULT)) {
					$this->register_and_enqueue_js(self::JS_DEBUG, array('jquery'));
				} else {
					$this->register_and_enqueue_js(self::JS, array('jquery'));
				}
			}
			do_action('foobox_admin_print_scripts');
		}

		function admin_inline_content() {
			if ( $this->check_admin_settings_page() ) {
				$this->inline_dynamic_css();
				$this->inline_dynamic_js();
			}
		}

		function frontend_print_styles() {
			if ( !apply_filters('foobox_enqueue_styles', true) ) return;

            //enqueue foobox CSS
            if ( $this->is_option_checked('dropie7support', false) ) {
                $this->register_and_enqueue_css(self::CSS_NOIE7);
            } else {
                $this->register_and_enqueue_css(self::CSS);
            }
		}

		function check_admin_settings_page() {
			return is_admin() && array_key_exists('page', $_GET) &&
				($_GET['page'] == FOOBOX_BASE_SLUG || $_GET['page'] == FOOBOX_BASE_PAGE_SLUG_SETTINGS || $_GET['page'] == 'foobox');
		}

		function frontend_print_scripts() {
			if (!apply_filters('foobox_enqueue_scripts', true)) return;

			//put JS in footer?
			$infooter = $this->is_option_checked('scripts_in_footer');

			if ($this->is_option_checked('enable_debug', self::DEBUG_DEFAULT)) {
				//enqueue debug foobox script
				$this->register_and_enqueue_js(
					$file = self::JS_DEBUG,
					$d = $this->get_js_depends(),
					$v = false,
					$f = $infooter);
			} else {
				//enqueue foobox script
				$this->register_and_enqueue_js(
					$file = self::JS,
					$d = $this->get_js_depends(),
					$v = false,
					$f = $infooter);
			}
		}

		function js_handle() {
			$file = self::JS;

			if ( $this->is_option_checked( 'enable_debug', self::DEBUG_DEFAULT ) ) {
				$file = self::JS_DEBUG;
			}

			return str_replace( '.', '-', pathinfo( $file, PATHINFO_FILENAME ) );
		}

		function css_handle() {
			$file = self::CSS;

			if ( $this->is_option_checked( 'dropie7support', false ) ) {
				$file = self::CSS_NOIE7;
			}

			return str_replace( '.', '-', pathinfo( $file, PATHINFO_FILENAME ) );
		}

		function inline_dynamic_js() {
			if ( !apply_filters('foobox_enqueue_scripts', true ) ) return;

			$foobox_js = $this->generate_javascript();

			$defer_js = !$this->is_option_checked( 'disable_defer_js', true );

			$script_type = $defer_js ? 'text/foobox' : 'text/javascript';

			echo '<script type="' . $script_type . '">' . $foobox_js . '</script>';

			if ( $defer_js ) {
				?>
				<script type="text/javascript">
					if (window.addEventListener){
						window.addEventListener("DOMContentLoaded", function() {
							var arr = document.querySelectorAll("script[type='text/foobox']");
							for (var x = 0; x < arr.length; x++) {
								var script = document.createElement("script");
								script.type = "text/javascript";
								script.innerHTML = arr[x].innerHTML;
								arr[x].parentNode.replaceChild(script, arr[x]);
							}
						});
					} else {
						console.log("FooBox does not support the current browser.");
					}
				</script>
				<?php
			}
		}

		function inline_dynamic_css() {

			if (!apply_filters('foobox_enqueue_styles', true)) return;

			//get custom CSS from the settings page
			$custom_css = $this->get_option('custom_css', '');

			if ( $this->supports_wp_inline_scripts() ) {
				wp_add_inline_style( $this->css_handle(), $custom_css );
			} else {
				echo '<style type="text/css">
' . $custom_css;
				echo '
</style>';
			}
		}

		function get_js_depends() {
			return array('jquery');
		}

		function disable_other_lightboxes() {
			?>
			<script type="text/javascript">
				jQuery.fn.prettyPhoto   = function () { return this; };
				jQuery.fn.fancybox      = function () { return this; };
				jQuery.fn.fancyZoom     = function () { return this; };
				jQuery.fn.colorbox      = function () { return this; };
				jQuery.fn.magnificPopup = function () { return this; };
			</script>
		<?php
		}

		/**
		 * Fired when the plugin is activated.
		 *
		 * @since    2.3.2.27
		 *
		 * @param    boolean    $network_wide    True if WPMU superadmin uses
		 *                                       "Network Activate" action, false if
		 *                                       WPMU is disabled or plugin is
		 *                                       activated on an individual blog.
		 */
		public static function activate( $network_wide ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				//do something for multisite!
			} else {
				//Let's check if FooGallery is installed. If, so then auto-activate the FooBox Extension inside FooGallery
				if ( ! current_user_can( 'activate_plugins' ) || ! class_exists( 'FooGallery_Plugin' ) )
					return;

				$api = foogallery_extensions_api();
				$api->activate( 'foobox', false );
			}
		}

		function render_page_getting_started() {
			require_once FOOBOX_PATH . 'includes/view-getting-started.php';
		}

		function render_page_settings() {
			if ( isset( $_GET['settings-updated'] ) ) {
				if ( false === get_option( 'foobox' ) ) { ?>
					<div id="message" class="updated">
						<p>
							<strong><?php _e( 'FooBox settings restored to defaults.', 'foobox-image-lightbox' ); ?></strong>
						</p>
					</div>
				<?php } else { ?>
					<div id="message" class="updated">
						<p><strong><?php _e( 'FooBox settings updated.', 'foobox-image-lightbox' ); ?></strong></p>
					</div>
				<?php }
			}

			$instance = $GLOBALS['foobox'];
			$instance->admin_settings_render_page();
		}

		function override_support_forum_url( $url ) {
			return fooboxV2::SUPPORT_URL;
		}

		function override_getting_started_title( $title ) {
			return __( 'Welcome to FooBox PRO!', 'foobox' );
		}

		function override_getting_started_tagline( $tagline ) {
			return __( 'Thank you for choosing FooBox PRO as your lightbox! A great looking responsive lightbox with built-in social sharing.', 'foobox' );
		}

		function shorten_share_url( $url ) {
			//check if we need to use bitly
			if ( $this->is_option_checked( 'social_use_bitly', false ) ) {
				$access_token = $this->get_option('social_bitly_token', '');

				if ( !empty( $access_token ) ) {

					//generate a short url

					$bitly = "https://api-ssl.bitly.com/v3/shorten?format=json&access_token={$access_token}&longUrl=" . urlencode($url);

					$response = wp_remote_get( $bitly, array('timeout' => '30',));

					if ( is_array( $response ) && '200' == $response['response']['code'] ) {

						$json = @json_decode( $response['body'], true );

						if ( isset( $json ) && 200 === $json['status_code'] ) {
							$url = $json['data']['url'];
						}
					}
				}
			}

			return $url;
		}

		function admin_settings_add_menu() {
			$settings_menu_role = $this->get_option('settingsmenurole', 'none');

			if ( 'none' !== $settings_menu_role ) {
				$user = wp_get_current_user();
				if ( in_array( $settings_menu_role, (array) $user->roles ) ) {
					$settings_title = $this->get_settings_title();
					$settings_menu = $this->get_settings_menu();

					add_options_page(
						$settings_title,
						$settings_menu,
						$settings_menu_role,
						$this->plugin_slug,
						'foobox_action_admin_menu_render_settings'
					);
				}
			}
		}

//		function admin_print_scripts() {
//			parent::admin_print_scripts();
//
//			if ( )
//		}
	}



	//run the plugin!
	$GLOBALS['foobox'] = new fooboxV2();
}