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
use OCP\AppFramework\Bootstrap\IRegistrationContext;
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

	public function testRegisterCallsRegisterEventListenerOnContext(): void {
		$this->context->expects($this->exactly(1))
			->method('registerEventListener')
			->with(
				ShareCreatedEvent::class,
				ShareCreatedEventListener::class,
			);

		$this->app->register($this->context);
	}
}
