<?php

declare(strict_types=1);

namespace Celema\Router;

/** @internal */
final class StaticRoute
{
	public function __construct(
		public readonly string $prefix,
		public readonly string $dir,
	) {}
}
