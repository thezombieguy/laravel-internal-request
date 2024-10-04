<?php

declare(strict_types=1);

namespace TheZombieGuy\InternalRequest\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request as RequestFacade;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use TheZombieGuy\InternalRequest\Exceptions\RouteNotFoundInternalRequestException;

final class InternalRequestService
{
    /**
     * @var callable|null
     */
    protected $beforeRequest;

    /**
     * @var callable|null
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
     * @param array<string, string> $urlParams
     * @param array<string, string> $queryParams
     * @param array<string, string> $headers
     * @throws RouteNotFoundInternalRequestException
     */
    public function request(
        string $routeName,
        string $method = 'GET',
        array $urlParams = [],
        array $queryParams = [],
        array $headers = ['content-type' => 'application/json'],
    ): Response {
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

    private function call(Request $request): Response
    {
        $response = App::handle($request);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new HttpException($response->getStatusCode(), (string) $response->getContent());
        }

        return $response;
    }

    /**
     * @param array<string, string> $urlParams
     * @param array<string, string> $queryParams
     * @param array<string, string> $headers
     * @throws RouteNotFoundInternalRequestException
     */
    private function buildRequest(
        string $routeName,
        string $method,
        array $urlParams,
        array $queryParams,
        array $headers
    ): Request {
        try {
            $url = \route($routeName, $urlParams);
        } catch (RouteNotFoundException) {
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
