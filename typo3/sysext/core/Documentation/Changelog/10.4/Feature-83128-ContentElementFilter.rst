.. include:: /Includes.rst.txt

========================================
Feature: #83128 - Content Element Filter
========================================

See :issue:`83128`

Description
===========

A backend user is now able to search for a set of content types in the "New
Content Element" wizard.

Impact
======

If a user enters a search query, any content type whose title or description
doesn't match the query are hidden to the user. Since content types are grouped in
tabs, tabs without content get disabled to the user. If the current active tab
becomes empty, the next available tab is activated.

.. index:: Backend, ext:backend
