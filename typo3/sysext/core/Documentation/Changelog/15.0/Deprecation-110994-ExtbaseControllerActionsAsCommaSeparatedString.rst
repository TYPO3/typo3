..  include:: /Includes.rst.txt

..  _deprecation-110994-1791567820:

===========================================================================
Deprecation: #110994 - Extbase controller actions as comma-separated string
===========================================================================

See :issue:`110994`

Description
===========

Extbase controller actions can be registered either as an array of action
names or as a comma-separated string of action names. Passing a
comma-separated string has been deprecated. This affects:

*   The :php:`$controllerActions` and :php:`$nonCacheableControllerActions`
    arguments of
    :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin()`
*   The :php:`controllerActions` option of Extbase backend modules registered
    in :file:`Configuration/Backend/Modules.php`

Impact
======

Passing actions as a comma-separated string triggers a PHP
:php:`E_USER_DEPRECATED` error. Support for comma-separated strings will be
removed in TYPO3 v16.0.

Affected installations
======================

TYPO3 installations with extensions that register Extbase plugins or Extbase
backend modules with controller actions defined as a comma-separated string
are affected.

Migration
=========

Pass an array of action names instead of a comma-separated string.

Before:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    ExtensionUtility::configurePlugin(
        'MyExtension',
        'List',
        [ItemController::class => 'list, show'],
        [ItemController::class => 'show'],
    );

After:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    ExtensionUtility::configurePlugin(
        'MyExtension',
        'List',
        [ItemController::class => ['list', 'show']],
        [ItemController::class => ['show']],
    );

Before:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php

    'controllerActions' => [
        ItemController::class => 'list, show',
    ],

After:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php

    'controllerActions' => [
        ItemController::class => ['list', 'show'],
    ],

..  index:: Backend, Frontend, PHP-API, NotScanned, ext:extbase
