<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use Celema\Router\Exception\RuntimeException;

final class TestRouterExceptionThrowingClass
{
	public function __construct()
	{
		throw new RuntimeException('dependency failed', 7);
	}
}
