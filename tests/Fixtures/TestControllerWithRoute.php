<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use Celema\Router\Route;

class TestControllerWithRoute
{
	public function __construct(
		protected Route $route,
	) {}

	public function routeOnly(): string
	{
		return $this->route::class;
	}
}
