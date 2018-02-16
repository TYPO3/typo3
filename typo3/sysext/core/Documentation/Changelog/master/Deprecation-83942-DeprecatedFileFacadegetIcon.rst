.. include:: ../../Includes.txt

====================================================
Deprecation: #83942 - Deprecated FileFacade::getIcon
====================================================

See :issue:`83942`

Description
===========

The method :php:`\TYPO3\CMS\Filelist\FileFacade::getIcon` has been marked as deprecated.


Impact
======

Calling the method :php:`\TYPO3\CMS\Filelist\FileFacade::getIcon` will trigger a deprecation warning.


Affected Installations
======================

Instances with extensions using the method :php:`\TYPO3\CMS\Filelist\FileFacade::getIcon`


Migration
=========

Either use the ViewHelper :html:`<core:iconForResource resource="{file}" />` or
:php:`GeneralUtility::makeInstance(IconFactory::class)->getIconForResource($resource)` to render a resource-based icon.

.. index:: FAL, FullyScanned
