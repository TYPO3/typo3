..  include:: /Includes.rst.txt

..  _breaking-107712-1760548718:

===============================================================================
Breaking: #107712 - New method hasSubmoduleOverview() in ModuleInterface
===============================================================================

See :issue:`107712`

Description
===========

The interface :php:`\TYPO3\CMS\Backend\Module\ModuleInterface` has been extended
with a new method :php:`hasSubmoduleOverview()` to support the new card-based
submodule overview feature introduced in
:ref:`feature-107712-1760548718`.

Impact
======

All custom implementations of
:php:`\TYPO3\CMS\Backend\Module\ModuleInterface` must now implement the new
method :php:`hasSubmoduleOverview(): bool`.

Existing implementations that do not implement this method will trigger a PHP
fatal error.

Affected installations
======================

TYPO3 installations with custom PHP code that directly implement the
:php-short:`\TYPO3\CMS\Backend\Module\ModuleInterface` are affected.

This is uncommon, as most backend modules use the provided
:php:`\TYPO3\CMS\Backend\Module\Module` class or extend from
:php:`\TYPO3\CMS\Backend\Module\BaseModule`.

Migration
=========

Add the :php:`hasSubmoduleOverview()` method to your custom implementation of
:php-short:`\TYPO3\CMS\Backend\Module\ModuleInterface`.

The method should typically return the configured value rather than a fixed
boolean:

..  code-block:: php

    use TYPO3\CMS\Backend\Module\ModuleInterface;

    class MyCustomModule implements ModuleInterface
    {
        protected array $configuration = [];

        public function hasSubmoduleOverview(): bool
        {
            // Return the configured value, defaulting to false
            return $this->configuration['showSubmoduleOverview'] ?? false;
        }
    }

This allows the behavior to be controlled through the module's configuration.

..  index:: Backend, PHP-API, NotScanned, ext:backend
