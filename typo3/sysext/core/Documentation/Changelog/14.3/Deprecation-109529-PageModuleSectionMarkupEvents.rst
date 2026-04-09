..  include:: /Includes.rst.txt

..  _deprecation-109529-1775733107:

==========================================================
Deprecation: #109529 - Page module section markup events
==========================================================

See :issue:`109529`

Description
===========

The PSR-14 events that allow listeners to inject HTML before or after the
content elements rendered inside a backend layout column have been
deprecated and will be removed in TYPO3 v15.0:

*   :php:`\TYPO3\CMS\Backend\View\Event\BeforeSectionMarkupGeneratedEvent`
*   :php:`\TYPO3\CMS\Backend\View\Event\AfterSectionMarkupGeneratedEvent`
*   :php:`\TYPO3\CMS\Backend\View\Event\AbstractSectionMarkupGeneratedEvent`

The accompanying methods :php:`GridColumn::getBeforeSectionMarkup()` and
:php:`GridColumn::getAfterSectionMarkup()` have likewise been deprecated.

These events were introduced in TYPO3 v10.3 alongside the legacy
:php:`PageLayoutView` class to enrich backend layout columns with custom
markup. They expose raw HTML strings as a column-level extension point
and force every refactoring of the page module to keep emitting that
markup at exactly the same position in the DOM. This makes it impossible
to keep them stable across versions while improving the page module:
ongoing work to rebuild the page module and its drag, drop and paste
behavior cannot proceed without locking the column's internal structure
to whatever the events happened to assume. Removing the events is a
prerequisite for that work.

The only listener that still consumed the event in the core,
:php:`PageLayoutViewDrawEmptyColposContent`, has been removed. Its job —
showing a placeholder block for backend layout cells that have no
:php:`colPos` configured — is now performed directly in the Fluid template
:file:`PageLayout/Grid/Column.fluid.html` via an :php:`{column.unassigned}`
condition. No extension action is required for this case.

Impact
======

Calling :php:`AbstractSectionMarkupGeneratedEvent::setContent()` from a
listener will trigger a deprecation-level log entry. The classes and the
two :php:`GridColumn` getters will be removed in TYPO3 v15.0.

Existing listeners will keep functioning in TYPO3 v14: the dispatch sites
in :php:`GridColumn` still fire the events and the Fluid partials still
render the resulting :php:`{column.beforeSectionMarkup}` and
:php:`{column.afterSectionMarkup}` strings.

Affected installations
======================

Instances or extensions that register a PSR-14 listener on
:php:`BeforeSectionMarkupGeneratedEvent` or
:php:`AfterSectionMarkupGeneratedEvent`, or that call
:php:`GridColumn::getBeforeSectionMarkup()` /
:php:`GridColumn::getAfterSectionMarkup()` directly, are affected.

Migration
=========

There is no direct replacement. Listeners that decorated backend layout
columns through these events should be removed.

..  index:: Backend, PHP-API, NotScanned, ext:backend
