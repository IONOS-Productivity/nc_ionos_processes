<?php

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Service;

use Exception;
use IONOS\MailNotificationAPI\Client\Model\ShareMessageByLink;
use OCA\IonosProcesses\AppInfo\Application;
use OCA\IonosProcesses\Listener\ShareCreatedEventListener;
use OCP\Exceptions\AppConfigException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Client for the internal mail delivery web service.
 */
class IonosMailerService {
	public const BRAND = 'IONOS';

	public function __construct(
		private IAppConfig $appConfig,
		private LoggerInterface $logger,
		private ApiClientService $apiClientService,
	) {
	}

	/**
	 * Send mails to the mail delivery web service
	 *
	 * @param string $eventName name of the event
	 * @param array $variables variables to send to the mail service
	 * @throws Exception
	 */
	public function send(string $eventName, array $variables): void {
		$apiBaseUrl = $this->appConfig->getValueString(Application::APP_ID, 'ionos_mail_base_url');
		$allowInsecure = $this->appConfig->getValueBool(Application::APP_ID, 'allow_insecure');
		$basicAuthUser = $this->appConfig->getValueString(Application::APP_ID, 'basic_auth_user');
		$basicAuthPass = $this->appConfig->getValueString(Application::APP_ID, 'basic_auth_pass');

		$this->logger->debug('send', [
			'event' => $eventName,
			'variables' => $variables,
			'apiBaseUrl' => $apiBaseUrl
		]);

		if (empty($apiBaseUrl)) {
			$this->logger->error('No mailer service url is configured');
			throw new AppConfigException('No mailer service configured');
		}

		if (empty($basicAuthUser)) {
			$this->logger->error('No mailer service user is configured');
			throw new AppConfigException('No mailer user configured');
		}

		if (empty($basicAuthPass)) {
			$this->logger->error('No mailer service pass is configured');
			throw new AppConfigException('No mailer service pass configured');
		}

		$client = $this->apiClientService->newClient([
			'auth' => [$basicAuthUser, $basicAuthPass],
			'verify' => !$allowInsecure,
		]);

		$apiInstance = $this->apiClientService->newEventAPIApi($client, $apiBaseUrl);

		if ($eventName === ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK) {
			$message = new ShareMessageByLink($variables);
			try {
				$this->logger->debug('Send message to mailer service', ['event' => $eventName, 'variables' => $variables]);
				$apiInstance->processShareByLinkEvent(self::BRAND, $message);
			} catch (Exception $e) {
				$this->logger->error('Exception when calling EventAPIApi->processShareByLinkEvent', ['exception' => $e]);
			}
		}
	}
}
