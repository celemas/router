<?php

declare(strict_types=1);

namespace Celema\Router\Tests\Fixtures;

use Celema\Router\After;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;

class TestAfterRendererText implements After
{
	public function __construct(
		protected ResponseFactoryInterface $responseFactory,
	) {}

	public function handle(mixed $data): Response
	{
		$response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'text/plain');
		$response->getBody()->write(print_r($data, return: true));

		return $response;
	}

	public function replace(After $handler): bool
	{
		return $handler instanceof TestAfterRendererText;
	}
}
