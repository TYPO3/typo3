.. include:: /Includes.rst.txt

===============================================================
Breaking: #82914 - Remove TypoScript option page.bodyTagMargins
===============================================================

See :issue:`82914`

Description
===========

The TypoScript option :typoscript:`page.bodyTagMargins` has been removed.


Impact
======

Setting this option will have no effect anymore.


Affected Installations
======================

Any TYPO3 installation using this option.


Migration
=========

Move the configuration into your used CSS files.

.. index:: TypoScript, Frontend, NotScanned
