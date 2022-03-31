.. include:: /Includes.rst.txt

==============================================================================
Deprecation: #89468 - Deprecate injection of EnvironmentService in Web Request
==============================================================================

See :issue:`89468`

Description
===========

The EnvironmentService is not needed any longer in the Web
Request of Extbase, therefore the property and the injection
method of said property have been marked as deprecated.


Impact
======

As of TYPO3 11.0, the property :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response::$environmentService` will no longer exist. If the
environment service is needed in a subclass of :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response`, it needs to be injected
manually.


Affected Installations
======================

All installations that implement subclasses of :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response` and expect an instance of the
:php:`EnvironmentService` to be injected into :php:`\TYPO3\CMS\Extbase\Mvc\Web\Response::$environmentService`.


Migration
=========

The environment service needs to be injected manually in the subclass.

.. index:: PHP-API, NotScanned, ext:extbase
