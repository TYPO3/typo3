.. include:: ../../Includes.txt

===========================================================
Breaking: #82915 - Remove TypoScript option page.stylesheet
===========================================================

See :issue:`82915`

Description
===========

The TypoScript option :ts:`page.stylesheet` has been removed.


Impact
======

Setting this option will have no effect anymore.


Affected Installations
======================

Any TYPO3 installation using this option.


Migration
=========

Use a configuration like :ts:`page.includeCSS.aFile = fileadmin/styles.css`

.. index:: Frontend, TypoScript, NotScanned
