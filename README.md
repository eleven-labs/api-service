# API Service

[![Build Status](https://travis-ci.org/eleven-labs/api-service.svg?branch=master)](https://travis-ci.org/eleven-labs/api-service)
[![Code Coverage](https://scrutinizer-ci.com/g/eleven-labs/api-service/badges/coverage.png)](https://scrutinizer-ci.com/g/eleven-labs/api-service/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/eleven-labs/api-service/badges/quality-score.png)](https://scrutinizer-ci.com/g/eleven-labs/api-service/)

This component read API service descriptions written 
in [OpenAPi/Swagger 2.0](https://github.com/OAI/OpenAPI-Specification) 
in order serialize requests, and parse responses into easy to use model structures.

## Dependencies

This component rely on well known interfaces that discribe:

- An HTTP client, using interfaces provided by [HTTPlug](http://httplug.io/)
- HTTP Messages, using the [PSR-7: HTTP message interfaces](http://www.php-fig.org/psr/psr-7/)
- Cache (used to cache schema files), using the [PSR-6: Caching Interface](http://www.php-fig.org/psr/psr-6/)

## Installation

This library can be easily installed via composer

```
composer require eleven-labs/api-service
```

## Usage

In order to consume an API, you will need to write an API service description.

As of now, we only support swagger files, but we plan to support RAML 1.0 and Api Blueprint in
a near future.

For standalone projects usage of the provided builder is encouraged:

```php
<?php
$apiService = ElevenLabs\Api\Service\ApiServiceBuilder::create()->build('file:///absolute/path/to/your/schema.json');

$operationId = 'getSomething';
$parameters = ['foo' => 'bar'];

// A Synchronous Request

/** @var \ElevenLabs\Api\Service\Resource\Resource $resource */
$resource = $apiService->call($operationId, $parameters);

// An Asynchronous Request

$promise = $apiService->callAsync($operationId, $parameters);
/** @var \ElevenLabs\Api\Service\Resource\Resource $resource */
$resource = $promise->wait();
```

**Important**: You **MUST** provide an **[operationId](http://swagger.io/specification/#operationId)** for each 
paths described in your swagger file.

### Builder Dependencies

You will need one of the [HttpClient adapter](http://docs.php-http.org/en/latest/clients.html) 
provided by HttPlug and the [HTTP Client Discovery Service](http://docs.php-http.org/en/latest/discovery.html?highlight=discovery).

```bash
# install the discivery service
composer require php-http/discovery
# install one of the http client adapter (here, we use the guzzle6 adapter)
composer require php-http/guzzle6-adapter
```

## Builder configuration

The builder provide additional methods to fine tune your API Service:

- `withCacheProvider(CacheItemPoolInterface $cacheProvider)`

    > Cache the API service description using a [PSR-6: Cache Interface](http://www.php-fig.org/psr/psr-7/)
- `withHttpClient(HttpClient $httpClient)`
    
    > Provide an HttpClient implementing the `Http\Client\HttpClient` interface.  
    By default, it will use the `Http\Discovery\HttpClientDiscovery::find()` method
- `withMessageFactory(MessageFactory $messageFactory)`
    
    > Provide a MessageFactory implementing the `Http\Message\MessageFactory` interface.  
    By default, it will use the `Http\Discovery\MessageFactoryDiscovery::find()` method
- `withUriFactory(UriFactory $uriFactory)`
    
    > Provide an UriFactory implementing the `Http\Message\UriFactory` interface.  
    By default, it will use the `Http\Discovery\UriFactory::find()` method
- `withSerializer(SerializerInterface $serializer)` 
    
    > Provide a Serializer.
    By default, it will use the Symfony Serializer.
- `withEncoder(EncoderInterface $encoder)`
    
    > Add an encoder to encode Request body and decode Response body.  
    By default, it register Symfony's `JsonEncoder` and `XmlEncoder`.
- `withPaginationProvider(PaginationProvider $paginatorProvide)`

    > When using the default `ResourceDenormalizer`, you can provide a pagination provider to add
    > pagination informations into `Collection` objects. Available implementations can be found in the 
    > `src/Pagination/Provider` folder. You can create your own by implementing 
    > the `ElevenLabs\Api\Service\Pagination\Provider\PaginationProviderInterface` interface.
- `withDenormalizer(NormalizerInterface $normalizer)` 

    > Add a denormalizer used to denormalize `Response` decoded body.  
    By default, it use the `ElevenLabs\Api\Service\Denormalizer\ResourceDenormalizer` that denormalize 
    a `Response` into a `Resource` object. A `Resource` object can be an `Item` or a `Collection`.
- `withBaseUri($baseUri)` 
    
    > Provide a base URI from which your API is exposed.  
    By default, it will use your the `schemes` key and `host` key defined in your API service description.
- `disableRequestValidation()`
    
    > Disable `Request` validation against your API service description.
    Enabled by default 
- `enableResponseValidation()`
    
    > Enable `Response` validation against your API service description.
    Disabled by default.
- `returnResponse()`
    
    > Return a PSR-7 Response when using `ApiService` `call()` and `callAsync()` methods instead of a denormalized object.
- `setDebug($bool)`
    
    > Enable debug mode.
    When enabled, it will expire the schema cache immediatly if a cache implementation is provided 
    using the `withCacheProvider(CacheItemPoolInterface $cacheProvider)` method.
    




