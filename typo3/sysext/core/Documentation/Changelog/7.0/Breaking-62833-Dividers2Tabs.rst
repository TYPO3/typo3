
.. include:: /Includes.rst.txt

======================================================
Breaking: #62833 - Removed dividers2tabs functionality
======================================================

See :issue:`62833`

Description
===========

The "dividers2tabs" option in the ctrl section of TCA allows to show tabs in FormEngine while editing records,
instead of showing all fields in one long column. This behaviour is the default since some TYPO3 versions.

This option has no effect anymore, as "dividers2tabs" is removed for TYPO3 CMS Core. The option can also be
safely removed from any extension that adds TCA data.


Impact
======

A third-party extension that overrides the dividers2tabs option for an existing table or that adds a TCA table
with this option disabled will have a record editing with tabs from now on.


Affected installations
======================

Installations with 3rd-party extensions with TCA tables that have "dividers2tabs" disabled.


.. index:: TCA, Backend
