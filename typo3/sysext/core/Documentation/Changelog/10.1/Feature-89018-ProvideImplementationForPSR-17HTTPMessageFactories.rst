.. include:: /Includes.rst.txt

==========================================================================
Feature: #89018 - Provide implementation for PSR-17 HTTP Message Factories
==========================================================================

See :issue:`89018`

Description
===========

Support for PSR-17_ HTTP Message Factories has been added.

PSR-17 HTTP Factories are intended to be used by PSR-15_ request handlers in order to create PSR-7_
compatible message objects.

PSR-17 consists of six factory interfaces:

- :php:`\Psr\Http\Message\RequestFactoryInterface`
- :php:`\Psr\Http\Message\ResponseFactoryInterface`
- :php:`\Psr\Http\Message\ServerRequestFactoryInterface`
- :php:`\Psr\Http\Message\StreamFactoryInterface`
- :php:`\Psr\Http\Message\UploadedFileFactoryInterface`
- :php:`\Psr\Http\Message\UriFactoryInterface`

Request handlers shall use dependency injection to use any of the available PSR-17 HTTP Factory interfaces.


Impact
======

PSR-17 HTTP Factory interfaces are provided by `psr/http-factory` and should be used as
dependencies for PSR-15 request handlers or services that need to create PSR-7 message objects.

It is discouraged to explicitly create PSR-7 instances of classes from the :php:`\TYPO3\CMS\Core\Http`
namespace (they are not public API). Use type declarations against PSR-17 HTTP Message Factory interfaces
and dependency injection instead.

Example usage
-------------

A middleware that needs to send a JSON response when a certain condition is met, uses the
PSR-17 response factory interface (the concrete TYPO3 implementation is injected as constructor
dependency) to create a new PSR-7 response object:

.. code-block:: php

    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;

    class StatusCheckMiddleware implements MiddlewareInterface
    {
        /** @var ResponseFactoryInterface */
        private $responseFactory;

        public function __construct(ResponseFactoryInterface $responseFactory)
        {
            $this->responseFactory = $responseFactory;
        }

        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            if ($request->getRequestTarget() === '/check') {
                $data = ['status' => 'ok'];
                $response = $this->responseFactory->createResponse()
                    ->withHeader('Content-Type', 'application/json; charset=utf-8');
                $response->getBody()->write(json_encode($data));
                return $response;
            }
            return $handler->handle($request);
        }
    }

.. _PSR-17: https://www.php-fig.org/psr/psr-17/
.. _PSR-15: https://www.php-fig.org/psr/psr-15/
.. _PSR-7: https://www.php-fig.org/psr/psr-7/

.. index:: PHP-API, ext:core
