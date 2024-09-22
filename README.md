# Laravel Internal Request

Sometimes, it's easier to request data from an existing route internally rather than make an external http call back to your application. this package allows just that.

## Installation

```bash 
composer require thezombieguy/laravel-internal-request
```

## Usage

Quite simple, just invoke the request services as follows:

```php
$service = new InternalRequestService();
```

When you want to call your internal route, make your request.

```php
$response = $service->request('valid.route.name');
```

By default, the service will request an internal route, and return a standard response similar to an Http call.

You can pass in methods, query params, url params, and extra headers as well. Let's assume you have a route called test.route with teh structure /test/{id}.

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

Additionally, you can hook into actions before and after the request. Here's an example. Let's ssay you are calling the internal route from Livewire. In order not to mess up Livewires main request object, you'll want to catch and restore the state of the request as it was after the handling of the request.

```php
$service = new InternalRequestService();
 
// Grab the current request state.
$originalRequest = \request();

$service->setAfterRequest(static function () use (&$originalRequest) {
    // Reset the request as it was before making the internal route request.
    App::instance('request', $originalRequest);
});

$response = $service->request('your.route');
```

This will make the request, and then once the request is processed and returned, reset the request back to its original state. The `setBeforeRequest()` method will execute code before you make the request.
