<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use LogicException;

final class TestThrowingClass
{
	public function __construct()
	{
		throw new LogicException('constructor failed');
	}
}
