.. include:: /Includes.rst.txt

====================================================
Feature: #88791 - Introduce PreviewAspect in Context
====================================================

See :issue:`88791`

Description
===========

A PreviewAspect for handling the preview flag has been introduced. This aspect may be used to indicate that the
frontend is in preview mode (for example in case a workspace is previewed or hidden pages or records should be shown).

Impact
======

The Context API has a new Aspect called "frontend.preview". It can be used to determine whether the frontend is currently in preview mode.

.. code-block:: php

   GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.preview', 'isPreview');

This Aspect replaces the now deprecated property :php:`TypoScriptFrontendController->fePreview`. Accessing this property
triggers a PHP :php:`E_USER_DEPRECATED` error, and fetches the information from the new Context Aspect instead.

.. index:: Frontend, PHP-API, ext:core
