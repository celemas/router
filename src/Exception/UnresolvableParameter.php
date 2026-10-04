<?php

declare(strict_types=1);

namespace Celema\Router\Exception;

/**
 * Marks View's own parameter resolution failures, so that exceptions thrown
 * while constructing dependencies can bubble unchanged.
 *
 * @internal
 */
final class UnresolvableParameter extends RuntimeException {}
