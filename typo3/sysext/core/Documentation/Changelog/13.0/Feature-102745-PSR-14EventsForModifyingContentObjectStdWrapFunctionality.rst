.. include:: /Includes.rst.txt

.. _feature-102745-1705054628:

==================================================================================
Feature: #102745 - PSR-14 events for modifying ContentObject stdWrap functionality
==================================================================================

See :issue:`102745`

Description
===========

Four new PSR-14 events have been introduced:

* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsInitializedEvent`
* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsInitializedEvent`
* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsExecutedEvent`
* :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsExecutedEvent`

They serve as more powerful replacement of the :doc:`removed <../13.0/Breaking-102745-RemovedContentObjectStdWrapHook>`,
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']` hook.

Instead of registering one hook class, implementing four different methods - due
to the :doc:`deprecated interface <../13.0/Deprecation-102745-UnusedInterfaceForStdWrapHook>`
- extension authors are now able to register dedicated listeners. Next to the
individual events, it's also possible to register listeners to listen on the
parent :php:`\TYPO3\CMS\Frontend\ContentObject\Event\EnhanceStdWrapEvent`. Since
this event is extended by all other events, registered listeners are called
on each occurrence.

All events provide the same functionality. The difference is only the execution
order in which they are called in the stdWrap processing chain.

Available methods:

* :php:`getContent()` - Returns the current content (stdWrap result)
* :php:`setContent()` - Allows to modify the final content (stdWrap result)
* :php:`getConfiguration()` - Returns the corresponding TypoScript configuration
* :php:`getContentObjectRenderer()` - Returns the current :php:`ContentObjectRenderer` instance

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsExecutedEvent;
    use TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsInitializedEvent;
    use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsInitializedEvent;
    use TYPO3\CMS\Frontend\ContentObject\Event\EnhanceStdWrapEvent;

    final class EnhanceStdWrapEventListener
    {
        #[AsEventListener]
        public function __invoke(EnhanceStdWrapEvent $event): void
        {
            // listen to all events
        }

        #[AsEventListener]
        public function individualListener(BeforeStdWrapFunctionsInitializedEvent $event): void
        {
            // listen on BeforeStdWrapFunctionsInitializedEvent only
        }

        #[AsEventListener]
        public function listenOnMultipleEvents(AfterStdWrapFunctionsInitializedEvent|AfterStdWrapFunctionsExecutedEvent $event): void
        {
            // Union type to listen to different events
        }
    }

Impact
======

Using the new PSR-14 events, it's now possible to fully influence the stdWrap
functionality in TYPO3's Core API class :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`.

Using the new individual events, developers are now also able to simplify their
code by just listening for the relevant parts in the stdWrap processing.

.. index:: Frontend, PHP-API, ext:frontend
