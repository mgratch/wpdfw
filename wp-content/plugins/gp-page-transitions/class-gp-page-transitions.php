<?php

class GP_Page_Transitions extends GWPerk {

	public $version = GP_PAGE_TRANSITIONS_VERSION;
	
	protected $min_gravity_perks_version = '1.2.13';
	protected $min_gravity_forms_version = '1.0'; // @todo '2.0'

	private static $instance = null;

	public static function get_instance( $perk_file ) {
		if( null == self::$instance ) {
			self::$instance = new self( $perk_file );
		}
		return self::$instance;
	}

	public function init() {

		if( is_admin() ) {

			$this->add_tooltip( $this->key( 'enable_page_transitions' ), sprintf( '<h6>%s</h6> %s', __( 'Page Transitions', 'gp-page-transitions' ), __( 'Enable animated transitions when navigating from page to page.', 'gp-page-transitions' ) ) );
			$this->add_tooltip( $this->key( 'enable_auto_progress' ),    sprintf( '<h6>%s</h6> %s', __( 'Auto-progression', 'gp-page-transitions' ), __( 'Automatically progress to the next page when the last field of the current page has been completed. Some field types do not support auto-progression.', 'gp-page-transitions' ) ) );
			$this->add_tooltip( $this->key( 'enable_soft_validation' ),  sprintf( '<h6>%s</h6> %s', __( 'Soft Validation', 'gp-page-transitions' ),  __( 'Provides the smoothest experience. Pages are not submitted as you progress through the form. Required fields are validated on the frontend to catch obvious mistakes. Full validation for all pages is processed on the final page submission.', 'gp-page-transitions' ) ) );
			$this->add_tooltip( $this->key( 'transition_style' ),        sprintf( '<h6>%s</h6> %s', __( 'Transition Style', 'gp-page-transitions' ), __( 'Select the desired transition style. <b>Slide</b> will scroll the form pages horizontally. <b>Fade</b> will fade the current page out before fading the next page in.', 'gp-page-transitions' ) ) );
			$this->add_tooltip( $this->key( 'hide_buttons' ),            sprintf( '<h6>%s</h6> %s', __( 'Hide Next/Prev Buttons', 'gp-page-transitions' ), __( 'Check the corresponding checkbox to hide the next and previous buttons when auto-progression is enabled.', 'gp-page-transitions' ) ) );

			add_action( 'gform_editor_js', array( $this, 'form_editor_settings' ) );

		}

		add_action( 'gform_enqueue_scripts',       array( $this, 'enqueue_form_scripts' ) );
		add_action( 'gform_form_args',             array( $this, 'force_ajax_mode' ) );
		add_filter( 'gform_pre_render',            array( $this, 'pre_render' ), 11 ); // allows form to be more easily modified by users before init script is added
		add_filter( 'gform_validation_message',    array( $this, 'add_submission_result_script_block' ), 99, 2 );
		add_filter( 'gform_confirmation_anchor',   array( $this, 'suppress_form_anchor' ), 10, 2 );
		add_filter( 'gform_progress_bar',          array( $this, 'modify_progress_bar' ), 10, 3 );
		add_filter( 'gform_input_mask_script',     array( $this, 'enable_auto_progress_for_input_masks' ), 10, 4 );
		add_action( 'gform_register_init_scripts', array( $this, 'enable_auto_progress_for_phone_input_mask' ) );

	}

	public function pre_render( $form ) {

		if( ! $this->is_any_feature_enabled( $form ) ) {
			return $form;
		}

		if( $this->is_auto_progress_enabled( $form ) ) {
			$this->add_auto_progress_field_class( $form );
			$form = $this->hide_next_prev_buttons( $form );
		}

		$transition_style = rgar( $form, $this->key( 'transitionStyle' ) );

		/**
		 * Filter all of the properties that will be used to initialize the GP Page Transitions JS functionality.
		 *
		 * @since 1.0
		 *
		 * @param array $args {
		 *     An array of properties that will be used to initialize the GP Page Transitions JS functionality.
		 *
         *     @type int  $formId                Current form ID.
		 *     @type bool $enablePageTransitions Flag indicating whether page transitions are enabled.
		 *     @type bool $enableAutoProgress    Flag indicating whether auto-progression is enabled.
		 *     @type bool $hideNextButton        Flag indicating whether next button should be hidden.
		 *     @type bool $hidePrevButton        Flag indicating whether previous button should be hidden.
		 *     @type bool $enableSoftValidation  Flag indicating whether soft validation is enabled.
		 *     @type array $validationSelectors {
		 *         An array of validation selector objects which control which inputs are validated and how.
		 *
		 *         @type int    $id                Field ID of the selector.
		 *         @type array  $selectors         An array of selector strings (i.e. '#input_1_2').
		 *         @type string $relation          Specifies how the validation should be applied. Should 'all' selectors have a value or does validation pass if 'any' selector has a value?
		 *         @type string $validationMessage Message to be displayed if field fails validation.
		 *     }
		 *     @type string $validationClass            Class(es) to be applied to the field container when a field fails validation.
		 *     @type string $validationMessageContainer Markup that will wrap the validation message. Must include "{0}" wherever the message should be included in the markup.
		 *     @type array  $submission {
		 *         The result of the submission. Used to reset the state of the GPPageTransitions JS object after a submission.
		 *
		 *         @type bool $hasError   Flag indicating whether the submission has an error.
		 *         @type int  $sourcePage Page number from which the form was submitted.
		 *         @type int  $errorPage  Page number on which the first field with an error resides.
		 *     }
		 *     @type array pagination {
		 *         An array of properties specific to how the form's pagination is configured.
		 *
		 *         @type string $type               The pagination type of the current form.
		 *         @type bool   $startAtZero        Flag indicating whether or not the progress bar should start at zero and only show 100% on the confirmation page.
		 *         @type int    $pageCount          The total number of pages on the current form.
		 *         @type array  $progressIndicators An array of the progress indicator markup (progress bar or steps) for each page of the form. This is only used if 'gppt_is_custom_pagination' filter is configured to return true.
		 *         @type array  $pages              An array of page names specified for the current form.
		 *         @type bool   $isCustom           Flag indicating whether or not the progress indicators are custom or standard.
		 *         @type array  $labels             An array of labels used to recreate Gravity Forms' standard page verbiage.
		 *     }
		 *     @type array $transitionSettings {
		 *         An array of properties specific to the transition script. See Cycle options for a full list of all options that can be specified here.
		 *             http://jquery.malsup.com/cycle/options.html
		 *
		 *         @type string $fx              The transition effect used to transitions pages. Defaults to 'scrollHorz'.
		 *         @type string $easing          Specifies how the transition effect should be applied. Defaults to 'easeInOutBack' if transition is set to "Slide" or null for "Fade".
		 *         @type bool   $sync            Flag indicating whether the transition of the next page should happen at the same time as the current page is transition out. Defaults to false for "Fade", true for "Slide".
		 *         @type int    $speed           Specifies the speed at which the transition should be applied.
		 *         @type int    $timeout         Set to 0 to disable automatic transitions. Note: this does not relate to the Auto-progression feature.
		 *         @type bool   $containerResize Flag indicating whether or not to resize the container to fit the largest page when the script is initialized. Defaults to false.
		 *         @type bool   $slideResize     Flag indicating whether or not to force pages to specific size before each transition.
		 *     }
		 * }
		 * @param array $form Current form object.
		 *
		 * @see http://gravitywiz.com/documentation/gppt_script_args
		 */
		$args = apply_filters( 'gppt_script_args', array(
			'formId'                 => $form['id'],
			'enablePageTransitions'  => $this->is_page_transitions_enabled( $form ),
			'enableAutoProgress'     => $this->is_auto_progress_enabled( $form ),
			'hideNextButton'         => rgar( $form, $this->key( 'hideNextButton' ) ),
			'hidePrevButton'         => rgar( $form, $this->key( 'hidePrevButton' ) ),
			'enableSoftValidation'   => $this->is_soft_validation_enabled( $form ),
			'validationSelectors'    => $this->get_validation_selectors( $form ),
			'validationClass'        => 'gfield_error gfield_contains_required',
			'validationMessageContainer' => "<div class='gfield_description validation_message'>{0}</div>",
			'submission'             => $this->get_submission_result( $form ),
			'pagination'             => $this->get_pagination_script_args( $form ),
			'transitionSettings'     => array(
				'fx'              => $transition_style ? $transition_style : 'scrollHorz',
				'easing'          => $transition_style == 'scrollHorz' ? 'easeInOutBack' : null,
				'sync'            => $transition_style == 'fade' ? 0 : 1,
				'speed'           => $transition_style == 'fade' ? 400 : 800,
				'timeout'         => 0,
				'containerResize' => 0,
				'slideResize'     => 0,
			),
		), $form );
		$args = apply_filters( 'gppt_scripts_args_' . $form['id'], $args, $form );

		$script = 'if( ! window["GPPageTransitions_' . $form['id'] . '"] ) { window["GPPageTransitions_' . $form['id'] . '"] = new GPPageTransitions( ' . json_encode( $args ) . ' ); } window["GPPageTransitions_' . $form['id'] . '"].init( currentPage );';
		GFFormDisplay::add_init_script( $form['id'], $this->key( 'init' ), GFFormDisplay::ON_PAGE_RENDER, $script );

		if( $this->is_page_transitions_enabled( $form ) ) {
			$form['cssClass'] = $this->add_css_class( $form['cssClass'], 'gppt-has-page-transitions' );
		}

		return $form;
	}

	public function get_pagination_script_args( $form ) {

		/**
		 * Filter the custom pagination flag to enable custom pagination support.
		 *
		 * @since 1.0
		 *
		 * @param bool  $is_custom Set to true to enable custom pagination support.
		 * @param array $form      Current form object.
		 *
		 * @see http://gravitywiz.com/documentation/gppt_is_custom_pagination/
		 */
		$is_custom = apply_filters( 'gppt_is_custom_pagination', false, $form );
		$is_custom = apply_filters( "gppt_is_custom_pagination_{$form['id']}", $is_custom, $form );

		$args = array(
			'type'               => rgars( $form, 'pagination/type' ),
			'startAtZero'        => apply_filters( 'gform_progressbar_start_at_zero', rgars( $form, 'pagination/display_progressbar_on_confirmation' ), $form ),
			'pageCount'          => $this->get_page_count( $form ),
			'progressIndicators' => $is_custom ? $this->get_all_progress_indicators( $form ) : array(),
			'pages'              => rgars( $form, 'pagination/pages' ),
			'isCustom'           => $is_custom,
			'labels'             => array(
				'step' => esc_html__( 'Step', 'gravityforms' ),
				'of'   => esc_html__( 'of', 'gravityforms' )
			)
		);

		return $args;
	}

	/**
	 * Append a script block containing the submission result to the form validation message.
	 *
	 * The form validation message will only be output if there is an error once the form has actually been submitted.
	 * Since we're forcing AJAX submissions for all page-transition-enabled, we can be confident that this is the only
	 * scenario in which we will need an updated JS submission object.
	 *
	 * @param string $markup Default GF validation markup.
	 * @param array  $form   Current form object.
	 *
	 * @return string
	 */
	public function add_submission_result_script_block( $markup, $form ) {

		if( ! $this->is_soft_validation_enabled( $form ) ) {
			return $markup;
		}

		$result = $this->get_submission_result( $form );
		if( $result['hasError'] ) {
			$script = sprintf( '<script type="text/javascript"> if( window["GPPageTransitions_%1$d"] ) { window["GPPageTransitions_%1$d"].submission = %2$s; } </script>', $form['id'], json_encode( $result ) );
			$markup .= $script;
		}

		return $markup;
	}

	public function get_validation_selectors( $form ) {

		$selectors = array();

		// selectors will be grouped by page; set empty array as base for each page index
		for( $i = 1; $i <= $this->get_page_count( $form ); $i++ ) {
			$selectors[ $i ] = array();
		}

		foreach( $form['fields'] as $field ) {

			if( ! $field->isRequired ) {
				continue;
			}

			// intentionally uses 'gravityforms' domain so translations are automatically picked up here as well
			$validation_message = empty( $field->errorMessage ) ? __( 'This field is required.', 'gravityforms' ) : $field->errorMessage;
			$default_selector   = sprintf( '#input_%d_%d', $form['id'], $field->id );

			$selector           = array(
				'id'                => $field->id,
				'selectors'         => array( $default_selector ),
				'relation'          => 'any',
				'validationMessage' => $validation_message,
			);

			switch( $field->get_input_type() ) {
				case 'checkbox':
				case 'radio':
					$selector['selectors'] = array( sprintf( '%s input[type="%s"]', $default_selector, $field->get_input_type() ) );
					break;
				case 'name':
					// GF only requires first and last name regardless of other enabled inputs (e.g. middle name)
					$selector['relation'] = 'all';
					$selector['selectors'] = array(
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 3 ), // first name
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 6 ), // last name
					);
					break;
				case 'address':
					$selector['relation'] = 'all';
					$selector['selectors'] = array(
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 1 ), // street address
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 3 ), // city
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 4 ), // state
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 5 ), // zip
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 6 ), // country
					);
					break;
				case 'date':
					if( in_array( $field->dateType , array( 'datefield', 'datedropdown' ) ) ) {
						$selector['relation'] = 'all';
						$selector['selectors'] = array(
							sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 1 ), // month
							sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 3 ), // day
							sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 4 ), // year
						);
					}
					break;
				case 'time':
					$selector['relation'] = 'all';
					$selector['selectors'] = array(
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 1 ), // hour
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 3 ), // minute
						sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 4 ), // am/pm
					);
					break;
				case 'fileupload':
					if( $field->multipleFiles ) {
						$selector['selectors'] = array(
							sprintf( '#field_%d_%d input[type="file"]', $form['id'], $field->id ),
						);
					}
					break;
				case 'email':
					if( $field->emailConfirmEnabled ) {
						$selector['relation'] = 'all';
						$selector['selectors'][] = sprintf( '#input_%d_%d_%d', $form['id'], $field->id, 2 ); // confirm email
					}
					break;
				case 'list':
					$selector['relation'] = 'all';
					$selector['selectors'] = array( sprintf( '#field_%1$d_%2$d [name="input_%2$d[]"]', $form['id'], $field->id ) );
					$selector['bypassCache'] = true;
					break;
				case 'singleproduct':
					$selector['selectors'] = array( sprintf( '#ginput_quantity_%d_%d', $form['id'], $field->id ) );
					break;
			}

			$selectors[ $field->pageNumber ][] = $selector;

		}

		return $selectors;
	}

	public function modify_progress_bar( $markup, $form, $confirmation ) {

		if( ! $this->is_soft_validation_enabled( $form ) ) {
			return $markup;
		}

		$result = $this->get_submission_result( $form );
		if( $result['hasError'] == false ) {
			return $markup;
		}

		$markup = GFFormDisplay::get_progress_bar( $form, $result['sourcePage'] );

		return $markup;
	}

	public function get_submission_result( $form ) {

		$submission = rgar( GFFormDisplay::$submission, $form['id'], array( 'is_valid' => true ) );
		$result = array( 'hasError' => false );

		if( ! $submission['is_valid'] ) {

			$result['hasError']  = true;
			$result['sourcePage'] = intval( $submission['source_page_number'] );
			$result['errorPage']  = intval( GFFormDisplay::get_first_page_with_error( $form ) );

		}

		return $result;
	}

	public function add_auto_progress_field_class( $form ) {
		foreach( $form['fields'] as &$field ) {
			if( $this->supports_auto_progress( $field ) ) {
				$field['cssClass'] = $this->add_css_class( 'gppt-auto-progress-field', rgar( $field, 'cssClass' ) );
			}
		}
		return $form;
	}
	
	public function suppress_form_anchor( $anchor, $form ) {
		if( $this->is_page_transitions_enabled( $form ) ) {
			$anchor = false;
		}
		return $anchor;
	}

	public function enqueue_form_scripts( $form ) {

		if( ! $this->is_any_feature_enabled( $form ) ) {
			return;
		}

		// scripts
		wp_enqueue_script( 'easing', $this->get_base_url() . '/js/jquery.easing.1.3.js', array('jquery') );
		wp_enqueue_script( 'cycle', $this->get_base_url() . '/js/jquery.cycle.all.js', array('jquery', 'easing' ) );
		wp_enqueue_script( 'gp-page-transitions', $this->get_base_url() . '/js/gp-page-transitions.js', array('jquery', 'cycle' ), $this->version );

		// styles
		wp_enqueue_style( 'gp-page-transitions', $this->get_base_url() . '/css/gp-page-transitions.css', array(), $this->version );

	}

	public function force_ajax_mode( $form_args ) {

		$form = GFAPI::get_form( $form_args['form_id'] );

		if( $this->is_page_transitions_enabled( $form ) ) {
			$form_args['ajax'] = true;
		}

		return $form_args;
	}

	/**
	 * There is no built in way to add pagination options in GF. We must add the setting via JS.
	 */
	public function form_editor_settings( ) {

		ob_start(); ?>

		<style type="text/css">
			#gws_pagination_tab > ul > li {
				margin: 10px 0;
			}
			#gws_pagination_tab label {
				margin-right: 10px;
			}
			#gws_pagination_tab input[type="checkbox"] + label {
				vertical-align: top;
			}
			.gppl-child-settings {
				display: none;
				border-left: 4px solid #eee;
				padding: 20px 14px;
				margin-left: 6px;
			}
			#gppt-transition-style {
				min-width: 100px;
			}
			#gppt-enable-auto-progress-child-settings label {
				vertical-align: top;
			}
			.gppt-error {
				background-color: #FFEBE8;
				border-color: #CC0000;
				border-width: 1px;
				border-style: solid;
				padding: 10px;
				margin: 10px 15px 10px 0;
				-moz-border-radius: 3px;
				-khtml-border-radius: 3px;
				-webkit-border-radius: 3px;
				border-radius: 3px;
				max-width: 480px !important;
			}
			.gppt-error p {
				margin: 0.5em 0;
				line-height: 1;
				padding: 2px;
			}
			#gppt-hide-next-button-warning {
				margin-top: 10px;
			}
		</style>

		<div id="gws_pagination_tab">
			<ul class="gforms_form_settings">

				<li id="gppt-page-transitions" class="gp-field-setting">

					<input type="checkbox" id="gppt-enable-page-transitions" value="1" onclick="GPPageTransitions.toggleSettings( '<?php echo $this->key( 'enablePageTransitions' ); ?>', this.checked, jQuery( '#gppt-enable-page-transitions-child-settings' ) );" />
					<label for="gppt-enable-page-transitions">
						<?php _e( 'Enable Page Transitions', 'gp-page-transitions' ); ?>
						<?php gform_tooltip( $this->key( 'enable_page_transitions' ) ) ?>
					</label>

					<div id="gppt-enable-page-transitions-child-settings" class="gppl-child-settings">

						<div class="gp-row">
							<label for="gppt-transition-style">
								<?php _e( 'Transition Style', 'gp-page-transitions' ); ?>
								<?php gform_tooltip( $this->key( 'transition_style' ) ) ?>
							</label>
							<select type="checkbox" id="gppt-transition-style" onchange="gperk.setFormProperty( '<?php echo $this->key( 'transitionStyle' ); ?>', this.value );">
								<?php foreach( $this->get_transition_styles() as $value => $label ): ?>
									<option value="<?php echo $value; ?>"><?php echo $label; ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="gp-row">
							<input type="checkbox" id="gppt-enable-soft-validation" value="1" onclick="gperk.setFormProperty( '<?php echo $this->key( 'enableSoftValidation' ); ?>', this.checked );" />
							<label for="gppt-enable-soft-validation">
								<?php _e( 'Enable Soft Validation', 'gp-page-transitions' ); ?>
								<?php gform_tooltip( $this->key( 'enable_soft_validation' ) ) ?>
							</label>
						</div>

					</div>

				</li>

				<li>

					<input type="checkbox" id="gppt-enable-auto-progress" value="1" onclick="GPPageTransitions.toggleSettings( '<?php echo $this->key( 'enableAutoProgress' ); ?>', this.checked, jQuery( '#gppt-enable-auto-progress-child-settings' ) );" />
					<label for="gppt-enable-auto-progress">
						<?php _e( 'Enable Auto-progression', 'gp-page-transitions' ); ?>
						<?php gform_tooltip( $this->key( 'enable_auto_progress' ) ) ?>
					</label>

					<div id="gppt-enable-auto-progress-child-settings" class="gppl-child-settings">

						<label for="gppt-hide-next-button">
							<?php _e( 'Hide:', 'gp-page-transitions' ); ?>
							<?php gform_tooltip( $this->key( 'hide_buttons' ) ) ?>
						</label>
						<input type="checkbox" id="gppt-hide-next-button" value="1" onchange="GPPageTransitions.toggleHideNextButton( this.checked );" />
						<label for="gppt-hide-next-button">
							<?php _e( 'Next Button', 'gp-page-transitions' ); ?>
						</label>
						<input type="checkbox" id="gppt-hide-prev-button" value="1" onclick="gperk.setFormProperty( '<?php echo $this->key( 'hidePrevButton' ); ?>', this.checked );" />
						<label for="gppt-hide-prev-button">
							<?php _e( 'Previous Button', 'gp-page-transitions' ); ?>
						</label>

						<div id="gppt-hide-next-button-warning" class="gp-notice" style="display:none;">
							<i class="fa fa-warning"></i>
							<?php _e( 'You have opted to hide the Next button. Make sure that each form page ends with a field that supports auto-progression to ensure the form can be completed.', 'gp-page-transitions' ); ?>
						</div>

					</div>

				</li>

			</ul>
		</div>

		<?php $options_html = ob_get_clean(); ?>

		<script type="text/javascript">

			jQuery( document ).ready( function( $ ) {

				window.GPPageTransitions = function() {

					var self = this;

					self.$pageSettingsElem = $( '#pagination_settings' );
					self.optionsHtml       = '<?php echo str_replace( array( "\n", "\r" ), '', str_replace( "'", "\'", $options_html ) ); ?>';
					self.toggleOnInit      = [ 'gppt-enable-page-transitions', 'gppt-enable-auto-progress', 'gppt-enable-soft-validation' ];
					self.options           = {
						'gppt-enable-page-transitions': key( 'enablePageTransitions' ),
						'gppt-enable-auto-progress':    key( 'enableAutoProgress' ),
						'gppt-enable-soft-validation':  key( 'enableSoftValidation' ),
						'gppt-transition-style':        key( 'transitionStyle' ),
						'gppt-hide-next-button':        key( 'hideNextButton' ),
						'gppt-hide-prev-button':        key( 'hidePrevButton' )
					};

					GPPageTransitions.toggleSettings = function( prop, isChecked, $childSettings ) {

						if( prop == key( 'enablePageTransitions' ) && isChecked && ! form[ prop ] ) {
							$( '#gppt-enable-soft-validation' ).prop( 'checked', true );
							form[ key( 'enableSoftValidation' ) ] = true;
						}

						form[ prop ] = isChecked;

						if( isChecked ) {
							$childSettings.slideDown();
						} else {
							$childSettings.slideUp( function() {
								// reset child settings
								$childSettings.find( 'select, input' ).each( function() {
									var $input = $( this ),
										value  = null;
									if( $input.is( ':checkbox' ) ) {
										$input.prop( 'checked', false );
									} else if( $input.is( 'select' ) ) {
										$input.find( 'option:first-child' ).prop( 'selected', true );
										value = $input.val();
									}
									form[ self.options[ $input.attr( 'id' ) ] ] = value;
								} );
							} );

						}

					};

					GPPageTransitions.toggleHideNextButton = function ( isChecked ) {

						form[ key( 'hideNextButton' ) ] = isChecked;

						var $hideNextButtonWarning = $( '#gppt-hide-next-button-warning' );

						if( isChecked ) {
							$hideNextButtonWarning.slideDown();
						} else {
							$hideNextButtonWarning.slideUp()
						}

					};

					self.initUI = function() {

						self.$pageSettingsElem.append( self.optionsHtml );

						gperk.addTab( self.$pageSettingsElem, '#gws_pagination_tab', '<?php _e( 'Perks', 'gp-page-transitions' ); ?>' );

						gform_initialize_tooltips();

						for( var id in self.options ) {
							if( self.options.hasOwnProperty( id ) && form[ self.options[ id ] ] ) {

								var $input = $( '#' + id );

								if( $input.is( 'input[type="checkbox"]' ) ) {
									$input.prop( 'checked', form[ self.options[ id ] ] == true ).change();
								} else {
									$input.val( form[ self.options[ id ] ] )
								}

								if( $.inArray( id, self.toggleOnInit ) != -1 ) {
									GPPageTransitions.toggleSettings( self.options[ id ], $input.is( ':checked' ), $( '#' + id + '-child-settings' ) );
								}

							}
						}

					};

					self.initUI();

				};

				function key( key ) {
					return '<?php echo $this->key( '' ); ?>' + key;
				}

				var gpptAdmin = new GPPageTransitions();

			} );

		</script>

		<?php
	}

	public function hide_next_prev_buttons( $form ) {

		$hide_next_button      = rgar( $form, $this->key( 'hideNextButton' ) );
		$hide_prev_button      = rgar( $form, $this->key( 'hidePrevButton' ) );
		$save_continue_enabled = rgars( $form, 'save/enabled' ) == true;

		/**
		 * Filter the visibility of the form footer.
		 *
		 * If "Hide Next Button" and "Hide Previous Button" are enabled and GF's Save & Continue feature is disabled, the
		 * footer will automatically be hidden.
		 *
		 * @since 1.0
		 *
		 * @param bool  $hide_footer Set to false to disable hiding the footer. Defaults to true.
		 * @param array $form        Current form object.
		 *
		 * @see http://gravitywiz.com/documentation/gppt_hide_footer/
		 */
		$hide_footer = apply_filters( 'gppt_hide_footer', $hide_next_button && $hide_prev_button && ! $save_continue_enabled, $form );
		$hide_footer = apply_filters( "gppt_hide_footer_{$form['id']}", $hide_footer, $form );

		if( $hide_footer ) {
			$form['cssClass'] = $this->add_css_class( rgar( $form, 'cssClass' ), 'gppt-no-buttons' );
		}

		add_filter( 'gform_next_button',     array( $this, 'hide_next_button' ), 10, 2 );
		add_filter( 'gform_previous_button', array( $this, 'hide_prev_button' ), 10, 2 );

		return $form;
	}

	public function hide_next_button( $button, $form ) {
		if( rgar( $form, $this->key( 'hideNextButton' ) ) ) {
			$button = $this->hide_button( $button );
		}
		return $button;
	}

	public function hide_prev_button( $button, $form ) {
		if( rgar( $form, $this->key( 'hidePrevButton' ) ) ) {
			$button = $this->hide_button( $button );
		}
		return $button;
	}

	public function hide_button( $button ) {
		return sprintf( '<div class="gppt-hide">%s</div>', $button );
	}

	/**
	 * Enable auto-progress for input masks by modifing the initialization code for the input mask to tirgger a custom
	 * event when the input mask is complete.
	 *
	 * @param $script
	 * @param $form_id
	 * @param $field_id
	 * @param $mask
	 *
	 * @return mixed
	 */
	public function enable_auto_progress_for_input_masks( $script, $form_id, $field_id, $mask ) {

		$form = GFAPI::get_form( $form_id );
		$field = GFFormsModel::get_field( $form, $field_id );

		if( $this->is_auto_progress_enabled( $form ) && $this->supports_auto_progress( $field ) ) {
			$search = "'{$mask}'";
			$replace = sprintf( '%s, %s', $search, "{ completed: function() { jQuery( this ).trigger( 'gpptAutoProgress' ); } }" );
			$script = str_replace( $search, $replace, $script );
		}

		return $script;
	}

	/**
	 * Register init script to allow Phone input mask to auto-progress form.
	 *
	 * Phone field registers it's own input mask. We use the same logic to register a new version which triggers the
	 * gpptAutoProgress event when the input mask is complete.
	 *
	 * @param $form
	 */
	public function enable_auto_progress_for_phone_input_mask( $form ) {

		if( ! $this->is_auto_progress_enabled( $form ) ) {
			return;
		}

		foreach( $form['fields'] as $field ) {

			if( $field->get_input_type() == 'phone' && $this->supports_auto_progress( $field ) ) {

				$phone_format = $field->get_phone_format();
				$mask         = rgar( $phone_format, 'mask' );

				$search  = "'{$mask}'";
				$replace = sprintf( '%s, %s', $search, "{ completed: function() { jQuery( this ).trigger( 'gpptAutoProgress' ); } }" );
				$script  = str_replace( $search, $replace, $field->get_form_inline_script_on_page_render( $form ) );

				GFFormDisplay::add_init_script( $form['id'], $field->type . '_' . $field->id . ' _alt', GFFormDisplay::ON_PAGE_RENDER, $script );

			}
		}

	}



	// HELPERS //

	public function get_transition_styles() {
		/**
		 * Filter available transition styles (will appear in the Transition Style setting).
		 *
		 * @since 1.0
		 *
		 * @param array $styles An array of transion styles. Array key is the Cycle.js name for the transition effect. Value is the label for the transition effect.
		 *
		 * @see http://gravitywiz.com/documentation/gppt_transition_styles/
		 */
		return apply_filters( 'gppt_transition_styles', array(
			'scrollHorz' => __( 'Slide', 'gp-page-transitions' ),
			'fade'       => __( 'Fade', 'gp-page-transitions' )
		) );
	}

	public function get_all_progress_indicators( $form ) {

		$page_count = $this->get_page_count( $form );
		$type = $form['pagination']['type'];
		$indicators = array();

		if( ! in_array( $type, array( 'steps', 'percentage' ) ) ) {
			return $indicators;
		}

		for( $i = 1; $i <= $page_count; $i++ ) {
			if( $type == 'steps' ) {
				$indicators[] = GFFormDisplay::get_progress_steps( $form, $i );
			} else {
				$indicators[] = GFFormDisplay::get_progress_bar( $form, $i );
			}
		}

		return $indicators;
	}

	public function get_page_count( $form ) {
		return count( $form['pagination']['pages'] );
	}

	public function is_any_feature_enabled( $form ) {
		return $this->is_page_transitions_enabled( $form ) || $this->is_soft_validation_enabled( $form ) || $this->is_auto_progress_enabled( $form );
	}

	public function is_page_transitions_enabled( $form ) {
		return rgar( $form, $this->key( 'enablePageTransitions' ) ) == true;
	}

	/**
	 * Soft Validation refers to the process of suppressing all form errors (in fact, each page will not be submitted at all),
	 * until the final page is submitted. Then the user will be directed to the first page with errors.
	 *
	 * @param $form
	 *
	 * @return bool
	 */
	public function is_soft_validation_enabled( $form ) {
		return $this->is_page_transitions_enabled( $form ) && rgar( $form, $this->key( 'enableSoftValidation' ) ) == true;
	}

	public function is_auto_progress_enabled( $form ) {
		return rgar( $form, $this->key( 'enableAutoProgress' ) ) == true;
	}

	public function supports_auto_progress( $field ) {

		$conditions = $this->get_auto_progress_support_conditions();

		foreach( $conditions as $condition ) {

			if( isset( $condition['type'] ) && $field->get_input_type() != $condition['type'] ) {
				continue;
			}

			if( isset( $condition['callback'] ) && is_callable( $condition['callback'] ) && ! call_user_func( $condition['callback'], $field ) ) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function get_auto_progress_support_conditions() {

		/**
		 * Filter the conditions that dictate whether or not a field supports auto-progression.
		 *
		 * Each condition supports a 'type' and 'callback' property. If the field type supports auto-progression
		 * unconditionally, you only need to specific the 'type' property. If the field type only supports auto-progression
		 * if certain requirements are met, specify a callback function which will determine if the field passed to it
		 * meets the requirements for the condition's field type.
		 *
		 * @since 1.0
		 *
		 * @param array $conditions An array of auto-progression condition objects.
		 *
		 * @see http://gravitywiz.com/documentation/gppt_auto_progress_support_conditions/
		 */
		$conditions = apply_filters( 'gppt_auto_progress_support_conditions', array(
			array(
				'type' => 'text',
				'callback' => array( $this, 'auto_progress_condition_text' ),
			),
			array(
				'type' => 'radio',
			),
			array(
				'type' => 'select',
			),
			array(
				'type' => 'date',
				'callback' => array( $this, 'auto_progress_condition_date' ),
			),
			array(
				'type' => 'fileupload',
				'callback' => array( $this, 'auto_progress_condition_fileupload' ),
			),
			array(
				'type' => 'phone',
				'callback' => array( $this, 'auto_progress_condition_phone' )
			),
			array(
				'type' => 'likert',
			),
			array(
				'type' => 'rating'
			)
		) );

		return $conditions;
	}

	public function auto_progress_condition_text( $field ) {
		return $field['inputMask'] == true;
	}

	public function auto_progress_condition_date( $field ) {
		return in_array( $field['dateType'], array( 'datepicker', 'datedropdown' ) );
	}

	public function auto_progress_condition_fileupload( $field ) {
		return ! $field['multipleFiles'];
	}

	public function auto_progress_condition_phone( $field ) {
		return $field->phoneFormat != 'international';
	}

}