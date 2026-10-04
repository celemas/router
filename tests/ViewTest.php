<?php

declare(strict_types=1);

namespace Celema\Router\Tests;

use Celema\Router\Exception\RuntimeException;
use Celema\Router\Route;
use Celema\Router\Tests\Fixtures\TestAttribute;
use Celema\Router\Tests\Fixtures\TestAttributedController;
use Celema\Router\Tests\Fixtures\TestAttributeDiff;
use Celema\Router\Tests\Fixtures\TestAttributeExt;
use Celema\Router\Tests\Fixtures\TestCallableAttribute;
use Celema\Router\Tests\Fixtures\TestController;
use Celema\Router\Tests\Fixtures\TestControllerWithRequest;
use Celema\Router\Tests\Fixtures\TestControllerWithRequestAndRoute;
use Celema\Router\Tests\Fixtures\TestControllerWithRoute;
use Celema\Router\Tests\Fixtures\TestMiddleware1;
use Celema\Router\Tests\Fixtures\TestMiddleware2;
use Celema\Router\Tests\Fixtures\TestRequestDependency;
use Celema\Router\Tests\Fixtures\TestThrowingClass;
use Celema\Router\Tests\Fixtures\TestUnresolvableClass;
use Celema\Router\View;
use Error;
use GdImage;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;

class ViewTest extends TestCase
{
	public function testAttribute(): void
	{
		$route = Route::any('/', #[TestAttribute] static fn() => 'celema')->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		$this->assertInstanceOf(TestAttribute::class, $view->attributes()[0]);
	}

	public function testAttributeWithCallAttribute(): void
	{
		$route = Route::any('/', #[TestCallableAttribute] static fn() => 'celema')->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route), null);

		/** @var TestCallableAttribute $attr */
		$attr = $view->attributes(TestCallableAttribute::class)[0];
		$this->assertTrue($attr->initialized);
	}

	public function testClosure(): void
	{
		$route = Route::any('/', static fn() => 'celema')->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		$this->assertSame('celema', (string) $view->execute($this->request())->getBody());
	}

	public function testFunction(): void
	{
		$route = Route::any('/{name}', 'testViewWithAttribute')->after($this->renderer());
		$view = new View($this->routeMatch($route, '/symbolic'), null);

		$this->assertSame('symbolic', (string) $view->execute($this->request())->getBody());
		$this->assertInstanceOf(TestAttribute::class, $view->attributes()[0]);
	}

	public function testControllerClassMethod(): void
	{
		$route = Route::any('/', [TestController::class, 'textView'])->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		$this->assertSame('text', (string) $view->execute($this->request())->getBody());
		$this->assertInstanceOf(TestAttribute::class, $view->attributes()[0]);
	}

	public function testControllerGroupMethodString(): void
	{
		$route = Route::any('/', 'textView')
			->controller(TestController::class)
			->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		$this->assertSame('text', (string) $view->execute($this->request())->getBody());
	}

	public function testControllerObjectMethod(): void
	{
		$controller = new TestController();
		$route = Route::any('/', [$controller, 'textView'])->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		$this->assertSame('text', (string) $view->execute($this->request())->getBody());
		$this->assertInstanceOf(TestAttribute::class, $view->attributes()[0]);
	}

	public function testInvokableClass(): void
	{
		$route = Route::any('/', 'Celema\Router\Tests\Fixtures\TestInvokableClass')->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route), null);

		$this->assertSame('Invokable', (string) $view->execute($this->request())->getBody());
	}

	public function testNonexistentControllerView(): void
	{
		$this->throws(
			RuntimeException::class,
			'Route action method not found: ' . TestController::class . '::nonexistentView',
		);

		$route = Route::any('/', [TestController::class, 'nonexistentView'])->after($this->renderer());
		$view = new View($this->routeMatch($route), null);
		$view->execute($this->request());
	}

	public function testNonexistentController(): void
	{
		$this->throws(
			RuntimeException::class,
			'Route controller not found: ' . NonexisitentTestController::class,
		);

		$route = Route::any('/', [NonexisitentTestController::class, 'nonexistentView'])->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route), null);
		$view->execute($this->request());
	}

	public function testBareMethodStringWithoutController(): void
	{
		$this->throws(
			RuntimeException::class,
			"Route action string is not callable: textView. Use a callable, [Controller::class, 'method'], "
				. 'an invokable controller class, or a controller group.',
		);

		$route = Route::any('/', 'textView')->after($this->renderer());
		$view = new View($this->routeMatch($route), null);
		$view->execute($this->request());
	}

	/** @param array<array-key, mixed> $action */
	#[DataProvider('invalidControllerActions')]
	public function testInvalidControllerActionArray(array $action): void
	{
		$this->throws(RuntimeException::class, "Controller actions must use [Controller::class, 'method'].");

		$route = new Route('/', $action);
		$view = new View($this->routeMatch($route), null);
		$view->execute($this->request());
	}

	/** @return iterable<string, array{array<array-key, mixed>}> */
	public static function invalidControllerActions(): iterable
	{
		yield 'empty' => [[]];
		yield 'empty controller' => [['', 'arrayView']];
		yield 'empty method' => [[TestController::class, '']];
	}

	public function testNonCallableControllerMethod(): void
	{
		$this->throws(
			RuntimeException::class,
			'Route action method is not callable: ' . TestController::class . '::privateView',
		);

		$route = Route::any('/', [TestController::class, 'privateView'])->after($this->renderer());
		$view = new View($this->routeMatch($route), null);
		$view->execute($this->request());
	}

	public function testControllerWithRequestInConstructor(): void
	{
		$request = $this->request();
		$route = Route::any('/', [TestControllerWithRequest::class, 'requestOnly'])->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route), null);

		$this->assertSame($request::class, (string) $view->execute($request)->getBody());
	}

	public function testControllerWithRouteInConstructor(): void
	{
		$route = Route::any('/', [TestControllerWithRoute::class, 'routeOnly'])->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route), null);

		$this->assertSame($route::class, (string) $view->execute($this->request())->getBody());
	}

	public function testControllerWithRequestRouteAndParamInConstructor(): void
	{
		$request = $this->request();
		$route = Route::any(
			'/{param}',
			[TestControllerWithRequestAndRoute::class, 'requestAndRoute'],
		)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/celema'), null);

		$this->assertSame(
			$request::class . $route::class . 'celema',
			(string) $view->execute($request)->getBody(),
		);
	}

	public function testViewWithRouteParams(): void
	{
		$request = $this->request();
		$route = Route::any(
			'/{string}/{float}-{int}',
			[TestControllerWithRequest::class, 'routeParams'],
		)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/symbolic/7.13-23'), null);

		$this->assertSame(
			'{"string":"symbolic","float":7.13,"int":23,"request":"Laminas\\\\Diactoros\\\\ServerRequest"}',
			(string) $view->execute($request)->getBody(),
		);
	}

	public function testViewWithDefaultValueParams(): void
	{
		// Should overwrite the default value
		$route = Route::any(
			'/{string}/{int}',
			[TestController::class, 'routeDefaultValueParams'],
		)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/symbolic/17'), null);

		$this->assertSame(
			'{"string":"symbolic","int":17}',
			(string) $view->execute($this->request())->getBody(),
		);

		// Should use the default value
		$route = Route::any('/{string}', [TestController::class, 'routeDefaultValueParams'])->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route, '/symbolic'), null);

		$this->assertSame(
			'{"string":"symbolic","int":13}',
			(string) $view->execute($this->request())->getBody(),
		);
	}

	public function testViewWithWrongRouteParams(): void
	{
		$this->throws(
			RuntimeException::class,
			"View parameters cannot be resolved. Details: Type 'string' is not a class or interface. Source: \n"
				. TestControllerWithRequest::class
				. '::routeParams(..., string $string, ...)',
		);

		$route = Route::any(
			'/{wrong}/{param}',
			[TestControllerWithRequest::class, 'routeParams'],
		)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/symbolic/test'), null);
		$view->execute($this->request());
	}

	public function testViewWithWrongTypeForIntParam(): void
	{
		$this->throws(RuntimeException::class, "View parameters cannot be resolved. Details: Cannot cast 'int' to int");

		$route = Route::any(
			'/{string}/{float}-{int}',
			[TestControllerWithRequest::class, 'routeParams'],
		)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/symbolic/7.13-wrong'), null);
		$view->execute($this->request());
	}

	public function testViewWithWrongTypeForFloatParam(): void
	{
		$this->throws(
			RuntimeException::class,
			"View parameters cannot be resolved. Details: Cannot cast 'float' to float",
		);

		$route = Route::any(
			'/{string}/{float}-{int}',
			[TestControllerWithRequest::class, 'routeParams'],
		)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/symbolic/wrong-13'), null);
		$view->execute($this->request());
	}

	public function testViewWithUnsupportedParamBubblesUnderlyingError(): void
	{
		$this->throws(Error::class, 'GdImage');

		$route = Route::any('/{name}', static fn(GdImage $name) => $name)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/symbolic'), null);
		$view->execute($this->request());
	}

	public function testViewWithThrowingParamAndDefaultBubblesException(): void
	{
		$this->throws(LogicException::class, 'constructor failed');

		$route = Route::any('/', static fn(?TestThrowingClass $param = null) => $param)->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route), null);
		$view->execute($this->request());
	}

	public function testAttributeFilteringCallableView(): void
	{
		$route = Route::any(
			'/',
			#[TestAttribute, TestAttributeExt, TestAttributeDiff] static fn() => 'celema',
		)->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		$this->assertSame(3, count($view->attributes()));
		$this->assertSame(2, count($view->attributes(TestAttribute::class)));
		$this->assertSame(1, count($view->attributes(TestAttributeExt::class)));
		$this->assertSame(1, count($view->attributes(TestAttributeDiff::class)));
		$this->assertInstanceOf(TestAttributeDiff::class, $view->attributes(TestAttributeDiff::class)[0]);
	}

	public function testControllerViewIncludesClassAttributes(): void
	{
		$route = new Route('/', [TestAttributedController::class, 'view']);
		$view = new View($this->routeMatch($route), null);
		$attributes = $view->attributes();

		$this->assertCount(2, $attributes);
		$this->assertInstanceOf(TestAttributeDiff::class, $attributes[0]);
		$this->assertInstanceOf(TestAttribute::class, $attributes[1]);
	}

	public function testAttributeFilteringControllerView(): void
	{
		$route = new Route('/', [TestController::class, 'arrayView']);
		$view = new View($this->routeMatch($route), null);

		$this->assertSame(3, count($view->attributes()));
		$this->assertSame(2, count($view->attributes(TestAttribute::class)));
		$this->assertSame(1, count($view->attributes(TestAttributeExt::class)));
		$this->assertSame(1, count($view->attributes(TestAttributeDiff::class)));
	}

	public function testViewWithUnionTypeParam(): void
	{
		$route = Route::any('/', static fn(string|int $param) => $param)->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		try {
			$view->execute($this->request());
			$this->fail('RuntimeException was not thrown');
		} catch (RuntimeException $e) {
			$this->assertStringStartsWith(
				'View parameters cannot be resolved. Details: Autowiring does not support union or '
					. "intersection types. Source: \n",
				$e->getMessage(),
			);
			$this->assertStringContainsString('{closure:', $e->getMessage());
			$this->assertStringEndsWith('(..., string|int $param, ...)', $e->getMessage());
		}
	}

	public function testViewWithUntypedParam(): void
	{
		$route = Route::any('/', static fn($param) => $param)->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		try {
			$view->execute($this->request());
			$this->fail('RuntimeException was not thrown');
		} catch (RuntimeException $e) {
			$this->assertStringStartsWith(
				'View parameters cannot be resolved. Details: Autowired entities need to have typed '
					. "constructor parameters. Source: \n",
				$e->getMessage(),
			);
			$this->assertStringContainsString('{closure:', $e->getMessage());
			$this->assertStringEndsWith('(..., $param, ...)', $e->getMessage());
		}
	}

	public function testRouteParamNamedLikeClassTypedParamIsResolvedByType(): void
	{
		$request = $this->request();
		$route = Route::any(
			'/{request}',
			static fn(ServerRequestInterface $request) => $request::class,
		)->after($this->renderer());
		$view = new View($this->routeMatch($route, '/celema'), null);

		$this->assertSame($request::class, (string) $view->execute($request)->getBody());
	}

	public function testAutowiredDependencyReceivesCurrentRequest(): void
	{
		$request = $this->request()->withAttribute('marker', 'current');
		$route = Route::any(
			'/',
			static fn(TestRequestDependency $dependency) => $dependency->request->getAttribute('marker'),
		)->after($this->renderer());
		$view = new View($this->routeMatch($route), null);

		$this->assertSame('current', (string) $view->execute($request)->getBody());
	}

	public function testMiddlewareIncludesRouteAndAttributeMiddleware(): void
	{
		$routeMiddleware = new TestMiddleware2();
		$route = Route::any('/', #[TestMiddleware1] static fn() => 'celema')->middleware($routeMiddleware);
		$view = new View($this->routeMatch($route), null);
		$middleware = $view->middleware();

		$this->assertCount(2, $middleware);
		$this->assertSame($routeMiddleware, $middleware[0]);
		$this->assertInstanceOf(TestMiddleware1::class, $middleware[1]);
	}

	public function testViewWithUnresolvableParamAndDefault(): void
	{
		// When a param cannot be autowired but has a default value, the default should be used
		$route = Route::any('/', static fn(?TestUnresolvableClass $param = null) => $param)->after(
			$this->renderer(),
		);
		$view = new View($this->routeMatch($route), null);
		$response = $view->execute($this->request());

		// Default value (null) is used because TestUnresolvableClass can't be autowired
		$this->assertSame('', (string) $response->getBody());
	}
}
