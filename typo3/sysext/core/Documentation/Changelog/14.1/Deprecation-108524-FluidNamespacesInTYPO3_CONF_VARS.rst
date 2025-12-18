..  include:: /Includes.rst.txt

..  _deprecation-108524-1766073657:

==========================================================
Deprecation: #108524 - Fluid namespaces in TYPO3_CONF_VARS
==========================================================

See :issue:`108524`

Description
===========

Registering global namespaces for Fluid templates in `TYPO3_CONF_VARS` has
been deprecated.

Impact
======

Defined namespaces in :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']`
will no longer be registered in TYPO3 v15.

Affected installations
======================

Installations and extensions that use
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']`
to define additional global namespaces or extend existing global namespaces.

Migration
=========

Standard use cases, such as registering a new global namespace or extending
an existing one, can be migrated to the dedicated `Configuration/Fluid/Namespaces.php`
configuration file.

Before:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['myext'][] = 'MyVendor\\MyExtension\\ViewHelpers';

After:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Fluid/Namespaces.php

    <?php

    return [
        'myext' => ['MyVendor\\MyExtension\\ViewHelpers'],
    ];

See :ref:`Feature: #108524 - Configuration file to register global Fluid namespaces <feature-108524-1766073747>`
for more details and examples.

..  index:: Fluid, LocalConfiguration, FullyScanned, ext:fluid
