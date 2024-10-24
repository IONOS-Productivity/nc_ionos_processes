<?php

declare(strict_types=1);

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Tests\Listener;

use DateTime;
use OCA\IonosProcesses\Listener\ShareCreatedEventListener;
use OCA\IonosProcesses\Service\IonosMailerService;
use OCP\EventDispatcher\Event;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Enum to write expressive, speaking boolean in test config.
 */
enum UserIs {
	case FOUND;
	case NOT_FOUND;
}

class ShareCreatedEventListenerTest extends TestCase {
	public const MOCK_USER_ID = '123e4567-e89b-12d3-a456-426614174000';
	public const MOCK_SHARE_TOKEN = 'mock-token';
	public const MOCK_NOTE = 'mock-note';
	public const MOCK_RECIPIENT = 'mock-recipient';
	private LoggerInterface $mockLogger;
	private IUserManager $mockUserManager;
	private IUser $mockUser;
	private IonosMailerService $mockMailer;
	private IL10N $mockL10N;
	private IURLGenerator $mockUrlGenerator;
	private ShareCreatedEvent $mockEvent;
	private IShare $mockShare;
	private DateTime $mockTimestamp;
	private Node $mockNode;

	private ShareCreatedEventListener $listener;

	protected function setUp(): void {
		$this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
			->getMock();

		$this->mockUser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();

		$this->mockUserManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();

		$this->mockMailer = $this->getMockBuilder(IonosMailerService::class)
			->onlyMethods([
				'send',
			])
			->getMock();

		$this->mockL10N = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();

		$this->mockUrlGenerator = $this->getMockBuilder(IURLGenerator::class)
			->disableOriginalConstructor()
			->getMock();

		$this->mockNode = $this->getMockBuilder(Node::class)
			->getMock();

		$this->mockTimestamp = $this->getMockBuilder(\DateTime::class)
			->getMock();

		$this->mockShare = $this->getMockBuilder(IShare::class)
			->getMock();

		$this->mockEvent = $this->getMockBuilder(ShareCreatedEvent::class)
			->setConstructorArgs([$this->mockShare])
			->getMock();

		$this->mockEvent
			->method('getShare')
			->willReturn($this->mockShare);

		$this->listener = new ShareCreatedEventListener(
			$this->mockLogger,
			$this->mockUserManager,
			$this->mockL10N,
			$this->mockUrlGenerator,
			$this->mockMailer,
		);
	}

	public function testNonShareTypeEmailShallBeIgnored(): void {
		$this->expectNotToPerformAssertions();

		$this->mockShare
			->method('getShareType')
			->willReturnOnConsecutiveCalls(
				IShare::TYPE_USER,
				IShare::TYPE_GROUP,
				IShare::TYPE_USERGROUP,
				IShare::TYPE_LINK,
			);

		$this->mockLogger
			->method('debug')
			->will($this->onConsecutiveCalls(
				'share type ' . IShare::TYPE_USER . ' ignored',
				'share type ' . IShare::TYPE_GROUP . ' ignored',
				'share type ' . IShare::TYPE_USERGROUP . ' ignored',
				'share type ' . IShare::TYPE_LINK . ' ignored',
			));


		// Four calls for the event types
		$this->listener->handle($this->mockEvent);
		$this->listener->handle($this->mockEvent);
		$this->listener->handle($this->mockEvent);
		$this->listener->handle($this->mockEvent);
	}

	private function configureMocks(
		UserIs $userFound,
		string $mockLanguageCode,
		?int $mockExpiration,
	): void {
		$mockUserId = '123e4567-e89b-12d3-a456-426614174000';
		$mockNodeName = 'mock-name';
		$mockUrl = 'mock-url';

		$this->mockL10N
			->method('getLanguageCode')
			->willReturn($mockLanguageCode);

		if ($userFound == UserIs::FOUND) {
			$this->mockUser
				->method('getUID')
				->willReturn(ShareCreatedEventListenerTest::MOCK_USER_ID);

			$this->mockUserManager
				->method('get')
				->willReturn($this->mockUser);
		} else {
			$this->mockUserManager
				->method('get')
				->willReturn(null);
		}

		$this->mockUserManager
			->method('get')
			->willReturn($mockUserId);

		$this->mockUrlGenerator
			->method('linkToRouteAbsolute')
			->with(
				'files_sharing.sharecontroller.showShare',
				[
					'token' => ShareCreatedEventListenerTest::MOCK_SHARE_TOKEN,
				],
			)
			->willReturn($mockUrl);

		$this->mockNode
			->method('getName')
			->willReturn($mockNodeName);

		$this->mockShare
			->method('getShareType')
			->willReturn(IShare::TYPE_EMAIL);

		$this->mockShare
			->method('getNode')
			->willReturn($this->mockNode);

		$this->mockShare
			->method('getToken')
			->willReturn(ShareCreatedEventListenerTest::MOCK_SHARE_TOKEN);

		$this->mockShare
			->method('getSharedWith')
			->willReturn(ShareCreatedEventListenerTest::MOCK_RECIPIENT);

		$this->mockShare
			->method('getNote')
			->willReturn(ShareCreatedEventListenerTest::MOCK_NOTE);

		if ($mockExpiration !== null) {
			$this->mockShare
				->method('getExpirationDate')
				->willReturn($this->mockTimestamp);

			$this->mockTimestamp
				->method('getTimestamp')
				->willReturn($mockExpiration);
		} else {
			$this->mockShare
				->method('getExpirationDate')
				->willReturn(null);
		}
	}

	public function testShareTypeEmailNoExpirationDate(): void {
		$mockLanguageCode = 'lang_LOCALE';
		$mockNodeName = 'mock-name';
		$mockUrl = 'mock-url';
		$mockExpiration = null;

		$this->configureMocks(
			UserIs::FOUND,
			$mockLanguageCode,
			$mockExpiration,
		);

		$this->mockMailer
			->expects($this->exactly(1))
			->method('send')
			->with(
				ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK,
				[
					'senderUserId' => ShareCreatedEventListenerTest::MOCK_USER_ID,
					'fileName' => $mockNodeName,
					'resourceUrl' => $mockUrl,
					'note' => ShareCreatedEventListenerTest::MOCK_NOTE,
					'expirationDate' => $mockExpiration,
					'language' => $mockLanguageCode,
					'receiverEmails' => [
						ShareCreatedEventListenerTest::MOCK_RECIPIENT,
					]
				]
			);

		$this->listener->handle($this->mockEvent);
	}

	public function testShareTypeEmailHasExpirationDate(): void {
		$mockLanguageCode = 'lang_LOCALE';
		$mockNodeName = 'mock-name';
		$mockUrl = 'mock-url';
		$mockExpiration = 123456789;

		$this->configureMocks(
			UserIs::FOUND,
			$mockLanguageCode,
			$mockExpiration,
		);

		$this->mockMailer
			->expects($this->exactly(1))
			->method('send')
			->with(
				ShareCreatedEventListener::EVENT_NAME_SHARE_BY_LINK,
				[
					'senderUserId' => ShareCreatedEventListenerTest::MOCK_USER_ID,
					'fileName' => $mockNodeName,
					'resourceUrl' => $mockUrl,
					'note' => ShareCreatedEventListenerTest::MOCK_NOTE,
					'expirationDate' => $mockExpiration,
					'language' => $mockLanguageCode,
					'receiverEmails' => [
						ShareCreatedEventListenerTest::MOCK_RECIPIENT,
					]
				]
			);

		$this->listener->handle($this->mockEvent);
	}

	public function testShareTypeEmailUserNotFound(): void {
		$mockLanguageCode = 'lang_LOCALE';
		$mockExpiration = 123456789;

		$this->configureMocks(
			UserIs::NOT_FOUND,
			$mockLanguageCode,
			$mockExpiration,
		);

		$this->mockMailer
			->expects($this->exactly(0))
			->method('send');

		$this->mockLogger
			->method('error')
			->with("can not find user for share with token '" . ShareCreatedEventListenerTest::MOCK_SHARE_TOKEN . "'");

		$this->listener->handle($this->mockEvent);
	}

	public function testShareTypeEmailNodeNotFound(): void {
		$mockLanguageCode = 'lang_LOCALE';
		$mockExpiration = 123456789;

		$this->mockShare
			->method('getNode')
			->willThrowException(new NotFoundException('node not found'));

		$this->configureMocks(
			UserIs::FOUND,
			$mockLanguageCode,
			$mockExpiration,
		);

		$this->mockMailer
			->expects($this->exactly(0))
			->method('send');

		$this->mockLogger
			->method('error')
			->with("can not find node for share with token '" . ShareCreatedEventListenerTest::MOCK_SHARE_TOKEN . "': node not found");

		$this->listener->handle($this->mockEvent);
	}
}
