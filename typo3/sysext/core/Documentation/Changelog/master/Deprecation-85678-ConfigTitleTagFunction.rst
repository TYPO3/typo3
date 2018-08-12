.. include:: ../../Includes.txt

=============================================
Deprecation: #85678 - config.titleTagFunction
=============================================

See :issue:`85678`

Description
===========

The TypoScript option :ts:`config.titleTagFunction` has been marked as deprecated and will be removed with TYPO3 v10.


Impact
======

Installations using the option will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances using the option.


Migration
=========

Please use the new TitleTag API to alter the title tag.

.. index:: TypoScript, NotScanned
