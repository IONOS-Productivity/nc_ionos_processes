<?php

declare(strict_types=1);

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Tests\Service;

use GuzzleHttp\ClientInterface;
use IONOS\MailNotificationAPI\Client\Api\EventAPIApi;
use OCA\IonosProcesses\Service\ApiClientService;
use PHPUnit\Framework\TestCase;

class ApiClientServiceTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
	}
	public function testCreateNewClientSuccessfully() {
		$service = new ApiClientService();
		$config = ['base_uri' => 'https://api.example.com'];
		$client = $service->newClient($config);

		$this->assertInstanceOf(ClientInterface::class, $client);
		$this->assertEquals('https://api.example.com', $client->getConfig('base_uri'));
	}

	public function testCreateNewEventAPIApiSuccessfully() {
		$service = new ApiClientService();
		$client = $this->createMock(ClientInterface::class);
		$apiBaseUrl = 'https://api.example.com';
		$eventApi = $service->newEventAPIApi($client, $apiBaseUrl);

		$this->assertInstanceOf(EventAPIApi::class, $eventApi);
		$this->assertEquals($apiBaseUrl, $eventApi->getConfig()->getHost());
	}

	public function testThrowExceptionWhenApiBaseUrlIsEmpty() {
		$service = new ApiClientService();
		$client = $this->createMock(ClientInterface::class);
		$apiBaseUrl = '';

		$this->expectException(\InvalidArgumentException::class);

		$service->newEventAPIApi($client, $apiBaseUrl);
	}
}
