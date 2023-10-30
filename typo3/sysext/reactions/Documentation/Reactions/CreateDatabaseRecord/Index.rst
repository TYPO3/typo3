..  include:: /Includes.rst.txt

..  _create-database-record:

======================
Create database record
======================

A basic reaction shipped with the system extension can be used to create records
triggered and enriched by data from the caller.


Navigate to the backend module
==============================

To create a new reaction navigate to the :guilabel:`System > Reactions` backend
module. If you call it the first time you will see the invitation to create a
new reaction:

..  figure:: /Images/BackendModuleEmpty.png
    :alt: Backend module "Reactions" with no reactions available
    :class: with-shadow

    Backend module "Reactions" with no reactions available


Create a new reaction
=====================

Click on the button :guilabel:`Create new reaction` to add a new reaction.

..  figure:: /Images/CreateDatabaseRecordConfiguration.png
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
    random secret. Store the secret somewhere safe.

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

For our example we select the :guilabel:`Page Content` table and populate the
following fields:

..  figure:: /Images/CreateDatabaseRecordPageContent.png
    :alt: Form with additional configuration for page content
    :class: with-shadow

    Form with additional configuration for page content

We now save and close the form - our newly created reaction is now visible:

..  figure:: /Images/BackendModuleWithRecord.png
    :alt: Backend module with reaction
    :class: with-shadow

    Backend module with reaction


Call the reaction manually
==========================

TYPO3 can now react on this reaction. Clicking on the :guilabel:`Example`
button, the skeleton of a cURL request for use on the command line is showing
up. We can adjust and run it on the console, using our placeholders as payload:

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

The content is now available on the configured page:

..  figure:: /Images/CreateDatabaseRecordContentElement.png
    :alt: The created page content record on the configured page
    :class: with-shadow

    The created page content record on the configured page


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
            'tx_myextension_domain_model_mytable',
            [
                'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:tx_myextension_domain_model_mytable',
                'tx_myextension_domain_model_mytable',
                'myextension-tx_myextension_domain_model_mytable-icon',
            ]
        );
    }

In case your extension depends on EXT:reactions the :php:`isLoaded()` check
might be skipped. Please note that tables which configured
:ref:`adminOnly <t3tca:ctrl-reference-adminonly>` to true are not allowed.
