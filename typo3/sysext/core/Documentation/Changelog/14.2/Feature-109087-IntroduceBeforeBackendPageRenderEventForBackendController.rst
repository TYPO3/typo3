..  include:: /Includes.rst.txt

..  _feature-109087:

===============================================================================
Feature: #109087 - Introduce BeforeBackendPageRenderEvent for BackendController
===============================================================================

See :issue:`109087`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\BeforeBackendPageRenderEvent`
has been introduced. It is dispatched in :php-short:`\TYPO3\CMS\Backend\Controller\BackendController`
before the main backend page is rendered. It provides access to:

*   :php:`$view` (:php-short:`\TYPO3\CMS\Core\View\ViewInterface`) – assign template
    variables to the backend top frame view
*   :php:`$javaScriptRenderer` (:php-short:`\TYPO3\CMS\Core\Page\JavaScriptRenderer`) – add
    custom JavaScript modules to the backend top frame
*   :php:`$pageRenderer` (:php-short:`\TYPO3\CMS\Core\Page\PageRenderer`) – add assets
    such as CSS files (marked :php:`@internal`)

Example
=======

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/BeforeBackendPageRenderEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Backend\Controller\Event\BeforeBackendPageRenderEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

    #[AsEventListener(identifier: 'my-extension/before-backend-page-render')]
    final class BeforeBackendPageRenderEventListener
    {
        public function __invoke(BeforeBackendPageRenderEvent $event): void
        {
            $event->javaScriptRenderer->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create(
                    '@my-vendor/my-extension/backend-module.js'
                )
            );
        }
    }

Impact
======

It is now possible to add custom JavaScript modules and other assets to the
TYPO3 backend top frame using the new PSR-14 event
:php:`\TYPO3\CMS\Backend\Controller\Event\BeforeBackendPageRenderEvent`.

..  index:: Backend, PHP-API, ext:backend
