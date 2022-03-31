.. include:: /Includes.rst.txt

===========================================================================
Deprecation: #88807 - AdminPanel InitializableInterface has been deprecated
===========================================================================

See :issue:`88807`

Description
===========

`\TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface` has been deprecated in favor of the newly
introduced `\TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface`.


Impact
======

Using `\TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface` will trigger a deprecation message.


Affected Installations
======================

All instances that use `\TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface` are affected.


Migration
=========

Switch to `\TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface` instead:

- change method name `initializeModule` to `enrich`
- change return value to return an instance of
  :php:`\Psr\Http\Message\ServerRequestInterface`

.. index:: Frontend, PHP-API, PartiallyScanned, ext:adminpanel
