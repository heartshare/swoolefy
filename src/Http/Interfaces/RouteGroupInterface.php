<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Swoolefy\Http\Interfaces;

/**
 * RouteGroup Interface
 *
 * @package Slim
 * @since   3.0.0
 */
interface RouteGroupInterface
{
    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern();

    /**
     * Prepend middleware to the group middleware collection
     *
     * @param callable|string $callable The callback routine
     *
     * @return RouteGroupInterface
     */
    public function add($callable);

}
