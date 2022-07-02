.. include:: /Includes.rst.txt

=======================================================================
Deprecation: #94316 - HTTP header manipulating methods from HttpUtility
=======================================================================

See :issue:`94316`

Description
===========

In order to properly handle PSR-7 response objects, explicit :php:`die()`
or :php:`exit()` calls, as well as directly manipulating HTTP headers with
:php:`header()` should be avoided. Therefore following methods from
:php:`\TYPO3\CMS\Core\Utility\HttpUtility` have been marked as deprecated:

*  :php:`redirect()`
*  :php:`setResponseCode()`
*  :php:`setResponseCodeAndExit()`

The TYPO3 Core already provides a couple of possibilities to properly handle
such events in a PSR-7 conform way. Most of the time, a proper PSR-7 response
can be passed back to the call stack (request handler). Unfortunately there
might still be some places, inside the call stack, where it's not possible to
directly return a PSR-7 response. In such case, the
:php:`\TYPO3\CMS\Core\Http\PropagateResponseException`
could be thrown. It will automatically be caught by a PSR-15 middleware and the
given PSR-7 response will then directly be returned, making any :php:`die()`
or :php:`exit()` call obsolete.

The usage is as following:

.. code-block:: php

   // Before
   HttpUtility::redirect('https://example.com', HttpUtility::HTTP_STATUS_303);

   // After

   // Inject PSR-17 ResponseFactoryInterface
   public function __construct(ResponseFactoryInterface $responseFactory)
   {
      $this->responseFactory = $responseFactory
   }

   // Create redirect response
   $response = $this->responseFactory
      ->createResponse(303)
      ->withAddedHeader('location', 'https://example.com')

   // Return Response directly
   return $reponse;

   // or throw PropagateResponseException
   throw new PropagateResponseException($response);

.. note::

   Throwing exceptions for returning an immediate PSR-7 Response is considered
   as an intermediate solution only, until it's possible to return PSR-7
   responses in every relevant place. Therefore, the exception is marked
   as :php:`@internal` and will most likely vanish again in the future.

Impact
======

Calling one of those methods will trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

All TYPO3 installations calling those methods in custom code. The extension
scanner will find all usages as strong match.

Migration
=========

Replace all occurrences in custom extension code. Therefore, create a redirect
response with the PSR-17 ResponseFactoryInterface, and pass it back to the call
stack (request handler). In case, it's not possible to directly return a PSR-7
Response, you can use the :php:`\TYPO3\CMS\Core\Http\PropagateResponseException`
as an intermediate solution.

.. index:: PHP-API, FullyScanned, ext:core
