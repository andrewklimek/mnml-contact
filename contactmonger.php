<?php
/*
Plugin Name: Contact Monger
Plugin URI:  https://github.com/andrewklimek/contactmonger/
Description: shortcode [contactmonger]
Version:     0.2
Author:      Andrew J Klimek
Author URI:  https://readycat.net
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Contact Monger is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free 
Software Foundation, either version 2 of the License, or any later version.

Contact Monger is distributed in the hope that it will be useful, but without 
any warranty; without even the implied warranty of merchantability or fitness for a 
particular purpose. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with 
Contact Monger. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


add_shortcode( 'contactmonger', 'contactmonger' );
function contactmonger( $atts, $content ) {

	ob_start();?>
	<form id="contactmonger-form" action="" method="post">
		<div class="fields-wrapper">
			<div>
				<input type="text" name="first_name" placeholder="First Name">
				<input type="text" name="last_name" placeholder="Last Name">
			</div>
			<input type="text" name="company" placeholder="Company">
			<input type="email" name="email" placeholder="Email Address">
			<textarea name="message" placeholder="Comment"></textarea>
		</div>
		<input type="submit" value="SEND">
	</form>
	<?php
	$out = ob_get_clean();

	return $out;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'formmonger/v1', '/submit', array(
		'methods' => 'POST',
		'callback' => 'contactmonger_submit',
	) );
} );


function contactmonger_submit( $request ) {
	
	$data = $request->get_params();
	$message = '';	
	foreach ( $data as $key => $value ) {
		$message .= "{$key}: {$value}\n";
	}
	$to = get_option('admin_email');
	// $to = 'andrew@readycat.net';
	
	$sent = wp_mail( $to, "Website Contact Form", $message );

	if ( $sent ) {
		return "success";
	} else {
		return new WP_Error( 'mail_send_failed', 'mail send failed', array( 'status' => 404 ) );
	}
	
}

/**
 * Setup JavaScript
 */
add_action( 'wp_enqueue_scripts', function() {

	//load script
	wp_enqueue_script( 'contactmonger-submit', plugin_dir_url( __FILE__ ) . 'submit.js', array( 'jquery' ), plugin_dir_path( __FILE__ ) . 'submit.js' );

	//localize data for script
	wp_localize_script( 'contactmonger-submit', 'FORM_SUBMIT', array(
			'root' => esc_url_raw( rest_url() ),
			'success' => 'Thanks!',
			'failure' => 'Your submission could not be processed.',
		)
	);

});
