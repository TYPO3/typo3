.. include:: /Includes.rst.txt

.. _breaking-97729-1654627167:

==========================================================
Breaking: #97729 - Respect attribute approved in XLF files
==========================================================

See :issue:`97729`

Description
===========

The new option :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['requireApprovedLocalizations']`
controls whether only approved translations are taken into account when parsing XLF files.

This option is enabled by default for new and existing TYPO3 installations.

Impact
======

If set to `true` - which is the default value - only approved translations are used.
Any non-approved translation will be ignored.
If the attribute approved is omitted, the translation is still taken into account.

..  code-block:: xml

    <trans-unit id="label2" resname="label2" approved="yes">
      <source>This is label #2</source>
      <target>Ceci est le libellé no. 2</target>
    </trans-unit>

Affected installations
======================

All TYPO3 translations using translations from XLF files.

Migration
=========

Either set :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['requireApprovedLocalizations']`
to `false` or add `approved="yes"` to all translations.

.. index:: Backend, Fluid, Frontend, TCA, TypoScript, NotScanned, ext:core
