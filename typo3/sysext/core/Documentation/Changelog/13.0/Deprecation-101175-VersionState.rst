.. include:: /Includes.rst.txt

.. _deprecation-101175-1687941546:

==============================================
Deprecation: #101175 - Methods in VersionState
==============================================

See :issue:`101175`

Description
===========

The methods :php:`\TYPO3\CMS\Core\Versioning\VersionState::cast()` and
:php:`\TYPO3\CMS\Core\Versioning\VersionState->equals()` have been
marked as deprecated.

Impact
======

Calling the methods :php:`\TYPO3\CMS\Core\Versioning\VersionState::cast()`
and :php:`\TYPO3\CMS\Core\Versioning\VersionState->equals()` will trigger a
PHP deprecation warning.

Affected installations
======================

TYPO3 installations calling :php:`\TYPO3\CMS\Core\Versioning\VersionState::cast()`
and :php:`\TYPO3\CMS\Core\Versioning\VersionState->equals()`.

Migration
=========

Before:

..  code-block:: php

    $versionState = \TYPO3\CMS\Core\Versioning\VersionState::cast($value);
    if ($versionState->equals(VersionState::MOVE_POINTER) {
        // ...
    }

After:

..  code-block:: php

    $versionState = \TYPO3\CMS\Core\Versioning\VersionState::tryFrom($value)
    if ($versionState === VersionState::MOVE_POINTER) {
        // ...
    }

.. index:: Backend, NotScanned
