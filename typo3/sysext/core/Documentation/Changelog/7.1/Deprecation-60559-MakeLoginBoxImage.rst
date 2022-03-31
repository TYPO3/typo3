
.. include:: /Includes.rst.txt

=========================================
Deprecation: #60559 - makeLoginBoxImage()
=========================================

See :issue:`60559`

Description
===========

Method `TYPO3\CMS\Backend\Controller::makeLoginBoxImage()` has been marked as deprecated.


Impact
======

Backend login images are no longer rendered. The method body is empty and does not return rendered HTML any longer.


Affected installations
======================

The method was unused with default backend login screen for a long time already, an installation is only affected if a
3rd party extension was loaded that changed the default login screen and used `makeLoginBoxImage()` or the template marker
`LOGINBOX_IMAGE`.


Migration
=========

Free an affected 3rd party extension from using this method or unload the extension.


.. index:: PHP-API, Backend
