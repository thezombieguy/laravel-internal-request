<?php

declare(strict_types=1);

namespace TheZombieGuy\InternalRequest\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use JsonException;
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
     * @throws RouteNotFoundInternalRequestException|JsonException
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

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new HttpException(
                $response->getStatusCode(),
                $response->getContent() ?: Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown error',
                null,
                [],
                $response->getStatusCode(),
            );
        }

        return $response;
    }

    /**
     * @param array<string, string> $urlParams
     * @param array<string, array<string>|string|null> $queryParams
     * @param array<string, array<string>|string|null> $headers
     * @param array<string, array<string>|string|null> $bodyParams
     * @throws RouteNotFoundInternalRequestException|JsonException
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

        // Build default server parameters
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $url,
            'CONTENT_TYPE' => $headers['Content-Type'] ?? 'application/json',
            'HTTP_ACCEPT' => $headers['Accept'] ?? 'application/json',
        ];

        // Create the request
        $request = Request::create(
            uri: $url,
            method: $method,
            content: $method === 'GET' ? null : \json_encode($bodyParams, JSON_THROW_ON_ERROR)
        );

        // Set headers
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        // Populate server variables
        foreach ($server as $key => $value) {
            $request->server->set($key, $value);
        }

        return $request;
    }
}
