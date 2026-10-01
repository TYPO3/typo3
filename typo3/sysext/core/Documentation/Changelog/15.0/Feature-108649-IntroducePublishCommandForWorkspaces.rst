..  include:: /Includes.rst.txt

..  _feature-108649-1736956800:

===========================================================
Feature: #108649 - Introduce publish command for workspaces
===========================================================

See :issue:`108649`

Description
===========

A new top-level DataHandler command `publish` has been introduced for
publishing workspace records to the live workspace. This command uses the
**versioned UID** as the primary identifier (command map key), which is more
intuitive since you are publishing the workspace version of a record.

The new command automatically resolves:

*   The **live record ID** from the versioned record's `t3ver_oid` field
*   The **workspace ID** from the versioned record's `t3ver_wsid` field

This means callers no longer need to know or pass the live ID when publishing.
The workspace context is also set automatically based on the record being
published, ensuring correct permission checks regardless of the user's
currently selected workspace.

Example
=======

Publishing a workspace record now uses the versioned UID as the command key:

..  code-block:: php
    :caption: Publishing a workspace record

    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Publish a workspace record using its versioned UID
    $versionedUid = 123; // The UID of the workspace version
    $commandMap = [
        'tt_content' => [
            $versionedUid => [
                'publish' => [],
            ],
        ],
    ];

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start([], $commandMap);
    $dataHandler->process_cmdmap();

Optional parameters can be passed to the publish command:

..  code-block:: php
    :caption: Publishing with comment and notification recipients

    $commandMap = [
        'tt_content' => [
            $versionedUid => [
                'publish' => [
                    'comment' => 'Publishing content update',
                    'notificationAlternativeRecipients' => [1, 2, 3],
                ],
            ],
        ],
    ];

Comparison with the legacy command
----------------------------------

The legacy approach required knowing both the live ID and versioned ID:

..  code-block:: php
    :caption: Legacy publish command (still supported)

    // Legacy: Uses live ID as key, versioned ID in swapWith
    $commandMap = [
        'tt_content' => [
            $liveUid => [
                'version' => [
                    'action' => 'swap',
                    'swapWith' => $versionedUid,
                ],
            ],
        ],
    ];

The new command simplifies this by only requiring the versioned UID:

..  code-block:: php
    :caption: New publish command

    // New: Uses versioned ID as key, live ID resolved automatically
    $commandMap = [
        'tt_content' => [
            $versionedUid => [
                'publish' => [],
            ],
        ],
    ];


Impact
======

The new `publish` command simplifies the publishing workflow by:

*   Using the versioned UID as the primary identifier (the record being published)
*   Automatically resolving the live ID from the versioned record's `t3ver_oid`
*   Automatically setting the workspace context from the versioned record's `t3ver_wsid`
*   Gracefully handling cascading operations (e.g., when publishing hierarchical
    deletes, child records that were already removed via cascade are silently skipped)

The legacy `version` command with `action => 'swap'` or
`action => 'publish'` continues to work for backward compatibility.


..  index:: Backend, PHP-API, ext:workspaces
