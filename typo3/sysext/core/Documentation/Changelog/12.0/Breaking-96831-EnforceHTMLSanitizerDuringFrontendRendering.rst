.. include:: /Includes.rst.txt

.. _breaking-96831:

===================================================================
Breaking: #96831 - Enforce HTML sanitizer during frontend rendering
===================================================================

See :issue:`96831`

Description
===========

TYPO3 security fix `TYPO3-CORE-SA-2021-013 <https://typo3.org/security/advisory/typo3-core-sa-2021-013>`_
introduced Composer package `typo3/html-sanitizer` to mitigate cross-site scripting vulnerabilities in
rich-text content. In order to relax the strict invocation, a corresponding feature flag has been added
in a follow-up release - which only was a temporary solution.

The feature flag `security.frontend.htmlSanitizeParseFuncDefault` is dropped, and content processing via
TypoScript :typoscript:`stdWrap.parseFunc` now enables HTML sanitization per default in case it has not been
disabled explicitly in corresponding invocation.

Sites that used a version prior to TYPO3 v12.0 received a corresponding deprecation message already.

Impact
======

Rich-text content processed with TypoScript :typoscript:`stdWrap.parseFunc` is HTML sanitized per default.
Feature flag `security.frontend.htmlSanitizeParseFuncDefault` does not have any effect anymore.

Affected Installations
======================

All scenarios that use TypoScript :typoscript:`stdWrap.parseFunc`, a direct invocation via PHP of
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::parseFunc()` or Fluid
view-helper :html:`<f:format.html>`.

Migration
=========

The following documents already tackled and described the scenario and implications:

* :doc:`9.5.x: Important: #94484 - Introduce HTML Sanitizer <../9.5.x/Important-94484-IntroduceHTMLSanitizer>`
* :doc:`12.0: Breaking: #96520 - Enforce non-empty configuration in cObj::parseFunc <Breaking-96520-EnforceNon-emptyConfigurationInCObjparseFunc>`

.. index:: Frontend, TypoScript, NotScanned, ext:frontend
