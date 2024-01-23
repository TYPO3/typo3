.. include:: /Includes.rst.txt

.. _feature-101603-1691322746:

============================================================================
Feature: #101603 - PSR-14 event for modifying record overlay icon identifier
============================================================================

See :issue:`101603`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent`
has been introduced which serves as a direct replacement for the now removed
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Core\Imaging\IconFactory']['overrideIconOverlay']`
hook.

To modify the overlay icon identifier, the following methods are available:

- :php:`setOverlayIconIdentifier()`: Allows to set the overlay icon identifier
- :php:`getOverlayIconIdentifier()`: Returns the overlay icon identifier
- :php:`getTable()`: Returns the record's table name
- :php:`getRow()`: Returns the record's database row
- :php:`getStatus()`: Returns the record's visibility status

Example
=======

The corresponding event listener class:

..  code-block:: php

    <?php

    namespace Vendor\MyPackage\Core\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;

    final class ModifyRecordOverlayIconIdentifierEventListener
    {
        #[AsEventListener('my-package/core/modify-record-overlay-icon-identifier')]
        public function __invoke(ModifyRecordOverlayIconIdentifierEvent $event): void
        {
            $event->setOverlayIconIdentifier('my-overlay-icon-identifier');
        }
    }

Impact
======

It's now possible to modify the overlay icon identifier of any record icon,
using the new PSR-14 event :php:`ModifyRecordOverlayIconIdentifierEvent`.

.. index:: Backend, PHP-API, ext:core
