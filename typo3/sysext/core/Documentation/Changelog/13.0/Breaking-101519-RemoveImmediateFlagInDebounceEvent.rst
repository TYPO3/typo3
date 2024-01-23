.. include:: /Includes.rst.txt

.. _breaking-101519-1690884375:

==============================================================
Breaking: #101519 - Remove `immediate` flag in `DebounceEvent`
==============================================================

See :issue:`101519`

Description
===========

With the introduction in TYPO3 v10, the :js:`DebounceEvent` module had the
possibility to shift the event handler execution to the beginning of the
debounce sequence, enabled via the optional :js:`immediate` parameter.

The parameter is unused in TYPO3 and using this feature has a negative impact
on :abbr:`UX (User Experience)`. If used, the event handler is directly executed
and the user has to wait a specific time after the last event was triggered,
before any further execution is possible.

The flag :js:`immediate` has been therefore removed.


Impact
======

The :js:`DebounceEvent` module now always waits until a certain time has passed
after the last trigger of the event happened before executing the event handler.
This is mostly used in potential heavy tasks, for example, an Ajax request that
is sent depending on the content of a search field.


Affected installations
======================

All extensions using the removed flag are affected.


Migration
=========

There is no direct migration possible. An extension author either may
re-implement the removed behavior manually, or use the :js:`ThrottleEvent`
module, providing a similar behavior.

.. index:: JavaScript, NotScanned, ext:core
