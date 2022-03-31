
.. include:: /Includes.rst.txt

================================================================
Breaking: #72497 - Removed recode support for Charset Conversion
================================================================

See :issue:`72497`

Description
===========

The support for GNU-recode when converting from one charset to another has been dropped. The CharsetConverter
now only supports `mbstring`. In case `mbstring` is not present a polyfill will kick in.


Impact
======

Setting `$TYPO3_CONF_VARS[SYS][t3lib_cs_convMethod] = 'recode';` will have no effect anymore.
Conversion is then done through the default TYPO3-internal conversion.


Affected Installations
======================

Installations that have the option `$TYPO3_CONF_VARS[SYS][t3lib_cs_convMethod]` set to `recode`.


Migration
=========

No migration.

.. index:: PHP-API, LocalConfiguration
