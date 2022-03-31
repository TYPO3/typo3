.. include:: /Includes.rst.txt

================================================================
Feature: #93117 - Add reset button to Backend User module filter
================================================================

See :issue:`93117`

Description
===========

The backend user module provides a filter functionality with a
couple of options to filter for. The filter state (selected options)
is also saved in the backend user settings, which means,
the filter state will remain after switching to another module.

Since there are a lot of filter options which previously had to be
reset one by one, a new reset button is now introduced. This button
allows to reset the whole filter at once.


Impact
======

It's now possible to reset the whole backend user module filter
at once, using the new reset button.

.. index:: Backend, ext:beuser
