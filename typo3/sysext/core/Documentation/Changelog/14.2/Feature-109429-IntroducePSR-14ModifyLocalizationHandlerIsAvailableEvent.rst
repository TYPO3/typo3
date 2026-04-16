..  include:: /Includes.rst.txt

..  _feature-109429-1774888319:

===============================================================================
Feature: #109429 - Introduce PSR-14 `ModifyLocalizationHandlerIsAvailableEvent`
===============================================================================

See :issue:`109429`

Description
===========

With :issue:`108049`, the translation workflow in the backend has been modernized
in the `Content > Layout` and `Content > Record` module views. Technically, this
workflow wizard is backed by localization handlers and finishers.

The PSR-14 event :php-short:`\TYPO3\CMS\Backend\Localization\Event\ModifyLocalizationHandlerIsAvailableEvent`
is introduced now and fired in :php-short:`\TYPO3\CMS\Backend\Localization\LocalizationHandlerRegistry`
to allow overruling the available state of any registered localization handler based
on the :php-short:`\TYPO3\CMS\Backend\Localization\LocalizationInstructions\LocalizationInstructions`.

The event provides the following properties:

*   :php:`public readonly string $identifier`: String identifier returned by
    :php:`LocalizationHandlerInterface::getIdentifier()` from the handler.
*   :php:`public readonly string $className`: The concrete className in case
    a handler has been xclass'd without changing the identifier.
*   :php:`public readonly LocalizationInstructions $instructions`: The localization
    instruction passed to the handler's `isAvailable()` method to define the context.
*   :php:`public bool $isAvailable`: The returned availability state by the handler,
    which can be altered by a PSR-14 Event listener.

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/DisableManualLocalizationHandlerForCustomTableEventListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Backend\Localization\Event\ModifyLocalizationHandlerIsAvailableEvent;
    use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
    use TYPO3\CMS\Backend\Localization\LocalizationMode;

    final class DisableManualLocalizationHandlerForCustomTableEventListener
    {
        #[AsEventListener(identifier: 'myext/disable-manual-localization-handler-custom-table')]
        public function __invoke(
            ModifyLocalizationHandlerIsAvailableEvent $event,
        ): void {
            if ($event->identifier !== 'manual') {
                // Return early if not ManualLocalizationHandler
                return;
            }
            if ($event->className !== ManualLocalizationHandler::class) {
                // Return early in case manual identifier is provided but
                // customized (xlassed) class given. Just for the sake of
                // an example for that property.
            }
            if ($event->instructions->mode !== LocalizationMode::TRANSLATE) {
                // Return early in case not handling translation
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

Custom extensions (public or project-specific) are now able to intercept,
which handlers are available for offering localization steps in the translation
wizard, based on the provided localization context.

..  index:: Backend, PHP-API, ext:backend
