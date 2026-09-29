..  include:: /Includes.rst.txt

..  _feature-110850-1790715721:

==========================================================
Feature: #110850 - Try out reactions in the backend module
==========================================================

See :issue:`110850`

Description
===========

Each reaction in the :guilabel:`Administration > Integrations > Reactions`
module now has a :guilabel:`Try out` button. It opens a modal showing what an
external system has to send to that reaction, and lets an administrator check
a payload against it before any request is sent.

The modal offers:

Endpoint
    The absolute URL of the reaction, with a button to copy it.

Example
    A :bash:`curl` command for this reaction, with a button to copy it. Its
    JSON body is composed of the placeholders the field map reads, so
    :php:`${customer.name}` becomes :json:`{"customer": {"name": "value"}}`.
    The secret is shown as a placeholder only.

Dry run
    Offered for reactions creating a database record. A payload, prefilled with
    the example body, is edited in a JSON code editor and resolved against the
    field map. The result lists the fields that would be written and their
    values, and the fields that would be skipped together with the reason, the
    same reasons the endpoint reports in :php:`skippedFields`.

The dry run resolves the payload as the backend user the reaction impersonates,
including the item processors of a field, so field permissions and offered
items are the ones the endpoint applies. No record is written and the secret is
not checked.

It also names what would keep the endpoint from creating any record at all,
whatever the payload:

*   the reaction is disabled, not active yet, or expired,
*   the reaction impersonates no backend user, or one that is disabled or
    deleted,
*   the impersonated user may not modify records of the target table,
*   the storage page does not exist, or the impersonated user may not create
    records on it.

The URL and the inline example previously shown in the list are replaced by
the :guilabel:`Try out` button.

..  note::

    The dry run does not repeat every check of :php:`DataHandler`. Whether the
    page type of the storage page allows the table, the root level
    restrictions of a table, and the evaluation of a field value are only known
    when the record is actually written.

Impact
======

Integrators configuring a reaction see the request the reaction expects rather
than a generic example, and can verify a field map, the permissions of the
impersonated user and the storage page before connecting an external system.
Mistakes that previously only showed up as a :php:`400` response, a skipped
field or a missing record are visible in the backend module.

..  index:: Backend, JavaScript, ext:reactions
