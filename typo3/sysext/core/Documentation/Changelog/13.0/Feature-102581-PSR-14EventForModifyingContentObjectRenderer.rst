.. include:: /Includes.rst.txt

.. _feature-102581-1701449291:

===================================================================
Feature: #102581 - PSR-14 event for modifying ContentObjectRenderer
===================================================================

See :issue:`102581`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterContentObjectRendererInitializedEvent`
has been introduced which serves as a drop-in replacement for the now removed
hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']`.

The event is being dispatched after :php:`ContentObjectRenderer` has been
initialized in its :php:`start()` method. The :php:`ContentObjectRenderer`
instance can be accessed using the :php:`getContentObjectRenderer()` method.

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\ContentObject\Event\AfterContentObjectRendererInitializedEvent;

    final class AfterContentObjectRendererInitializedEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterContentObjectRendererInitializedEvent $event): void
        {
            $event->getContentObjectRenderer()->setCurrentVal('My current value');
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to modify the
:php:`ContentObjectRenderer` instance, after it has been initialized.

.. index:: Frontend, PHP-API, ext:frontend
