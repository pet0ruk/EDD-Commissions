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
		'permissions' => array(
			'can_remove_from_formbuilder' => true,
			'can_change_meta_key'         => false,
			'can_add_to_formbuilder'      => true,
		),
		'template'	  => 'eddc_user_paypal',
		'title'       => 'PayPal Email', // l10n on output
		'phoenix'	   => true,
	);

	/** @var array Characteristics are things that can change from field to field of the same field type. Like the placeholder between two email fields. Stored in db. */
	public $characteristics = array(
		'name'        => 'eddc_user_paypal',
		'template'	  => 'eddc_user_paypal',
		'is_meta'     => true,  // in object as public (bool) $meta;
		'public'      => false,
		'required'    => true,
		'label'       => 'PayPal Email',
		'css'         => '',
		'default'     => '',
		'size'        => '',
		'help'        => '',
		'placeholder' => '',
	);

	public function extending_constructor( ){
		// exclude from profile form in admin
		add_filter( 'fes_templates_to_exclude_render_profile_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
		add_filter( 'fes_templates_to_exclude_validate_profile_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
		add_filter( 'fes_templates_to_exclude_save_profile_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );

		// exclude from registration form in admin
		add_filter( 'fes_templates_to_exclude_render_registration_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
		add_filter( 'fes_templates_to_exclude_validate_registration_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
		add_filter( 'fes_templates_to_exclude_save_registration_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
	}

	public function exclude_from_admin( $fields ){
		array_push( $fields, 'eddc_user_paypal' );
		return $fields;
	}

	/** Returns the HTML to render a field in admin */
	public function render_field_admin( $user_id = -2, $readonly = -2 ) {
		return ''; // don't render field in the admin
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

	/** Returns the commissions paypal to render a field for the formbuilder */
	public function render_formbuilder_field( $index ) {
		$removable = $this->can_remove_from_formbuilder();
        ob_start(); ?>
        <li class="user_bio">
            <?php $this->legend( $this->title(), $this->get_label(), $removable ); ?>
            <?php FES_Formbuilder_Templates::hidden_field( "[$index][template]", $this->template() ); ?>
            <div class="fes-form-holder">
                <?php FES_Formbuilder_Templates::public_radio( $index, $this->characteristics, $this->form_name ); ?>
                <?php FES_Formbuilder_Templates::standard( $index, $this ); ?>
                <?php FES_Formbuilder_Templates::common_text( $index, $this->characteristics ); ?>
            </div>
        </li>
        <?php
		return ob_get_clean();
	}

	public function validate( $values = array(), $save_id = -2, $user_id = -2 ) {
        $name = $this->name();
		if ( !empty( $values[ $name ] ) ){
			// if the value is set
			if ( filter_var($url, FILTER_VALIDATE_EMAIL) === false ){
				// if that's not a email address
				return __( 'Please enter a valid email address', 'edd_fes' );
			}
		} else { 
			// if the url is required but isn't present
			if ( $this->required() ){
				return __( 'Please fill out this field.', 'edd_fes' );
			}
		}
        return apply_filters( 'fes_validate_' . $this->template() . '_field', false, $values, $name, $save_id, $user_id ); 
	}
	
	public function sanitize( $values = array(), $save_id = -2, $user_id = -2 ){
        $name = $this->name();
		if ( !empty( $values[ $name ] ) ){
			$values[ $name ] = filter_var( $values[ $name ], FILTER_SANITIZE_EMAIL );
			$values[ $name ] = sanitize_email( $values[ $name ] );
		}
		return apply_filters( 'fes_sanitize_' . $this->template() . '_field', $values, $name, $save_id, $user_id );
	}
}
