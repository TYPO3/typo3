.. include:: /Includes.rst.txt

.. _feature-104832-1725537890:

==========================================================================
Feature: #104832 - PSR-14 Event to alter the results of PageTreeRepository
==========================================================================

See :issue:`104832`

Description
===========

Up until TYPO3 v9, it was possible to alter the rendering of one of TYPO3's
superpowers — the page tree in the TYPO3 Backend User Interface.

This was done via a "Hook", but was removed due to the migration towards a
SVG-based tree rendering.

As the Page Tree Rendering has evolved, and the hook system has been replaced
in favor of PSR-14 Events, a new event :php:`TYPO3\CMS\Backend\Tree\Repository\AfterRawPageRowPreparedEvent`
has been introduced.


Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration, will rmoeve any children for displaying for the page with the
UID 123:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Backend\Tree\Repository\AfterRawPageRowPreparedEvent;

    final class MyEventListener
    {
        #[AsEventListener]
        public function __invoke(AfterRawPageRowPreparedEvent $event): void
        {
            $rawPage = $event->getRawPage();
            if ((int)$rawPage['uid'] === 123) {
                $rawPage['_children'] = [];
                $event->setRawPage($rawPage);
            }
        }
    }

Impact
======

Using the new PSR-14 event, it's now possible to modify the populated
:php:`page` properties or its children records.

.. index:: Backend, PHP-API, ext:backend
