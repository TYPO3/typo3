
.. include:: /Includes.rst.txt

==============================================================================
Deprecation: #72827 - Module Icon configuration via [labels][tabs_images][tab]
==============================================================================

See :issue:`72827`

Description
===========

When registering a non-extbase module, the option to configure an icon was
previously done with the module configuration option `[labels][tabs_images][tab]`.
To clarify the naming, the property "icon" is introduced which expects a reference
to the icon via the `EXT:myextension/path/to/the/file.png` syntax.

The old option `[labels][tabs_images][tab]` has been marked as deprecated.


Impact
======

When using the old configuration property `[labels][tabs_images][tab]`, a
deprecation message is thrown.


Affected Installations
======================

Installations with custom backend non-extbase modules of third-party-extensions that
still use the old configuration property.


Migration
=========

Replace the `[labels][tabs_images][tab]` with `[icon]` in `ext_tables.php` in
your extension like this:

.. code-block:: php

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'system',
        'dbint',
        '',
        '',
        array(
            'routeTarget' => \TYPO3\CMS\Lowlevel\View\DatabaseIntegrityView::class . '::mainAction',
            'access' => 'admin',
            'name' => 'system_dbint',
            'workspaces' => 'online',
            'icon' => 'EXT:lowlevel/Resources/Public/Icons/module-dbint.svg',
            'labels' => 'LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod.xlf'
        )
    );

.. index:: PHP-API, Backend
