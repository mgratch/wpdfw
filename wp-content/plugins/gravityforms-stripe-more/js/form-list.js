function ToggleStripeMode( img, form_id ) {
	var is_live = img.src.indexOf( 'active1.png' ) >= 0;
	var toggle = '';
	if ( is_live ) {
		img.src = img.src.replace( 'active1.png', 'active0.png' );
		jQuery( img ).attr( 'title', stripe_form_list.test_text ).attr( 'alt', stripe_form_list.test_text );
		toggle = 'test';
	}
	else {
		img.src = img.src.replace( 'active0.png', 'active1.png' );
		jQuery( img ).attr( 'title', stripe_form_list.live_text ).attr( 'alt', stripe_form_list.live_text );
		toggle = 'live';
	}

	var post_data = { action: 'gfp_more_stripe_update_form_stripe_mode',
		gfp_more_stripe_update_form_stripe_mode: stripe_form_list.nonce,
		form_id: form_id,
		mode: toggle };

	jQuery.post( ajaxurl, post_data, function ( response ) {
		if ( '0' !== response ) {
			alert( stripe_form_list.update_mode_error_message );
			if ( 'live' === toggle ) {
				img.src = img.src.replace( 'active1.png', 'active0.png' );
				jQuery( img ).attr( 'title', stripe_form_list.test_text ).attr( 'alt', stripe_form_list.test_text );
			}
			else {
				img.src = img.src.replace( 'active0.png', 'active1.png' );
				jQuery( img ).attr( 'title', stripe_form_list.live_text ).attr( 'alt', stripe_form_list.live_text );
			}
		}
	} );

	return true;
}