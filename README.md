# Laravel Internal Request
**Easily request data from internal routes within your Laravel application without making external HTTP calls.**

![Tests](https://github.com/thezombieguy/laravel-internal-request/actions/workflows/ci.yml/badge.svg)
![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Downloads](https://img.shields.io/packagist/dt/thezombieguy/laravel-internal-request)



Sometimes, it's easier and more efficient to request data from an existing internal route rather than making an external HTTP call back to your application. This package provides a clean and simple interface to make internal route requests, saving overhead and improving performance.

## Requirements

- PHP >= 8.1
- Laravel 10.x or 11.x

## Installation

You can install this package via Composer. It's recommended for scenarios where you want to avoid external HTTP calls and instead make requests to your own application internally.

```bash 
composer require thezombieguy/laravel-internal-request
```

## Usage

To use the internal request service, resolve it from the Laravel service container:

```php
use TheZombieGuy\InternalRequest\Services\InternalRequestService;

$service = app(InternalRequestService::class);
```

To call an internal route, use the $service->request() method:

```php
$response = $service->request('valid.route.name');
```

By default, the service will send a get request to the internal route and return a standard response, similar to an external HTTP call.

## Passing Parameters
You can also pass HTTP methods, query parameters, URL parameters, and additional headers. Let's assume you have a route called `test.route` with the URL structure `/test/{id}`:

```php
use TheZombieGuy\InternalRequest\Services\InternalRequestService;

$service = app(InternalRequestService::class);

$urlParams = ['id' => 123];
$queryParams = ['foo' => 'bar'];
$headers = ['x-test-header' => 'something fun'];

$response = $service->request(
    'test.route',
    'GET',
    $urlParams,
    $queryParams,
    $headers
);
```

This will call the route `/test/123` with the url params and headers.

## Hooking Into the Request Lifecycle

You can hook into the request lifecycle to execute custom logic before or after the internal request is made. This is useful when calling the internal route from services like Livewire, where you may need to avoid altering the original request object.

### Example: Resetting Request State

In your `AppServiceProvider`, you can bind the `afterRequest` callback to restore the original request object after making an internal request:
```php
class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind the service and add the default afterRequest hook
        $this->app->bind(InternalRequestService::class, function () {
            $service = new InternalRequestService();

            // Define the default afterRequest closure
            $originalRequest = \request();
            $service->setAfterRequest(static function () use (&$originalRequest) {
                // Reset the request back to its original state
                App::instance('request', $originalRequest);
            });

            return $service;
        });
    }
}
```
And then just call your service in the code wherever you require.

```php
use TheZombieGuy\InternalRequest\Services\InternalRequestService;

$service = \app(InternalRequestService::class);
$response = $service->request('your.route');
```
The `setAfterRequest()` hook will restore the original request after the internal request has been completed. You can also use `setBeforeRequest()` to define logic that should execute before the internal request.
## License

This package is open-source software licensed under the [MIT License](LICENSE.md).
