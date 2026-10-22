.. include:: /Includes.rst.txt

.. _deprecation-88807:

===========================================================================
Deprecation: #88807 - AdminPanel InitializableInterface has been deprecated
===========================================================================

See :issue:`88807`

Description
===========

:php:`\TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface` has been deprecated in favor of the newly
introduced :php:`\TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface`.


Impact
======

Using :php:`\TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface` will trigger a deprecation message.


Affected Installations
======================

All instances that use :php:`\TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface` are affected.


Migration
=========

Switch to :php:`\TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface` instead:

- change method name `initializeModule` to `enrich`
- change return value to return an instance of
  :php:`\Psr\Http\Message\ServerRequestInterface`

.. index:: Frontend, PHP-API, PartiallyScanned, ext:adminpanel
