<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use Psr\Http\Message\ServerRequestInterface as Request;

final class TestRequestDependency
{
	public function __construct(
		public readonly Request $request,
	) {}
}
