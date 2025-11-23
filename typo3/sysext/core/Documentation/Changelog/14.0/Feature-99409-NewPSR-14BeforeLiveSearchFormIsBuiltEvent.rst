..  include:: /Includes.rst.txt

..  _feature-99409-1691445436:

=============================================================
Feature: #99409 - New PSR-14 BeforeLiveSearchFormIsBuiltEvent
=============================================================

See :issue:`99409`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Search\Event\BeforeLiveSearchFormIsBuiltEvent`
has been added.

To modify the live search form data, the following methods are available:

-   :php:`addHint()`: Adds a single hint.
-   :php:`addHints()`: Adds one or multiple hints.
-   :php:`setHints()`: Sets hints and can be used to reset or overwrite the
    current ones.
-   :php:`getHints()`: Returns all current hints.
-   :php:`getRequest()`: Returns the current PSR-7 request.
-   :php:`getSearchDemand()`: Returns the :php:`SearchDemand` used by the live search.
-   :php:`setSearchDemand()`: Sets a custom :php:`SearchDemand` object.
-   :php:`getAdditionalViewData()`: Returns the additional view data set to be
    used in the template.
-   :php:`setAdditionalViewData()`: Sets the additional view data to be used in
    the template.

..  note::

    The method :php:`setAdditionalViewData()` is useful to provide additional
    data to the template without the need to cross-class (XCLASS) the controller.
    The additional view data can then be used in an overridden backend template
    of the live search form.

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
        #[AsEventListener(
            identifier: 'my-package/backend/search/modify-live-search-form-data'
        )]
        public function __invoke(BeforeLiveSearchFormIsBuiltEvent $event): void
        {
            $event->addHints(...[
                'my-extension.messages:identifier',
            ]);
        }
    }

Impact
======

With the new PSR-14 event :php:`BeforeLiveSearchFormIsBuiltEvent`, it is now
possible to modify the form data for the backend live search.

..  index:: Backend, PHP-API, ext:backend
