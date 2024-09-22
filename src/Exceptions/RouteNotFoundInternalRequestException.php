<?php

declare(strict_types=1);

namespace thezombieguy\InternalRequest\Exceptions;

use Exception;

class RouteNotFoundInternalRequestException extends Exception
{
    public function __construct(string $routeName)
    {
        $message = "The route '{$routeName}' could not be found";
        parent::__construct($message);
    }
}
