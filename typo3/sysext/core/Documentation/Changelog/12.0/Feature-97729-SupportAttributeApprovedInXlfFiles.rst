.. include:: /Includes.rst.txt

.. _feature-97729-1654626734:

=========================================================
Feature: #97729 - Respect attribute approved in XLF files
=========================================================

See :issue:`97729`

Description
===========

The attribute `approved` of the XLIFF standard is now supported by TYPO3 when
parsing XLF files. This attribute can either have the value `yes` or `no` and
indicates whether the translation is final or not.

..  code-block:: xml

    <trans-unit id="label2" resname="label2" approved="yes">
      <source>This is label #2</source>
      <target>Ceci est le libellé no. 2</target>
    </trans-unit>

The setting :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['requireApprovedLocalizations']`
can be used to control the behaviour.

- If it is set to `true` (which is the default setting), only translations with no attribute `approved`
  or with the attribute `approved` set to `yes` will be used.
- If it is set to `false`, all translations are used.

This attribute is particularly useful when working with third-party software and translation agencies.
Allowing unapproved translations may increase the number of translations, possibly at the expense of their quality.

Impact
======

Crowdin supports this attribute. Currently only approved translations are exported.
Therefore no change is expected for official translations.

.. index:: Backend, Fluid, Frontend, TCA, TypoScript, ext:core
