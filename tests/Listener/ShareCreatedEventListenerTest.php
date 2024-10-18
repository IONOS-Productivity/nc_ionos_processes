<?php

declare(strict_types=1);

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Tests\Listener;

use OCA\IonosProcesses\Listener\ShareCreatedEventListener;
use OCP\EventDispatcher\Event;
use OCP\Files\Node;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShareCreatedEventListenerTest extends TestCase {
	private LoggerInterface $mockLogger;
	private ShareCreatedEvent $mockEvent;
	private IShare $mockShare;
	private Node $mockNode;

	private ShareCreatedEventListener $listener;

	protected function setUp(): void {
		$this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
			->getMock();

		$this->mockNode = $this->getMockBuilder(Node::class)
			->getMock();

		$this->mockShare = $this->getMockBuilder(IShare::class)
			->getMock();

		$this->mockEvent = $this->getMockBuilder(ShareCreatedEvent::class)
			->setConstructorArgs([$this->mockShare])
			->getMock();

		$this->mockEvent
			->method('getShare')
			->willReturn($this->mockShare);

		$this->listener = new ShareCreatedEventListener($this->mockLogger);
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

	public function testShareTypeEmailShallHandled(): void {
		$mockNodeName = 'mock-name';

		$this->mockNode
			->method('getName')
			->willReturn($mockNodeName);

		$this->mockShare
			->method('getShareType')
			->willReturn(IShare::TYPE_EMAIL);

		$this->mockShare
			->method('getNode')
			->willReturn($this->mockNode);

		$this->mockLogger->expects($this->exactly(1))
			->method('debug')
			->with(
				'share created for file ' . $mockNodeName,
			);

		$this->listener->handle($this->mockEvent);
	}
}
