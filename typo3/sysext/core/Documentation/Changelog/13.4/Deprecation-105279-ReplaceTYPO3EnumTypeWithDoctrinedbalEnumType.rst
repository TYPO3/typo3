..  include:: /Includes.rst.txt

..  _deprecation-105279-1728669356:

=========================================================================
Deprecation: #105279 - Replace TYPO3 EnumType with doctrine/dbal EnumType
=========================================================================

See :issue:`105279`

Description
===========

TYPO3 provided a custom Doctrine DBAL column type implementation for native
:sql:`ENUM` that was only compatible with MySQL and MariaDB connections.

The Doctrine DBAL Team implemented `ENUM` support with
`Release 4.2.0 <https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md>`__ in
class :php:`\Doctrine\DBAL\Types\EnumType`, only supporting MySQL and MariaDB
as well.

TYPO3 removed its custom implementation with TYPO3 v13.4.0.

Class :php:`\TYPO3\CMS\Core\Database\Schema\Types\EnumType` has been marked as deprecated
and is replaced with an class alias of :php-short:`\Doctrine\DBAL\Types\EnumType`.
The alias will be removed with TYPO3 v14.

See `Release 4.2.0 <https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md>`__

Impact
======

doctrine/dbal >= 4.2.0 is incompatible with TYPO3 versions before v13.4.0.
Composer based instances using TYPO3 v13.3 or older should add an according
conflict to their :file:`composer.json`.

Affected installations
======================

Instances using the the :sql:`ENUM` type directly or by any 3rd party
extension using TYPO3 13.0 to 13.3 in composer mode will break, when
the `doctrine/dbal` composer packages is updated to `4.2.0` or newer.

Migration
=========

Upgrade (directly) to TYPO3 v13.4 or ensure to avoid updating to
Doctrine DBAL 4.2.x or newer versions in composer based instances.

Replace :php:`\TYPO3\CMS\Core\Database\Schema\Types\EnumType` type
declarations with :php:`\Doctrine\DBAL\Types\EnumType`.

..  index:: Database, PHP-API, NotScanned, ext:core
