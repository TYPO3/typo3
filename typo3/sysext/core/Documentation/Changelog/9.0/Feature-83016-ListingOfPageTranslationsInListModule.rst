.. include:: ../../Includes.txt

=============================================================
Feature: #83016 - Listing of page translations in list module
=============================================================

See :issue:`83016`

Description
===========

Listing and editing translations of the current page are re-introduced for the List module. This feature
was previously available for v8 and below due to the concept of "pages_language_overlay", which resided on the
actually current page.

However, due to the removal of "pages_language_overlay" database table, page translations are only accessible
when visiting the list module of the parent page.

The original behaviour was now reintroduced, but with an improved visibility and additional restrictions.


Impact
======

When inside the list module, page translations are always shown as first table listing on top of the module.

.. index:: Backend
