.. include:: /Includes.rst.txt

.. _feature-91082:

========================================================================
Feature: #91082 - Add new option "show scheduled records" to admin panel
========================================================================

See :issue:`91082`

Description
===========

The admin panel has a new checkbox to show all records regardless of start and end time restrictions.

This is especially helpful if content on a page has different start and end times and an editor
wants to have an overview of all elements on that page. Without this option an editor has to simulate
multiple dates to be able to see all records, because records which are visible on one date, can be invisible on another.

Impact
======

With this new option a TYPO3 user is able to view all records on
a page regardless of the start and end time.

.. index:: Frontend, ext:adminpanel
