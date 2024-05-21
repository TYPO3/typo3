..  include:: /Includes.rst.txt

..  _important-88886-1784901300:

==========================================================================
Important: #88886 - Extbase persistence respects the language overlay type
==========================================================================

See :issue:`88886`

Description
===========

The Extbase persistence layer previously applied "mixed" language overlay
semantics at all times when overlaying records: If a record was not available
in the requested language, the record in the default language was returned -
regardless of the fallback type configured for the site language.

Extbase now respects the overlay type (derived from the :yaml:`fallbackType`
setting of the site language configuration) and the configured fallback chain
when fetching aggregate roots and related child objects. Translation behavior
of Extbase queries is now consistent with the regular translation behavior of
pages and content in TYPO3:

*   :yaml:`fallbackType: strict`: Records that are not translated into the
    requested language are not returned anymore. :php:`Repository->findByUid()`
    on an untranslated record of the default language now returns :php:`null`,
    and untranslated related child objects (for example categories or tags) are
    filtered from relations of a translated aggregate root.

*   :yaml:`fallbackType: fallback`: Unchanged behavior. Records are overlaid
    with their translation if available, the default language record is used
    otherwise.

*   :yaml:`fallbackType: free`: Unchanged behavior. No overlays are performed
    for regular queries. Identity lookups via :php:`Repository->findByUid()`
    keep resolving translations using mixed overlay semantics.

Fetching a translated record directly by its uid (for example
:php:`$postRepository->findByUid($uidOfTranslatedRecord)`) still returns the
translated record, regardless of the language of the current context.

Note that records created through Extbase in the frontend - for example via
form submissions - are persisted with :sql:`sys_language_uid=0` (default
language) unless a language is explicitly assigned. On sites using
:yaml:`fallbackType: strict`, such records are not visible in translated
languages until they have been translated.

To keep the previous behavior for individual queries, a custom language aspect
using mixed overlay semantics can be set on the query settings:

..  code-block:: php

    use TYPO3\CMS\Core\Context\LanguageAspect;

    $querySettings = $query->getQuerySettings();
    $languageAspect = $querySettings->getLanguageAspect();
    $querySettings->setLanguageAspect(
        new LanguageAspect(
            $languageAspect->getId(),
            $languageAspect->getContentId(),
            LanguageAspect::OVERLAYS_MIXED,
            $languageAspect->getFallbackChain(),
        ),
    );

..  index:: Database, PHP-API, ext:extbase
