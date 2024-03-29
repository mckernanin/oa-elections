<?php
$object_id  = get_the_ID();

$cmb = cmb2_get_metabox( 'unit_fields', $object_id );

$cmb->add_hidden_field( array(
	'field_args'  => array(
		'id'    => '_post_id',
		'type'  => 'hidden',
		'default' => $object_id,
	),
) );

// If the post was submitted successfully, notify the user.
if ( isset( $_GET['update'] ) ) {
	// Get submitter's name
	$name = get_post_meta( $post->ID, 'submitted_author_name', 1 );
	$name = $name ? ' '. $name : '';
	// Add notice of submission to our output
	echo '<h3>' . sprintf( __( 'Thank you%s, your election has been updated. You will be notified by email once your election has been scheduled.', 'OA-Election' ), esc_html( $name ) ) . '</h3><br />';
}

if ( isset( $_GET['new_election'] ) ) {
	// Get submitter's name
	$name = get_post_meta( $post->ID, 'submitted_author_name', 1 );
	$name = $name ? ' '. $name : '';
	// Add notice of submission to our output
	echo '<h3>' . sprintf( __( 'Thank you%s, your election has been submitted. You will be notified by email once your election has been scheduled.', 'OA-Election' ), esc_html( $name ) ) . '</h3><br />';
}

echo '<h2>' . $cmb->meta_box['title'] . '</h2>';
echo cmb2_get_metabox_form( $cmb, 'unit_fields' );
