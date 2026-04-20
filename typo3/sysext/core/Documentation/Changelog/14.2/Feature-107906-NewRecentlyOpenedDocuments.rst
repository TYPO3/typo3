..  include:: /Includes.rst.txt

..  _feature-107906-1761739282:

===================================================
Feature: #107906 - Recently opened documents widget
===================================================

See :issue:`107906`

Description
===========

A new `Recently Opened Documents` dashboard widget has been introduced to
display documents that are currently open or recently accessed in the
TYPO3 backend. This allows editors and administrators to quickly return to
their work and access frequently edited content without navigating through
the page tree or search.

The widget provides a configurable interface where users can set the number
of documents to display, offering flexibility based on their workflow
needs. Each document entry shows the record icon and title for easy
identification and quick access.

Key benefits:

*   Displays recently opened documents with icons and titles
*   Provides quick access to ongoing work without searching
*   Offers configurable display limits for different workflow needs
*   Shows document type icons for visual identification
*   Improves editing efficiency by reducing navigation time
*   Displays documents in reverse chronological order (most recent first)

The widget retrieves documents from the FormEngine session data, ensuring
that only currently open or recently accessed documents are displayed.
Deleted records are filtered out to maintain data accuracy.

Impact
======

This feature improves editorial efficiency by providing immediate access to
recently opened documents in the dashboard interface, reducing
the time spent navigating through the backend to resume work.

..  index:: Backend, ext:dashboard, Usability
