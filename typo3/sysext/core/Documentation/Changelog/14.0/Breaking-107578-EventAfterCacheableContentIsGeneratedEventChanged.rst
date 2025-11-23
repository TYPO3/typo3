..  include:: /Includes.rst.txt

..  _breaking-107578-1759400756:

=======================================================================
Breaking: #107578 - Event AfterCacheableContentIsGeneratedEvent changed
=======================================================================

See :issue:`107578`

Description
===========

The frontend rendering event
:php:`\TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent`
has been adjusted due to the removal of the
:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` class.

The method :php:`getController()` has been removed and replaced by two new
methods: :php:`getContent()` and :php:`setContent()`.

Impact
======

Event listeners that call :php:`getController()` will trigger a fatal PHP
error and must be adapted.

Affected installations
======================

This event is commonly used in frontend rendering–related extensions, since
it provides an opportunity to access and manipulate the fully rendered
response body content at a late point in the rendering chain.

Instances with extensions listening to
:php-short:`\TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent` may be
affected. The extension scanner will detect such usages.

Migration
=========

In most cases, :php:`AfterCacheableContentIsGeneratedEvent->getController()` was
used within event listeners to get and modify the
:php:`TypoScriptFrontendController->content` property at the end of the
rendering process.

The event now provides :php:`getContent()` and :php:`setContent()` methods
to achieve the same goal more directly.

**Before:**

..  code-block:: php

    #[\TYPO3\CMS\Core\Attribute\AsEventListener('my-extension')]
    public function indexPageContent(
        \TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent $event
    ): void {
        $tsfe = $event->getController();
        $content = $tsfe->content;
        // ... $content is manipulated here
        $tsfe->content = $content;
    }

**After:**

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

    #[AsEventListener('my-extension')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $content = $event->getContent();
        // ... $content is manipulated here
        $event->setContent($content);
    }

**Version check for TYPO3 v13/v14 compatibility:**

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Information\Typo3Version;
    use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

    #[AsEventListener('my-extension')]
    public function indexPageContent(
        AfterCacheableContentIsGeneratedEvent $event
    ): void {
        $version = new Typo3Version();
        if ($version->getMajorVersion() < 14) {
            // @todo: Remove if() when TYPO3 v13 compatibility is dropped
            $tsfe = $event->getController();
            $content = $tsfe->content;
        } else {
            $content = $event->getContent();
        }

        // ... $content is manipulated here

        if ($version->getMajorVersion() < 14) {
            // @todo: Remove if() when TYPO3 v13 compatibility is dropped
            $tsfe = $event->getController();
            $tsfe->content = $content;
        } else {
            $event->setContent($content);
        }
    }

..  index:: PHP-API, FullyScanned, ext:frontend
