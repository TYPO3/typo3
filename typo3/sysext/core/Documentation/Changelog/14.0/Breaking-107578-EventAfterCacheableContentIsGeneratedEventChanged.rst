..  include:: /Includes.rst.txt

..  _breaking-107578-1759400756:

=======================================================================
Breaking: #107578 - Event AfterCacheableContentIsGeneratedEvent changed
=======================================================================

See :issue:`107578`

Description
===========

The frontend rendering related event :php:`AfterCacheableContentIsGeneratedEvent`
had to be changed due to the removal of class :php:`TypoScriptFrontendController`:
Method :php:`getController()` is removed and substituted with methods :php:`getContent()`
and :php:`setContent()`.


Impact
======

Event listeners that call :php:`getController()` will trigger a fatal PHP error and
have to be adapted.


Affected installations
======================

The event is relatively commonly used within frontend rendering related extensions
since it gives an opportunity to get and manipulate the fully rendered Response body
content at a late point in the rendering chain.

Instances with extensions listening for event :php:`AfterCacheableContentIsGeneratedEvent`
may be affected. The extension scanner will find affected extensions.

Migration
=========

In most cases, method :php:`AfterCacheableContentIsGeneratedEvent->getController()` is used
in event listeners to get and/or set :php:`TypoScriptFrontendController->content` at a late
point within the frontend rendering chain.

The event has been changed by removing :php:`getController()` and adding :php:`getContent()`
and :php:`setContent()` instead.

Example before:

.. code-block:: php

    #[AsEventListener('my-extension')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $tsfe = $event->getController();
        $content = $tsfe->content;
        // ... $content is manipulated here
        $tsfe->content = $content;
    }

Example now:

.. code-block:: php

    #[AsEventListener('my-extension')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $content = $event->getContent();
        // ... $content is manipulated here
        $event->setContent($content);
    }

Extensions aiming for TYPO3 v13 and v14 compatibility in a single version can use a version
check gate:

.. code-block:: php

    #[AsEventListener('my-extension')]
    public function indexPageContent(AfterCacheableContentIsGeneratedEvent $event): void
    {
        if ((new Typo3Version)->getMajorVersion() < 14) {
            // @todo: Remove if() when TYPO3 v13 compatibility is dropped, keep else body only
            $tsfe = $event->getController();
            $content = $tsfe->content;
        } else {
            $content = $event->getContent();
        }
        // ... $content is manipulated here
        if ((new Typo3Version)->getMajorVersion() < 14) {
            // @todo: Remove if() when TYPO3 v13 compatibility is dropped, keep else body only
            $tsfe = $event->getController();
            $tsfe->content = $content;
        } else {
            $event->setContent($content);
        }
    }


..  index:: PHP-API, FullyScanned, ext:frontend
