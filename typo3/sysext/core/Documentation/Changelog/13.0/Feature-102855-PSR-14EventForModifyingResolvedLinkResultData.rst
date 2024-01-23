.. include:: /Includes.rst.txt

.. _feature-102855-1705568085:

=======================================================================
Feature: #102855 - PSR-14 event for modifying resolved link result data
=======================================================================

See :issue:`102855`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Core\LinkHandling\Event\AfterLinkResolvedByStringRepresentationEvent`
has been introduced which serves as a more powerful replacement for the now removed
hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Link']['resolveByStringRepresentation']`.

The event is being dispatched after :php:`LinkService` has tried to resolve a
given :php:`$urn` using defined link handlers. This means, the new event is
always dispatched, even if a handler successfully resolved the :php:`$urn`
and also even in cases, TYPO3 would have thrown the
:php:`UnknownLinkHandlerException` exception.

Therefore, the new event can not only be used to resolve custom link types
but also to modify the link result data of existing link handlers and
can additionally also be used to resolve situations where no handler could be
found for a `t3://` URN.

The event features the following methods:

* :php:`getResult()` - Returns the resolved link result data
* :php:`setResult()` - Allows to modify the final link result data
* :php:`getUrn()` - Returns the link parameter (URN) to be resolved
* :php:`getResolveException()` - Returns the exception, which will be thrown in case no link type has been resolved

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\LinkHandling\Event\AfterLinkResolvedByStringRepresentationEvent;

    final class AfterLinkResolvedByStringRepresentationEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterLinkResolvedByStringRepresentationEvent $event): void
        {
            if (str_contains($event->getUrn(), 'myhandler://123')) {
                $event->setResult([
                    'type' => 'my-type',
                ]);
            }
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to fully modify the resolved
link result data from :php:`LinkService->resolveByStringRepresentation()`,
just before the result is being returned. Therefore, even the resolved data
of existing handlers can be manipulated.

.. index:: PHP-API, ext:core
