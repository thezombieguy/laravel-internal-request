<?php

declare(strict_types=1);

namespace TheZombieGuy\InternalRequest\Tests;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TheZombieGuy\InternalRequest\Exceptions\RouteNotFoundInternalRequestException;
use TheZombieGuy\InternalRequest\Services\InternalRequestService;

class InternalRequestServiceTest extends TestCase
{
    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        Route::get('test/{id}', static function ($id) {
            return \response()->json([
                'message' => 'Success',
                'id' => $id,
                'query' => \request()->query(),
            ]);
        })->name('test.route');

        Route::post('test/{id}', static function ($id) {
            return \response()->json([
                'message' => 'Created',
                'id' => $id,
                'input' => \request()->input(),
            ]);
        })->name('test.post.route');
    }

    /**
     * @throws \JsonException
     * @throws RouteNotFoundInternalRequestException
     */
    public function testInternalRequestWithParams(): void
    {
        $service = new InternalRequestService();

        $urlParams = ['id' => $this->faker->uuid];
        $queryParams = ['foo' => $this->faker->word];
        $headers = ['x-test-header' => $this->faker->word];

        $response = $service->request(
            'test.route',
            'GET',
            $urlParams,
            $queryParams,
            $headers
        );

        // Decode the JSON response
        $responseData = \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Assertions to check if the route was loaded correctly
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Success', $responseData['message']);
        $this->assertEquals($urlParams['id'], $responseData['id']);
        $this->assertEquals($queryParams['foo'], $responseData['query']['foo']);
    }

    /**
     * @throws \JsonException
     * @throws RouteNotFoundInternalRequestException
     */
    public function testPostToInternalRequestWithParams(): void
    {
        $service = new InternalRequestService();

        $urlParams = ['id' => $this->faker->uuid];
        $queryParams = ['foo' => $this->faker->word];
        $headers = ['x-test-header' => $this->faker->word];
        $bodyParams = ['bar' => $this->faker->word];

        $response = $service->request(
            'test.post.route',
            'POST',
            $urlParams,
            $queryParams,
            $headers,
            $bodyParams,
        );

        // Decode the JSON response
        $responseData = \json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Assertions to check if the route was loaded correctly
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Created', $responseData['message']);
        $this->assertEquals($urlParams['id'], $responseData['id']);
        $this->assertEquals($bodyParams['bar'], $responseData['input']['bar']);
    }

    public function testInternalRequestWithCallbacks(): void
    {
        $service = new InternalRequestService();

        $urlParams = ['id' => $this->faker->uuid];
        $queryParams = ['foo' => $this->faker->word];
        $headers = ['x-test-header' => $this->faker->word];

        $beforeCalled = false;
        $afterCalled = false;

        $service->setBeforeRequest(function () use (&$beforeCalled) {
            $beforeCalled = true;
        });

        $service->setAfterRequest(function () use (&$afterCalled) {
            $afterCalled = true;
        });

        $response = $service->request(
            'test.route',
            'GET',
            $urlParams,
            $queryParams,
            $headers
        );

        $this->assertTrue($beforeCalled);
        $this->assertTrue($afterCalled);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testThrowsCustomExceptionForNonExistentRoute(): void
    {
        // Create the service instance
        $service = new InternalRequestService();

        // Expect the custom exception to be thrown
        $this->expectException(RouteNotFoundInternalRequestException::class);

        // Optionally, you can assert the exception message or context
        $this->expectExceptionMessage("The route 'non.existent.route' could not be found");

        // Call the request method with a non-existent route
        $service->request('non.existent.route', 'GET');
    }

    public function testAllowsSuccessfulHttpStatusCodes(): void
    {
        Route::get('success-test', static function () {
            return response('', 201); // Created
        })->name('success.route');

        $service = new InternalRequestService();

        $response = $service->request('success.route', 'GET');

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testAllowsSuccessfulHttpStatusCodesWithNoContent(): void
    {
        Route::get('no-content-test', static function () {
            return response()->noContent() ;// Created
        })->name('no-content.route');

        $service = new InternalRequestService();

        $response = $service->request('no-content.route', 'GET');

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testThrowsExceptionForClientErrors(): void
    {
        Route::get('client-error-test', static function () {
            return response('Not Found', 404); // Client error
        })->name('client.error.route');

        $service = new InternalRequestService();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $service->request('client.error.route', 'GET');
    }

    public function testThrowsExceptionForServerErrors(): void
    {
        Route::get('server-error-test', static function () {
            return response('Internal Server Error', 500); // Server error
        })->name('server.error.route');

        $service = new InternalRequestService();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(500);

        $service->request('server.error.route', 'GET');
    }
}
