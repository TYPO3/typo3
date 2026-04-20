..  include:: /Includes.rst.txt

..  _feature-106153-1770150965:

========================================================================
Feature: #106153 - Improve DebugExceptionHandler with copy functionality
========================================================================

See :issue:`106153`

Description
===========

The debugging exception handler, which can be configured for backend and
frontend error reporting, provides a large stack trace with details about
an error.

This is often essential when reporting bugs in TYPO3 or debugging custom
code.

The output has now been improved:

*   Each stack trace segment's file name and the line number where the error
    occurred now has a "Copy path" button. Clicking it copies the full
    path, file name, and line number to the browser clipboard.

*   The bottom of the page shows two buttons: one toggles the output above
    to hide or reveal the file contents, and the other copies the entire
    stack trace in plain text format so that it can be forwarded in error
    reports.

*   A brief section explains what a "stack trace" is, and a jump link is
    available to go from the top of the page to the export section.

..  hint::

    The "copy to clipboard" functionality is based on JavaScript. Some
    browsers, such as Firefox, allow access to the clipboard only when
    the site is accessed via `https`. If copying fails, the condensed
    output that would have been written to the clipboard is shown instead
    in a box below so it can be copied manually.

Thanks to Olivier Dobberkau, whose extension
`https://github.com/dkd-dobberkau/enhanced-error-handler`__ inspired the
rework of this feature.

Impact
======

Errors and their stack traces can now be copied and forwarded much more
easily for support requests, without the need to save an HTML file or take
screenshots.

File names and line numbers of errors can also be copied easily and
inserted into an IDE to jump directly to the relevant code.

..  index:: Backend, ext:core
