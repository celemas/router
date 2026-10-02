<?php

declare(strict_types=1);

namespace Celema\Router\Tests;

use Celema\Router\Route;
use Celema\Router\Tests\Fixtures\TestClass;
use Celema\Router\Tests\Fixtures\TestContainer;
use Celema\Router\Tests\Fixtures\TestRecorder;
use Celema\Router\Tests\Fixtures\TestRecordingController;
use Celema\Router\View;

final class ViewContainerTest extends TestCase
{
	public function testRegisteredParametersComeFromTheContainer(): void
	{
		$registered = new TestClass();
		$received = [];
		$route = Route::any('/', static function (TestClass $param) use (&$received): string {
			$received[] = $param;

			return 'ok';
		})->after($this->renderer());
		$container = new TestContainer([TestClass::class => $registered]);

		new View($this->routeMatch($route), $container)->execute($this->request());
		new View($this->routeMatch($route), $container)->execute($this->request());

		$this->assertSame([$registered, $registered], $received);
	}

	public function testUnregisteredParametersAreCreatedPerExecution(): void
	{
		$received = [];
		$route = Route::any('/', static function (TestClass $param) use (&$received): string {
			$received[] = $param;

			return 'ok';
		})->after($this->renderer());
		$container = new TestContainer();

		new View($this->routeMatch($route), $container)->execute($this->request());
		new View($this->routeMatch($route), $container)->execute($this->request());

		$this->assertCount(2, $received);
		$this->assertNotSame($received[0], $received[1]);
	}

	public function testControllersAreConstructedPerExecution(): void
	{
		$recorder = new TestRecorder();
		$route = Route::any('/', [TestRecordingController::class, 'action'])->after($this->renderer());
		$container = new TestContainer([TestRecorder::class => $recorder]);

		$response = new View($this->routeMatch($route), $container)->execute($this->request());
		new View($this->routeMatch($route), $container)->execute($this->request());

		$this->assertSame('recorded', (string) $response->getBody());
		$this->assertCount(2, $recorder->items);
		$this->assertNotSame($recorder->items[0], $recorder->items[1]);
	}
}
