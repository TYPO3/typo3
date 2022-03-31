.. include:: /Includes.rst.txt

=========================================================================
Feature: #86818 - Reintroduce keyboard accessible version of the pagetree
=========================================================================

See :issue:`86818`

Description
===========

This feature makes the pagetree focusable via keyboard using the tab key. Now it is also possible to use
arrows, home and end keys in order to navigate through the pagetree. Besides that, using enter and
space keys will open the page in the according content area.

Of course, it is still possible to use both mouse and keyboard navigation.

This change follows the best practices as described in WAI-ARIA Authoring Practices 1.1,
see the `W3 document`_ for further reading.

.. _W3 document: https://www.w3.org/TR/wai-aria-practices-1.1/#keyboard-interaction-22

Impact
======

Added :html:`tabindex`, :html:`role`, :html:`aria-*` and :html:`id` attributes to pagetree elements
as advised in WAI-ARIA Authoring Practices 1.1. Screenreaders are now able to recognize the pagetree as
tree element.

.. index:: Backend, JavaScript, ext:backend
