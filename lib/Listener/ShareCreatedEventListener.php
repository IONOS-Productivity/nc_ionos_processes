<?php

namespace OCA\IonosProcesses\Listener;

use OCP\IUserSession;
use OCP\EventDispatcher\Event;
use OCP\Share\IShare;
use OCP\Share\Events\ShareCreatedEvent;
use OCA\IonosProcesses\Service\IonosMailerService;
use Psr\Log\LoggerInterface;

/**
 * Notify customers about created shares via internal mail service.
 */
class ShareCreatedEventListener {
	public function __construct(
		private LoggerInterface $logger,
		private IUserSession $session,
		private IonosMailerService $mailer,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof ShareCreatedEvent)) {
			return;
		}

		$share = $event->getShare();
		$shareType = $share->getShareType();

		if ($shareType !== IShare::TYPE_EMAIL) {
			$this->logger->debug("share type " . $shareType . " ignored");
			return;
		}

		// TODO
		$data = [
			"senderUserId" => "123e4567-e89b-12d3-a456-426614174000",
			"fileName" => $share->getNode()->getName(),
			"resourceUrl" => "http://example.com/resource",
			"note" => "Please review this document.",
			"expirationDate" => 1672531199000,
			"language" => "en_US",
			"receiverEmails" => [
				"receiver1@example.com",
			]
		]

		$this->mailer->send("share-by-link", $data);
	}
}
