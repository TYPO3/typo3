..  include:: /Includes.rst.txt

..  _deprecation-107287-1734253200:

====================================================================
Deprecation: #107287 - FileCollectionRegistry->addTypeToTCA() method
====================================================================

See :issue:`107287`

Description
===========

The method
:php:`\TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry->addTypeToTCA()`
has been deprecated in TYPO3 v14.0 and will be removed in TYPO3 v15.0.

This method was originally intended to register additional file collection
types by directly manipulating the global :php:`$GLOBALS['TCA']` array for the
:sql:`sys_file_collection` table. With modern TCA configuration patterns, this
approach is no longer recommended.

Impact
======

Calling this method will trigger a deprecation-level log entry and will stop
working in TYPO3 v15.0.

Affected installations
======================

Instances using the :php:`FileCollectionRegistry->addTypeToTCA()` method
directly are affected.

The extension scanner will report usages as **weak match**.

Migration
=========

Instead of using this method, configure file collection types directly in your
TCA configuration files. Move the TCA configuration from the method call to
your extension's
:file:`Configuration/TCA/Overrides/sys_file_collection.php` file.

..  code-block:: php
    :caption: Before (deprecated)

    use TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $fileCollectionRegistry = GeneralUtility::makeInstance(
        FileCollectionRegistry::class
    );
    $fileCollectionRegistry->addTypeToTCA(
        'mytype',
        'My Collection Type',
        'description,my_field',
        ['my_field' => ['config' => ['type' => 'input']]]
    );

..  code-block:: php
    :caption: After (recommended) – EXT:my_extension/Configuration/TCA/Overrides/sys_file_collection.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    $GLOBALS['TCA']['sys_file_collection']['types']['mytype'] = [
        'showitem' => '
            sys_language_uid, l10n_parent, l10n_diffsource,
            title, --palette--;;1, type, description, my_field
        ',
    ];

    $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][] =
        [
            'label' => 'My Collection Type',
            'value' => 'mytype',
        ];

    // Add additional columns if needed
    ExtensionManagementUtility::addTCAcolumns(
        'sys_file_collection',
        [
            'my_field' => [
                'config' => [
                    'type' => 'input',
                ],
            ],
        ]
    );

..  index:: TCA, FullyScanned, ext:core
