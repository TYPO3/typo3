..  include:: /Includes.rst.txt

..  _feature-106153-1770150965:

========================================================================
Feature: #106153 - Improve DebugExceptionHandler with copy functionality
========================================================================

See :issue:`106153`

Description
===========

The debugging exception error handler (which can be set for backend and
frontend error reporting) provides a large stack trace with details of
an error.

This is often vital when reporting bugs in TYPO3 or debugging custom
code.

The output has now been improved:

*   Each stack trace segment's filename and line where the error occurred
    now has a "copy path" button. Clicking on it will copy the whole
    path, filename and line number to the browser's clipboard.

*   The bottom of the page shows two buttons: One to toggle the output
    above to hide or reveals the file's contents. And another to copy
    the whole stack trace in plain text format, so that it can be forwarded
    in error reports.

*   A brief section explains what a 'stack trace' is, and a jump functionality
    to get from the top to the export section is available.

..  hint::

    The "copy to clipboard" functionality is based on JavaScript. Some
    browsers like Firefox only allow access to the clipboard when
    accessing the site via `https`. When copying fails, the condensed
    output that would have been written to the clipboard is instead revealed
    in a box below, so it can be manually copied.

Thanks to Olivier Dobberkau, whose extension `https://github.com/dkd-dobberkau/enhanced-error-handler`__
inspired rework of this feature.

Impact
======

Errors and their stack traces can now be much more easily copied and forwarded
for support questions, without the need to dump a HTML file or even to make
screenshots.

File names and the lines of an occurred error can easily be copied to be inserted
into the IDE to jump directly to the related code.

..  index:: TCA, Backend, ext:core
