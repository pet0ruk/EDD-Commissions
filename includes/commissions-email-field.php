<?php
class FES_Commissions_Email_Field extends FES_Field {

	/** @var bool For 3rd parameter of get_post/user_meta */
	public $single = true;

	/** @var array Supports are things that are the same for all fields of a field type. Like whether or not a field type supports jQuery Phoenix. Stored in obj, not db. */
	public $supports = array(
		'multiple'    => false,
		'forms'       => array(
			'registration'     => true,
			'submission'       => false,
			'vendor-contact'   => false,
			'profile'          => true,
			'login'            => false,
		),
		'position'    => 'extension',
		'phoenix'     => true,
		'permissions' => array(
			'can_remove_from_formbuilder' => true,
			'can_change_meta_key'         => false,
			'can_add_to_formbuilder'      => true,
		),
		'input_type'  => 'eddc_user_paypal',
		'title'       => 'PayPal Email', // l10n on output
	);

	/** @var array Characteristics are things that can change from field to field of the same field type. Like the placeholder between two email fields. Stored in db. */
	public $characteristics = array(
		'name'        => '',
		'is_meta'     => true,  // in object as public (bool) $meta;
		'public'      => true,
		'required'    => false,
		'label'       => '',
		'css'         => '',
		'default'     => '',
		'size'        => '',
		'help'        => '',
		'placeholder' => '',
		'class'       => 'FES_Commissions_Email_Field', /** we don't use this yet, but future, store the name of the class used for a field on the field in the db */
	);

	/** Returns the HTML to render a field in admin */
	public function render_field_admin( $user_id = -2, $readonly = -2 ) {
		if ( $user_id === -2 ) {
			$user_id = get_current_user_id();
		}

		if ( $readonly === -2 ) {
			$readonly = $this->readonly;
		}

		$user_id   = apply_filters( 'fes_render_commissions_email_field_user_id_admin', $user_id, $this->id );
		$readonly  = apply_filters( 'fes_render_commissions_email_field_readonly_admin', $readonly, $user_id, $this->id );
		$value     = $this->get_field_value_admin( $this->save_id, $user_id, $readonly );
        ob_start(); ?>
        <div class="fes-fields">
            <input id="fes-<?php echo $this->name(); ?>" type="email" class="email" data-required="<?php echo $this->required(); ?>" data-type="text"<?php $this->required_html5( $readonly ); ?> name="<?php echo esc_attr( $this->name() ); ?>" placeholder="<?php echo esc_attr( $this->characteristics[ 'placeholder' ] ); ?>" value="<?php echo esc_attr( $value ) ?>" size="<?php echo esc_attr( $this->characteristics[ 'size' ] ) ?>" />
        </div>
        <?php
		return ob_get_clean();
	}

	/** Returns the HTML to render a field in frontend */
	public function render_field_frontend( $user_id = -2, $readonly = -2 ) {
		if ( $user_id === -2 ) {
			$user_id = get_current_user_id();
		}

		if ( $readonly === -2 ) {
			$readonly = $this->readonly;
		}

		$user_id   = apply_filters( 'fes_render_commissions_email_field_user_id_frontend', $user_id, $this->id );
		$readonly  = apply_filters( 'fes_render_commissions_email_field_readonly_frontend', $readonly, $user_id, $this->id );
		$value     = $this->get_field_value_frontend( $this->save_id, $user_id, $readonly );
        ob_start(); ?>
        <div class="fes-fields">
            <input id="fes-<?php echo $this->name(); ?>" type="email" class="email" data-required="<?php echo $this->required(); ?>" data-type="text"<?php $this->required_html5( $readonly ); ?> name="<?php echo esc_attr( $this->name() ); ?>" placeholder="<?php echo esc_attr( $this->characteristics[ 'placeholder' ] ); ?>" value="<?php echo esc_attr( $value ) ?>" size="<?php echo esc_attr( $this->characteristics[ 'size' ] ) ?>" />
        </div>
        <?php
		return ob_get_clean();
	}

	/** Returns the HTML to render a field for the formbuilder */
	public function render_formbuilder_field( $index ) {
		$removable = isset( $this->supports[ 'permissions' ][ 'can_remove_from_formbuilder' ] ) ? $this->supports[ 'permissions' ][ 'can_remove_from_formbuilder' ] : true;
		ob_start(); ?>
        <li class="eddc_user_paypal">
            <?php FES_Formbuilder_Templates::legend( $this->characteristics[ 'label' ], $this->characteristics, $removeable ); ?>
            <?php FES_Formbuilder_Templates::hidden_field( "[$index][input_type]", 'eddc_user_paypal' ); ?>
            <?php FES_Formbuilder_Templates::hidden_field( "[$index][template]", 'eddc_user_paypal' ); ?>

            <div class="fes-form-holder">
                <?php FES_Formbuilder_Templates::common( $index, 'eddc_user_paypal', true, $this->characteristics, !$removable, 'eddc_user_paypal' ); ?>
                <?php FES_Formbuilder_Templates::common_text( $index, $this->characteristics ); ?>
            </div> <!-- .fes-form-holder -->
        </li>
        <?php
		return ob_get_clean();
	}

	/** Validates field */
	public function validate(  $save_id = -2, $values = array(), $user_id = -2 ) {
		// todo: for email fields, let's validate required entry, min/max length
		return true; // we'll return an error object in the future when we implement validation
	}
}
