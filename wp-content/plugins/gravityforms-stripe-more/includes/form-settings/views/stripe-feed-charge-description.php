<?php
/**
 *
 */
?>
<tr valign="top" id="stripe_field_container_charge_description"
	<?php echo rgars( $feed, 'meta/type' ) != 'product' ? "style='display:none;'" : '' ?>>
	<th scope="row">
		<label for="gfp_more_stripe_charge_description_field">
			<?php _e( 'Charge Description', 'gravityforms-stripe-more' ); ?>
			<?php //gform_tooltip( 'stripe_alternate_charge_option' ) ?>
		</label>
	</th>
	<td>

<select id="gfp_more_stripe_charge_description_field" name="gfp_more_stripe_charge_description_field">
<?php echo $charge_description_field_options; ?>
</select>
	</td>
</tr>