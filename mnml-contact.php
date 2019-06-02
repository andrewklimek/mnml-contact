<?php
/*
Plugin Name: Minimalist Contact
Plugin URI:  https://github.com/andrewklimek/mnml-contact/
Description: shortcode [mnmlcontact]
Version:     0.3.1
Author:      Andrew J Klimek
Author URI:  https://andrewklimek.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimalist Contact is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free 
Software Foundation, either version 2 of the License, or any later version.

Minimalist Contact is distributed in the hope that it will be useful, but without 
any warranty; without even the implied warranty of merchantability or fitness for a 
particular purpose. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with 
Minimalist Contact. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/


add_shortcode( 'mnmlcontact', 'mnmlcontact' );
function mnmlcontact( $a='', $c='' ) {
	
	wp_enqueue_script( 'mnmlcontact-submit' );

	return <<<FORM
	<form id="mnmlcontact" method="post">
		<div class="fields-wrapper fff fff-column">
			<input type="text" name="name" placeholder="Name">
			<input type="email" name="email" placeholder="Email Address" required>
			<textarea name="message" placeholder="Comment"></textarea>
			<label><input type="checkbox" name="subscribe" value="1"> Send me news</label>
		</div>
		<input type="submit" value="SEND">
	</form>
FORM;
}

function echo_mnmlcontact() {
    echo mnmlcontact();
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'mnmlcontact/v1', '/submit', array(
		'methods' => 'POST',
		'callback' => 'mnmlcontact_submit',
	) );
} );


function mnmlcontact_submit( $request ) {

	$data = $request->get_params();
	$message = $signup = '';	
	foreach ( $data as $key => $value ) {
		$message .= "{$key}: {$value}\n";
	}
	$to = get_option('admin_email');
	
	if ( ! empty( $data['name'] ) ) {
		$subject = "contact form: {$data['name']}";
	} else {
		$subject = get_option('blogname') ." Contact Form";
	}
	$headers = array();
	
	if ( ! empty( $data['email'] ) ) {
		$headers[] = "Reply-To: <{$data['email']}>";
	}
	
	$sent = wp_mail( $to, $subject, $message, $headers );

	if ( $sent ) {
		// if ( $signup )
		// 	return $signup;
		// else
			return "success";
	} else {
		return new WP_Error( 'mail_send_failed', 'mail send failed', array( 'status' => 404 ) );
	}
	
}

/**
* Setup JavaScript
*/
add_action( 'wp_enqueue_scripts', function() {

	$suffix = SCRIPT_DEBUG ? "" : ".min";

	wp_register_script( 'mnmlcontact-submit', plugin_dir_url( __FILE__ ) . 'submit'.$suffix.'.js', null, null );

	//localize data for script
	// wp_localize_script( 'mnmlcontact-submit', 'FORM_SUBMIT', array(
		// 			'url' => esc_url_raw( rest_url('mnmlcontact/v1/submit') ),
		// 			'success' => 'Thanks!',
		// 			'failure' => 'Your submission could not be processed.',
		// 		)
		// 	);

});


add_filter('script_loader_tag', function($tag, $handle) {
	return ( 'mnmlcontact-submit' !== $handle ) ? $tag : str_replace( ' src', ' defer src', $tag );
}, 10, 2);
