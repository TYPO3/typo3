..  include:: /Includes.rst.txt

..  _feature-108762-1737820800:

================================================================
Feature: #108762 - Introduce change_stage command for workspaces
================================================================

See :issue:`108762`

Description
===========

A new top-level DataHandler command :php:`change_stage` has been introduced for
changing the stage of workspace records. Like the :php:`publish` command, it is
keyed by the uid of the workspace version, and its parameters are passed flat
instead of being nested below an :php:`action`.

The new command provides a cleaner API compared to the legacy :php:`version`
command with :php:`action => 'setStage'`.

Example
=======

Changing the stage of a workspace record now uses a flat command structure:

..  code-block:: php
    :caption: Changing the stage of a workspace record

    use TYPO3\CMS\Core\DataHandling\DataHandler;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Change the stage of a workspace record using its versioned UID
    $versionedUid = 123; // The UID of the workspace version
    $commandMap = [
        'tt_content' => [
            $versionedUid => [
                'change_stage' => [
                    'stage' => -10, // Target stage ID (e.g., -10 for "Ready to publish")
                ],
            ],
        ],
    ];

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $dataHandler->start([], $commandMap);
    $dataHandler->process_cmdmap();

Optional parameters can be passed to the change_stage command:

..  code-block:: php
    :caption: Changing stage with comment and notification recipients

    $commandMap = [
        'tt_content' => [
            $versionedUid => [
                'change_stage' => [
                    'stage' => -10,
                    'comment' => 'Ready for review',
                    'notificationAlternativeRecipients' => [1, 2, 3],
                ],
            ],
        ],
    ];

Comparison with the legacy command
----------------------------------

The legacy approach required nested parameters with an action key:

..  code-block:: php
    :caption: Legacy setStage command (still supported)

    // Legacy: Nested structure with action parameter
    $commandMap = [
        'tt_content' => [
            $versionedUid => [
                'version' => [
                    'action' => 'setStage',
                    'stageId' => -10,
                    'comment' => 'Ready for review',
                ],
            ],
        ],
    ];

The new command simplifies this with a flat structure:

..  code-block:: php
    :caption: New change_stage command

    // New: Flat structure, consistent with publish command
    $commandMap = [
        'tt_content' => [
            $versionedUid => [
                'change_stage' => [
                    'stage' => -10,
                    'comment' => 'Ready for review',
                ],
            ],
        ],
    ];


Impact
======

The new :php:`change_stage` command simplifies the stage change workflow by:

*   Using a flat command structure instead of nested :php:`version.action.setStage`
*   Providing API consistency with the :php:`publish` command
*   Keeping the parameter naming simple (:php:`stage` instead of :php:`stageId`)

A :php:`change_stage` command without a valid :php:`stage` is rejected and
logged, the stage of the record is left untouched.

The legacy :php:`version` command with :php:`action => 'setStage'` continues
to work for backward compatibility. Dependent records, such as inline children,
are staged with the same command the parent record was requested with, so
:php:`processCmdmap_*` hooks always receive the command in the shape it was
sent. If a command map contains both commands for the same record,
:php:`change_stage` takes precedence and the legacy command is dropped, even
if :php:`change_stage` is rejected for lacking a valid :php:`stage`.

The workspaces backend module now sends stage changes as :php:`change_stage`
commands. Extensions implementing :php:`processCmdmap_preProcess`,
:php:`processCmdmap` or :php:`processCmdmap_postProcess` hooks that react to
the legacy :php:`version` command with :php:`action => 'setStage'` must also
handle :php:`change_stage` to keep reacting to stage changes triggered in the
backend module.


..  index:: Backend, PHP-API, ext:workspaces
