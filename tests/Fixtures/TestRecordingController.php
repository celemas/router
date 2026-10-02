<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

final class TestRecordingController
{
	public function __construct(TestRecorder $recorder)
	{
		$recorder->items[] = $this;
	}

	public function action(): string
	{
		return 'recorded';
	}
}
