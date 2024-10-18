<?php

namespace OCA\IonosProcesses\Listener;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;
use OCP\Share\Events\ShareCreatedEvent;
use Psr\Log\LoggerInterface;

/**
 * Notify customers about created shares via internal mail service.
 */
class ShareCreatedEventListener {
	public function __construct(
		private LoggerInterface $logger,
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

		$this->logger->debug("share created for file " . $share->getNode()->getName());
	}
}
