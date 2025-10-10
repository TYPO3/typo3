..  include:: /Includes.rst.txt

..  _breaking-107663-1760110062:

=================================================================
Breaking: #107663 - New method getDependsOnSubmodules() required
=================================================================

See :issue:`107663`

Description
===========

The :php:`\TYPO3\CMS\Backend\Module\ModuleInterface` has been extended with a
new method :php:`getDependsOnSubmodules()` to support the new submodule
dependency feature introduced in :ref:`feature-107663-1760110062`.


Impact
======

All custom implementations of :php:`\TYPO3\CMS\Backend\Module\ModuleInterface`
must now implement the new :php:`getDependsOnSubmodules(): bool` method.

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

Add the :php:`getDependsOnSubmodules()` method to your custom
:php:`ModuleInterface` implementation.

The method should typically return the value from the module's configuration
rather than a static boolean value:

..  code-block:: php

    public function getDependsOnSubmodules(): bool
    {
        // Return the configured value, defaulting to false
        return $this->configuration['dependsOnSubmodules'] ?? false;
    }

This allows the behavior to be controlled through the module configuration.

..  index:: Backend, PHP-API, NotScanned, ext:backend
