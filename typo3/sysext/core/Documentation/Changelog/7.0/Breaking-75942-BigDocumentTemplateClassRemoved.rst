
.. include:: ../../Includes.txt

====================================================
Breaking: #75942 - BigDocumentTemplate class removed
====================================================

See :issue:`75942`

Description
===========

The following class has been removed:

:code:`\TYPO3\CMS\Backend\Template\BigDocumentTemplate`


Impact
======

Extensions that still use the removed class for their backend module won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed class.


Migration
=========

Use :code:`\TYPO3\CMS\Backend\Template\DocumentTemplate` instead.


.. index:: PHP-API, Backend
