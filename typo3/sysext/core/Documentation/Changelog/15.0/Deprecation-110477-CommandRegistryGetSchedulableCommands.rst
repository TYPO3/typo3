..  include:: /Includes.rst.txt

..  _deprecation-110477-1786635811:

==================================================================
Deprecation: #110477 - CommandRegistry->getSchedulableCommands()
==================================================================

See :issue:`110477`

Description
===========

The method
:php:`\TYPO3\CMS\Core\Console\CommandRegistry->getSchedulableCommands()` has
been marked as deprecated and will be removed in TYPO3 v16.0.

Impact
======

Calling the method triggers a PHP :php:`E_USER_DEPRECATED` error.

Affected installations
======================

Installations with custom extensions that call
:php:`CommandRegistry->getSchedulableCommands()` are affected. The method is
not used by TYPO3 Core.

The extension scanner reports usages as a weak match.

Migration
=========

Use :php:`CommandRegistry->getSchedulableCommandsConfiguration()` to retrieve
the configuration indexed by command identifier. If command instances are
needed, retrieve them individually with :php:`CommandRegistry->get()`:

..  code-block:: php

    foreach (array_keys($commandRegistry->getSchedulableCommandsConfiguration()) as $commandIdentifier) {
        $command = $commandRegistry->get($commandIdentifier);
    }

..  index:: CLI, FullyScanned, PHP-API, ext:core
