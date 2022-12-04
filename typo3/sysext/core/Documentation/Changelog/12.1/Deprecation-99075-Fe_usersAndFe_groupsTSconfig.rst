.. include:: /Includes.rst.txt

.. _deprecation-99075-1668337874:

=====================================================
Deprecation: #99075 - fe_users and fe_groups TSconfig
=====================================================

See :issue:`99075`

Description
===========

The two database fields :sql:`fe_users.TSconfig` and :sql:`fe_groups.TSconfig`
have been marked as deprecated in TYPO3 v12 and will be removed in v13 along with
its PHP API.


Impact
======

Backend users and groups provide these :guilabel:`TSconfig` fields as well, they are the base
of the well-known `UserTsConfig` configuration to specify rendering and behavior of
TYPO3 backend-related details. This is kept.

Frontend users and groups had these fields as well, they are unused by TYPO3 core
and only a few extensions ever used them.

The frontend user and group related database fields, the editing functionality of
these fields in the backend (TCA), and according PHP API will be removed with
TYPO3 v13. In detail:

* Database field :sql:`fe_users.TSconfig` will be removed from the table definition.
* Database field :sql:`fe_groups.TSconfig` will be removed from the table definition.
* Rendering and editing setup of field :php:`fe_users.TSconfig` will be removed from TCA.
* Rendering and editing setup of field :php:`fe_groups.TSconfig` will be removed from TCA.
* Default configuration value :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig']`
  will be removed.
* PHP method :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->getUserTSconf()`
  will be removed.


Affected installations
======================

Instances are relatively unlikely to be affected: Only a few extensions ever used these
fields to store configuration for frontend users, most likely extensions related to
additional authentication mechanisms.

The extension scanner will find extensions that access :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultUserTSconfig']`
or call :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->getUserTSconf()` as "weak" matches.


Migration
=========

Extensions should avoid using the fields to store and access configuration in a
TypoScript-like syntax. Affected extensions should add own fields prefixed with an
extension-specific key, or switch to a file-based configuration approach, if possible.

To simulate the deprecated logic, extensions may extract the deprecated parsing logic from
:php:`FrontendUserAuthentication` class into an own service, probably by fetching group data
using :php:`\TYPO3\CMS\Core\Authentication\GroupResolver`, and then merge and parse group
data of the field with frontend user-specific data.


.. index:: Database, Frontend, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned, ext:frontend
