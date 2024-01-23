.. include:: /Includes.rst.txt

.. _feature-102835-1705314133:

=======================================================================
Feature: #102835 - Add PSR-14 events to manipulate TypoLinkCodecService
=======================================================================

See :issue:`102835`

Description
===========

TYPO3's main API for encoding and decoding TypoLink's has been extended
and now provides two new PSR-14 events :php:`\TYPO3\CMS\Core\LinkHandling\Event\BeforeTypoLinkEncodedEvent`
and :php:`\TYPO3\CMS\Core\LinkHandling\Event\AfterTypoLinkDecodedEvent`, which
allow developers to fully manipulate the encoding and decoding functionality.

A common use case for extensions is to extend the TypoLink parts to allow
editors adding additional information, e.g. custom attributes to be added
to the link markup. Previously, this required extensions to extended / cross
class :php:`TypoLinkCodecService`. This is no longer necessary when using the
new events.

The :php:`BeforeTypoLinkEncodedEvent` therefore allows to set :php:`$parameters`,
to be encoded while the :php:`AfterTypoLinkDecodedEvent` allows to modify the
decoded :php:`$typoLinkParts.`.

Both events provide the used :php:`$delimiter` and the :php:`$emptyValueSymbol`
next to the corresponding input value, either the :php:`$typoLinkParts` to be
encoded or the :php:`$typoLink` to be decoded.

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\LinkHandling\Event\AfterTypoLinkDecodedEvent;
    use TYPO3\CMS\Core\LinkHandling\Event\BeforeTypoLinkEncodedEvent;

    final class TypoLinkCodecServiceEventListener
    {
        #[AsEventListener]
        public function encodeTypoLink(BeforeTypoLinkEncodedEvent $event): void
        {
            $typoLinkParameters = $event->getParameters();

            if (str_contains($typoLinkParameters['class'] ?? '', 'foo')) {
                $typoLinkParameters['class'] .= ' bar';
                $event->setParameters($typoLinkParameters);
            }
        }

        #[AsEventListener]
        public function decodeTypoLink(AfterTypoLinkDecodedEvent $event): void
        {
            $typoLink = $event->getTypoLink();
            $typoLinkParts = $event->getTypoLinkParts();

            if (str_contains($typoLink, 'foo')) {
                $typoLinkParts['foo'] = 'bar';
                $event->setTypoLinkParts($typoLinkParts);
            }
        }
    }


Impact
======

Using the new PSR-14 events, it's now possible to fully influence the
encoding and decoding of any TypoLink.

.. index:: PHP-API, ext:core
