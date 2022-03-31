.. include:: /Includes.rst.txt

====================================================
Breaking: #92118 - TCA ctrl thumbail setting dropped
====================================================

See :issue:`92118`

Description
===========

The TCA setting :php:`$GLOBALS['TCA'][$aTableName]['ctrl']['thumbnail']` has been dropped.


Impact
======

Setting the control field for a custom table has no effect anymore.


Affected Installations
======================

The setting has been used in the list module for tables with image fields to render a preview
of attached images. It has been used for :php:`tt_content` in core versions until TYPO3 v7.
There are probably not many extensions using the setting. The list module will
no longer show preview images for rendered rows.


Migration
=========

Drop this setting during extension clean up. The setting is simply ignored, no PHP error will be thrown.

.. index:: TCA, NotScanned, ext:recordlist
