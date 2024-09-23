<?php
/**
 * SPDX-FileLicenseText: 2024 Strato AG <t.lehmann@strato.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\IonosProcesses\Listener;

use OC\Authentication\Events\RemoteWipeStarted;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Handle remote wipe events to send mails to IONOS mail service.
 *
 * @template-implements IEventListener<RemoteWipeStarted>
 */
class RemoteWipeListener implements IEventListener {
	private LoggerInterface $logger;

	public function __construct(
		LoggerInterface $logger,
	) {
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof RemoteWipeStarted) {
			$this->logger->debug("remote wipe started for token: " . $event->getToken());
		}
	}
}
