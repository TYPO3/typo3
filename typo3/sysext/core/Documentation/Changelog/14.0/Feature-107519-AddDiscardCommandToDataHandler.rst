..  include:: /Includes.rst.txt

..  _feature-107519-1742215067:

=======================================================
Feature: #107519 - Add "discard" command to DataHandler
=======================================================

See :issue:`107519`

Description
===========

The :php:`\TYPO3\CMS\Core\DataHandling\DataHandler` PHP API has been
extended with a new "discard" command to simplify workspace management.

This new command provides a cleaner, more explicit way to discard workspace
records compared to the previous approach using version commands.

The new "discard" command can be used in the :php:`$commandArray` parameter
when calling the DataHandler to remove versioned records from a workspace.

Impact
======

The "discard" command offers a more intuitive API for workspace operations:

*   Instead of using complex version commands with actions like "clearWSID"
    or "flush", you can now use the straightforward "discard" command.
*   The command name clearly indicates its purpose.
*   The command handles all aspects of discarding workspace records,
    including its child records.

Usage
=====

When using the "discard" command, it is crucial to use the uid of the
versioned record (workspace version), not the live record's uid, as it serves
no purpose.

..  code-block:: php
    :caption: Discarding a workspace record using DataHandler

    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Example: Discard a versioned page record
    $versionedPageUid = 123; // This must be the UID of the workspace version!

    $commandArray = [
        'pages' => [
            $versionedPageUid => [
                'discard' => true,
            ],
        ],
    ];

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start([], $commandArray);
    $dataHandler->process_cmdmap();

..  code-block:: php
    :caption: Discarding multiple records of different types

    $commandArray = [
        'pages' => [
            456 => ['discard' => true], // Versioned page UID
        ],
        'tt_content' => [
            789 => ['discard' => true], // Versioned content element UID
            790 => ['discard' => true], // Another versioned content element UID
        ],
    ];

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start([], $commandArray);
    $dataHandler->process_cmdmap();

..  important::
    Always ensure you are using the UID of the versioned record when
    discarding workspace records. Using the live record's UID will not work
    as expected. You can identify versioned records by checking that
    :sql:`t3ver_wsid` > 0 and :sql:`t3ver_oid` points to the live record.

Migration from Legacy Commands
==============================

The new "discard" command replaces the previous version-based approach,
which is not widely known:

..  code-block:: php
    :caption: Legacy approach (still supported but discouraged)

    // Old way - will be removed in future versions
    $commandArray = [
        'pages' => [
            $versionedUid => [
                'version' => [
                    'action' => 'clearWSID',
                ],
            ],
        ],
    ];

..  code-block:: php
    :caption: New recommended approach

    // New way - cleaner and more explicit
    $commandArray = [
        'pages' => [
            $versionedUid => [
                'discard' => true,
            ],
        ],
    ];

The previous "clearWSID" and "flush" actions are still supported for backward
compatibility but are considered deprecated and will be removed in future
versions.

..  index:: PHP-API, ext:core
