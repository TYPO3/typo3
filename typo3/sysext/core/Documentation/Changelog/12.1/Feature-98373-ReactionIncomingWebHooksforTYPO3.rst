.. include:: /Includes.rst.txt

.. _feature-98373-1663587471:

=========================================================
Feature: #98373 - Reactions - Incoming webhooks for TYPO3
=========================================================

See :issue:`98373`

Description
===========

This feature adds the possibility to receive webhooks in TYPO3.

With the new :guilabel:`System > Reactions` backend module it is possible to
configure the reactions triggered by any webhook.

A webhook is defined as an authorized POST request to the backend.

The core provides a basic default reaction that can be used to create
records triggered and enriched by data from the caller.

Additionally, the Core provides the :php:`\TYPO3\CMS\Reactions\Reaction\ReactionInterface`
to allow extension authors to add their own reaction types.

Any reaction record is defined by a unique uid (UUID) and also requires a secret.
Both information are generated in the backend. The secret is only visible once and
stored in the database as an encrypted value like a backend user password.

Next to static field values, the "create record" reaction features placeholders,
which can be used to dynamically set field values by resolving the incoming
data from the webhook's payload. The syntax for those values is :code:`${key}`.
The key can be a simple string or a path to a nested value like :code:`${key.nested}`.

Definition of the placeholders in the record
--------------------------------------------

..  code-block:: text

    ${title}
    ${description}
    ${key.nested}

Example payload for placeholders
--------------------------------

..  code-block:: json

    {
        "title": "My title",
        "description": "My description",
        "key": {
            "nested": "bar"
        }
    }

By default, only a few tables can be selected for external creation in the
create record reaction. In case you want to allow your own tables to be
available in the reactions' table selection, add the table in a coresponding TCA
override file with:

.. code-block:: php

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'sys_reaction',
            'table_name',
            [
                'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:my_table',
                'my_table',
                'myext-my_table-icon',
            ]
        );
    }

In case your extension depends on EXT:reactions the :php:`isLoaded()` check
might be skipped. Please note that tables with :php:`adminOnly` set are not
allowed.

Impact
======

This feature allows everybody to provide additional value for any TYPO3 instance.
By reacting to webhooks, TYPO3 can now be used to create records in the backend.
Furthermore, by implementing the :php:`ReactionInterface`, it is possible to
create any custom reaction.

.. index:: Backend, ext:reactions
