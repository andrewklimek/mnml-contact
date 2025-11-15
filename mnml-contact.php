<?php
/*
Plugin Name: Minimalist Contact
Plugin URI:  https://github.com/andrewklimek/mnml-contact/
Description: shortcode [mnmlcontact subject='custom email subject' textarea='placeholder text' subscribe='label text or false for no check box'] 
Version:     0.6.4
Author:      Andrew J Klimek
Author URI:  https://andrewklimek.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

add_shortcode( 'mnmlcontact', 'mnmlcontact' );
function mnmlcontact( $atts, $content='', $tag='' ) {

	$atts = shortcode_atts([
		'subscribe' => '',
		'textarea' => 'message',
		'name' => 'name',
		'email' => 'email address',
		'submit' => 'send',
		'subject' => '',
		'to' => '',
		'style' => '',
		'class' => '',
		'timeout' => '10',
		'choice' => '',
	], $atts, $tag );
	
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
	
	ob_start();?>
	<form id=mnmlcontact method=post<?php
		if ( $atts['class'] ) echo ' class="' . $atts['class'] . '"';
		if ( $atts['style'] ) echo ' style="' . $atts['style'] . '"';
		?> onsubmit="event.preventDefault();let b=this.querySelector('#mnmlcsub');b.disabled=true;b.value='sending';fetch('/wp-json/mnmlcontact/v1/s',{method:'POST',body:new FormData(this),}).then(r=>{return r.json()}).then(r=>{this.innerHTML=r})">
		<div class="fields-wrapper fff fff-column">
			<?php
			if ( $atts['choice'] ) {
				echo "<div>";
				$choices = explode('|', $atts['choice'] );
				foreach( $choices as $i => $choice ) {
					if ( $i === 0 && isset($choices[2]) ) echo "<span>{$choice}</span> ";
					else echo "<label><input name=choice value='". trim(strip_tags($choice)) ."' type=radio required>{$choice}</label> ";
				}
				echo "</div>";
			}
			?>
			<input type=email name=email autocomplete=email placeholder="<?php echo $atts['email']; ?>" required>
			<input type=text name=name autocomplete=name placeholder="<?php echo $atts['name']; ?>">
			<?php if ( $atts['textarea'] ) echo "<textarea name=message placeholder='{$atts['textarea']}'></textarea>";



			if ( $atts['subject'] ) echo "<input type=hidden name=subject value='{$atts['subject']}'>";
			echo $to_field; ?>
			<div class='fff fff-spacebetween fff-middle'><?php
				if ( $atts['subscribe'] ) {
					if ( "hidden" === $atts['subscribe'] ) echo "<input type=hidden name=subscribe value=on>";
					else echo "<label><input type=checkbox name=subscribe> {$atts['subscribe']}</label>";
				}
				if ( $atts['timeout'] ) {
					echo "<input id=mnmlcsub type=submit value='{$atts['submit']}' disabled>";
					echo "<script>setTimeout(\"document.getElementById('mnmlcsub').disabled=0\",{$atts['timeout']}e3)</script>";
				} else {
					echo "<input id=mnmlcsub type=submit value='{$atts['submit']}'>";
				}
			?></div>
		</div>
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
		'permission_callback' => '__return_true',
	) );
} );


function mnmlcontact_submit( $request ) {

	$data = $request->get_params();
	$message = '';

	$is_bot_gibberish = function($text) {
		$len = strlen($text);
		if ($len < 10 || $len > 200) return false;
		if (strpos(trim($text), ' ')) return false;
		$upper = preg_match_all('/[A-Z]/', $text);
		$lower = preg_match_all('/[a-z]/', $text);
		return ($upper > 2) && ($lower > 2);
	};

	if ( $data['message'] && $is_bot_gibberish( $data['message'] ) ) {
		error_log("GIBBERISH SPAM: " . $data['message'] . " from " . $_SERVER['REMOTE_ADDR']);
		http_response_code(403);
		exit;
	}

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
		// $subject = get_option('blogname') ." Contact Form";
		$subject = "Contact Form";

		if ( !empty( $data['choice'] ) ) $subject .= " [". $data['choice'] ."]";

	}
	else
	{
		$subject = $data['subject'];
		unset( $data['subject'] );
	}
	if ( ! empty( $data['name'] ) ) $subject .= " - {$data['name']}";
	elseif ( ! empty( $data['email'] ) ) $subject .= " - {$data['email']}";
	
	$headers = array();
	
	if ( ! empty( $data['email'] ) ) {
		$headers[] = "Reply-To: <{$data['email']}>";
	}
	
	foreach ( $data as $key => $value ) {
		$message .= "{$key}: {$value}\n";
	}

	// $message .= "\nuser agent:\n" . $_SERVER['HTTP_USER_AGENT'];
	$message .= "\nuser IP:\n" . $_SERVER['REMOTE_ADDR'];
	
	add_filter( 'wp_mail_from_name', function( $name ){ return $name === 'WordPress' ? get_option( 'blogname' ) : $name; }, 20 );
	
	$sent = wp_mail( $to, $subject, $message, $headers );
	
	if ( ! empty( $data['subscribe'] ) && ! empty( $data['email'] ) ) {

		do_action( 'mnml_contact_subscribe',  $data['email'], $data['name'] );
		// basic method of logging subscribers to a tsv until an integration is made.
		// file_put_contents( __DIR__ . '/signups.tsv', "{$data['email']}\t{$data['name']}\n", FILE_APPEND );
	}
	
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
