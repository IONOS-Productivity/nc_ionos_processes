<?php

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Listener;

use OCA\IonosProcesses\Service\IonosMailerService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * Notify customers about created shares via internal mail service.
 */
class ShareCreatedEventListener implements IEventListener {
	public const EVENT_NAME_SHARE_BY_LINK = 'share-by-link';

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IUserManager $userManager,
		private readonly IL10N $l10n,
		private readonly IURLGenerator $urlGenerator,
		private readonly IonosMailerService $mailer,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof ShareCreatedEvent)) {
			return;
		}

		$share = $event->getShare();
		$shareType = $share->getShareType();

		if ($shareType !== IShare::TYPE_EMAIL) {
			$this->logger->debug('share type ' . $shareType . ' ignored');
			return;
		}

		$user = $this->userManager->get($share->getSharedBy());

		if ($user === null) {
			$this->logger->error("can not find user for share with token '" . $share->getToken() . "'");
			return;
		}

		try {
			$filename = $share->getNode()->getName();
		} catch (NotFoundException $e) {
			$this->logger->error("can not find node for share with token '" . $share->getToken() . "': " . $e->getMessage());
			return;
		}

		$language = $this->l10n->getLanguageCode();
		$userId = $user->getUID();
		$note = $share->getNote();
		$expirationDate = $share->getExpirationDate();
		$recipient = $share->getSharedWith();
		$url = $this->urlGenerator->linkToRouteAbsolute('files_sharing.sharecontroller.showShare', [
			'token' => $share->getToken()
		]);

		$data = [
			'senderUserId' => $userId,
			'fileName' => $filename,
			'resourceUrl' => $url,
			'note' => $note,
			'expirationDate' => $expirationDate?->getTimestamp(),
			'language' => $language,
			'receiverEmails' => [
				$recipient,
			]
		];

		$this->mailer->send(ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK, $data);
	}
}
