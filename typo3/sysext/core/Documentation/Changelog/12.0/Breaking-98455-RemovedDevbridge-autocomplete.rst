.. include:: /Includes.rst.txt

.. _breaking-98455-1664349649:

=================================================
Breaking: #98455 - Removed devbridge-autocomplete
=================================================

See :issue:`98455`

Description
===========

The jQuery library :js:`devbridge-autocomplete` used to provide an auto-suggest
feature has been removed from TYPO3 along with its CSS.

Impact
======

Importing the module :js:`jquery/autocomplete` and calling `.autocomplete()` on
a jQuery object will lead to JavaScript errors.

Affected installations
======================

All extensions relying on :js:`devbridge-autocomplete` are affected.

Migration
=========

If absolutely mandatory, install and import :js:`devbridge-autocomplete` in your
extension or site package.

Otherwise, remove the import of :js:`jquery/autocomplete` from your JavaScript
code and implement an auto-suggest feature on your own by combining the modules
:js:`@typo3/core/event/debounce-event` and :js:`@typo3/core/ajax/ajax-request`.

Listen on the :js:`input` event on an input field using :js:`DebounceEvent`,
send an AJAX request to the endpoint, and render the returned result list.
Finally, bind a :js:`click` event handler on each result item that executes the
desired action and hides the result list again.

.. index:: Backend, JavaScript, NotScanned, ext:backend
