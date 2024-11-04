<?php

declare(strict_types=1);

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Tests\Service;

use IONOS\MailNotificationAPI\Client\Api\EventAPIApi;
use IONOS\MailNotificationAPI\Client\Model\ShareMessageByLink;
use OCA\IonosProcesses\AppInfo\Application;
use OCA\IonosProcesses\Listener\ShareCreatedEventListener;
use OCA\IonosProcesses\Service\ApiClientService;
use OCA\IonosProcesses\Service\IonosMailerService;
use OCP\Exceptions\AppConfigException;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class IonosMailerServiceTest extends TestCase {
	private IonosMailerService $service;
	private IAppConfig $mockAppConfig;
	private LoggerInterface $mockLogger;

	private EventAPIApi $mockApiInstance;
	private ApiClientService $mockApiClientService;
	private \GuzzleHttp\Client $mockGruzzleClient;

	protected function setUp(): void {
		parent::setUp();
		$this->mockAppConfig = $this->createMock(IAppConfig::class);

		$this->mockLogger = $this->createMock(LoggerInterface::class);

		$this->mockApiClientService = $this->createMock(ApiClientService::class);

		$this->mockGruzzleClient = $this->createMock(\GuzzleHttp\Client::class);

		$this->mockApiInstance = $this->createMock(EventAPIApi::class);

		$this->service = new IonosMailerService(
			$this->mockAppConfig,
			$this->mockLogger,
			$this->mockApiClientService
		);
	}

	public function testShareByMailSendMailsSuccessfully() {
		$this->mockAppConfig->method('getValueString')->willReturnMap([
			[Application::APP_ID, 'ionos_mail_base_url', 'https://api.ionos.com'],
			[Application::APP_ID, 'basic_auth_user', 'user'],
			[Application::APP_ID, 'basic_auth_pass', 'pass']
		]);
		$this->mockAppConfig->method('getValueBool')->willReturn(true);

		$this->mockApiClientService->expects($this->once())
			->method('newClient')
			->with([
				'auth' => ['user', 'pass'],
				'verify' => false,
			])->willReturn($this->mockGruzzleClient);

		$this->mockApiClientService->expects($this->once())
			->method('newEventAPIApi')
			->with(
				$this->mockGruzzleClient,
				'https://api.ionos.com'
			)
			->willReturn($this->mockApiInstance);

		$this->mockApiInstance->expects($this->once())
			->method('processShareByLinkEvent')
			->with('IONOS', $this->isInstanceOf(ShareMessageByLink::class));

		$matcher = $this->exactly(2);
		$this->mockLogger
			->expects($matcher)
			->method('debug');

		$this->service->send(ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK, ['key' => 'value']);
	}

	public function testThrowExceptionWhenNoMailerServiceUrlConfigured() {
		$this->mockAppConfig->method('getValueString')->willReturnMap([
			[Application::APP_ID, 'ionos_mail_base_url', ''],
			[Application::APP_ID, 'basic_auth_user', 'user'],
			[Application::APP_ID, 'basic_auth_pass', 'pass']
		]);

		$this->expectException(AppConfigException::class);
		$this->expectExceptionMessage('No mailer service configured');

		$this->service->send(ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK, ['key' => 'value']);
	}

	public function testThrowExceptionWhenNoMailerServiceUserConfigured() {
		$this->mockAppConfig->method('getValueString')->willReturnMap([
			[Application::APP_ID, 'ionos_mail_base_url', 'https://api.ionos.com'],
			[Application::APP_ID, 'basic_auth_user', ''],
			[Application::APP_ID, 'basic_auth_pass', 'pass']
		]);

		$this->expectException(AppConfigException::class);
		$this->expectExceptionMessage('No mailer user configured');

		$this->service->send(ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK, ['key' => 'value']);
	}

	public function testThrowExceptionWhenNoMailerServicePassConfigured() {
		$this->mockAppConfig->method('getValueString')->willReturnMap([
			[Application::APP_ID, 'ionos_mail_base_url', 'https://api.ionos.com'],
			[Application::APP_ID, 'basic_auth_user', 'user'],
			[Application::APP_ID, 'basic_auth_pass', '']
		]);

		$this->expectException(AppConfigException::class);
		$this->expectExceptionMessage('No mailer service pass configured');

		$this->service->send(ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK, ['key' => 'value']);
	}

	public function testLogErrorWhenExceptionThrownByApi() {
		$exception = new \Exception('mocked exception');
		$this->mockApiInstance
			->method('processShareByLinkEvent')
			->willThrowException($exception);

		$this->mockApiClientService->expects($this->once())
			->method('newEventAPIApi')
			->willReturn($this->mockApiInstance);

		$this->service = new IonosMailerService(
			$this->mockAppConfig,
			$this->mockLogger,
			$this->mockApiClientService
		);

		$this->mockAppConfig->method('getValueString')->willReturnMap([
			[Application::APP_ID, 'ionos_mail_base_url', 'https://api.ionos.com'],
			[Application::APP_ID, 'basic_auth_user', 'user'],
			[Application::APP_ID, 'basic_auth_pass', 'pass']
		]);
		$this->mockAppConfig->method('getValueBool')->willReturn(true);

		$this->mockLogger->expects($this->once())->method('error')->with(
			'Exception when calling EventAPIApi->processShareByLinkEvent',
			['exception' => $exception]
		);

		$this->service->send(ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK, ['key' => 'value']);
	}
}
