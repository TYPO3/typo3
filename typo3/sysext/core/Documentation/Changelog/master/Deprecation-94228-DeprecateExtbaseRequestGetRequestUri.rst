.. include:: ../../Includes.txt

===============================================================
Deprecation: #94228 - Deprecate extbase request getRequestUri()
===============================================================

See :issue:`94228`

Description
===========

To further prepare extbase towards PSR-7 compatible requests, the
extbase :php:`TYPO3\CMS\Extbase\Mvc\Request` has to be streamlined.

Method :php:`getRequestUri()` has been deprecated and shouldn't be
used any longer.


Impact
======

Using the method will log a deprecation message, it will be
removed with v12.



Affected Installations
======================

Extbase based extensions may use this method. The extension scanner
will find usages as weak match.


Migration
=========

When :php:`getRequestUri()` is called in extensions, the same information
can be retrieved from the native PSR-7 request. At the moment, this is usually
only available using :php:`$GLOBALS['TYPO3_REQUEST']`, but this will change
when the extbase request is compatible with PSR-7 ServerRequestInterface.
A substitution looks like this for now:

.. code-block:: php

    // @todo Adapt this example as soon as extbase Request implements ServerRequestInterface
    $request = $GLOBALS['TYPO3_REQUEST'];
    /** @var NormalizedParams $normalizedParams */
    $normalizedParams = $request->getAttribute('normalizedParams');
    $requestUrl = $normalizedParams->getRequestUrl();


.. index:: PHP-API, FullyScanned, ext:extbase
