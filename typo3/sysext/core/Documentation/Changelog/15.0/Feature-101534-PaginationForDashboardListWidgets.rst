..  include:: /Includes.rst.txt

..  _feature-101534-1790839494:

========================================================
Feature: #101534 - Pagination for dashboard list widgets
========================================================

See :issue:`101534`

Description
===========

The dashboard :php:`\TYPO3\CMS\Dashboard\Widgets\ListWidget` can now
render a paginator in its footer. Widgets that list a series of records are
typically limited to a few rows for space reasons - with pagination, editors
can browse through all of them without leaving the dashboard.

To enable pagination, the data provider of the list widget implements the new
interface :php:`\TYPO3\CMS\Dashboard\Widgets\PaginatedListDataProviderInterface`
instead of :php:`\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface`:

..  code-block:: php

    use TYPO3\CMS\Core\Pagination\ArrayPaginator;
    use TYPO3\CMS\Core\Pagination\PaginatorInterface;
    use TYPO3\CMS\Dashboard\Widgets\PaginatedListDataProviderInterface;

    final readonly class MyListDataProvider implements PaginatedListDataProviderInterface
    {
        public function getPaginator(int $currentPage, int $itemsPerPage): PaginatorInterface
        {
            return new ArrayPaginator($this->fetchItems(), $currentPage, $itemsPerPage);
        }
    }

Any implementation of :php:`\TYPO3\CMS\Core\Pagination\PaginatorInterface`
can be returned, for example the
:php:`\TYPO3\CMS\Core\Pagination\QueryBuilderPaginator`, which only fetches
the rows of the requested page from the database.

The number of items per page is not defined by the data provider, but by the
widget: backend users can change it in the settings of each widget instance
(default: 10). Integrators can set the new option :yaml:`itemsPerPage` in the
widget registration to change the default, which also locks the setting for
editors.

The widget displays compact "previous" and "next" buttons together with the
current page number if there is more than one page. Switching pages reloads
only the widget content via the existing AJAX route :php:`dashboard_widget_get`,
which now accepts an additional :php:`page` query argument. Custom widgets
implementing :php:`\TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface` can
read the argument from the request in their widget context and reuse the
Fluid partial :html:`Widget/Pagination`.

The "Latest backend logins" widget is the first widget making use of the
pagination. Its existing :php:`limit` setting now defines how many logins are
shown at once, and editors can browse through the older logins.

Impact
======

List widgets can offer more than the first few items without additional
dashboard space. Existing data providers implementing
:php:`\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface` continue to
work unchanged.

..  index:: Backend, PHP-API, ext:dashboard
