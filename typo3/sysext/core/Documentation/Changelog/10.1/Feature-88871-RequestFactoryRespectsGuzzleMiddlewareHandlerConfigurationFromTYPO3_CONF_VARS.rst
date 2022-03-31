.. include:: /Includes.rst.txt

=============================================================
Feature: #88871 - Handle middleware handler in RequestFactory
=============================================================

See :issue:`88871`

Description
===========

Guzzle offers the possibility to register custom middleware handlers during the client initialization.
With this feature it is now possible to define those custom handlers in :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']` as an array.
The :php:`\TYPO3\CMS\Core\Http\RequestFactory` builds a handler stack based on the
:php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']` array and injects it into the created client.

Impact
======

The default handler stack (guzzle defaults) will be extended and not overwritten.

Example:
--------

.. code-block:: php

   # Add custom middleware to default Guzzle handler stack
   $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'][] =
      (\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\ACME\Middleware\Guzzle\CustomMiddleware::class))->handler();
   $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'][] =
      (\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\ACME\Middleware\Guzzle\SecondCustomMiddleware::class))->handler();

.. index:: PHP-API, ext:core
