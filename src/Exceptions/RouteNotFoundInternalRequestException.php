<?php

declare(strict_types=1);

namespace thezombieguy\InternalRequest\Exceptions;

use Exception;

class RouteNotFoundInternalRequestException extends Exception
{
    public function __construct(string $routeName)
    {
        $message = "The route '{$routeName}' could not be found. Please use an existing route.";
        parent::__construct($message);
    }
}