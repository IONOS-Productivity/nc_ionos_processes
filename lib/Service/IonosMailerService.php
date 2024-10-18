<?php

namespace OCA\IonosProcesses\Service;

/**
 * Client for the internal mail delivery web service.
 */
class IonosMailerService {
	public function __construct() {
	}

	/**
	 * Send mails to the mail delivery web service
	 *
	 * @param string eventName name of the event
	 * @param array associative array of variables to the mail service
	 */
	public function send(string $eventName, array $variables): void {
		// Stub
	}
}
