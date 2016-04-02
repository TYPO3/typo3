================================================================
Breaking: #72497 - Removed recode support for Charset Conversion
================================================================

Description
===========

The support for GNU-recode when converting from one charset to another has been dropped. The CharsetConverter
now only supports ``mbstring`` and ``iconv`` as well as the home-made TYPO3-internal conversion.


Impact
======

Setting ``$TYPO3_CONF_VARS[SYS][t3lib_cs_convMethod] = 'recode';`` will have no effect anymore.
Conversion is then done through the default TYPO3-internal conversion.


Affected Installations
======================

Installations that have the option ``$TYPO3_CONF_VARS[SYS][t3lib_cs_convMethod]`` set to ``recode``.


Migration
=========

Use the Install Tool and the Preset information to see which other, better supported conversion libraries (mbstring
or iconv) are available.

.. index:: setting
