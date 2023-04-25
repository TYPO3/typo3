.. include:: /Includes.rst.txt

.. _deprecation-100584-1681452843:

=======================================================
Deprecation: #100584 - GeneralUtility::linkThisScript()
=======================================================

See :issue:`100584`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript()`
has been marked as deprecated and should not be used any longer.

The method uses the super global :php:`$_GET` which should be avoided. Instead,
data should be retrieved via the PSR-7 :php:`ServerRequestInterface`.

Controllers should typically create URLs using the :php:`\TYPO3\CMS\Backend\Routing\UriBuilder`.


Impact
======

Using the method triggers a deprecation level log entry in TYPO3 v12, the
method will be removed with TYPO3 v13.


Affected installations
======================

The method was typically used in backend context: Extensions with own
backend modules may be affected. The extension scanner finds usages
with a strong match.


Migration
=========

:php:`linkThisScript()` was typically used when a link to some view is
created that should return back to the current view later.

Controllers usually "know" the route a view should return to and the relevant
GET parameters.

A transition could look like this:

.. code-block:: php

    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    $queryParams = $request->getQueryParams();
    $url = $uriBuilder->buildUriFromRoute(
        'my_route',
        [
            'table' => $queryParams['table'] ?? '',
            'uid' => (int)($queryParams['uid'] ?? 0),
        ]
    );


.. index:: Backend, PHP-API, FullyScanned, ext:core
