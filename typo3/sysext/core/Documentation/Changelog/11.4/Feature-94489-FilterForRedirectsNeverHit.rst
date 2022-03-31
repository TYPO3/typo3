.. include:: /Includes.rst.txt

================================================
Feature: #94489 - Filter for redirects never hit
================================================

See :issue:`94489`

Description
===========

Since :issue:`89115` TYPO3 is able to automatically create redirects
whenever an editor changes a slug of a page. This is a really handy feature.
However on large sites this could quickly lead to a lot of redirects, which
may will never be used by any website visitor.

To support editors by managing their redirects, a new filter option
:guilabel:`Never hit` has been added to the Redirects modules' filter.
Activating this option therefore filters the list for redirects, which
where never hit before.

.. note::

   The filter option will only be available, if the "Redirects hit count"
   feature is enabled, see:
   :doc:`#83677 <../9.1/Feature-83677-GloballyDisableenableRedirectHitStatistics>`.

Impact
======

A new filter option :guilabel:`Never hit` is available in the Redirects
module, allowing editors to filter for redirects, which were never hit before.

.. index:: Backend, ext:redirects
