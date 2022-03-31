.. include:: /Includes.rst.txt

=============================================================================
Breaking: #93062 - Various group-related public properties in BE_USER removed
=============================================================================

See :issue:`93062`

Description
===========

The PHP API class :php:`BackendUserAuthentication` was built back in
PHP4 days and had a few public properties which have been removed.

Their purpose was to store data between methods while resolving
groups, where there are other methods containing all group-related
information already anyways.

- :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->groupList`
- :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication->includeGroupArray`


Impact
======

Accessing or setting these properties will raise a PHP warning.


Affected Installations
======================

TYPO3 installations with third-party extensions accessing these
:php:`BackendUserAuthentication` properties, which is highly unlikely,
or because they were built 10 years ago, still accessing these properties.


Migration
=========

Use :php:`BackendUserAuthentication->userGroupsUID` (array of group UIDs) instead,
which contains the groups in the proper order on how they were resolved.

If this is not needed directly, it is usually highly recommended to use the
Context API's "backend.user" aspect to retrieve groups of a
backend user.

.. index:: PHP-API, FullyScanned, ext:core
