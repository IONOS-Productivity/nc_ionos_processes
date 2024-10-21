<?php

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Service;

use Psr\Log\LoggerInterface;

/**
 * Client for the internal mail delivery web service.
 */
class IonosMailerService {
	public function __construct(
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * Send mails to the mail delivery web service
	 *
	 * @param string $eventName name of the event
	 * @param array $associative array of variables to the mail service
	 */
	public function send(string $eventName, array $variables): void {
		// Stub
		$this->logger->debug($eventName . ' = ' . json_encode($variables));
	}
}
