.. include:: /Includes.rst.txt

.. _breaking-97265:

================================================
Breaking: #97265 - Simplified access mode system
================================================

See :issue:`97265`

Description
===========

In preparation of a deployable backend access rights system based on
configuration files, some rarely used details of the permission system
have been streamlined and simplified:

* The global configuration option :php:`TYPO3_CONF_VARS['BE']['explicitADmode']`
  has been removed and is not evaluated anymore.

* The only valid value for TCA config option :php:`authMode` on :php:`'type' => 'select'`
  fields is now :php:`explicitAllow`. The values :php:`explicitDeny` and :php:`individual`
  are invalid and no longer evaluated.

* With removal of :php:`authMode' => 'individual'` for TCA select fields, the sixth
  :php:`items` option is obsolete and removed. The values :php:`EXPL_ALLOW` and
  :php:`EXPL_DENY` are without any effect.

* Handling of TCA config option :php:`authMode_enforce` has been removed.

* The fourth tuple of :sql:`be_groups` field :sql:`explicit_allowdeny` that was
  previously set to either :sql:`ALLOW` or :sql:`DENY` is removed.

* The fourth argument on :php:`BackendUserAuthentication->checkAuthMode()` has
  been removed.

Impact
======

Using any of the above removed options will trigger a PHP :php:`E_USER_DEPRECATED` error.
Using :php:`explicitDeny` and :php:`individual` as value for TCA config option
:php:`authMode` is no longer supported by the system and may need manual
adaptions. Accessing :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode']`
may lead to a PHP warning level error.

Affected Installations
======================

* Instances with extensions using :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode']`.
  The extension scanner will typically find affected instances.

* Instances with TCA select fields using :php:`'authMode' => 'explicitDeny'`.

* Instances with TCA select fields using :php:`'authMode' => 'individual'` and select
  items being set to :php:`EXPL_ALLOW` or :php:`EXPL_DENY`. This is a very rarely used
  option and it's unlikely modern extensions use this in practice: There is not a single
  extension in the TER using this option combination and it's unlikely to be used in
  custom extensions, either.

* Instances manually dealing with the :sql:`explicit_allowdeny` of table :sql:`be_groups`
  may be affected if they expect the fourth field being set to :sql:`ALLOW` or :sql:`DENY`.
  This is unlikely since the Core provides an API for this field using
  :php:`BackendUserAuthentication->checkAuthMode()`.

* Instances calling :php:`BackendUserAuthentication->checkAuthMode()` with four instead of
  three arguments. The extension scanner will find usages as weak match.

* Instances using :php:`authMode_enforce` for :php:`'type' => 'select'` fields.

Migration
=========

The majority of instances does not need to take care of anything. The values of the database
field :sql:`explicit_allowdeny` for table :sql:`be_groups` are updated with an upgrade wizard.
This should be executed. The following parts of this section outline options for rare cases
if specific seldom used options are used.

Accessing explicitADmode
------------------------

The handling of :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode']` has been changed as
if it is always set to :php:`explicitAllow`. Extensions should not assume this global array
key being set anymore since TYPO3 Core v12. Extensions that need to stay compatible with v11
and v12 should fall back: :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow'`.

Using authMode_enforce='strict'
-------------------------------

Extensions with select fields using :php:`authMode` previously had different handling
if :php:`authMode_enforce => 'strict'` has been set: Let's say an editor accesses a record
with an :php:`authMode` field being set to a value it has no access to. With :php:`authMode_enforce`
*not* being set to :php:`strict`, the editor was still able to edit the record and set the value
to something it had access to. With :php:`authMode_enforce` being set to :php:`strict`, the editor
was not allowed to access the record. This has been streamlined: The backend interface no longer
renders those records for the editor and an "access denied" message is rendered instead. To
prevent this, a group this editor is member of needs to be adapted to allow access to this
particular value in the "Explicitly allow field values" (:sql:`explicit_allowdeny`) field.

Using authMode='explicitDeny'
-----------------------------

The "deny list" approach for single field values has been removed, the only allowed option
for :php:`authMode` is :php:`explicitAllow`. Extensions using config value :php:`explicitDeny`
should be adapted to switch to :php:`explicitAllow` instead. The upgrade wizard
"Migrate backend groups "explicit_allowdeny" field to simplified format." that transfers
existing :sql:`be_groups` rows to the new format *drops* any :sql:`DENY` fields and instructs
admins no set new access rights of affected backend groups.

Using authMode='individual'
---------------------------

Handling of :php:`authMode` being set to :php:`individual` has been fully dropped. There is
no Core-provided alternative. This has been an obscure setting since ever and there is no
direct migration. Extension that rely on this handling need to find a substitution based on
Core hooks, Core events or other existing Core API functionality.

.. index:: Backend, Database, LocalConfiguration, PHP-API, TCA, PartiallyScanned, ext:core
