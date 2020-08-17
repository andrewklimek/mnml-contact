<?php
/*
Plugin Name: Minimalist Contact
Plugin URI:  https://github.com/andrewklimek/mnml-contact/
Description: shortcode [mnmlcontact subject='custom email subject' textarea='placeholder text' subscribe='label text or false for no check box'] 
Version:     0.6.2
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
function mnmlcontact( $atts, $content='', $tag ) {

	$atts = shortcode_atts( array(
		'subscribe' => 'subscribe',
		'textarea' => 'message',
		'subject' => '',
		'to' => '',
		'style' => '',
		'class' => '',
	), $atts, $tag );
	
	// Save TO address to database and replace with an ID for privacy
	$to_field = "";
	if ( $atts['to'] )
	{
		$to_index = false;
		$tos = get_option( 'mnmlcontact_to', array('skip 0') );
		$to_index = array_search( $atts['to'], $tos );// see if address has been saved before
		if ( ! $to_index ) {
			// email not in DB (or option not set at all)
			$to_index = count( $tos );
			$tos[] = $atts['to'];
			update_option( 'mnmlcontact_to', $tos );
		}
		$to_field = "<input type=hidden name=to value={$to_index}>";
	}
	
	// wp_enqueue_script( 'mnmlcontact-submit' );
	
	ob_start();?>
	<form id=mnmlcontact method=post<?php
		if ( $atts['class'] ) echo ' class="' . $atts['class'] . '"';
		if ( $atts['style'] ) echo ' style="' . $atts['style'] . '"';
		?> onsubmit="event.preventDefault();var t=this,x=new XMLHttpRequest;x.open('POST','/wp-json/mnmlcontact/v1/s'),x.onload=function(){t.innerHTML=JSON.parse(x.response)},x.send(new FormData(t))">
		<div class="fields-wrapper fff fff-column">
			<input type=text name=name autocomplete=name placeholder=name>
			<input type=email name=email autocomplete=email placeholder="email address" required>
			<?php if ( $atts['textarea'] ) echo "<textarea name=message placeholder='{$atts['textarea']}'></textarea>";
			if ( $atts['subject'] ) echo "<input type=hidden name=subject value='{$atts['subject']}'>";
			echo $to_field; ?>
			<div class='fff fff-spacebetween fff-middle'>
				<?php if ( $atts['subscribe'] ) echo "<label><input type=checkbox name=subscribe checked> {$atts['subscribe']}</label>"; ?>
				<input id=mnmlcsub type=submit value=send disabled>
			</div>
		</div>
		<script>setTimeout("document.getElementById('mnmlcsub').disabled=0",1e4)</script>
	</form>
	<?php
	return ob_get_clean();
}

function echo_mnmlcontact() {
    echo mnmlcontact();
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'mnmlcontact/v1', '/s', array(
		'methods' => ['POST','GET'],
		'callback' => 'mnmlcontact_submit',
	) );
} );


function mnmlcontact_submit( $request ) {

	$data = $request->get_params();
	$message = '';	

	// lowercase email address
	if ( ! empty( $data['email'] ) ) $data['email'] = strtolower( $data['email'] );
	
	// TO
	$to = "";
	if ( isset( $data['to'] ) )// ID for a TO email address, passed as a hidden field (don't want the actual address in the HTML)
	{
		if ( $tos = get_option( 'mnmlcontact_to' ) )
			$to = $tos[ (int) $data['to'] ];
		unset( $data['to'] );
	}	
	if ( ! $to ) $to = apply_filters( 'mnmlcontact_to', false );
	if ( ! $to ) $to = get_option('admin_email');
	
	if ( empty( $data['subject'] ) )
	{
		$subject = get_option('blogname') ." Contact Form";
	}
	else
	{
		$subject = $data['subject'];
		unset( $data['subject'] );
	}
	if ( ! empty( $data['name'] ) ) $subject .= ": {$data['name']}";
	elseif ( ! empty( $data['email'] ) ) $subject .= ": {$data['email']}";
	
	$headers = array();
	
	if ( ! empty( $data['email'] ) ) {
		$headers[] = "Reply-To: <{$data['email']}>";
	}
	
	foreach ( $data as $key => $value ) {
		$message .= "{$key}: {$value}\n";
	}

	$message .= "\nuser agent:\n" . $_SERVER['HTTP_USER_AGENT'];
	
	$sent = wp_mail( $to, $subject, $message, $headers );
	
	// basic method of logging subscribers to a tsv until an integration is made.
	if ( ! empty( $data['subscribe'] ) )
		file_put_contents( __DIR__ . '/signups.tsv', "{$data['email']}\t{$data['name']}\n", FILE_APPEND );
	
	if ( $sent )
	{
		if ( "GET" === $request->get_method() )
		{
			// You can make a link that auto submits tis form with URL parameters
			// I might use it to give people a one click sign up (submit form with "subscribe" checked)
			// If that methos was used, redirect them to a thank-you page.
			wp_redirect( home_url( "signup-success" ) );
			exit;
		}
		else
		{
			$success_message = '<p>';// style="border:1px solid;padding:12px"
			if ( ! empty( $data['message'] ) ) $success_message .= 'Your message was sent.  ';
			if ( ! empty( $data['subscribe'] ) ) $success_message .= 'Thanks for subscribing!';
			elseif ( empty( $data['message'] ) && isset( $data['message'] ) ) {// if message field was in form but is empty (and subscribe field is also empty via elseif)
				$success_message .= 'You did not enter a message';
				if ( isset( $data['subscribe'] ) )// the subscribe field is empty (via elseif) but was in form
					$success_message .= ', nor you did not tick “subscribe”';
				$success_message .= '... did something go wrong? <a href="javascript:window.location.reload()">Click here to refresh and try again.</a>';
			}
			return $success_message;
		}
	}
	else
	{
		error_log("Contact form failed");
		error_log( var_export( $data, true ) );
		return "Something went wrong.  Please try again later.";
		// return new WP_Error( 'mail_send_failed', 'mail send failed', array( 'status' => 404 ) );
	}
	
}

/**
* Setup JavaScript - Currently putting JS in the form element's onsubmit attribute
*
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
*/
