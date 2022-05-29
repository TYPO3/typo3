.. include:: /Includes.rst.txt

=====================================================
Deprecation: #94228 - Extbase request getRequestUri()
=====================================================

See :issue:`94228`

Description
===========

To further prepare Extbase towards PSR-7 compatible requests, the
Extbase :php:`TYPO3\CMS\Extbase\Mvc\Request` has to be streamlined.

Method :php:`getRequestUri()` has been deprecated and shouldn't be
used any longer.


Impact
======

Using the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extbase based extensions may use this method. The extension scanner
will find usages as weak match.


Migration
=========

When :php:`getRequestUri()` is called in extensions, the same information
can be retrieved from the native PSR-7 request. At the moment, this is usually
only available using :php:`$GLOBALS['TYPO3_REQUEST']`, but this will change
when the Extbase request is compatible with PSR-7 ServerRequestInterface.
A substitution looks like this for now:

.. code-block:: php

    // @todo Adapt this example as soon as Extbase Request implements ServerRequestInterface
    $request = $GLOBALS['TYPO3_REQUEST'];
    $normalizedParams = $request->getAttribute('normalizedParams');
    $requestUrl = $normalizedParams->getRequestUrl();


.. index:: PHP-API, FullyScanned, ext:extbase
