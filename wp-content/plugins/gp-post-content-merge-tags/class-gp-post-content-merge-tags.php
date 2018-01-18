<?php

class GP_Post_Content_Merge_Tags extends GWPerk {

	public $version = GP_POST_CONTENT_MERGE_TAGS_VERSION;
	public $min_gravity_perks_version = '1.2.13';
	public $min_gravity_forms_version = '2.0';
	public $prefix = 'gpPostContentMergeTags';
    
    public static $_entry = null;

	private static $instance = null;

	public static function get_instance( $perk_file ) {
		if( null == self::$instance )
            self::$instance = new self( $perk_file );
        return self::$instance;
    }

    public function init() {

        parent::init();

	    load_plugin_textdomain( 'gp-post-content-merge-tags', false, basename( dirname( __file__ ) ) . '/languages/' );
        
        $this->register_tooltips();

	    // settings + ui
	    add_filter( 'gform_confirmation_ui_settings', array( $this, 'add_confirmation_ui_setting' ), 10, 3 );
	    add_filter( 'gform_pre_confirmation_save',    array( $this, 'save_confirmation_ui_setting' ), 10, 2 );
	    add_action( 'gform_entry_info',               array( $this, 'add_confirmation_url_entry_detail'), 9, 2  );
	    add_filter( 'gform_custom_merge_tags',        array( $this, 'add_merge_tags' ) );

	    // post editor merge tag button
	    add_filter( 'wp_tiny_mce_init',     array( $this, 'tiny_mce_init' ) );
	    add_filter( 'mce_external_plugins', array( $this, 'register_tinymce_button' ) );
	    add_filter( 'mce_buttons',          array( $this, 'enqueue_tinymce_button' ) );
	    add_filter( 'quicktags_settings',   array( $this, 'add_quicktag_button' ) );

	    // frontend
        add_filter( 'the_content',              array( $this, 'replace_merge_tags' ), 1 );
        add_filter( 'gform_replace_merge_tags', array( $this, 'replace_encrypted_entry_id_merge_tag' ), 10, 3 );
	    add_filter( 'gform_replace_merge_tags', array( $this, 'replace_confirmation_url_merge_tag' ), 10, 3 );
	    add_filter( 'gform_replace_merge_tags', array( $this, 'replace_pretty_entry_id_merge_tag' ), 10, 3 );
        add_filter( 'gform_confirmation',       array( $this, 'append_eid_parameter' ), 20, 3 );
        add_action( 'gform_entry_created',      array( $this, 'save_pretty_id' ), 20, 3 );

	    // ajax
	    add_filter( 'wp_ajax_gppcmt_get_form', array( $this, 'ajax_get_form' ) );

	    // shortcodes
	    add_shortcode( 'eid', array( $this, 'do_eid_shortcode' ) );
	    add_shortcode( 'noeid', array( $this, 'do_noeid_shortcode' ) );

    }

    ## ADMIN

	public function tiny_mce_init( $settings ) {

		if( ! $this->should_load_scripts() ) {
			return;
		}

		$data = array(
			'initFormId' => $this->get_post_form_id(),
			'postId'     => rgget( 'post' ),
			'baseUrl'    => $this->get_base_url(),
			'gfBaseUrl'  => GFCommon::get_base_url(),
			'nonce'      => wp_create_nonce( 'gppcmt_get_form' )
		);

		$scripts = array();

		if( ! wp_script_is( 'gform_form_admin' ) ) {
			$scripts[] = 'gform_form_admin';
		}

		if( ! wp_script_is( 'gform_gravityforms' ) ) {
			$scripts[] = 'gform_gravityforms';
		}

		wp_print_scripts( $scripts );

		//if( rgars( $settings, 'content/selector' ) == '#content' ): ?>

			<script type="text/javascript">
				var gppcmtData = <?php echo json_encode( $data ); ?>;
			</script>

			<style type="text/css">
				.mce-gppcmt .mce-ico { background-size: 16px 16px; background-repeat: no-repeat; }
				.mce-gppcmt .mce-caret { display: none; }
				.mce-header.mce-menu-item .mce-text {
					font-weight: bold;
					text-transform: capitalize;
				}
				.mce-menu .mce-header.mce-menu-item:hover,
				.mce-menu .mce-header.mce-menu-item.mce-selected,
				.mce-menu .mce-header.mce-menu-item:focus,
				.mce-menu .mce-header.mce-menu-item-normal.mce-active,
				.mce-menu .mce-header.mce-menu-item-preview.mce-active {
					background-color: transparent;
				}
				.mce-header.mce-menu-item:hover .mce-text,
				.mce-header.mce-menu-item.mce-selected .mce-text,
				.mce-header.mce-menu-item:focus .mce-text {
					color: #333;
				}
			</style>
			
		<?php //endif;
	}

	public function ajax_get_form() {

		if( ! check_admin_referer( 'gppcmt_get_form', 'nonce' ) ) {
			return;
		}

		$form_id = intval( rgpost( 'formId' ) );
		$post_id = intval( rgpost( 'postId' ) );
		$return  = array();
		
		if( ! $form_id ) {

			$forms = GFFormsModel::get_forms( true );

			foreach( $forms as $form ) {
				$return[] = array(
					'title' => esc_js( GFCommon::truncate_middle( $form->title, 50 ) ),
					'id'    => intval( $form->id ),
				);
			}

		} else {

			$form = GFAPI::get_form( $form_id );
			if( ! is_wp_error( $form ) ) {
				$this->update_post_form_id( $post_id, $form_id );
				$return = $form;
				$return['mergeTags'] = GFCommon::get_merge_tags( $form['fields'], '#content' );
			}

		}

		echo json_encode( $return );

		die();
	}

	/**
	 * Determine if admin scripts should be loaded.
	 *
	 * Yes if form ID is in the query string or specified via custom field for this post - and - the form exists.
	 *
	 * @return bool
	 */
	public function should_load_scripts() {

		$form_id = $this->get_post_form_id();
		if( ! $form_id ) {
			return false;
		}

		$form = GFAPI::get_form( $form_id );
		if( ! $form ) {
			return false;
		}

		return true;
	}

	public function get_post_form_id( $post_id = false ) {

		$form_id = rgget( 'form_id' );
		if( $form_id ) {
			return $form_id;
		}

		if( $post_id == false ) {
			$post_id = rgget( 'post' );
		}

		$form_id = get_post_meta( $post_id, '_gppcmt_form_id', true );
		if( $form_id ) {
			return $form_id;
		}

		return false;
	}

	public function update_post_form_id( $post_id, $form_id ) {
		update_post_meta( $post_id, '_gppcmt_form_id', $form_id );
	}

	/**
	 * Use the [eid] shortcode to replace merge tags in places where "the_content" filter might not be applied but
	 * shortcodes are still processed.
	 *
	 * Examples:
	 * [eid tag="{Field A:3}" /]
	 * [eid]{Field A:3}[/eid]
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function do_eid_shortcode( $atts, $content = null ) {

		$atts = shortcode_atts( array(
			'tag'   => false,
			'field' => false
		), $atts );

		// If there is no entry, return nothing.
		$entry = $this->get_entry();
		if( empty( $entry ) ) {
			return '';
		}

		if( $atts['tag'] ) {
			$content = $atts['tag'];
		}

		if ( $atts['field'] ) {

			$field_id = $atts['field'];

			// @todo: Update GWPerk to extend GFAddON so we can use $this->get_field_value();
			return rgar( $entry, $field_id );
		}

		$content = $this->replace_merge_tags( $content );

		return do_shortcode( $content );
	}

	public function do_noeid_shortcode( $atts, $content = null ) {

		// Only return the shortcode contents if there is no entry.
		$entry = $this->get_entry();
		if( empty( $entry ) ) {
			return do_shortcode( GFCommon::replace_variables_prepopulate( $content ) );
		}

		return '';
	}

    public function register_tooltips() {
        $this->add_tooltip( 'gppcmtEnable', sprintf( '<h6>%s</h6> %s', __( 'Post Content Merge Tags', 'gp-post-content-merge-tags' ), __( 'Use Gravity Forms merge tags in your post content. Click the "Edit Page Content" link to add merge tags to your page content.', 'gp-post-content-merge-tags' ) ) );
    }

	public function add_merge_tags( $merge_tags ) {

		$merge_tags[] = array(
			'tag'   => '{encrypted_entry_id}',
			'label' => esc_html__( 'Encrypted Entry ID', 'gp-post-content-merge-tags' )
		);

		$merge_tags[] = array(
			'tag'   => '{confirmation_url}',
			'label' => esc_html__( 'Confirmation URL', 'gp-post-content-merge-tags' )
		);

		if( $this->is_pretty_id_enabled() ) {
			$merge_tags[] = array(
				'tag'   => '{pretty_entry_id}',
				'label' => esc_html__( 'Pretty Entry ID', 'gp-post-content-merge-tags' )
			);
		}

		return $merge_tags;
	}

    public function replace_merge_tags( $content ) {

	    $process_general_merge_tags = apply_filters( 'gppct_process_general_merge_tags', true, $content );

        $entry = $this->get_entry();
        if( ! $entry ) {
	        // if there is no entry but general merge tags are enabled (by default), process pre-population merge tags
	        if( $process_general_merge_tags ) {
		        $content = GFCommon::replace_variables_prepopulate( $content, false, false, false, false, false );
	        }
        } else {

	        $form = GFFormsModel::get_form_meta( $entry['form_id'] );

	        $content = $this->replace_field_label_merge_tags( $content, $form );
	        $content = GFCommon::replace_variables( $content, $form, $entry, false, false, false );

        }

        return $content;
    }

    function replace_field_label_merge_tags( $text, $form ) {

        preg_match_all( '/{([^:]+?)}/', $text, $matches, PREG_SET_ORDER );
        if( empty( $matches ) )
            return $text;

        foreach( $matches as $match ) {

            list( $search, $field_label ) = $match;

            foreach( $form['fields'] as $field ) {

                $full_input_id = false;
                $matches_admin_label = strcasecmp( rgar( $field, 'adminLabel' ), $field_label ) === 0;
                $matches_field_label = false;

                if( is_array( $field->get_entry_inputs() ) ) {
                    foreach( $field['inputs'] as $input ) {
                        if( strcasecmp( GFFormsModel::get_label( $field, $input['id'] ), $field_label ) === 0 ) {
                            $matches_field_label = true;
                            $input_id = $input['id'];
                            break;
                        }
                    }
                } else {
                    $matches_field_label = strcasecmp( GFFormsModel::get_label( $field ), $field_label ) === 0;
                    $input_id = $field['id'];
                }

                if( ! $matches_admin_label && ! $matches_field_label )
                    continue;

                $replace = sprintf( '{%s:%s}', $field_label, (string) $input_id );
                $text = str_replace( $search, $replace, $text );

                break;
            }

        }

        return $text;
    }

    function replace_encrypted_entry_id_merge_tag( $text, $form, $entry ) {

        if( strpos( $text, '{encrypted_entry_id}' ) === false ) {
            return $text;
        }

        // $entry is not always a "full" entry
        $entry_id = rgar( $entry, 'id' );
        if( $entry_id ) {
            $entry_id = $this->prepare_eid( $entry['id'], true );
        }

        return str_replace( '{encrypted_entry_id}', $entry_id, $text );
    }

    function replace_confirmation_url_merge_tag ( $text, $form, $entry ) {

	    if( strpos( $text, '{confirmation_url}' ) === false ) {
		    return $text;
	    }

	    $url = gform_get_meta( $entry['id'], 'gppcmt_url' );
	    if( empty( $url ) ) {
		    $confirmation = GFFormDisplay::handle_confirmation( $form, $entry );
		    if( isset( $confirmation['redirect'] ) ) {
			    $bits = parse_url( $confirmation['redirect'] );
			    parse_str( $bits['query'], $query );
			    if( rgar( $query, 'eid' ) ) {
				    $url = $confirmation['redirect'];
			    }
		    }
	    }

	    return str_replace( '{confirmation_url}', $url, $text );
    }

    function replace_pretty_entry_id_merge_tag( $text, $form, $entry ) {

	    if( strpos( $text, '{pretty_entry_id}' ) === false ) {
		    return $text;
	    }

	    $pretty_id = gform_get_meta( $entry['id'], 'gppcmt_pretty_id' );

	    return str_replace( '{pretty_entry_id}', $pretty_id, $text );
    }

    function append_eid_parameter( $confirmation, $form, $entry ) {

	    if( ! rgars( $form, 'confirmation/gppcmtEnable' ) ) {
		    return $confirmation;
	    }

        $is_ajax_redirect = is_string( $confirmation ) && strpos( $confirmation, 'gformRedirect' );
        $is_redirect      = is_array( $confirmation ) && isset( $confirmation['redirect'] );

        if( ! ( $is_ajax_redirect || $is_redirect ) ) {
            return $confirmation;
        }

	    /**
	     * Should "eid" parameter be encrypted? Defaults to true if pretty id is not enabled.
	     *
	     * @since 1.0
	     *
	     * @param 
	     */
	    $encrypt = apply_filters( 'gppcmt_encrypt_eid', ! $this->is_pretty_id_enabled(), $confirmation, $form, $entry );
        $eid = $this->prepare_eid( $entry['id'], $encrypt );

        if( $is_ajax_redirect ) {
            preg_match_all( '/gformRedirect.+?(http.+?)(?=\'|")/', $confirmation, $matches, PREG_SET_ORDER );
            list( $full_match, $url ) = $matches[0];
            $redirect_url = $this->add_eid_query_arg( $eid, $url );
            $confirmation = str_replace( $url, $redirect_url, $confirmation );
        } else {
            $redirect_url             = $this->add_eid_query_arg( $eid, $confirmation['redirect'] );
            $confirmation['redirect'] = $redirect_url;
        }

        gform_update_meta( $entry['id'], 'gppcmt_url', $redirect_url );

        return $confirmation;
    }

	/**
	 * Using add_query_arg() will replace spaces in parameter names with underscores.
	 *
	 * My Parameter=1
	 * My_Parameter=1
	 *
	 * @param $eid
	 * @param $url
	 *
	 * @return mixed|string
	 */
    function add_eid_query_arg( $eid, $url ) {

	    $url .= strpos( $url, '?' ) ? '&' : '?';
	    $url .= "eid={$eid}";

	    return $url;
    }


    function save_pretty_id( $entry ) {
		if( $this->is_pretty_id_enabled() ) {
			gform_update_meta( $entry['id'], 'gppcmt_pretty_id', $this->generate_pretty_id() );
		}
    }

    function generate_pretty_id( $length = 6 ) {

	    $pretty_id = false;

		for( $i = 0; $i <= 9; $i++ ) {

		    $length = max( $length, 4 ); // 4 min gives us 1,413,720 possible unique IDs
		    $pretty_id = '';
		    do {
			    $pretty_id .= uniqid();
		    } while( strlen( $pretty_id ) < $length );
		    $pretty_id = substr( $pretty_id, -$length );
		    $is_unique = $this->check_unique( $pretty_id );

		    if( $is_unique ) {
			    break;
		    }

	    }

	    return $pretty_id;
    }

    function check_unique( $value ) {
		global $wpdb;
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT count( lead_id ) FROM {$wpdb->prefix}rg_lead_meta WHERE meta_value = %s", $value ) );
		return $result == false;
    }

    function prepare_eid( $entry_id, $force_encrypt = false ) {

        $eid = $entry_id;

        if( $force_encrypt ) {

	        $do_encrypt = apply_filters( 'gppcmt_encrypt_eid', $force_encrypt );

	        if( $do_encrypt && is_callable( array( 'GFCommon', 'encrypt' ) ) ) {
		        $eid = rawurlencode( GFCommon::encrypt( $eid ) );
	        }

        } else if( $this->is_pretty_id_enabled() ) {
        	$eid = gform_get_meta( $entry_id, 'gppcmt_pretty_id' );
        }

        return $eid;
    }

    function get_entry() {

	    GravityPerks::log_debug( sprintf( '%s: Start.', __METHOD__ ) );

        if( ! self::$_entry ) {

	        GravityPerks::log_debug( sprintf( '%s: Entry is not cached.', __METHOD__ ) );

            $entry_id = $this->get_entry_id();
	        GravityPerks::log_debug( sprintf( '%s: Entry ID = %s.', __METHOD__, $entry_id ) );
            if( ! $entry_id ) {
	            return false;
            }

	        $entry = false;

            if( $this->is_pretty_id_enabled() ) {
	            GravityPerks::log_debug( sprintf( '%s: Pretty ID is enabled.', __METHOD__ ) );
                $entry = $this->get_entry_by_pretty_id( $entry_id );
	            GravityPerks::log_debug( sprintf( '%s: Entry from Pretty ID: %s.', __METHOD__, print_r( $entry, true ) ) );
            }

            // Even if Pretty ID is enabled, we still want to support raw entry IDs.
            if( ! $entry || is_wp_error( $entry ) ) {
	            $entry = GFAPI::get_entry( $entry_id );
	            GravityPerks::log_debug( sprintf( '%s: Get entry by raw entry ID. Entry: %s', __METHOD__, print_r( $entry, true ) ) );
            }

            if( is_wp_error( $entry ) ) {
	            GravityPerks::log_debug( sprintf( '%s: Oops. Error: %s', __METHOD__, print_r( $entry, true ) ) );
	            return false;
            }

            self::$_entry = $entry;

        }

        return self::$_entry;
    }

    function get_entry_id() {

        $entry_id = rgget( 'eid' );
        if( $entry_id ) {
            return $this->maybe_decrypt_entry_id( $entry_id );
        }

        $post = get_post();
        if( $post ) {
            $entry_id = get_post_meta( $post->ID, '_gform-entry-id', true );
        }

        return $entry_id ? $entry_id : false;
    }

    function is_pretty_id_enabled() {
		return apply_filters( 'gppcmt_enable_pretty_id', false );
    }

    function get_entry_by_pretty_id( $id ) {
		global $wpdb;

		if( version_compare( GFForms::$version, '2.3-beta-1', '>=' ) ) {
			$entry_id = $wpdb->get_var( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}gf_entry_meta WHERE meta_value = %s", $id ) );
		} else {
			$entry_id = $wpdb->get_var( $wpdb->prepare( "SELECT lead_id FROM {$wpdb->prefix}rg_lead_meta WHERE meta_value = %s", $id ) );
		}

		if( ! $entry_id ) {
			return false;
		}

		return GFAPI::get_entry( $entry_id );
    }

    function maybe_decrypt_entry_id( $entry_id ) {

        // if encryption is enabled, 'eid' parameter MUST be encrypted
        $do_encrypt = false; //Change this later to reflect new way of passing args

        if( ! $entry_id ) {
            return null;
        } else if( ! $do_encrypt && is_numeric( $entry_id ) && intval( $entry_id ) > 0 ) {
            return $entry_id;
        } else {
            // gEYs6Cqzh1akKc7Y4RGkV8HtcJqQZRmNH+ONxuFEvXM
            // 0FSCGpzzmt+4Y05fFsJ4ipRZfqD/zdi2ecEeMMRKCjc=
            $decrypted_entry_id = is_callable( array( 'GFCommon', 'decrypt' ) ) ? GFCommon::decrypt( $entry_id ) : $entry_id;
	        if( ! is_numeric( $decrypted_entry_id ) && $this->is_pretty_id_enabled() ) {
		        return $entry_id;
	        }
            return intval( $decrypted_entry_id );
        }

    }

    function add_confirmation_ui_setting( $ui_settings, $confirmation, $form ) {

	    $subsetting_open  = '<td colspan="2" class="gf_sub_settings_cell"><div class="gf_animate_sub_settings"><table style="width:100%"><tr>';
        $subsetting_close = '</tr></table></div></td>';

        $confirmation_type = rgar( $confirmation, 'type' ) ? rgar( $confirmation, 'type' ) : 'message';
        $is_valid          = ! empty( GFCommon::$errors );
	    $class             = ! $is_valid && $confirmation_type == 'page' && ! rgar( $confirmation, 'pageId' ) ? 'gfield_error' : '';

	    $edit_url = add_query_arg( array(
		    'post'    => rgar( $confirmation, 'pageId' ),
		    'action'  => 'edit',
		    'form_id' => $form['id'],
	    ), admin_url( 'post.php' ) );

	    ob_start(); ?>

	    <style type="text/css">
		    #gppcmt_edit_link:before {
			    content: '|';
			    display: inline-block;
			    padding: 0 5px 0 2px;
			    color: #ddd;
		    }
	    </style>

        <tr class="form_confirmation_page_container" <?php echo $confirmation_type != 'page' ? 'style="display:none;"' : '' ?> class="<?php echo $class; ?>">
            <?php echo $subsetting_open; ?>
            <th><?php _e( 'Post Content Merge Tags', 'gp-post-content-merge-tags' ); ?> <?php gform_tooltip( 'gppcmtEnable' ) ?></th>
            <td>
                <input type="checkbox" id="gppcmt_enable" name="gppcmt_enable" value="1" <?php echo empty( $confirmation['gppcmtEnable'] ) ? '' : "checked='checked'" ?> />
                <label for="gppcmt_enable"><?php _e( 'Enable Merge Tags in Page Content', 'gp-post-content-merge-tags' ) ?></label>
	            <a href="<?php echo esc_html( $edit_url ) ?>" id="gppcmt_edit_link" target="_blank"><?php _e( 'Edit Page Content', 'gp-post-content-merge-tags' ) ?></a>
            </td>
            <?php echo $subsetting_close; ?>
        </tr>

	    <script type="text/javascript">
		    ( function( $ ) {

			    var $confirmationPage = $( '#form_confirmation_page' ),
				    $enable           = $( '#gppcmt_enable' ),
				    $editLink         = $( '#gppcmt_edit_link' );

			    $confirmationPage.change( function() {
				    toggleEditLink();
			    } );

			    $enable.change( function() {
				    toggleEditLink();
			    } );

			    function toggleEditLink() {
				    if( ! $confirmationPage.val() || ! $enable.is( ':checked' ) ) {
					    $editLink.hide();
				    } else {
					    $editLink.show();
					    $editLink.attr( 'href', 'post.php?post={0}&action=edit&form_id={1}'.format( $confirmationPage.val(), form.id ) );
				    }
			    }

		    } )( jQuery );
	    </script>

        <?php $ui_settings['form_post_content_merge'] = ob_get_contents();
        ob_end_clean(); 
    

        return $ui_settings;
    }
    
    function save_confirmation_ui_setting( $confirmation, $form ) {
        $confirmation['gppcmtEnable'] = (bool) rgpost( 'gppcmt_enable' );
        return $confirmation;
    }
    
    function add_confirmation_url_entry_detail( $form_id, $entry ) {

	    $gppcmt_url = gform_get_meta( $entry['id'], 'gppcmt_url' );

		if ( $gppcmt_url ) {

	        printf(
		        '<div>%1$s: <a href="%2$s" alt="%3$s" title="%3$s">%4$s</a></div>',
		        esc_html__( 'Confirmation URL', 'gp-post-content-merge-tags' ),
		        $gppcmt_url,
		        esc_attr__( 'View Confirmation Page', 'gp-post-content-merge-tags' ),
		        esc_attr__( 'View Page', 'gp-post-content-merge-tags' )
	        );

		}

    }

    function register_tinymce_button( $plugin_array ) {

	    if( $this->should_load_scripts() ) {
		    $plugin_array['gppcmt'] = $this->get_base_url() . '/js/gp-post-content-merge-tags-tinymce.js';
	    }

        return $plugin_array;
    }

    function enqueue_tinymce_button( $buttons ) {

	    if( $this->should_load_scripts() && ! in_array( 'gppcmt', $buttons ) ) {
		    array_push( $buttons, 'gppcmt' );
	    }

        return $buttons;
    }

    public function add_quicktag_button( $init ) {
	    $init['buttons'] .= ',gppcmt';
	    return $init;
    }
    
    public function documentation() {
        return array(
            'type'  => 'url',
            'value' => 'http://gravitywiz.com/gp-post-content-merge-tags/'
        );
    }

}

function gp_post_content_merge_tags() {
    return GP_Post_Content_Merge_Tags::get_instance( null );
}