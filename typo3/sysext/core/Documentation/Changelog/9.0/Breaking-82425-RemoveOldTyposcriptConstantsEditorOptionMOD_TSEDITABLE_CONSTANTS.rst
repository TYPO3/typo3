.. include:: ../../Includes.txt

==================================================================================================
Breaking: #82425 - Remove old typoscript constants editor option "###MOD_TS:EDITABLE_CONSTANTS###"
==================================================================================================

See :issue:`82425`

Description
===========

The special functionality on the constant comment `###MOD_TS:EDITABLE_CONSTANTS###`
has been dropped. This rarely used feature makes this comment a casual
comment without further added features.

A public property of PHP class has been dropped together with that removal:

* Property :php:`TYPO3\CMS\Core\TypoScript\ExtendedTemplateService->edit_divider`


Impact
======

The constants editor does not show constants before the comment `###MOD_TS:EDITABLE_CONSTANTS###` anymore as default constants.


Affected Installations
======================

All installations which have configured the constants comment `###MOD_TS:EDITABLE_CONSTANTS###`. Since this has been a widely
unknown feature, most instances should not be affected.

.. index:: Backend, TypoScript, PartiallyScanned
