<?php
/*
Plugin Name: Minimalist Contact
Plugin URI:  https://github.com/andrewklimek/mnml-contact/
Description: shortcode [mnmlcontact]
Version:     0.2
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
function mnmlcontact( $atts, $content ) {
	
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
		
		// if ( ! empty( $data['subscribe'] ) ) {
		// 	$signup = mnmlcontact_ac_add( $data );
		// 	error_log( $signup );
		// }
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


function mnmlcontact_ac_add( $data ) {
	
	$url = 'https://stereoscenic.api-us1.com';// no trailing /

	$params = array(

	    // the API Key can be found on the "Your Settings" page under the "API" tab.
	    // replace this with your API Key
	    'api_key'      => 'af82edd471fa4bc1e49c86e309818e640edbf0458091c986406c82ad9704d8679b62db68',

	    // this is the action that adds a contact
	    'api_action'   => 'contact_add',

	    // define the type of output you wish to get back
	    // possible values:
	    // - 'xml'  :      you have to write your own XML parser
	    // - 'json' :      data is returned in JSON format and can be decoded with
	    //                 json_decode() function (included in PHP since 5.2.0)
	    // - 'serialize' : data is returned in a serialized format and can be decoded with
	    //                 a native unserialize() function
	    'api_output'   => 'json'
	);
	
	// here we define the data we are posting in order to perform an update
	$post = array(
	    'email'                    => $data['email'],
	    //'first_name'               => $name[0],// names added below
	    //'last_name'                => $name[1],
	    'tags'                     => 'api',
	    //'ip4'                    => '127.0.0.1',

	    // any custom fields
	    //'field[345,0]'           => 'field value', // where 345 is the field ID
	    //'field[%PERS_1%,0]'      => 'field value', // using the personalization tag instead (make sure to encode the key)

	    // assign to lists:
	    'p[4]'                   => 4, // example list ID (REPLACE '123' WITH ACTUAL LIST ID, IE: p[5] = 5)
	    'status[4]'              => 1, // 1: active, 2: unsubscribed (REPLACE '123' WITH ACTUAL LIST ID, IE: status[5] = 1)
	    //'form'          => 1001, // Subscription Form ID, to inherit those redirection settings
	    //'noresponders[123]'      => 1, // uncomment to set "do not send any future responders"
	    //'sdate[123]'             => '2009-12-07 06:00:00', // Subscribe date for particular list - leave out to use current date/time
	    // use the folowing only if status=1
	    'instantresponders[4]' => 1, // set to 0 to if you don't want to sent instant autoresponders
	    //'lastmessage[123]'       => 1, // uncomment to set "send the last broadcast campaign"

	    //'p[]'                    => 345, // some additional lists?
	    //'status[345]'            => 1, // some additional lists?
	);
	
	if ( ! empty( $data['name'] ) ) {
		$name = explode( ' ', $data['name'], 2 );
		$post['first_name'] = $name[0];
        if ( ! empty( $name[1] ) )
    		$post['last_name'] = $name[1];
	}

	$query = http_build_query( $params );
	$body = http_build_query( $post );

	// clean up the url
	// $url = rtrim($url, '/ ');


	// define a final API request - GET
	$url = $url . '/admin/api.php?' . $query;

	$response = wp_remote_post( $url, array( 'body' => $body ) );
	
	if ( is_wp_error( $response ) ) {
				
		die( 'Request failed. '. $response->get_error_message() );
				
	} else {
		$result = json_decode($response['body']);

		// Result info that is always returned
		return ( $result->result_code ? 'SUCCESS' : 'FAILED' ) . ' - ' . $result->result_message . ' - ' . $data['email'];
	}

}