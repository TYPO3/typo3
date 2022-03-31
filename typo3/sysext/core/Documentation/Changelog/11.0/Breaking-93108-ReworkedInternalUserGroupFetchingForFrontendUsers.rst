.. include:: /Includes.rst.txt

===========================================================================
Breaking: #93108 - Reworked internal user group fetching for frontend users
===========================================================================

See :issue:`93108`

Description
===========

Frontend users now support the same loading mechanism for usergroups as
backend users, making it easier to exchange functionality by unifying the
code base.

In previous versions, the Authentication Service was used to fetch groups and
enable groups, which can be achieved via the :php:`AfterGroupsResolved` PSR-14 event.

Fetching groups and permissions belongs to authorization, and not authentication
(identities), where this removal is conceptually suited outside of
authentication services.

The respective methods and properties

* :php:`TYPO3\CMS\Core\Authentication\AuthenticationService->getGroups()`
* :php:`TYPO3\CMS\Core\Authentication\AuthenticationService->getSubGroups()`
* :php:`TYPO3\CMS\Core\Authentication\AuthenticationService->db_groups`

have been removed.

At the same time, much of the PHP 4-based code base from frontend users
within :php:`FrontendUserAuthentication` has been marked as internal or removed
completely, allowing this information not to be read or modified from the
outside anymore.

* :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->TSdataArray`
* :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->userTS`
* :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->userTSUpdated`
* :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->userData_change`


Impact
======

The authentication services "subtype", "getGroupsFE" and "authGroupsFE" are never
executed anymore.

Accessing the properties will trigger a PHP warning.

Affected Installations
======================

TYPO3 installations with custom extensions handling group related authentication
services, e.g. LDAP extensions.


Migration
=========

Use the mentioned PSR-14 event to load custom groups from different sources or
based on rules, or use a custom PSR-15 middleware to inject custom groups,
not based on a specific user, but related to a request.

It is possible to keep extensions compatible with TYPO3 v10 and v11 by keeping
the AuthenticationService "getGroupsFE" subtype, and adding the PSR-14 event to
an extension.

.. index:: Frontend, FullyScanned, ext:frontend
