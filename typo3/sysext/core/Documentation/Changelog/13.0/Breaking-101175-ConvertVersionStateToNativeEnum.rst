.. include:: /Includes.rst.txt

.. _breaking-VersionState-1687856333:

==============================================================
Breaking: #101175 - Convert VersionState to native backed enum
==============================================================

See :issue:`101175`

Description
===========

The class :php:`\TYPO3\CMS\Core\Versioning\VersionState` is now
converted to a native PHP backed enum.

Impact
======

Since :php:`\TYPO3\CMS\Core\Versioning\VersionState` is no longer
a class, the existing class constants are no longer available, but are
enum instances instead.

In addition it's not possible to instantiate it anymore or call
the :php:`equals()` method.

Affected installations
======================

TYPO3 code using the following code:

Using the following class constants:

- :php:`\TYPO3\CMS\Core\Versioning\VersionState::DEFAULT_STATE`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::NEW_PLACEHOLDER`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::DELETE_PLACEHOLDER`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::MOVE_POINTER`

Class instantiation:

- :php:`new \TYPO3\CMS\Core\Versioning\(VersionState::*->value)`

where * denotes one of the enum values.

Method invocation:

- :php:`\TYPO3\CMS\Core\Versioning\VersionState::cast()`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::cast()->equals()`

Migration
=========

Use the new syntax for getting the values:

- :php:`\TYPO3\CMS\Core\Versioning\VersionState::DEFAULT_STATE->value`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::NEW_PLACEHOLDER->value`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::DELETE_PLACEHOLDER->value`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::MOVE_POINTER->value`

Class instantiation should be replaced by:

- :php:`\TYPO3\CMS\Core\Versioning\VersionState::tryFrom($row['t3ver_state'])`

Method invocation of :php:`cast()`/:php:`equals()` should be replaced by:

- :php:`\TYPO3\CMS\Core\Versioning\VersionState::tryFrom(...)`
- :php:`\TYPO3\CMS\Core\Versioning\VersionState::tryFrom(...) === VersionState::MOVE_POINTER`

.. index:: Backend, NotScanned, ext:backend, ext:core, ext:frontend, ext:workspaces
