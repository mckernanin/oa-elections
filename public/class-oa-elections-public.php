<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://mckernan.in
 * @since      1.0.0
 *
 * @package    OA_Elections
 * @subpackage OA_Elections/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    OA_Elections
 * @subpackage OA_Elections/public
 * @author     Kevin McKernan <kevin@mckernan.in>
 */
class OA_Elections_Public {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		add_shortcode( 'unit-edit-form', array( $this, 'unit_edit_form' ) );
		add_shortcode( 'election-request', array( $this, 'election_request' ) );

		add_action( 'init', array( $this, 'rewrites' ) );
		add_action( 'cmb2_after_init', array( $this, 'unit_edit_form_submission_handler' ) );

	}

	function rewrites() {
		add_rewrite_rule(
			'^election/([^/]*)/([^/]*)/?',
			'index.php?oa_election=$matches[1]&editing_section=$matches[2]',
			'top'
		);
		add_rewrite_tag( '%editing_section%', '([^&]+)' );
	}

	/**
	 * Shortcode to display a CMB2 form for a post ID.
	 * @param  array  $atts Shortcode attributes
	 * @return string       Form HTML markup
	 */
	function unit_edit_form( $atts = array() ) {
		ob_start();
		include( 'partials/unit-edit-form.php' );
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * Shortcode to display a CMB2 form for a post ID.
	 * @param  array  $atts Shortcode attributes
	 * @return string       Form HTML markup
	 */
	function election_request( $atts = array() ) {
		ob_start();
		include( 'partials/election-request.php' );
		$output = ob_get_clean();
		return $output;
	}

	function unit_edit_form_submission_handler() {

		// If no form submission, bail
		if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
			return false;
		}

		$email_address = $_POST['_oa_election_leader_email'];

		if ( null == username_exists( $email_address ) ) {

			// Generate the password and create the user
			$password = wp_generate_password( 12, false );
			$user_id = wp_create_user( $email_address, $password, $email_address );

			// Set the nickname
			wp_update_user(
				array(
				'ID'          => $user_id,
				'nickname'    => $email_address,
				)
			);

			// Set the role
			$user = new WP_User( $user_id );
			$user->set_role( 'contributor' );

			// Email the user
			wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );

		} // end if`

		$post_id = $_POST['_post_id'];
		$current_post_type = get_post_type( $post_id );
		if ( 'oa_election' !== $current_post_type ) {
			$user_id = get_current_user_id();
			$post_data = array(
				'post_type'   => 'oa_election',
				'post_status' => 'published',
				'post_author' => $user_id ? $user_id : 1,
				'post_title'  => 'Troop ' . $_POST['_oa_election_unit_number'] . ' - ' . date( 'Y' ),
			);
			$post_id = wp_insert_post( $post_data, true );
		}
		unset( $_POST['_post_id'] );

		// Get CMB2 metabox object
		$cmb = cmb2_get_metabox( 'unit_fields', $post_id );
		$post_data = array();
		// Check security nonce
		if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
			return $cmb->prop( 'submission_error', wp_die( 'security_fail', __( 'Security check failed.' ) ) );
		}

		/**
		 * Fetch sanitized values
		 */
		$sanitized_values = $cmb->get_sanitized_values( $_POST );

		// Loop through remaining (sanitized) data, and save to post-meta
		foreach ( $sanitized_values as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = array_filter( $value );
				if ( ! empty( $value ) ) {
					update_post_meta( $post_id, $key, $value );
				}
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
		/*
		 * Redirect back to the form page with a query variable with the new post ID.
		 * This will help double-submissions with browser refreshes
		 */
		$args = array(
			'p' => $post_id,
			'update' => true,
		);

		if ( 'oa_election' !== $current_post_type ) {
			$args['new_election'] = true;
			$args['update'] = false;
		}

		wp_redirect( esc_url_raw( add_query_arg( $args ) ) );
		exit;
	}
}
