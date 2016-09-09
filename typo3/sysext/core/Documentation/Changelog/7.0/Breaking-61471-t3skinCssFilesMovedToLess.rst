
.. include:: ../../Includes.txt

=====================================================
Breaking: #61471 - EXT:t3skin CSS files moved to less
=====================================================

See :issue:`61471`

Description
===========

All CSS files in EXT:t3skin are moved to less files and handled by less CSS pre processor
and merged to a single CSS file.


Impact
======

Single CSS files can not be included anymore. This may result in broken layouts.


Affected installations
======================

A TYPO3 instance is affected if an extension loads single CSS files from EXT:t3skin. Backend modules of
extensions usually get CSS core stuff loaded by default, which will not be a problem. An extension is
only affected if single CSS files are explicitly referenced. This should be a rare case.


Migration
=========

Most simple solution is to copy over the "old" CSS file from an older instance. Directly including those
files from t3skin is discouraged. A better solution is to refactor the extension to use the full t3skin
CSS file and to overlay it with required changes in an own file.
