
.. include:: /Includes.rst.txt

============================================
Breaking: #68020 - Dropped DisableBigButtons
============================================

See :issue:`68020`

Description
===========

The TSconfig option `mod.web_layout.disableBigButtons` has been dropped, setting it
to 0 has no effect anymore.


Impact
======

The option is ignored and instances using this will not get the buttons rendered in
page module anymore.

These methods have been removed, but it is very unlikely an extension is affected:

* `TYPO3\CMS\Backend\View\PageLayoutView->linkRTEbutton()`
* `TYPO3\CMS\Backend\View\PageLayoutView->isRTEforField()`
* `TYPO3\CMS\Backend\View\PageLayoutView->getSpecConfForField()`


Affected Installations
======================

Instances that had User / Page TSconfig with this option may have a slightly
different Web -> Page view.


.. index:: PHP-API, Backend, TSConfig
