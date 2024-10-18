<?php

declare(strict_types=1);

/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\IonosProcesses\Tests\AppInfo;

use OC\AppFramework\Bootstrap\Coordinator;
use OCA\IonosProcesses\AppInfo\Application;
use OCA\IonosProcesses\Listener\ShareCreatedEventListener;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Share\Events\ShareCreatedEvent;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase {
	private Application $app;
	private IRegistrationContext $context;

	protected function setUp(): void {
		parent::setUp();

		$this->app = new Application();
		$coordinator = \OC::$server->get(Coordinator::class);
		$this->app->register($coordinator->getRegistrationContext()->for('nc_ionos_processes'));

		$this->context = $this->createMock(IRegistrationContext::class);
	}

	public function testBootCallsInjectFn(): void {
		$mockBootContext = $this->getMockBuilder(IBootContext::class)
			->onlyMethods([
				// Under test
				'injectFn',
				// Required by abstract base class
				'getAppContainer',
				'getServerContainer',
			])
			->getMock();

		$mockBootContext->expects($this->exactly(1))
			->method('injectFn')
			->with([
				$this->app,
				'registerEventsScripts',
			]);

		$this->app->boot($mockBootContext);
	}

	public function testRegisterEventsScripts(): void {
		$mockDispatcher = $this->getMockBuilder(IEventDispatcher::class)
			->onlyMethods([
				// Under test
				'addServiceListener',
				// Required by abstract base class
				'addListener',
				'hasListeners',
				'removeListener',
				'dispatch',
				'dispatchTyped',
			])
			->getMock();

		$mockDispatcher->expects($this->exactly(1))
			->method('addServiceListener')
			->with(
				ShareCreatedEvent::class,
				ShareCreatedEventListener::class,
			);

		$this->app->registerEventsScripts($mockDispatcher);
	}
}
