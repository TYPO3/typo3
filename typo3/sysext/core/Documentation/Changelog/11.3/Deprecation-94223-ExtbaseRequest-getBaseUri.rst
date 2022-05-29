.. include:: /Includes.rst.txt

===================================================
Deprecation: #94223 - Extbase Request->getBaseUri()
===================================================

See :issue:`94223`

Description
===========

To further prepare Extbase towards PSR-7 compatible requests, the
Extbase :php:`TYPO3\CMS\Extbase\Mvc\Request` has to be streamlined.

Method :php:`getBaseUri()` has been deprecated and shouldn't be
used any longer.


Impact
======

Using the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

This getter is probably used rather seldom in extensions since both
frontend and backend take care of base URI's in many cases already.
The extension scanner will find remaining usages.


Migration
=========

When :php:`getBaseUri()` is called in extensions, it is most likely
in a view related component. Since Fluid ViewHelpers currently still
don't receive an instance of the native PSR-7 request (which will change),
a typical substitution of this getter looks like this for now:

.. code-block:: php

    // @todo Adapt this example as soon as ViewHelpers receive a ServerRequestInterface
    $request = $GLOBALS['TYPO3_REQUEST'];
    $normalizedParams = $request->getAttribute('normalizedParams');
    $baseUri = $normalizedParams->getSiteUrl();

.. index:: PHP-API, FullyScanned, ext:extbase
