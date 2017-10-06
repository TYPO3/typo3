.. include:: ../../Includes.txt

==============================================================================
Breaking: #82689 - Backend AbstractWizardController not extends AbstractModule
==============================================================================

See :issue:`82689`

Description
===========

The PHP class :php:`TYPO3\CMS\Backend\Controller\Wizard\AbstractWizardController` no
longer extends class :php:`TYPO3\CMS\Backend\Module\AbstractModule`. This can be breaking
if wizard classes of extensions depend on method :php:`processRequest()` or the initialized
property :php:`moduleTemplate`.

PHP class :php:`TYPO3\CMS\Backend\Module\AbstractModule` has been deprecated and should not be used any longer.


Impact
======

* Using class :php:`AbstractModule` will throw a deprecation warning
* Extensions with wizards extending class :php:`AbstractWizardController`
  may fatal if they use property :php:`moduleTemplate`
* Extensions with wizards extending class :php:`AbstractWizardController`
  may fatal if they use they registered routes to method :php:`processRequest`


Affected Installations
======================

Installations with extensions with one of the above described patterns.


Migration
=========

Extensions that extend :php:`AbstractModule` should initialize :php:`moduleTemplate`
at an appropriate place instead. Instead of :php:`processRequest()`, routes should be
registered in an extensions :file:`Configuration/Backend/Routes.php` and
:file:`Configuration/Backend/AjaxRoutes.php`.

.. index:: Backend, PHP-API, PartiallyScanned