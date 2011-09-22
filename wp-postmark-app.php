<?php
/*
Plugin Name: WP Postmark App
Version: 1.0
Plugin URI: http://voceconnect.com
Description: Hooks into wp_mail to instead use http://postmarkapp.com API
Author: Sean O'Shaughnessy, with a major assist from Mike Pretty who put up with my "how do I...?" questions for an hour...
Author URI: http://voceconnect.com
*/

// require PHP 5
if (version_compare(PHP_VERSION, '5.0.0', '<') ) exit("Sorry, WP Postmark App will only run on PHP version 5 or greater!\n");

/**
 * set the global phpmailer object to our WP Postmark subclass...
 *
 * @return void
 **/
function extend_phpmailer_with_wppostmarkapp() {
	global $phpmailer;

	if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
	}

	include(__DIR__.'/class-wp-postmark-app.php');
	$phpmailer = new WPPostmarkApp();
}
add_action('init', 'extend_phpmailer_with_wppostmarkapp');
