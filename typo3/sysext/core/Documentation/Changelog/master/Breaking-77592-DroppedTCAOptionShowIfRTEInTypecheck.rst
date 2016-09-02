
.. include:: ../../Includes.txt

=============================================================
Breaking: #77592 - Dropped TCA option showIfRTE in type=check
=============================================================

Description
===========

The TCA setting `showIfRTE` for type=check is not evaluated anymore, and removed from the TCA on all fields.


Impact
======

All TCA columns having this option set will be shown at any time inside FormEngine. The option is removed from the final TCA
used inside TYPO3.

The TCA migration will throw a deprecation information when building the final TCA.


Affected Installations
======================

TYPO3 instances using old extensions which provide custom TCA configurations having this option set.


Migration
=========

Remove the setting from the TCA, and if still needed, use a custom display condition to achieve the same functionality.