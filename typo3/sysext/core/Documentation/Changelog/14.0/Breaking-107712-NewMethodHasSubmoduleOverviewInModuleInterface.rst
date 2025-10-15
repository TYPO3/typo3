..  include:: /Includes.rst.txt

..  _breaking-107712-1760548718:

===============================================================================
Breaking: #107712 - New method hasSubmoduleOverview() in ModuleInterface
===============================================================================

See :issue:`107712`

Description
===========

The :php:`\TYPO3\CMS\Backend\Module\ModuleInterface` has been extended with a
new method :php:`hasSubmoduleOverview()` to support the new card-based
submodule overview feature introduced in :ref:`feature-107712-1760548718`.

Impact
======

All custom implementations of :php:`\TYPO3\CMS\Backend\Module\ModuleInterface`
must now implement the new :php:`hasSubmoduleOverview(): bool` method.

Existing implementations that do not implement this method will fail with a
fatal PHP error.

Affected installations
======================

TYPO3 installations with custom PHP code that directly implements the
:php:`ModuleInterface`. This is uncommon, as most modules use the provided
:php:`\TYPO3\CMS\Backend\Module\Module` class or extend from
:php:`\TYPO3\CMS\Backend\Module\BaseModule`.

Migration
=========

Add the :php:`hasSubmoduleOverview()` method to your custom
:php:`ModuleInterface` implementation.

The method should typically return the value from the module's configuration
rather than a static boolean value:

..  code-block:: php

    public function hasSubmoduleOverview(): bool
    {
        // Return the configured value, defaulting to false
        return $this->configuration['showSubmoduleOverview'] ?? false;
    }

This allows the behavior to be controlled through the module configuration.

..  index:: Backend, PHP-API, NotScanned, ext:backend
