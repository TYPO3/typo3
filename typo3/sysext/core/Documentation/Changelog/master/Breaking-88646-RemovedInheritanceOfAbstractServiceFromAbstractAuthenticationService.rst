.. include:: ../../Includes.txt

============================================================================================
Breaking: #88646 - Removed inheritance of AbstractService from AbstractAuthenticationService
============================================================================================

See :issue:`88646`

Description
===========

The AbstractAuthenticationService PHP class is used for any kind of Authentication
or Authorization towards Backend Users and Frontend Users.

It was previously based on :php:`AbstractService` for any kind of Service API, which
also includes manipulating files and execution of external applications, which is
there for legacy reasons since TYPO3 3.x, where the Service API via :php:`GeneralUtility::makeInstanceService` was added.

In order to refactor the Authentication API, the AbstractAuthenticationService
class does not inherit from AbstractService anymore. Instead, the most required
methods for executing a service is added to the Abstract class directly.


Impact
======

Any calls or checks on the AbstractAuthenticationService class or methods, properties or constants that reside within AbstractService will result in PHP errors or warnings.

Since AbstractAuthenticationService is used for most custom Authentication APIs,
this could affect some of the hooks or custom authentication providers available.


Affected Installations
======================

TYPO3 installations that have custom Authentication providers for frontend or backend
users / groups - e.g. LDAP or Two-Factor-Authentication.


Migration
=========

If your custom Authentication Service extends from AbstractAuthenticationService
but requires methods or properties from AbstractService, ensure to copy over the
necessary methods/properties/constants into your custom Authentication provider.

.. index:: PHP-API, NotScanned