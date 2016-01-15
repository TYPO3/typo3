================================================================
Deprecation: #72733 - Deprecate more methods of DocumentTemplate
================================================================

Description
===========

The following methods from ``TYPO3\CMS\Backend\Template\DocumentTemplate`` have been deprecated:

* ``wrapInCData``
* ``funcMenu``
* ``getDragDropCode``
* ``getTabMenu``
* ``getVersionSelector``


Impact
======

Calling one of the aforementioned methods will write an entry in the deprecation log.


Affected Installations
======================

Instances with custom backend modules that use one of the aforementioned methods.


Migration
=========

Some replacements are available in the ``\TYPO3\CMS\Backend\Template\ModuleTemplate`` class.