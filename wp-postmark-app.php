<?php
/*
Plugin Name: WP Postmark App
Version: 1.0
Plugin URI: http://voceconnect.com
Description: Hooks into wp_mail to instead use http://postmarkapp.com API
Author: Sean O'Shaughnessy, with a major assist from Mike Pretty who put up with my "how do I...?" questions for an hour...
Author URI: http://voceconnect.com
*/

// include the Postmark API
// taken from https://github.com/Znarkus/postmark-php
include(dirname(__FILE_)."/postmark-php/Postmark.php");

/**
 * hooks into phpmailer_init and gets the phpmailer object.
 * we'll pull the data we need from the object, then update the object's
 * "to:" address to be null, thus causing $phpmailer->send() to fail
 * this way we don't send the same message twice - once through postmark,
 * and again through phpmailer.
 *
 * @return void
 **/
function postmarkapp_mailer($phpmailer) {
	// make sure our constants have been defined, otherwise return
	if( !defined('POSTMARKAPP_API_KEY') || !defined('POSTMARKAPP_MAIL_FROM_ADDRESS') || !defined('POSTMARKAPP_MAIL_FROM_NAME'))
		die("DEFINE YOUR STUFF"); #return;

	// use PHP5 Reflection to get the private "$to" var out of
	// the phpmailer object...
	// http://www.php.net/manual/en/book.reflection.php
	$ref_class = new ReflectionClass('PHPMailer');
	$ref_property = $ref_class->getProperty('to');
	$ref_property->setAccessible(true);
	$phpmailers_to = $ref_property->getValue($phpmailer);

	// i miss teh nitrous
	$phpmailers_to = $phpmailers_to[0];

	// remove empty elements in the array
	foreach ($phpmailers_to as $k => $v) {
		if ($v == "")
			unset($phpmailers_to[$k]);
	}

	// set a comma separated string of to addresses
	$postmark_to = implode(',', $phpmailers_to);

	// set up the other vars we need
	$postmark_subject = $phpmailer->Subject;
	$postmark_message_plain = $phpmailer->Body;

	// set up the postmark mail object
	$postmark_email = new Mail_Postmark();
	$postmark_email->addTo($postmark_to);
	$postmark_email->subject($postmark_subject);
	$postmark_email->messagePlain($postmark_message_plain);

	// send it!
	try {
		$postmark_email->send();
	} catch (Exception $e) {
		error_log($e);
	}

	// clear $phpmailer object to address
	$phpmailer->ClearAddresses();
	$phpmailer->ClearAllRecipients();
	$phpmailer->ClearBCCs();
	$phpmailer->ClearCCs();
	$phpmailer->ClearCustomHeaders();

	// use reflection again to set exceptions to false
	// on phpmailer, this way it "appears" it was sent
	$ref_property = $ref_class->getProperty('exceptions');
	$ref_property->setAccessible(true);
	$ref_property->setValue($phpmailer, false);
}
add_action('phpmailer_init', 'postmarkapp_mailer');