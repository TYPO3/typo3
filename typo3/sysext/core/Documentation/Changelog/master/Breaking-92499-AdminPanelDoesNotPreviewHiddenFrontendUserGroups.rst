.. include:: ../../Includes.txt

==========================================================================
Breaking: #92499 - AdminPanel does not preview hidden Frontend User Groups
==========================================================================

See :issue:`92499`

Description
===========

Admin Panel previously allowed to also render a page with frontend groups that
were hidden / disabled. This feature has been removed,
in order to ensure consistency for the authentication process.

The AbstractUserAuthentication property `showHiddenRecords` which
was used to transfer this information is removed.


Impact
======

The Admin Panel selector now only shows a list of non-hidden groups
to simulate from.

Using the removed PHP property `showHiddenRecords` will result
in a PHP notice.


Affected Installations
======================

TYPO3 installations with Admin Panel activated and Frontend Groups
that are disabled.


Migration
=========

It is recommended to include groups where no user is assigned to
for simulation purposes, if this feature is needed to preview
content.

.. index:: Frontend, ext:adminpanel, FullyScanned
