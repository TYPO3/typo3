
.. include:: /Includes.rst.txt

================================================================================
Breaking: #64637 - Compatibility CSS Styled Content TypoScript templates removed
================================================================================

See :issue:`64637`

Description
===========

CSS Styled Content ships compatibility TypoScript templates for being compatible with older versions. For TYPO3 CMS 7,
all templates to render a compatibility frontend for the following versions have been removed without substitution.

* TYPO3 CMS 6.1
* TYPO3 CMS 6.0
* TYPO3 CMS 4.7
* TYPO3 CMS 4.6
* TYPO3 CMS 4.5

Impact
======

It is not possible to have the TYPO3 CMS 7 Frontend rendering based on CSS Styled Content to behave like a version
prior to TYPO3 CMS 6.2.


Affected installations
======================

All installations using the TypoScript templates to have the TYPO3 frontend powered via CSS Styled Content with
a compatibility TypoScript will result in no Frontend output.


Migration
=========

For installations still needing the old templates, an old version of the template (e.g. from TYPO3 CMS 6.2)
need to be included separately. Any other installation still using the old TypoScript templates should migrate to
the latest TypoScript templates of CSS Styled Content by choosing the correct version inside the Web=>Template
module.


.. index:: Frontend, TypoScript
