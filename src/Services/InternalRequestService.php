<?php

declare(strict_types=1);

namespace thezombieguy\InternalRequest\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request as RequestFacade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use thezombieguy\InternalRequest\Exceptions\RouteNotFoundInternalRequestException;

class InternalRequestService
{
    /**
     * @var callable
     */
    protected $beforeRequest;

    /**
     * @var callable
     */
    protected $afterRequest;

    public function setBeforeRequest(callable $callback): self
    {
        $this->beforeRequest = $callback;
        return $this;
    }

    public function setAfterRequest(callable $callback): self
    {
        $this->afterRequest = $callback;
        return $this;
    }

    /**
     * @throws RouteNotFoundInternalRequestException
     */
    public function request(
        string $routeName,
        string $method = 'GET',
        array $urlParams = [],
        array $queryParams = [],
        array $headers = [],
    ): Response
    {
        $request = $this->buildRequest($routeName, $method, $urlParams, $queryParams, $headers);

        if ($this->beforeRequest) {
            \call_user_func($this->beforeRequest);
        }

        try {
            return $this->call($request);
        } finally {
            if ($this->afterRequest) {
                \call_user_func($this->afterRequest);
            }
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

    /**
     * @throws RouteNotFoundInternalRequestException
     */
    protected function buildRequest($routeName, $method, $urlParams, $queryParams, $headers): Request
    {
        try {
            $url = \route($routeName, $urlParams);
        } catch (RouteNotFoundException $e) {
            throw new RouteNotFoundInternalRequestException($routeName);
        }

        /**
         * @var Request $request
         */
        $request = RequestFacade::create($url, $method, $queryParams);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $request;
    }
}
