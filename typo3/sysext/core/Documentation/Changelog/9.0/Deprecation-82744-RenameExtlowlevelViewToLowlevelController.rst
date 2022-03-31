.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #82744 - Rename ext:lowlevel/View to lowlevel/Controller
=====================================================================

See :issue:`82744`

Description
===========

Two classes of extension lowlevel have been renamed:

* :php:`TYPO3\CMS\Lowlevel\View\ConfigurationView` to :php:`TYPO3\CMS\Lowlevel\Controller\ConfigurationController`
* :php:`TYPO3\CMS\Lowlevel\View\DatabaseIntegrityView` to :php:`TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController`


Impact
======

Old class usages will still work: Class aliases are in place for TYPO3 v9,
but will be removed in v10.


Affected Installations
======================

Extensions that call or instantiate the old class names. It is however rather unlikely
extensions depend on these controller classes directly. The extension scanner will find
any usages within extensions.


Migration
=========

Use new class names instead.

.. index:: Backend, PHP-API, FullyScanned, ext:lowlevel
