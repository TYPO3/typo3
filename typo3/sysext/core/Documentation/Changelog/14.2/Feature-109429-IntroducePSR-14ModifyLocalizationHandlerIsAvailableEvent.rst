..  include:: /Includes.rst.txt

..  _feature-109429-1774888319:

===============================================================================
Feature: #109429 - Introduce PSR-14 `ModifyLocalizationHandlerIsAvailableEvent`
===============================================================================

See :issue:`109429`

Description
===========

:issue:`108049` modernizes the translation workflow in the backend
in the `Content > Layout` and `Content > Record` module views. Technically, this
workflow wizard is backed by localization handlers and finishers.

The PSR-14 event
:php-short:`\TYPO3\CMS\Backend\Localization\Event\ModifyLocalizationHandlerIsAvailableEvent`
is now introduced and dispatched in
:php-short:`\TYPO3\CMS\Backend\Localization\LocalizationHandlerRegistry`
to allow the availability state of a localization
handler to be overridden based on :php-short:`\TYPO3\CMS\Backend\Localization\LocalizationInstructions\LocalizationInstructions`.

The event has the following properties:

*   :php:`public readonly string $identifier`: String identifier returned by
    :php:`LocalizationHandlerInterface::getIdentifier()` from the handler
*   :php:`public readonly string $className`: The concrete class name in case
    a handler has been XCLASSed without changing the identifier
*   :php:`public readonly LocalizationInstructions $instructions`: The
    localization instructions passed to the handler's `isAvailable()` method
    to define the context
*   :php:`public bool $isAvailable`: The availability state returned by the
    handler, which can be altered by a PSR-14 event listener

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/DisableManualLocalizationHandlerForCustomTableEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Backend\Localization\Event\ModifyLocalizationHandlerIsAvailableEvent;
    use TYPO3\CMS\Backend\Localization\Handler\ManualLocalizationHandler;
    use TYPO3\CMS\Backend\Localization\LocalizationMode;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class DisableManualLocalizationHandlerForCustomTableEventListener
    {
        #[AsEventListener(identifier: 'myext/disable-manual-localization-handler-custom-table')]
        public function __invoke(
            ModifyLocalizationHandlerIsAvailableEvent $event,
        ): void {
            if ($event->identifier !== 'manual') {
                // Return early if not ManualLocalizationHandler.
                return;
            }
            if ($event->className !== ManualLocalizationHandler::class) {
                // Return early if the manual identifier is provided but
                // a customized (XCLASSed) class is given. This is just an
                // example for that property.
                return;
            }
            if ($event->instructions->mode !== LocalizationMode::TRANSLATE) {
                // Return early if not handling translation
                // (localization) mode.
                return;
            }

            if ($event->instructions->mainRecordType === 'my_custom_table') {
                // Disallow translation/localization for 'my_custom_table'
                // in general with the default core handler.
                $event->isAvailable = false;
            }
        }
    }

Impact
======

Custom extensions (public and project-specific) are now able to intercept
and determine which handlers are available for localization steps in the translation
wizard, based on localization context.

..  index:: Backend, PHP-API, ext:backend
