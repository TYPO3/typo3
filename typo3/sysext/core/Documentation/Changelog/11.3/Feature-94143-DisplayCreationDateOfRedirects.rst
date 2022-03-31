.. include:: /Includes.rst.txt

====================================================
Feature: #94143 - Display creation date of redirects
====================================================

See :issue:`94143`

Description
===========

The EXT:redirects system extension provides a straightforward way of managing
redirects within a TYPO3 installation. The corresponding backend module
can be used to filter, create and analyse those redirects.

Measuring the redirects performance is possible via the "Redirects hit count"
feature, which - if enabled - displays the amount of hits for each redirect
in the listing. More detailed information, for example the last hit, are
available in the records' "Statistics" tab.

This tab is now extended to also display the creation date of the redirect.
This is especially useful to set the amount of hits in relation to the period
of the redirect existence.

.. note::

   The creation date will only be shown if the "Redirects hit count"
   feature is enabled, see:
   :doc:`#83677 <../9.1/Feature-83677-GloballyDisableenableRedirectHitStatistics>`.

Impact
======

The creation date of a redirect is now shown in the :guilabel:`Statistics` tab
of the record's editing mask.

.. index:: Backend, TCA, ext:redirects
