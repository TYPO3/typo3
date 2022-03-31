.. include:: /Includes.rst.txt

==============================================================================================
Deprecation: #83092 - Replace @transient with @TYPO3\\CMS\\Extbase\\Annotation\\ORM\\Transient
==============================================================================================

See :issue:`83092`

Description
===========

The :php:`@transient` annotation has been deprecated and must be replaced with the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\ORM\Transient`.


Impact
======

From version 9.0 on, :php:`@transient` is deprecated and will be removed in version 10.


Affected Installations
======================

All extensions that use :php:`@transient`


Migration
=========

Use :php:`@TYPO3\CMS\Extbase\Annotation\ORM\Transient` instead.

.. index:: PHP-API, ext:extbase, FullyScanned
