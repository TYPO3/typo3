.. include:: /Includes.rst.txt

===================================================
Feature: #89216 - PSR-18 HTTP Client Implementation
===================================================

See :issue:`89216`

Description
===========

Support for PSR-18_ HTTP Client has been added.

PSR-18 HTTP Client is intended to be used by PSR-15_ request handlers in order to perform HTTP
requests based on PSR-7_ message objects without relying on a specific HTTP client implementation.

PSR-18 consists of a client interfaces and three exception interfaces:

- :php:`\Psr\Http\Client\ClientInterface`
- :php:`\Psr\Http\Client\ClientExceptionInterface`
- :php:`\Psr\Http\Client\NetworkExceptionInterface`
- :php:`\Psr\Http\Client\RequestExceptionInterface`

Request handlers shall use dependency injection to retrieve the concrete implementation
of the PSR-18 HTTP client interface :php:`\Psr\Http\Client\ClientInterface`.


Impact
======

The PSR-18 HTTP Client interface is provided by `psr/http-client` and may be used as
dependency for services in order to perform HTTP requests using PSR-7 request objects.
PSR-7 request objects can be created with the PSR-17_ Request Factory interface.

Note: This does not replace the currently available Guzzle wrapper
:php:`\TYPO3\CMS\Core\Http\RequestFactory->request()`, but is available as a framework
agnostic, more generic alternative. The PSR-18 interface does not allow to pass request
specific guzzle options. But global options defined in :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']`
are taken into account as GuzzleHTTP is used as backend for this PSR-18 implementation.
The concrete implementations is internal and will be replaced by a native guzzle PSR-18
implementation once it is available.

Example usage
-------------

A middleware might need to request an external service in order to transform the response
into a new response. The PSR-18 HTTP client interface is used to perform the external
HTTP request. The PSR-17 Request Factory Interface is used to create the HTTP request that
the PSR-18 HTTP Client expects. The PSR-7 Response Factory is then used to create a new
response to be returned to the user. All off these interface implementations are injected
as constructor dependencies:

.. code-block:: php

    use Psr\Http\Client\ClientInterface;
    use Psr\Http\Message\RequestFactoryInterface;
    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;

    class ExampleMiddleware implements MiddlewareInterface
    {
        /** @var ResponseFactory */
        private $responseFactory;

        /** @var RequestFactory */
        private $requestFactory;

        /** @var ClientInterface */
        private $client;

        public function __construct(
            ResponseFactoryInterface $responseFactory,
            RequestFactoryInterface $requestFactory,
            ClientInterface $client
        ) {
            $this->responseFactory = $responseFactory;
            $this->requestFactory = $requestFactory;
            $this->client = $client;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            if ($request->getRequestTarget() === '/example') {
                $req = $this->requestFactory->createRequest('GET', 'https://api.external.app/endpoint.json')
                // Perform HTTP request
                $res = $this->client->sendRequest($req);
                // Process data
                $data = [
                    'content' => json_decode((string)$res->getBody());
                ];
                $response = $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json; charset=utf-8');
                $response->getBody()->write(json_encode($data));
                return $response;
            }
            return $handler->handle($request);
        }
    }


.. _PSR-18: https://www.php-fig.org/psr/psr-18/
.. _PSR-17: https://www.php-fig.org/psr/psr-17/
.. _PSR-15: https://www.php-fig.org/psr/psr-15/
.. _PSR-7: https://www.php-fig.org/psr/psr-7/

.. index:: PHP-API, ext:core
