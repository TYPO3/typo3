..  include:: /Includes.rst.txt

..  _feature-110453-1786523400:

======================================================================
Feature: #110453 - Derive PageRepository instances for another Context
======================================================================

See :issue:`110453`

Description
===========

:php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` is bound to a
:php:`Context` when it is created. That Context decides which language overlay
and which workspace the resolved records belong to. Code that needed records
for a *different* language - a link to a translated page, an hreflang tag, a
menu rendered without overlays - had to clone the global Context, set the
language aspect on the clone, and then create a new instance by hand:

..  code-block:: php

    $context = clone GeneralUtility::makeInstance(Context::class);
    $context->setAspect('language', $languageAspect);
    $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);

This forced every consumer to build the object itself, which prevented
:php:`PageRepository` from being injected as a regular service.

Two methods now derive a new instance from an existing one:

..  code-block:: php

    public function withContext(Context $context): self
    public function withLanguageAspect(LanguageAspect $languageAspect): self

:php:`withLanguageAspect()` keeps every other aspect - workspace, visibility,
frontend user - of the current Context and only exchanges the language aspect.
:php:`withContext()` replaces the Context as a whole and returns the same
instance if the given Context is already the active one.

Both methods leave the original instance untouched, so a single injected
:php:`PageRepository` can serve as the starting point for any number of
derived ones:

..  code-block:: php

    class MyLinkGenerator
    {
        public function __construct(
            private readonly PageRepository $pageRepository,
        ) {}

        public function getTranslatedPage(int $pageId, LanguageAspect $languageAspect): array
        {
            return $this->pageRepository->withLanguageAspect($languageAspect)->getPage($pageId);
        }
    }

Passing :php:`new LanguageAspect()` resolves records without applying any
language overlay at all.

Impact
======

Extension authors can inject :php:`PageRepository` through dependency injection
and derive Context-specific instances where they are needed, instead of
cloning a Context and calling
:php:`GeneralUtility::makeInstance(PageRepository::class, $context)`.

The existing constructor is unchanged and keeps working.

..  index:: PHP-API, ext:core
