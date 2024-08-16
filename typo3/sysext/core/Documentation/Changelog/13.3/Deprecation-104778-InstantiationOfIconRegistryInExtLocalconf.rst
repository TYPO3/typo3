.. include:: /Includes.rst.txt

.. _deprecation-104778-1724953249:

=========================================================================
Deprecation: #104778 - Instantiation of IconRegistry in ext_localconf.php
=========================================================================

See :issue:`104778`

Description
===========

Since TYPO3 v11 it is possible to automatically register own icons via
`Configuration/Icons.php`. Prior to this, extension authors used to register
icons manually via instantiating the IconRegistry in their ext_localconf.php
files. This method has now been deprecated. It is recommended to switch to
the newer method introduced with :issue:`94692`.

Impact
======

Instantiating :php:`\TYPO3\CMS\Core\Imaging\IconRegistry` inside
ext_localconf.php files will trigger a deprecation-level log entry.

Affected installations
======================

All installations, which instantiate :php:`\TYPO3\CMS\Core\Imaging\IconRegistry`
before the :php:`\TYPO3\CMS\Core\Core\Event\BootCompletedEvent`. This includes
`ext_localconf.php` files as well as `TCA/Overrides`.

Migration
=========

The most common use-cases can be accomplished via the `Configuration/Icons.php`
file.

Before:

..  code-block:: php
    :caption: EXT:example/ext_localconf.php

    <?php

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
      \TYPO3\CMS\Core\Imaging\IconRegistry::class,
    );
    $iconRegistry->registerIcon(
        'example',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        [
            'source' => 'EXT:example/Resources/Public/Icons/example.svg'
        ],
    );

After:

..  code-block:: php
    :caption: EXT:example/Configuration/Icons.php

    <?php

    return [
        'example' => [
            'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            'source' => 'EXT:example/Resources/Public/Icons/example.svg',
        ],
    ];

For more complex tasks, it is recommended to register an event listener for the
:php:`\TYPO3\CMS\Core\Core\Event\BootCompletedEvent`. At this stage the system
is fully booted and you have a completely configured IconRegistry at hand.

In case the registry was used in `TCA/Overrides` files to retrieve icon
identifiers, then this should be replaced completely with static identifiers.
The reason behind this is, that the registry isn't even fully usable at this
stage. TCA isn't fully built yet and icons can still be registered at a later
point.

.. index:: PHP-API, NotScanned, ext:core
