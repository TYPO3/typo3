.. include:: /Includes.rst.txt

=============================================================================
Feature: #95364 - Event to modify frontend user groups without authentication
=============================================================================

See :issue:`95364`

Description
===========

Prior to TYPO3 v11, the "getGroupsFE" authentication service
allowed to add and manipulate frontend user groups to be attached
to a FrontendUserAuthentication request during runtime.

Extensions use this approach to attach certain properties for
customization (for example country or region of a website user)
dynamically for a specific request.

This functionality was removed during the refactoring of the
authentication services (see :issue:`93108`).

A new Event :php:`ModifyResolvedFrontendGroupsEvent` has now been
introduced to modify user groups, even if there is no
authenticated user in place.


Impact
======

Use the new PSR-14 event to attach frontend user groups dynamically
during a frontend request.

.. index:: Frontend, PHP-API, ext:frontend
