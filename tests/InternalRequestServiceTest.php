<?php

declare(strict_types=1);

namespace thezombieguy\InternalRequest\Tests;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;
use thezombieguy\InternalRequest\InternalRequestServiceProvider;
use thezombieguy\InternalRequest\Services\InternalRequestService;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class InternalRequestServiceTest extends TestCase
{
    protected Generator $faker;

    protected function getPackageProviders($app): array
    {
        // If you have a service provider, add it here
        return [
            InternalRequestServiceProvider::class,
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();

        Route::get('test/{id}', static function ($id) {
            return \response()->json([
                'message' => 'Success',
                'id' => $id,
                'query' => \request()->query()
            ]);
        })->name('test.route');
    }

    /**
     * @throws \JsonException
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

    public function testInternalRequestWilCallbacks(): void
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
}