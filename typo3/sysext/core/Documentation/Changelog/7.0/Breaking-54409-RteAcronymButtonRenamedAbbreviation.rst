
.. include:: /Includes.rst.txt

==================================================================
Breaking: #54409 - RTE "acronym" button was renamed "abbreviation"
==================================================================

See :issue:`54409`

Description
===========

The "acronym" tag being deprecated, the RTE "acronym" button was renamed "abbreviation".
Accordingly, the RTE Acronym plugin was renamed Abbreviation.


Impact
======

The "abbreviation" button may not appear in the RTE toolbar, if configured as "acronym" in Page TSconfig, TCA special
configuration options and/or User TSconfig. Possible undefined PHP class errors.
Possible Javascript or file not found errors.


Affected installations
======================

An installation is affected if the "acronym" button was configured in Page TSconfig and/or User TSconfig.
An installation is affected if a 3rd party extension refers to the "acronym" button in TCA special configuration options.
An installation is affected if a 3rd party extension refers to class TYPO3\CMS\Rtehtmlarea\Extension\Acronym
An installation is affected if a 3rd party extension loads the JavaScript file of the Acronym plugin: EXT:rtehtmlarea/Resources/Public/Javascript/Plugins/Acronym.js


Migration
=========

There is no immediate impact on the RTE configuration in Page TSconfig and TCA special configuration options until
the automatic conversion of existing references to "acronym" is removed in TYPO3 CMS 8.0.
Intallations may run the upgrade wizard of the Install tool to migrate the contents of Page TSconfig, replacing "acronym" by "abbreviation".
Note that this string replacement will apply to all contents of PageTSconfig.
The migration of PageTSconfig may also be done manually.

User TSconfig must be modified to refer to "abbreviation" instead of "acronym".

Any affected 3rd party extension must be modified to refer to the "abbreviation" button rather than "acronym" in TCA special configuration options.
Any affected 3rd party extension must be modified to refer to class TYPO3\CMS\Rtehtmlarea\Extension\Abbreviation rather than TYPO3\CMS\Rtehtmlarea\Extension\Acronym
Any affected 3rd party extension must be modified to load EXT:rtehtmlarea/Resources/Public/Javascript/Plugins/Abbreviation.js rather than EXT:rtehtmlarea/Resources/Public/Javascript/Plugins/Acronym.js


.. index:: TSConfig, RTE, PHP-API, Backend
