<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

#[TestAttributeDiff]
final class TestAttributedController
{
	#[TestAttribute]
	public function view(): string
	{
		return 'attributed';
	}
}
