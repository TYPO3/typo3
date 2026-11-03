..  include:: /Includes.rst.txt

..  _deprecation-111326-1793692850:

==================================================
Deprecation: #111326 - LanguageAspect::OVERLAYS_ON
==================================================

See :issue:`111326`

Description
===========

The overlay type :php:`\TYPO3\CMS\Core\Context\LanguageAspect::OVERLAYS_ON`
(value :php:`'on'`) has been marked as deprecated and will be removed in
TYPO3 v16.0.

This overlay type is never created from a site configuration:
:yaml:`fallbackType: strict` results in
:php:`LanguageAspect::OVERLAYS_ON_WITH_FLOATING`,
:yaml:`fallbackType: fallback` in :php:`LanguageAspect::OVERLAYS_MIXED` and
:yaml:`fallbackType: free` in :php:`LanguageAspect::OVERLAYS_OFF`. It is only
used when a :php-short:`\TYPO3\CMS\Core\Context\LanguageAspect` is created
manually.

The record overlay in
:php-short:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` already treats
both overlay types the same way. The only difference is how records of the
requested language without a record in the default language ("floating"
records) are handled: with :php:`LanguageAspect::OVERLAYS_ON`, the TypoScript
content object :typoscript:`CONTENT` and Extbase repositories do not include
them. Removing this overlay type reduces the number of language overlay modes
that need to be considered.

Impact
======

Creating a :php-short:`\TYPO3\CMS\Core\Context\LanguageAspect` with the overlay
type :php:`'on'` triggers a PHP deprecation warning. The behavior stays the
same until the overlay type is removed in TYPO3 v16.0.

Affected installations
======================

TYPO3 installations with custom extensions that create a
:php-short:`\TYPO3\CMS\Core\Context\LanguageAspect` with
:php:`LanguageAspect::OVERLAYS_ON` or the value :php:`'on'`, for example to set
a custom language aspect on Extbase query settings or in the context.

The extension scanner reports usages of the constant as a **strong match**.

Migration
=========

Use :php:`LanguageAspect::OVERLAYS_ON_WITH_FLOATING` instead. This is the
overlay type that is used by sites with :yaml:`fallbackType: strict`.

Records of the requested language without a record in the default language are
then included in the result. If they must not be shown, exclude them in the
query. For the content object :typoscript:`CONTENT`, set the option
:typoscript:`select.includeRecordsWithoutDefaultTranslation = 0`.

..  code-block:: php
    :caption: Before (deprecated)

    use TYPO3\CMS\Core\Context\LanguageAspect;

    $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON);

..  code-block:: php
    :caption: After

    use TYPO3\CMS\Core\Context\LanguageAspect;

    $languageAspect = new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING);

..  index:: PHP-API, FullyScanned, ext:core
