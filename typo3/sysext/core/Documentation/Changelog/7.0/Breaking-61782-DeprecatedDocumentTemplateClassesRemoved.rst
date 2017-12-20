
.. include:: ../../Includes.txt

==============================================================
Breaking: #61782 - deprecated DocumentTemplate classes removed
==============================================================

See :issue:`61782`

Description
===========

The following deprecated classes have been removed:

:code:`\TYPO3\CMS\Backend\Template\MediumDocumentTemplate`
:code:`\TYPO3\CMS\Backend\Template\SmallDocumentTemplate`
:code:`\TYPO3\CMS\Backend\Template\StandardDocumentTemplate`


Impact
======

Extensions that still use one of the removed classes for their backend module won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses one of the removed classes.


Migration
=========

Use :code:`\TYPO3\CMS\Backend\Template\DocumentTemplate` instead.


.. index:: PHP-API, Backend
