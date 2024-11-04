<?php

namespace OCA\IonosProcesses\Service;

use GuzzleHttp\ClientInterface;
use IONOS\MailNotificationAPI\Client\Api\EventAPIApi;

class ApiClientService {

	/**
	 * Create a new client
	 *
	 * @param array $config
	 * @return ClientInterface
	 */
	public function newClient(array $config): ClientInterface {
		return new \GuzzleHttp\Client($config);
	}

	/**
	 * Create a new EventAPIApi
	 *
	 * @param ClientInterface $client
	 * @param string $apiBaseUrl
	 * @return EventAPIApi
	 */
	public function newEventAPIApi(ClientInterface $client, string $apiBaseUrl): EventAPIApi {

		if (empty($apiBaseUrl)) {
			throw new \InvalidArgumentException('API base URL is required');
		}

		$apiClient = new EventAPIApi(
			$client,
		);

		$apiClient->getConfig()->setHost($apiBaseUrl);

		return $apiClient;
	}
}
