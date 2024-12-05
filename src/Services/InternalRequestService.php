<?php

declare(strict_types=1);

namespace TheZombieGuy\InternalRequest\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use TheZombieGuy\InternalRequest\Exceptions\RouteNotFoundInternalRequestException;

final class InternalRequestService
{
    /**
     * @param callable|null $beforeRequest
     * @param callable|null $afterRequest
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function __construct(
        protected $beforeRequest = null,
        protected $afterRequest = null
    ) {
    }

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
     * @param array<string, array<string>|string|null> $queryParams
     * @param array<string, array<string>|string|null> $headers
     * @param array<string, array<string>|string|null> $bodyParams
     * @throws RouteNotFoundInternalRequestException
     */
    public function request(
        string $routeName,
        string $method = 'GET',
        array $urlParams = [],
        array $queryParams = [],
        array $headers = ['content-type' => 'application/json'],
        array $bodyParams = [],
    ): Response {
        $request = $this->buildRequest($routeName, $method, $urlParams, $queryParams, $headers, $bodyParams);

        if ($this->beforeRequest) {
            Event::dispatch('internal_request.before', $request);
            \call_user_func($this->beforeRequest);
        }

        try {
            return $this->call($request);
        } finally {
            if ($this->afterRequest) {
                Event::dispatch('internal_request.after', $request);
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
     * @param array<string, array<string>|string|null> $queryParams
     * @param array<string, array<string>|string|null> $headers
     * @param array<string, array<string>|string|null> $bodyParams
     * @throws RouteNotFoundInternalRequestException
     */
    private function buildRequest(
        string $routeName,
        string $method,
        array $urlParams,
        array $queryParams,
        array $headers,
        array $bodyParams,
    ): Request {
        try {
            $url = \route($routeName, $urlParams);
        } catch (RouteNotFoundException) {
            throw new RouteNotFoundInternalRequestException($routeName);
        }

        $url .= '?' . \http_build_query($queryParams);

        $request = Request::create($url, $method, $bodyParams);

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $request;
    }
}
