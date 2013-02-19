<?php

class NFGC_Messages {

	static private $_messages = array();
	static private $_allowed_message_types = array('info', 'warning', 'error');

	static public function init() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_help_notice' ) );
	}

	static public function admin_help_notice() {
		$messages = self::get_messages();

		if( !empty( $messages ) ){
			global $nfgc;
			include( $nfgc->get_template_path() .'/admin-messages.php');
		}

	}

	static public function add_message($type, $text) {
		if (in_array($type, self::$_allowed_message_types)) {
			self::$_messages[$type][] = $text;
		}
		else {
			// Error
			$type = 'error';
			$text = 'message not added!';
			self::$_messages[$type][] = $text;
		}
	}

	static public function get_messages($type = false) {
		if ($type != false)
			 return self::$_messages[$type];
		else return self::$_messages;
	}

}