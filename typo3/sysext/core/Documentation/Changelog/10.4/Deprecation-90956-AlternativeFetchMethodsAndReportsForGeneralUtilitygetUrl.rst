.. include:: /Includes.rst.txt

========================================================================================
Deprecation: #90956 - Alternative fetch methods and reports for GeneralUtility::getUrl()
========================================================================================

See :issue:`90956`

Description
===========

The short-hand method :php:`GeneralUtility::getUrl()` provides a
fast way to fetch the contents of a local file or remote URL.

For Remote URLs, TYPO3 v8 provides a object-oriented (PSR-7 compatible) way by using
the :php:`RequestFactory->request($url, $method, $options)` API. Under the hood, the PHP library GuzzleHTTP is used,
which evaluates what best option (e.g. curl library) should handle
the download to TYPO3.

In general, it is recommended for any third-party extension developer to use either
PHP's native :php:`file_get_contents($file)` method or the :php:`RequestFactory->request()` method to fetch a PSR-7 ResponseInterface object.

The additional arguments in :php:`GeneralUtility::getUrl()` which allowed
to send headers to the content or just do a HEAD request, or find reports on why
the request did not succeed have been marked as deprecated.

PHP's native Exception Handling and the response object give enough insights already to load the HTTP headers as well, or even do HTTP `POST` requests.


Impact
======

Calling the method :php:`GeneralUtility::getUrl()` with more than one
method argument will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations using a third-party extension with :php:`GeneralUtility::getUrl()`
and more than one parameter in the call.


Migration
=========

Depending on the use-case of using the additional method parameters,
certain alternatives exist since TYPO3 v8 already:

Fetching the headers (as array) from a HTTP response:

.. code-block:: php

   $response = GeneralUtility::makeInstance(RequestFactory::class)->request($url);
   $allHeaders = $response->getHeaders();
   // Also see $response->getHeader($headerName) and $response->getHeaderLine($headerName)

Sending additional headers with the HTTP request:

.. code-block:: php

   $response = GeneralUtility::makeInstance(RequestFactory::class)->request($url, 'GET', ['headers' => ['accept' => 'application/json']]);

Finding additional information about the response:

.. code-block:: php

   $response = GeneralUtility::makeInstance(RequestFactory::class)->request($url, 'GET', ['headers' => ['accept' => 'application/json']]);
   if ($response->getStatusCode() >= 300) {
      $content = $response->getReasonPhrase();
   } else {
      $content = $response->getBody()->getContents();
   }

.. index:: PHP-API, FullyScanned, ext:core
