
.. include:: ../../Includes.txt

==================================================================================
Breaking: #72889 - Removed RteHtmlParser htmlspecialchars() transformation options
==================================================================================

See :issue:`72889`

Description
===========

The TSconfig options `RTE.default.proc.dontHSC_rte` and `RTE.default.proc.dontUndoHSC_db` have been removed from the TYPO3 Core.


Impact
======

Setting these options has no effect anymore.


Affected Installations
======================

Any installation using these options for properly applying htmlspecialchars() to the RTE content when cleaning the HTML
input from an RTE and vice versa.


Migration
=========

Use `entryHtmlParser` and `exitHtmlParser` to apply htmlspecialchars while transforming content from the RTE or to the RTE.

.. index:: TSConfig, Backend, RTE
