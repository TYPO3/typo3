.. include:: ../../Includes.txt

====================================================
Deprecation: #85678 - $GLOBALS['TSFE']->altPageTitle
====================================================

See :issue:`85678`

Description
===========

The PHP property :php:`$GLOBALS['TSFE']->altPageTitle` has been marked as deprecated and will be removed with TYPO3 v10.


Impact
======

Installations using this property will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances using the property.


Migration
=========

Please use the new TitleTag API to alter the title tag.

.. index:: TypoScript, NotScanned
