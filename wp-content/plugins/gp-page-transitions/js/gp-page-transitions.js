/**
 * GP Page Transitions - Frontend Scripts
 */

( function( $ ) {

    window.GPPageTransitions = function( args ) {

        var self = this;

        // default props: formId, validationRules, progressIndicators
        for( prop in args ) {
            if( args.hasOwnProperty( prop ) ) {
                self[ prop ] = args[ prop ];
            }
        }

        self.$slider     = null;
        self.cycling     = false;
        self.rules       = {};
        self.initialized = false;
        self.sourcePage  = 0;
        self.inputs      = {};
        self.functions   = {};

        self.init = function( currentPage ) {

            self.initialized = false;

            // confirmation page will have no current page specified; no need to init on confirmation
            if( ! currentPage ) {
                return;
            }

            if( self.submission.hasError ) {
                self.sourcePage = self.submission.sourcePage;
            }

            self.currentPage  = parseInt( currentPage );
            self.$formElem    = $( '#gform_' + self.formId ); // @todo: might need to change for WC GF Product Add-ons
            self.$currentPage = $( '#gform_page_' + self.formId + '_' + self.currentPage );

            self.bindEvents();

            self.sourcePage = self.currentPage;
            self.initialized = true;

        };

        self.initPageTransitions = function() {

            var $formBody     = self.$formElem.find( '.gform_body' ),
                startingIndex = Math.max( 0, self.sourcePage - 1 ),//Math.max( 0, isForward ? self.currentPage - 2 : self.currentPage ),
                currentIndex  = self.currentPage - 1;

            $formBody.css( { width: $formBody.width() } );
            $formBody.find( '.gform_page' ).css( { width: $formBody.width() } );

            self.transitionSettings.before = self.resizeFormBodyTransition;
            self.transitionSettings.startingSlide = startingIndex;
            self.transitionSettings.after = function() { self.cycling = false; };

            self.$slider = self.$formElem.find( '.gform_body' ).cycle( self.transitionSettings );
            self.$formElem.addClass( 'gppt-transition-style-' + self.transitionSettings.fx );

            self.$slider.cycle( currentIndex );

            if( self.submission.hasError && startingIndex != currentIndex ) {
                self.updateProgressIndicator( self.currentPage );
            }

            // resize form body anytime form DOM changes (e.g. adding a row via the list field)
            self.observer = new MutationObserver( function( mutations ) {

                // find the first non-text node (removed or added) and trigger our resize
                // otherwise resize is fired when we update any text which can be problematic
                mutationLoop:
                for( var i = 0; i < mutations.length; i++ ) {
                    var nodes = mutations[i].addedNodes.length > 0 ? mutations[i].addedNodes : mutations[i].removedNodes;
                    for( var j = 0; j < nodes.length; j++ ) {
                        if( nodes[j].nodeType != 3 ) {
                            self.resizeFormBody( self.$currentPage );
                            break mutationLoop;
                        }
                    }
                }

            } );
            self.observer.observe( self.$formElem[0], { childList: true, subtree: true } );

        };

        self.bindEvents = function() {

            if( self.enablePageTransitions ) {
                if( ! self.hasConditionalLogic ) {
	                self.initPageTransitions();
                } else {
                	$( document ).bind( 'gform_post_conditional_logic', function() {
                		if( ! self.$slider && self.$formElem.is( ':visible' ) ) {
			                self.initPageTransitions();
		                }
		                self.resizeFormBody( self.$currentPage );
	                } );
                }
            }

            if( self.enablePageTransitions && self.enableSoftValidation ) {

                var $previousButtons = self.$formElem.find( '.gform_previous_button' ),
                    $nextButtons     = self.$formElem.find( '.gform_next_button' ),
                    $submitButton    = $( '#gform_submit_button_{0}'.format( self.formId ) );

                $submitButton.on( 'click', function( event ) {

                    if( ! self.validate( self.$currentPage ) ) {
                        event.preventDefault();
                        window[ 'gf_submitting_{0}'.format( self.formId ) ] = false;
                        return;
                    }

                    $( '#gform_target_page_number_{0}'.format( self.formId ) ).val( 0 );
                    $( '#gform_source_page_number_{0}'.format( self.formId ) ).val( self.pagination.pageCount );

                } );

                $previousButtons.each( function() {
                    $( this )
                        .attr( 'onclick', '' )
                        .attr( 'onkeypress', '' )
                        .on( 'click', function( event ) {

                            // previous button on last page is a submit button (yeah, no idea)
                            event.preventDefault();

                            if( self.cycling ) {
                                return;
                            }

                            self.$formElem.trigger( 'prevPage.gppt', [ self.currentPage - 1, self.currentPage, self.formId ] );

                        } );
                } );

                $nextButtons.each( function() {
                    $( this )
                        .attr( 'onclick', '' )
                        .attr( 'onkeypress', '' )
                        .on( 'click', function ( event ) {

                            if( self.cycling ) {
                                return;
                            }

                            if( self.validate() ) {
                                self.$formElem.trigger( 'nextPage.gppt', [ self.currentPage + 1, self.currentPage, self.formId ] );
                            }

                        } );
                } );

                self.$formElem.on( 'prevPage.gppt', function( event, newPage, oldPage, formId ) {

                    self.cycling = true;

                    self.currentPage  = newPage;
                    self.sourcePage   = oldPage;
                    self.$currentPage = $( '#gform_page_' + formId + '_' + self.currentPage );

                    self.$slider.cycle( 'prev' );
                    self.updateProgressIndicator( self.currentPage );
                    self.resizeFormBody( self.$currentPage );

                } );

                self.$formElem.on( 'nextPage.gppt', function( event, newPage, oldPage, formId ) {

                    self.cycling = true;

                    self.currentPage  = newPage;
                    self.sourcePage   = oldPage;
                    self.$currentPage = $( '#gform_page_' + formId + '_' + self.currentPage );

                    self.$slider.cycle( 'next' );
                    self.updateProgressIndicator( self.currentPage );
                    self.resizeFormBody( self.$currentPage );

                } );

            }

            if( self.enableAutoProgress ) {

                self.$formElem.find( '.gform_page' ).each( function() {

                    var $pageElem     = $( this ),
                        $field        = $pageElem.find( 'li.gfield.gppt-auto-progress-field:last-child' ),
                        $inputs       = false,
                        events        = [ 'gpptAutoProgress' ];

                    if( $field.find( 'input[value="gf_other_choice"]' ) ) {
                        // any radio button except the "other" radio button
                        $inputs = $field.find( 'input[type="radio"][value!="gf_other_choice"]' );
                        events.push( 'change' );
                    } else if( $field.find( '.gsurvey-likert' ).length > 0 ) {
                        $inputs = $field.find( '.gsurvey-likert tbody tr:last-child input' );
                        events.push( 'change' );
                    } else if( $field.find( '.gsurvey-rating' ).length > 0 ) {
                        $inputs = $field.find( '.gsurvey-rating label' );
                        events.push( 'click' );
                    } else {
                        $inputs = $field.find( 'input, select' ).not( 'input[type="hidden"]' );
                        if( ! $inputs.is( ':radio' ) ) {
                            $inputs = $inputs.last();
                        }
                        // filter out text inputs; they are exclusively handled by input masks
                        if( ! $inputs.is( ':text' ) ) {
                            events.push( 'change' );
                        }
                    }

                    $inputs.on( events.join( ' ' ), function( event ) {
                        var $nextButton = $pageElem.find( '.gform_next_button' );
                        if( $nextButton.length > 0 ) {
                            $nextButton.click();
                        } else {
                            $( '#gform_submit_button_' + self.formId ).focus();
                        }
                    } );

                } );

                // trigger a change event on Datepicker selection for auto-progress-enabled Datepicker fields.
                gform.addFilter( 'gform_datepicker_options_pre_init', function( options, formId, fieldId ) {

                    if( formId != self.formId || ! $( '#input_{0}_{1}'.format( formId, fieldId ) ).parents( '.gfield' ).hasClass( 'gppt-auto-progress-field' ) ) {
                        return options;
                    }

                    var onSelect = options.onSelect;

                    options.onSelect = function() {
                        if( typeof onSelect == 'function' ) {
                            onSelect();
                        }
                        $( this ).trigger( 'gpptAutoProgress' );
                    };

                    return options;
                } );

                // show AJAX spinner on Previous button if not other button is visible (specifically if Next button is hidden)
                gform.addFilter( 'gform_spinner_target_elem', function( $target ) {
                    var selectors = [ '#gform_submit_button_' + self.formId + ':visible', '.gform_next_button:visible', '.gform_previous_button:visible' ];
                    for( var i = 0; i < selectors.length; i++ ) {
                        var $newTarget = self.$currentPage.find( selectors[ i ] );
                        if( $newTarget.length > 0 ) {
                            return $newTarget;
                        }
                    }
                    return $target;
                } );

            }

        };

        self.validate = function() {

            var currentSelectors = self.validationSelectors[ self.currentPage ] ? self.validationSelectors[ self.currentPage ] : [],
                result           = true;

            for( var i = 0; i < currentSelectors.length; i++ ) {

                var selector = currentSelectors[ i ],
                    $inputs  = self.getInput( selector.selectors.join( ', ' ), selector.bypassCache ),
                    $parent  = $inputs.parents( 'li.gfield' ),
                    isEmpty  = false;

                // Condtionally hidden fields should not fails this validation.
	            if( gformIsHidden( $inputs ) ) {
					isEmpty = false;
	            }
	            // Make sure at least one checkbox or radio button is checked.
                else if( $inputs.is( ':checkbox' ) || $inputs.is( ':radio' ) ) {
                    isEmpty = $inputs.filter( ':checked' ).length == 0;
                }
                // support for multifile upload fields
                else if( $inputs.is( ':file' ) && window.gfMultiFileUploader.uploaders[ 'gform_multifile_upload_{0}_{1}'.format( self.formId, selector.id ) ] ) {
                    var uploader = window.gfMultiFileUploader.uploaders[ 'gform_multifile_upload_{0}_{1}'.format( self.formId, selector.id ) ];
                    isEmpty = uploader.files.length <= 0;
                }
                else {
                    if( selector.relation == 'any' ) {
                        isEmpty = ! $inputs.val();
                    } else {
                        $.each( $inputs, function() {
                            if( ! $( this ).val() && ! $( this ).hasClass( 'gform_hidden' ) ) {
                                isEmpty = true;
                                return false;
                            }
                        } );
                    }

                }

                if( isEmpty ) {
                    if( ! $parent.hasClass( self.validationClass.split( ' ' )[0] ) ) {
                        $parent.addClass( self.validationClass );
                        $parent.children( '.ginput_container' ).after( self.validationMessageContainer.format( selector.validationMessage ) );
                    }
                    result = false;
                } else {
                    if( $parent.hasClass( self.validationClass.split( ' ' )[0] ) ) {
                        $parent.removeClass( self.validationClass );
                        $parent.children( '.ginput_container' ).next().remove();
                    }
                }

            }

            if( result ) {
            	self.$formElem.parents( '.gform_wrapper' ).removeClass( self.validationClassForm );
            } else {
	            self.$formElem.parents( '.gform_wrapper' ).addClass( self.validationClassForm );
            }

            return result;
        };

        self.getNamespacedEvents = function( events, namespace ) {

            var events = events.split( ' ' ),
                namespacedEvents = [];

            for( var i = 0; i < events.length; i++ ) {
                namespacedEvents.push( events[i] + '.' + namespace );
            }

            return namespacedEvents.join( ' ' );
        };

        self.resizeFormBodyTransition = function( curr, next, opts, fwd ) {
            if( $( curr ).height() <= $( next ).height() ) {
                self.resizeFormBody( $( next ) );
            } else {
                setTimeout( function() {
                    self.resizeFormBody( $( next ) );
                }, 400 );
            }
        };

        self.resizeFormBody = function( $pageElem ) {
            var duration = self.initialized === true ? self.transitionSettings.speed / 4 : 0;
            $pageElem.parent().finish().animate( { 'height' : $pageElem.height() }, duration );
        };

        self.updateProgressIndicator = function( pageNumber, speed ) {

            if( self.pagination.type == 'none' ) {
                return;
            }

            var $progressIndicator = self.pagination.type == 'steps' ? $( '#gf_page_steps_' + self.formId ) : $('#gf_progressbar_wrapper_' + self.formId ),
                speed              = typeof speed == 'undefined' ? self.getProgressIndicatorTransitionSpeed() : speed;

            if( self.pagination.isCustom ) {

                $progressIndicator.fadeOut( speed, function() {
                    var newProgressIndicator = $( self.pagination.progressIndicators[ pageNumber - 1 ] );
                    $progressIndicator.html( newProgressIndicator.html() ).fadeIn( self.getProgressIndicatorTransitionSpeed() );
                } );

            }
            else if( self.pagination.type == 'steps' ) {

                var $steps = $progressIndicator.find( '.gf_step' );

                $steps.removeClass( 'gf_step_completed gf_step_active gf_step_next gf_step_pending' ).each( function( i ) {

                    var $step      = $( this ),
                        pageNumber = i + 1;

                    if( pageNumber < self.currentPage ) {
                        $step.addClass( 'gf_step_completed' );
                    }
                    else if( pageNumber == self.currentPage ) {
                        $step.addClass( 'gf_step_active' );
                    }
                    else if( pageNumber == self.currentPage + 1 ) {
                        $step.addClass( 'gf_step_next' );
                    } else {
                        $step.addClass( 'gf_step_pending' );
                    }

                } );

            }
            else {

                var $percentageBar     = $progressIndicator.find( '.gf_progressbar_percentage' ),
                    $percentNumber     = $percentageBar.children( 'span' ),
                    currentPercentage  = self.getProgressPercentage( self.progressBarStartAtZero ? self.sourcePage - 1 : self.sourcePage ),
                    targetPercentage   = self.getProgressPercentage( self.progressBarStartAtZero ? self.currentPage - 1 : self.currentPage ),
                    diffPoints         = Math.abs( targetPercentage - currentPercentage ),
                    isForward          = targetPercentage > currentPercentage,
                    $pageTitle         = $progressIndicator.find( '.gf_progressbar_title' ),
                    pageTitle          = [ self.pagination.labels.step, self.currentPage, self.pagination.labels.of, self.pagination.pageCount ],
                    pageName           = self.pagination.pages[ self.currentPage - 1 ];

                if( pageName ) {
                    pageTitle.push( '-', pageName );
                }

                $percentageBar
                    .width( targetPercentage + '%' )
                    .removeClass( 'percentbar_{0}'.format( currentPercentage ) )
                    .addClass( 'percentbar_{0}'.format( targetPercentage ) );

                $pageTitle.text( pageTitle.join( ' ' ) );

                var percentageInterval = setInterval( function() {

                    var currentPercentage = targetPercentage - ( isForward ? diffPoints : 0 - diffPoints );
                    diffPoints--;
                    $percentNumber.text( currentPercentage + '%' );

                    if( currentPercentage == targetPercentage ) {
                        clearInterval( percentageInterval );
                    }

                }, 1000 / diffPoints );

            }

        };

        self.getProgressIndicatorTransitionSpeed = function() {
            return self.transitionSettings.sync == 1 ? self.transitionSettings.speed / 2 : self.transitionSettings.speed;
        };

        self.getProgressPercentage = function( currentPage ) {
            return Math.floor( ( currentPage / self.pagination.pageCount ) * 100 );
        };

        self.getInput = function( selector, bypassCache ) {

            if( typeof self.inputs[ selector ] == 'undefined' || bypassCache ) {
                self.inputs[ selector ] = $( selector );
            }

            return self.inputs[ selector ];
        };

    };

} )( jQuery );