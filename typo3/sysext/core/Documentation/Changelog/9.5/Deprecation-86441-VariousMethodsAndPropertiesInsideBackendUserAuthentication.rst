.. include:: ../../Includes.txt

=====================================================================================
Deprecation: #86441 - Various methods and properties inside BackendUserAuthentication
=====================================================================================

See :issue:`86441`

Description
===========

Some minor changes have been made with :php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication` in order
to continue cleaning up the code.

The property :php:`checkWorkspaceCurrent_cache` has been marked as protected, as it is an internal cache.

The second argument of method :php:`modAccess()` has been marked as deprecated, as the method should not trigger runtime exceptions anymore.

The method :php:`isPSet()` has been marked as deprecated.

The following - mostly workspaces-related - methods have been marked as "internal":

* :php:`workspaceCannotEditOfflineVersion()`
* :php:`workspacePublishAccess()`
* :php:`workspaceSwapAccess()`
* :php:`workspaceCannotEditOfflineVersion()`


Impact
======

Calling the deprecated method or the protected property will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with enhanced workspace or permission functionality.


Migration
=========

Avoid using the methods, and re-implement the functionality on your own, if necessary.

.. index:: Frontend, FullyScanned, PHP-API
