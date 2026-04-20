..  include:: /Includes.rst.txt

..  _feature-107289-1734172800:

==================================================================
Feature: #107289 - Automatic history tracking for Extbase entities
==================================================================

See :issue:`107289`

Description
===========

TYPO3 now tracks the history of all Extbase domain entities by
listening to Extbase persistence events and storing them in the
:sql:`sys_history` table. This provides a comprehensive audit trail for
all frontend and backend operations on Extbase entities without requiring
any code changes.

The feature leverages TYPO3's existing
:php-short:`TYPO3\CMS\Backend\History\RecordHistoryStore`
infrastructure and integrates seamlessly with the backend record history
functionality.

The history tracking captures:

*   Create operations: when entities are persisted for the first time
*   Update operations: when existing entities are modified
*   Delete operations: when entities are removed from persistence

All operations are tracked with their proper user context (frontend users,
backend users, anonymous operations) and include full entity data
snapshots.

Configuration
=============

History tracking is **disabled** by default. It can be enabled with the
feature toggle `extbase.enableHistoryTracking` (available via
:guilabel:`System > Settings > Feature toggles`).

Once the feature toggle is enabled, history tracking is active for all
Extbase domain model storage tables. It can then be **disabled** via TCA
on a per-table basis:

..  code-block:: php
    :emphasize-lines: 11-13
    :caption: EXT:my_extension/Configuration/TCA/tx_myextension_domain_model_blog.php

    <?php
    declare(strict_types=1);

    return [
        'ctrl' => [
            'title' => 'my_extension.messages:my_title',
            'label' => 'uid',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'delete' => 'deleted',
            // ...
            'extbase' => [
                'enableHistoryTracking' => false,
            ],
        ],
        'columns' => [
            // ...
        ],
    ];

Defining this at the TCA level (instead of TypoScript `persistence`
configuration) means that it can be configured per table and
evaluated consistently in all contexts (backend, frontend, CLI).

If a third-party extension enables history tracking via TCA, it can be
disabled using TCA overrides. Disabling the feature toggle also disables
all history tracking, even for tables configured with
`enableHistoryTracking => true`.

In addition, the following PSR-14 event listeners can be deregistered or
replaced at instance level:

*   `extbase-history-tracker-persisted`
*   `extbase-history-tracker-updated`
*   `extbase-history-tracker-removed`

..  note::

    Enabling history tracking can generate a large number of history
    entries for Extbase entities. These entries are mixed with regular
    editorial changes made in the TYPO3 backend (FormEngine).

..  important::

    All changes to Extbase entity data are logged, including full initial
    data snapshots. This may have implications for GDPR / DSGVO and other
    security-related data handling requirements. Data may need to be
    pruned regularly. It is advisable to disable history tracking for
    tables containing sensitive data. For this reason, the feature toggle
    is disabled by default and requires explicit activation.

Impact
======

Changes to all Extbase domain entities can now be tracked
in the :sql:`sys_history` table, making them visible in the
backend record history. This requires enabling the feature toggle
`extbase.enableHistoryTracking` (default: `false`).

This feature provides administrators and developers with full visibility
into data changes without requiring interface implementations or code
modifications.

Technical details
=================

The implementation consists of a PSR-14 event listener
:php-short:`TYPO3\CMS\Extbase\EventListener\ExtbaseHistoryTracker`
which automatically registers for the following Extbase persistence
events:

*   :php-short:`TYPO3\CMS\Extbase\Event\Persistence\EntityAddedToPersistenceEvent`
*   :php-short:`TYPO3\CMS\Extbase\Event\Persistence\EntityUpdatedInPersistenceEvent`
*   :php-short:`TYPO3\CMS\Extbase\Event\Persistence\EntityRemovedFromPersistenceEvent`

All entities with valid TCA configuration are tracked automatically. This
uses the Extbase DataMap API, TCA Schema API, and RecordHistoryStore API.

..  index:: PHP-API, ext:extbase
