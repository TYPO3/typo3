.. include:: /Includes.rst.txt

.. _feature-88137-1673993076:

========================================================================
Feature: #88137 - Multi-level fallback for content in frontend rendering
========================================================================

See :issue:`88137`

Description
===========

TYPO3's Site Handling was introduced in TYPO3 v9 and allows to define a
"Fallback Type".

A fallback type allows to define the behavior of how pages and the content
should be fetched from the database when rendering a page in the frontend.

The option "strict" only renders content which was explicitly translated or
created in this defined language, and keeps the sorting behavior of the
default language.

The option "free" does not consider the default language or its sorting,
and only directly fetches content of the given Language ID.

The option "fallback" allows to define a fallback chain of other languages.
When a certain page in the given language is not available or created, TYPO3
first checks the fallback chain if a page is available in one of the languages
of the fallback chain.

A common scenario is this:
* German (Austria) - Language = 2
* German (Germany) - Language = 1
* English (Default) - Language = 0

TYPO3 now can deal with the language chain in fallback mode not only for pages,
but also for any kind of content.


Impact
======

When working in a scenario with "fallback" and multiple languages in the fallback
chain, TYPO3 now checks for each content if the target language is available,
and then checks for the same content if it is translated in the language of the
fallback chain (example above in "German (Germany)"), before falling back to
the default language - which was the behavior until now.

The language chain processing works with fallback mode (a.k.a. "overlays in mixed mode"),
both in TypoScript and Extbase code. Under the hood, the method
:php:`PageRepository->getLanguageOverlay()` is responsible for the chaining.

Current limitations:
* Content fallback only works in fallbackType=fallback
* Content fallback always stops at the default language (as this was the previous behavior)

.. index:: Frontend, PHP-API, TypoScript, ext:core
