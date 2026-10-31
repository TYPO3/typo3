..  include:: /Includes.rst.txt

..  _feature-111301-1793444414:

================================================================================
Feature: #111301 - Declare Record API public and expose page permission settings
================================================================================

See :issue:`111301`

Description
===========

Record API
----------

The objects representing database records, introduced with TYPO3 v13, are
now part of the public TYPO3 Core API:

*   :php:`\TYPO3\CMS\Core\Domain\Record`
*   :php:`\TYPO3\CMS\Core\Domain\RawRecord`
*   :php:`\TYPO3\CMS\Core\Domain\Page`
*   :php:`\TYPO3\CMS\Core\Domain\Record\ComputedProperties`
*   :php:`\TYPO3\CMS\Core\Domain\Record\SystemProperties`
*   :php:`\TYPO3\CMS\Core\Domain\Record\LanguageInfo`
*   :php:`\TYPO3\CMS\Core\Domain\Record\VersionInfo`
*   :php:`\TYPO3\CMS\Core\Domain\RecordFactory`, which creates these objects

These objects were already handed out by public API, for example by
:php:`\TYPO3\CMS\Core\Domain\RecordInterface::getRawRecord()`, by the
:php-short:`\TYPO3\CMS\Core\Domain\Event\BeforePageIsRetrievedEvent` or
by the `record-transformation` data processor in Fluid templates.

Page permission settings
------------------------

The new method :php:`\TYPO3\CMS\Core\Domain\Page::getPermissions()`
returns the permission settings of a page as an immutable value object
:php:`\TYPO3\CMS\Core\Domain\Record\PagePermissions`:

*   :php:`getOwnerUserId()` and :php:`getOwnerGroupId()` return the owning
    backend user and backend user group.
*   :php:`getOwnerPermissions()`, :php:`getGroupPermissions()` and
    :php:`getEverybodyPermissions()` return a
    :php-short:`\TYPO3\CMS\Core\Type\Bitmask\Permission` object each.

The permission fields are not part of the TCA of the `pages` table, so they
are not available as regular properties of a :php-short:`\TYPO3\CMS\Core\Domain\Page`
object. :php:`getPermissions()` reads them from the raw database row and
returns :php:`null` if the page was retrieved without them.

The settings are the same for every backend user. Whether a specific backend
user is granted a permission is still determined by
:php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication`.

..  code-block:: php
    :caption: Check whether everybody may view a page

    $permissions = $page->getPermissions();
    if ($permissions?->getEverybodyPermissions()->showPagePermissionIsGranted()) {
        // ...
    }

Impact
======

Extensions can type against the record objects and rely on them following
the regular deprecation policy of the TYPO3 Core. The permission settings of
a page are available in a typed way, without reading the `perms_*` fields of
the database row.

..  index:: PHP-API, ext:core
