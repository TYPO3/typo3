.. include:: /Includes.rst.txt

.. _feature-98373-1663587471:

=========================================================
Feature: #98373 - Reactions - Incoming WebHooks for TYPO3
=========================================================

See :issue:`98373`

Description
===========

This feature adds the possibility to send incoming webhooks to a TYPO3 instance.

With the new backend module, it is possible to configure the reactions triggered
by any webhook.

A webhook is defined as an authorized POST request to the backend.

The core provides a basic default reaction that can be used to create
records triggered and enriched by data from the caller.

Additionally, the core provides the :php:`\TYPO3\CMS\Reactions\Reaction\ReactionInterface`
to allow extension authors to add their own reaction types.

Any reaction record is defined by a unique uid and also requires a secret. Both
information are generated in the backend. The secret is only visible once and
stored in the database as an encrypted value like a backend user password.

Next to static field values does the "create record" reaction feature placeholders,
which can be used to dynamically set field values, by resolving the incoming
data from the webhooks' payload. The syntax to for those values is :code:`${key}`.
The key can be a simple string or a path to a nested value like :code:`${key.nested}`.

Definition of the placeholders in the record:
---------------------------------------------

.. code-block:: text

    ${title}
    ${description}
    ${key.nested}

Example payload for placeholders:
---------------------------------

.. code-block:: json

    {
        "title": "My title",
        "description": "My description",
        "key": {
            "nested": "bar"
        }
    }

Impact
======

This feature allows everybody to provide additional value for any TYPO3 instance.
By reacting to webhooks, TYPO3 can now be used to create records in the backend.
Furthermore, by implementing the :php:`ReactionInterface`, it is possible to
create any custom reaction.

.. index:: Backend, ext:reactions
