
.. include:: ../../Includes.txt

======================================================================
Breaking: #24900 - Remove $TYPO3_CONF_VARS[SYS][compat_version] option
======================================================================

See :issue:`24900`

Description
===========

The option `$TYPO3_CONF_VARS[SYS][compat_version]`, which was modified on update in the Install Tool wizard,
has been removed.

Any checks on `GeneralUtility::compat_version` are now made against the common constant `TYPO3_branch` instead of
the former TYPO3_CONF_VARS option.

Impact
======

Any usage of `$TYPO3_CONF_VARS[SYS][compat_version]` where the value is different than `TYPO3_branch` will result
in unexpected behaviour.

TypoScript conditions which check for older compat_version will have different behaviour now.

Affected installations
======================

Any installation where `$TYPO3_CONF_VARS[SYS][compat_version]` was not set to the currently running version
or where the value of compat_version was used to simulate behaviour of an older version.
E.g. TypoScript conditions or `GeneralUtility::compat_version` in extensions.


Migration
=========

Remove any direct usage of the option, and use the "compat_version" method within `GeneralUtility` as well as the
TypoScript condition "compat_version" which gives more accurate results.
