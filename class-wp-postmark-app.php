<?php
/**
 * subclass of PHPMailer that instead uses the Postmark API
 * to send e-mail.
 *
 * @package WP Postmark App
 **/
class WPPostmarkApp extends PHPMailer {
	public function __construct() {
		//include the postmark api...
		include(__DIR__."/postmark-php/Postmark.php");
	}

	/**
	 * overrides phpmailer::Send to send via the postmark api.
	 * we'll pull the data we need from the phpmailer object
	 *
	 * @return (boolean) - whether or not sending via postmark api failed...
	 **/
	public function Send() {
		// make sure our constants have been defined...
		if( !defined('POSTMARKAPP_API_KEY') || !defined('POSTMARKAPP_MAIL_FROM_ADDRESS') || !defined('POSTMARKAPP_MAIL_FROM_NAME')) {
			// ...if nothing is defined, fall back to parent class Send method
			return parent::Send();
		}

		// use PHP5 Reflection to get the private "$to" var out of
		// the phpmailer object...
		// http://www.php.net/manual/en/book.reflection.php
		$ref_class = new ReflectionClass('PHPMailer');
		$ref_property = $ref_class->getProperty('to');
		$ref_property->setAccessible(true);
		$phpmailers_to = $ref_property->getValue($this);

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
		$postmark_subject = $this->Subject;
		$postmark_message_plain = $this->Body;

		// set up the postmark mail object
		$postmark_email = new Mail_Postmark();
		$postmark_email->addTo($postmark_to);
		$postmark_email->subject($postmark_subject);
		$postmark_email->messagePlain($postmark_message_plain);

		// send it!
		try {
			$postmark_email->send();
			return true;
		} catch (Exception $e) {
			$this->SetError($e->getMessage());
			if ($this->exceptions) {
				throw $e;
			}
			return false;
		}
	}
}