<?php

declare(strict_types=1);

namespace thezombieguy\InternalRequest\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request as RequestFacade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InternalRequestService
{
    public function request(
        string $routeName,
        string $method = 'GET',
        array $urlParams = [],
        array $queryParams = [],
        array $headers = [],
    ): Response
    {
        $url = \route($routeName, $urlParams);

        $request = RequestFacade::create($url, $method, $queryParams);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $originalRequest = \request(); // Store the original request
        App::instance('request', $request); // Override the request instance in the container

        try {
            return $this->call($request);
        } finally {
            // Restore the original request instance
            App::instance('request', $originalRequest);
        }
    }

    protected function call(Request $request): Response
    {
        $response = App::handle($request);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new HttpException($response->getStatusCode(), $response->getContent());
        }

        return $response;
    }
}
