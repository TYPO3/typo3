.. include:: /Includes.rst.txt

===================================================
Deprecation: #88499 - BackendUtility::getViewDomain
===================================================

See :issue:`88499`

Description
===========

The static method :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getViewDomain()` has been marked
as deprecated, as it has been superseded by directly using the PageRouter of Site Handling.

Site Handling allows to generate proper frontend preview URLs the same way as TYPO3 Core does in
all other places, by calling the PageRouter of a Site object directly, so the workarounds are not
necessary anymore, making this method obsolete.


Impact
======

Calling :php:`BackendUtility::getViewDomain()` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installations with custom extensions that call this method.


Migration
=========

Substitute the method by directly detecting a site based on a given Page ID in the TYPO3 Backend.
Call the :php:`getRouter()` method on this Site object to create proper links to pages in TYPO3 Frontend.

Example with additional GET parameters:

.. code-block:: php

    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
    $url = $site->getRouter()->generateUri($pageId, ['type' => 13]);

.. index:: PHP-API, FullyScanned
