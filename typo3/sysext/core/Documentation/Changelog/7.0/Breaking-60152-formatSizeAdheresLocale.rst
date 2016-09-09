
.. include:: ../../Includes.txt

=================================================================
Breaking: #60152 - GeneralUtility::formatSize is now locale aware
=================================================================

See :issue:`60152`

Description
===========

The :code:`GeneralUtility::formatSize()` method now adheres to the currently set locale and
selects the correct decimal separator symbol.
This also applies to the TypoScript option :code:`stdWrap.bytes`, which uses the method internally,
as well as the Filelist content element type.

Impact
======

All output generated for locales, where the decimal separator is not a dot, will change to use
the correct symbol. e.g. comma for German.

Affected installations
======================

Any installation that uses the :code:`formatSize()` method in one of the ways mentioned.

Migration
=========

If you think you get the wrong decimal separator, ensure the locale is configured correctly
and the locale really exists on the server.

TypoScript option: :code:`config.locale`
Commandline: `locale -a`
