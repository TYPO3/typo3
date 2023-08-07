.. include:: /Includes.rst.txt

.. _feature-99409-1691445436:

=============================================================
Feature: #99409 - New PSR-14 BeforeLiveSearchFormIsBuiltEvent
=============================================================

See :issue:`99409`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Search\Event\BeforeLiveSearchFormIsBuiltEvent`
has been added.

To modify the live search form data, the following methods are available:

-   :php:`addHint()`: Add a single hint.
-   :php:`addHints()`: Add one or multiple hints.
-   :php:`setHints()`: Allows to set hints. Can be used to reset or overwrite current hints.
-   :php:`getHints()`: Returns all hints.
-   :php:`getRequest()`: Returns the current PSR-7 Request.
-   :php:`getSearchDemand()`: Returns the :php:`SearchDemand`, used by the live search.
-   :php:`setSearchDemand()`: Allows to set a custom :php:`SearchDemand` object.
-   :php:`getAdditionalViewData(): Returns the additional view data set to be used in the template.
-   :php:`setAdditionalViewData(): Set the additional view data to be used in the template.

..  note::

    :php:`setAdditionalViewData()` becomes handy to provide additional data to
    the template without the need to cross class ("xclass") the controller. The
    additional view data can be used in a overridden backend template of the
    live search form.

Example
-------

The corresponding event listener class:

..  code-block:: php

    <?php

    namespace MyVendor\MyPackage\Backend\Search\EventListener;

    use TYPO3\CMS\Backend\Search\Event\BeforeLiveSearchFormIsBuiltEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class BeforeLiveSearchFormIsBuiltEventListener
    {
        #[AsEventListener('my-package/backend/search/modify-live-search-form-data')]
        public function __invoke(BeforeLiveSearchFormIsBuiltEvent $event): void
        {
            $event->addHints(...[
                'LLL:EXT:my-package/Resources/Private/Language/locallang.xlf:identifier',
            ]);
        }
    }


Impact
======

It's now possible to modify the form data for the backend live search, using
the new PSR-14 event :php:`BeforeLiveSearchFormIsBuiltEvent`.

.. index:: Backend, PHP-API, ext:backend
