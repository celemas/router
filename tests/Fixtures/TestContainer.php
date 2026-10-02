<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use Psr\Container\ContainerInterface;
use RuntimeException;

final class TestContainer implements ContainerInterface
{
	/** @param array<string, object> $entries */
	public function __construct(
		private readonly array $entries = [],
	) {}

	public function get(string $id): mixed
	{
		return $this->entries[$id] ?? throw new RuntimeException('Not registered: ' . $id);
	}

	public function has(string $id): bool
	{
		return isset($this->entries[$id]);
	}
}
