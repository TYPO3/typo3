.. include:: /Includes.rst.txt

.. _feature-102614-1701869725:

============================================================
Feature: #102614 - PSR-14 event for modifying GetData result
============================================================

See :issue:`102614`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterGetDataResolvedEvent`
has been introduced which serves as a drop-in replacement for the now removed
hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getData']`.

The event is being dispatched just before :php:`ContentObjectRenderer->getData()`
is about to return the resolved "data". The event is therefore in comparison to
the removed hook not dispatched for every section of the parameter string, but
only once, making the former :php:`$secVal` superfluous.

To modify the :php:`getData()` result, the following methods are available:

- :php:`setResult()`: Allows to set the "data" to return
- :php:`getResult()`: Returns the resolved "data"
- :php:`getParameterString()`: Returns the parameter string, e.g. :typoscript:`field : title`
- :php:`getAlternativeFieldArray()`: Returns the alternative field array, if provided
- :php:`getContentObjectRenderer()`: Returns the current :php:`ContentObjectRenderer` instance

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Frontend\ContentObject\Event\AfterGetDataResolvedEvent;

    final class AfterGetDataResolvedEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterGetDataResolvedEvent $event): void
        {
            $event->setResult('modified-result');
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to modify the resolved
:php:`getData()` result.

.. index:: Frontend, PHP-API, ext:frontend
