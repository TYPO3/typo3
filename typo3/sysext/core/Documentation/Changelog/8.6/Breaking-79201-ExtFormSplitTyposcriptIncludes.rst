.. include:: /Includes.rst.txt

======================================================
Breaking: #79201 - EXT:form: Split TypoScript Includes
======================================================

See :issue:`79201`

Description
===========

The frontend specific TypoScript setup for EXT:form isn't loaded automatically anymore and must be added manually through
static includes. With this change a TYPO3 integrator can easier decide where the extension Typoscript is included.


Impact
======

Using the extension without adding static includes of EXT:form will result in an erroneous frontend output.


Affected Installations
======================

Any installation with activated EXT:form extension.


Migration
=========

Make sure to include the static TypoScript "Form" in your (root) template record. Same procedure as with static includes
of fluid_styled_content or css_styled_content.

.. index:: ext:form, TypoScript, Frontend
