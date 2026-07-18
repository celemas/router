<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

class TestInvokableClass
{
	public function __invoke()
	{
		return 'Invokable';
	}
}
