<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use Attribute;
use Celema\Wire\Call;

#[Attribute]
#[Call('init')]
class TestCallableAttribute
{
	public bool $initialized = false;

	public function init(): void
	{
		$this->initialized = true;
	}
}
