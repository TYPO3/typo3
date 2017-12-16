
.. include:: ../../Includes.txt

=========================================================================
Breaking: #68178 - Drop $GLOBALS['TYPO3_CONF_VARS']['SYS]['form_enctype']
=========================================================================

See :issue:`68178`

Description
===========

Setting `$GLOBALS['TYPO3_CONF_VARS']['SYS]['form_enctype']` has been dropped without replacement.


Impact
======

Extensions that used this setting in forms may end up with an empty `enctype` attribute
in `HTML` `form` fields.


Affected Installations
======================

Extensions that rely on this variable being set.


Migration
=========

Substitute the variable access with `multipart/form-data`.
