.. include:: /Includes.rst.txt

.. _deprecation-111328-1793598679:

===========================================================
Deprecation: #111328 - Deprecate hooks for page permissions
===========================================================

See :issue:`111328`

Description
===========

The following hooks have been marked as deprecated:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms']`

They are used to change the page permissions of backend users, for example
to apply access control lists.

Impact
======

If one of the hooks is registered in a TYPO3 installation, a PHP
:php:`E_USER_DEPRECATED` error is triggered when the permissions of a backend
user are evaluated.

The hooks keep working until they are removed. They are applied to the
methods of :php:`BackendUserAuthentication` and to the decisions of security
voters on pages alike, and are called with the backend user object the
principal of the decision was built from. Without such an object, a registered
hook denies all page permissions of users who are not administrators.

While a :php:`getPagePermsClause` hook is registered, the check whether a page
is within the webmounts of a user selects the pages up to the webmount with
the clause, instead of evaluating their permissions in PHP.


Affected installations
======================

TYPO3 installations with custom extensions using one of these hooks.

The extension scanner detects any usage of the hooks.


Migration
=========

Migrate to the newly introduced PSR-14 events:

* :php:`\TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionConstraintEvent`
  replaces the :php:`getPagePermsClause` hook.
* :php:`\TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionsEvent`
  replaces the :php:`calcPerms` hook.

The parameters of the hooks are available from the events: the page record and
the permissions of the :php:`calcPerms` hook, and the permission bitmask of the
:php:`getPagePermsClause` hook. Instead of appending SQL to the clause, build
constraints with the expression builder of the event and add them with
:php:`addConstraint()`. Instead of the backend user object, the events provide
the uid, the group uids and the workspace of the user.

..  seealso::
    :ref:`feature-111328-1793598679`

.. index:: Backend, PHP-API, FullyScanned, ext:core
