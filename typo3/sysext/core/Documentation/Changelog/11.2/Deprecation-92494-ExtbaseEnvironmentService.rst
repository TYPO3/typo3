.. include:: /Includes.rst.txt

================================================
Deprecation: #92494 - Extbase EnvironmentService
================================================

See :issue:`92494`

Description
===========

The extbase class :php:`TYPO3\CMS\Extbase\Service\EnvironmentService` is an API
for TYPO3's legacy constant :php:`TYPO3_MODE`. That constant has been marked as
deprecated in v11 and superseded by core API class
:php:`TYPO3\CMS\Core\Http\ApplicationType`, which relies on a PSR-7 request
to determine frontend or backend mode. The :php:`EnvironmentService` has now
been marked as deprecated as a logical follow-up to these works.


Impact
======

Using the class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Even though :php:`TYPO3\CMS\Extbase\Service\EnvironmentService` is :php:`@internal`, some extensions
may still rely on it. The extension scanner will find usages.


Migration
=========

Instances with extensions using that class should either make their code agnostic
to frontend or backend mode, or use :php:`ApplicationType`. Code examples can
be found in the `Changelog`_ file.

.. _`Changelog`: https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.0/Deprecation-92947-DeprecateTYPO3_MODEAndTYPO3_REQUESTTYPEConstants.html

.. index:: PHP-API, FullyScanned, ext:extbase
