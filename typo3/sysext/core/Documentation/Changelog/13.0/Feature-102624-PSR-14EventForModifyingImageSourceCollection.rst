.. include:: /Includes.rst.txt

.. _feature-102624-1701943870:

=====================================================================
Feature: #102624 - PSR-14 event for modifying image source collection
=====================================================================

See :issue:`102624`


Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Frontend\ContentObject\Event\ModifyImageSourceCollectionEvent`
has been introduced which serves as a drop-in replacement for the now removed
hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImageSourceCollection']`.

The event is being dispatched in :php:`ContentObjectRenderer->getImageSourceCollection()`
for each configured :php:`sourceCollection` and allows to enrich the final
source collection result.

To modify :php:`getImageSourceCollection()` result, the following methods are available:

- :php:`setSourceCollection()`: Allows to modify a source collection based on the corresponding configuration
- :php:`getSourceCollection()`: Returns the source collection
- :php:`getFullSourceCollection()`: Returns the current full source collection, being enhanced by the current :typoscript:`sourceCollection`
- :php:`getSourceConfiguration()`: Returns the current :typoscript:`sourceCollection` configuration
- :php:`getSourceRenderConfiguration()`: Returns the corresponding renderer configuration for the source collection
- :php:`getContentObjectRenderer()`: Returns the current :php:`ContentObjectRenderer` instance

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\ContentObject\Event\ModifyImageSourceCollectionEvent;

    final class ModifyImageSourceCollectionEventListener
    {
        #[AsEventListener]
        public function __invoke(ModifyImageSourceCollectionEvent $event): void
        {
            $event->setSourceCollection('<source src="bar-file.jpg" media="(max-device-width: 600px)">');
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to manipulate the
:typoscript:`sourceCollection`'s, used for an :php:`ImageContentObject`.


.. index:: Frontend, PHP-API, ext:frontend
