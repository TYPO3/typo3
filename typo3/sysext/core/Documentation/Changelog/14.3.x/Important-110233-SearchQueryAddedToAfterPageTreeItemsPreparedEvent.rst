..  include:: /Includes.rst.txt

..  _important-110233-1787820210:

==========================================================================
Important: #110233 - Search query added to AfterPageTreeItemsPreparedEvent
==========================================================================

See :issue:`110233`

Description
===========

The PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent`,
dispatched by :php:`\TYPO3\CMS\Backend\Controller\Page\TreeController` after the
backend page tree items have been resolved and prepared, now carries the current
page tree search query explicitly, instead of requiring event listeners to
extract it themselves from GET parameters of the current PSR-7 request.

A new method :php:`getSearchQuery(): ?string` has been added, returning the
search phrase used to filter the page tree, or :php:`null` if no search is
currently active.

Note that the :php:`$request` argument - and therefore the return value of
:php:`getRequest()` - is now nullable as well, since the event can be
dispatched in contexts where no PSR-7 request instance is available in future.


Impact
======

Custom event listeners that read the page tree search phrase via
:php:`$event->getRequest()->getQueryParams()['q']` should be adapted to use
the new :php:`$event->getSearchQuery()` method instead.

Listeners should furthermore no longer assume that :php:`getRequest()` always
returns an instance of :php:`ServerRequestInterface`, since it may be
:php:`null` in future major versions.


..  index:: Backend, PHP-API, ext:backend
