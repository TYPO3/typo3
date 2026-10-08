..  include:: /Includes.rst.txt

..  _create-database-record:

======================
Create database record
======================

A basic reaction shipped with the system extension can be used to create records
triggered and enriched by data from the caller.

..  _navigate-backend-module:

Navigate to the backend module
==============================

To create a new reaction navigate to the :guilabel:`Administration > Integrations > Reactions` backend
module. As long as no reaction exists, the module shows a card for each
available reaction type:

..  figure:: /Images/BackendModuleEmpty.avif
    :alt: Backend module "Reactions" with no reactions available
    :class: with-shadow

    Backend module "Reactions" with no reactions available

..  _create-reaction:

Create a new reaction
=====================

Click on the button :guilabel:`Create reaction` on the card of the
:guilabel:`Create database record` type to add a new reaction with that type
already selected. Once reactions exist, use the button
:guilabel:`Create new reaction` in the module header instead.

..  figure:: /Images/CreateDatabaseRecordConfiguration.avif
    :alt: Backend form to create a new database record
    :class: with-shadow

    Backend form to create a new database record

The form provides the following general configuration fields:

Reaction Type
    Select one of the available reaction types. The system extension comes
    with the :guilabel:`Create database record`.

Name
    Give the reaction a meaningful name. The name is displayed on the overview
    page of the backend module.

Description
    You can provide additional information to describe, for example, the purpose
    of this reaction in detail.

Identifier
    Any reaction record is defined by a unique identifier. It is part of the
    TYPO3 URL when calling this reaction.

Secret
    The secret is necessary to authorize the reaction from the outside. It can
    be re-created anytime, but will be visible only once (until the record is
    saved). Click on the "dices" button next to the form field to create a
    random secret. Click the copy button next to the field to copy the secret
    to the clipboard. Store the secret somewhere safe.

    ..  versionadded:: 15.0
        :changelog: feature-110851-1790717834

        The button to copy the secret to the clipboard was added.

The content of the "Additional configuration" section depends on the concrete
reaction type. For the "Create database record" the following fields are
available:

Table
    Select one of the tables from the list. The corresponding fields displayed
    below change depending on the selected table. See also
    :ref:`create-database-record-extend-tables-list` on how to extend this list.

Storage PID
    Select the page on which a new record should be stored.

Impersonate User
    Select the user with the appropriate access rights that is allowed to add a
    record of this type. If in doubt, use the CLI user ("_cli_").

Fields
    The available fields depend on the selected table.

    Next to static field values, placeholders are supported, which can be used
    to dynamically set field values by resolving the incoming data from the
    webhook's payload. The syntax for those values is :code:`${key}`. The key
    can be a simple string or a path to a nested value like
    :code:`${key.nested}`.

    Text, e-mail, number, date, colour, link, checkbox, radio button and
    single-value select fields can be mapped. Relations cannot, and neither can a select
    submitting more than one value, since the map holds one string per field.
    Only the fields you may edit yourself are offered, and the reaction writes
    only those the backend user it impersonates may write.

    Every field renders the control the editing form of the target table would
    show: a calendar for a date, a colour picker for a colour, the link browser
    for a link, the items of a select, except a :code:`text` column, which
    keeps a plain box. Each field is set to one of three things: "Do not set",
    which leaves the field to the default of the target table, "Fixed value",
    taken from the control itself, or "Payload value", a placeholder as
    described above. Every row starts on "Do not set", so a reaction writes
    the fields it was configured to write and no others.

    A field that is not set is left out of the created record entirely, and so
    is one the target table cannot hold the value of: a placeholder the request
    does not carry or answers with nothing, a select or radio value the field
    does not offer, and a checkbox value that is neither one of the wordings
    :code:`true`, :code:`yes` and :code:`on` nor a number the boxes of that
    field can hold. A call that resolves no value for any field creates no
    record and is answered with :code:`400`.

For our example we select the :guilabel:`Page Content` table and populate the
following fields:

..  figure:: /Images/CreateDatabaseRecordPageContent.png
    :alt: Form with additional configuration for page content
    :class: with-shadow

    Form with additional configuration for page content

We now save and close the form - our newly created reaction is now visible:

..  figure:: /Images/BackendModuleWithRecord.avif
    :alt: Backend module with reaction
    :class: with-shadow

    Backend module with reaction

..  _call-reaction:

Call the reaction manually
==========================

TYPO3 can now react on this reaction. Click the :guilabel:`Try Out` button of
the reaction. A modal shows:

Endpoint
    The URL of the reaction, with a button to copy it.

Example
    A cURL request for the command line, with a button to copy it. The JSON
    body contains the placeholders of the field map. The placeholder
    :code:`${customer.name}` becomes :json:`{"customer": {"name": "value"}}`.
    The request contains a placeholder instead of the secret.

..  figure:: /Images/ReactionTryOut.avif
    :alt: The Try Out modal with endpoint, example request and dry run
    :class: with-shadow

    The :guilabel:`Try Out` modal of a reaction

..  versionchanged:: 15.0
    :changelog: feature-110850-1790715721

    Before, the module showed the URL and a generic cURL example in the list.

Replace the secret placeholder and the values, and run the request on the
console:

..  code-block:: bash

    curl -X 'POST' \
        'https://example.com/typo3/reaction/a5cffc58-b69a-42f6-9866-f93ec1ad9dc5' \
          -d '{"header":"my header","text":"<p>my text</p>"}' \
          -H 'accept: application/json' \
          -H 'x-api-key: d9b230d615ac4ab4f6e0841bd4383fa15f222b6b'

When everything was okay and the record was created successfully, we receive a
confirmation:

..  code-block:: json

    {"success":true}

If something went wrong, this is also visible, for example:

..  code-block:: json

    {"success":false,"error":"Invalid secret given"}

A record can also be created while single fields of it were not written, for
example because a placeholder was not part of the payload. Those fields are
named in an additional :code:`skippedFields` key, together with the reason they
were left out, and the key is absent when everything was written:

..  code-block:: json

    {"success":true,"skippedFields":{"doktype":"placeholderUnresolved"}}

Where the target table itself rejected a value, a :code:`warnings` key says so
without naming the field. Those messages are written for the backend log, which
is where they stay, so look them up in :guilabel:`System > Log`.

The content is now available on the configured page:

..  figure:: /Images/CreateDatabaseRecordContentElement.png
    :alt: The created page content record on the configured page
    :class: with-shadow

    The created page content record on the configured page


..  _create-database-record-dry-run:

Check a payload with a dry run
==============================

..  versionadded:: 15.0
    :changelog: feature-110850-1790715721

Before an external system calls the reaction, check a payload in the
:guilabel:`Dry run` section of the :guilabel:`Try Out` modal:

#.  Edit the JSON in the field **Payload**. It is prefilled with the body of
    the example request.
#.  Click :guilabel:`Execute dry run`.

..  figure:: /Images/ReactionDryRun.avif
    :alt: Result of a dry run with the fields resolved from the payload
    :class: with-shadow

    Result of a dry run

The dry run resolves the payload against the field map as the backend user
that the reaction impersonates. It includes the item processors of a field.
The result lists the fields that would be written, with their values, and
the fields that would be skipped, with the reason. These are the same reasons
that the endpoint returns in :code:`skippedFields`.

The result also names problems that keep the endpoint from creating any
record, whatever the payload is:

*   The reaction is disabled, not active yet, or expired.
*   The reaction impersonates no backend user, or a disabled or deleted one.
*   The impersonated user may not modify records of the target table.
*   The storage page does not exist, or the impersonated user may not create
    records on it.

The dry run writes no record and does not check the secret.

..  note::

    The dry run does not repeat every check of the DataHandler. Whether the
    page type of the storage page allows the table, the root level
    restrictions of the table, and the evaluation of a field value are only
    checked when the record is written.

..  _create-database-record-extend-tables-list:

Extend list of tables
=====================

By default, only a few tables can be selected for external creation in the
create record reaction. In case you want to allow your own tables to be
available in the reaction's table selection, add the table in a corresponding
:ref:`TCA override file <t3coreapi:storing-changes-extension-overrides>`:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/sys_reaction.php

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            'sys_reaction',
            'table_name',
            [
                'label' => 'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:tx_myextension_domain_model_mytable',
                'value' => 'tx_myextension_domain_model_mytable',
                'icon' => 'myextension-tx_myextension_domain_model_mytable-icon',
            ]
        );
    }

In case your extension depends on EXT:reactions the :php:`isLoaded()` check
might be skipped. Please note that tables which configured
:ref:`adminOnly <t3tca:ctrl-reference-adminonly>` to true are not allowed.
