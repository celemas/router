<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use Celema\Router\Route;
use Psr\Http\Message\ServerRequestInterface as Request;

class TestControllerWithRequestAndRoute
{
	public function __construct(
		protected Route $route,
		protected Request $request,
		protected string $param,
	) {}

	public function requestAndRoute(): string
	{
		return $this->request::class . $this->route::class . $this->param;
	}
}
