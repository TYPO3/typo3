
.. include:: /Includes.rst.txt

================================================================
Deprecation: #72733 - Deprecate more methods of DocumentTemplate
================================================================

See :issue:`72733`

Description
===========

The following methods from `TYPO3\CMS\Backend\Template\DocumentTemplate` have
been marked as deprecated:

* `wrapInCData`
* `funcMenu`
* `getDragDropCode`
* `getTabMenu`
* `getVersionSelector`

The following method from `TYPO3\CMS\Backend\Template\ModuleTemplate` have
been marked as deprecated:

* `getVersionSelector`

Impact
======

Calling one of the aforementioned methods will trigger a deprecation log entry.


Affected Installations
======================

Instances with custom backend modules that use one of the aforementioned methods.


Migration
=========

Some replacements are available in the `\TYPO3\CMS\Backend\Template\ModuleTemplate` class.

Some other functionality is moved to separate classes when explicitly calling the version selector.

.. index:: PHP-API, Backend
