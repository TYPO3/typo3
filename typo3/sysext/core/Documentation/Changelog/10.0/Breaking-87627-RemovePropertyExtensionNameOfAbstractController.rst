.. include:: /Includes.rst.txt

======================================================================
Breaking: #87627 - Remove Property extensionName of AbstractController
======================================================================

See :issue:`87627`

Description
===========

:php:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController::$extensionName`
has been removed and is no longer available in subclasses of
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController`, i.e.
:php:`\TYPO3\CMS\Extbase\Mvc\Controller\ActionController` and their derivates.


Impact
======

Accessing the missing property :php:`$extensionName` will throw a fatal error.


Affected Installations
======================

All installations that read from :php:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController::$extensionName`.


Migration
=========

The extension name is set in and available through the request object that is available in the controller.
See :php:`\TYPO3\CMS\Extbase\Mvc\Controller\AbstractController::$request` and :php:`\TYPO3\CMS\Extbase\Mvc\Request::getControllerExtensionName()`
for more information.

.. index:: PHP-API, NotScanned, ext:extbase
