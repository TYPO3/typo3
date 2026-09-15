..  include:: /Includes.rst.txt

..  _important-110653-1789496364:

===============================================================================
Important: #110653 - Unidirectional MM relations are copied during localization
===============================================================================

See :issue:`110653`

Description
===========

When a translatable record with a unidirectional many-to-many relation is
localized, the relation is now copied to the translated record.

Previously, every MM field without :php:`MM_opposite_field` was considered the
local side of a bidirectional relation. This also matched unidirectional MM
fields and caused their relations to be omitted during localization.

TYPO3 now omits relations only when the field declares
:php:`MM_oppositeUsage` or another field points back to it through
:php:`MM_opposite_field`.

Affected installations
======================

Installations with translatable records that use unidirectional MM fields are
affected.

Migration
=========

No configuration change is required. Relations already omitted from existing
translations must be restored manually.

..  index:: TCA, ext:core
