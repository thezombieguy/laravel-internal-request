# Laravel Internal Request

Sometimes, it's easier to request data from an existing route internally rather than make an external http call back to your application. this package allows just that.**

## Requirements

- PHP >= 8.0
- Laravel 9.x or 10.x

## Installation

```bash 
composer require thezombieguy/laravel-internal-request
```

## Usage

It's simple to use. First, instantiate the internal request service:

```php
$service = new InternalRequestService();
```

To call an internal route, use the request() method:

```php
$response = $service->request('valid.route.name');
```

By default, the service will send a get request to the internal route and return a standard response, similar to an external HTTP call.

## Passing Parameters
You can also pass HTTP methods, query parameters, URL parameters, and additional headers. Let's assume you have a route called `test.route` with the URL structure `/test/{id}`:

```php
$service = new InternalRequestService();

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

This will call the route `/test/123` with the url params and headers appropriately passed into the request.

## Hooking Into the Request Lifecycle

You can hook into actions before and after the request to execute custom logic. For example, if you're calling the internal route from a Livewire component, you'll want to avoid altering the original request object. 

In your AppServiceProvider or another service provider, you can bind the afterRequest to the service instance:
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
$service = \app(InternalRequestService::class);
$response = $service->request('your.route');
```
This example makes the internal request and, once the response is returned, restores the request object to its original state. You can also use `setBeforeRequest()` to execute code before the request is made. 

## License

This package is open-source software licensed under the MIT License.