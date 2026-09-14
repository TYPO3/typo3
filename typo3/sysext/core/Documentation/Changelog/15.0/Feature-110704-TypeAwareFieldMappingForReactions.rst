..  include:: /Includes.rst.txt

..  _feature-110704-1789381200:

=========================================================
Feature: #110704 - Type aware field mapping for reactions
=========================================================

See :issue:`110704`

Description
===========

The "create database record" reaction allows filling fields of the record it
creates from the payload of the request. That field mapping is now type aware.
It covers checkbox, radio button and single value select fields beside the text
fields, and every field is configured with the control its own table uses.

A select therefore lists the items the target table declares, a checkbox is a
checkbox, a date is picked from a calendar and a link is picked with the link
browser, so a value is configured by picking it rather than by knowing what to
type: whether a page is hidden, which type it has, which backend layout it uses,
when it starts being visible, where it points.

Each mapped field is one of the following, chosen per row:

Do not set
    The field is left out of the record, so the default of the target table
    applies. Its control is shown disabled, so a field that is not written is
    visible as one at a glance. Every row starts here, and a field the map
    holds no value for stays out of the record, whether it carries no key for
    it at all or an empty one.

Fixed value
    The value picked in the field's own control, written on every call.

Payload value
    A :php:`${placeholder}` addressing a key of the payload the caller sends,
    for example :php:`${customer.name}` or :php:`${flags.archived}`.

The field map offers the fields the integrator configuring it may edit, so a
field declaring :php:`exclude` needs the permission for it to be granted and one
shown to admins only is not offered at all. The reaction applies the same rule
to the user it impersonates when it writes the record, and names a field that
user may not write in the response rather than leaving it out silently.

A :sql:`text` column is the one exception: it keeps a plain box, because the
editor such a column may declare belongs in the editing form of a record rather
than in a row configuring a value.

The controls are built from the target table through the FormEngine data
providers and resolved for the storage page of the reaction. An item list a
field composes per page, such as the columns of a backend layout, and the
:typoscript:`TCEFORM` configured on that page are therefore the ones offered,
so what the control shows is what the created record can hold.

Values are checked before the record is written. A value that does not pass is
not written, and the field is left to the default of the target table:

*   A select or radio value the field does not offer is left out rather than
    being stored as it stands. What the field offers is what the field map
    offered: the items it declares, the ones an :php:`itemsProcFunc` composes
    for the storage page, and the ones
    :typoscript:`TCEFORM.<table>.<field>.addItems` declares on that page. Where
    a processor cannot be run at all, the value counts as not offered.
*   A checkbox understands the wordings a caller is likely to send, so
    :php:`true`, :php:`"yes"`, :php:`"on"` and :php:`1` all check a single
    checkbox. A field holding more than one box takes a number, and a number
    outside the bitmask of that field is left out rather than being masked down
    to one the caller did not ask for.
*   A placeholder the payload does not carry, and one it answers with nothing at
    all, leaves the field out rather than writing an empty value over the
    default the target table declares.

The response names every field the reaction did not write, and why, in a
:php:`skippedFields` key that is absent when it wrote all of them:

..  code-block:: json

    {
        "success": true,
        "skippedFields": {
            "doktype": "placeholderUnresolved",
            "nav_title": "valueNotOffered"
        }
    }

The reason is one of:

:php:`notMappable`
    The key is no field of the target table the field map offers.

:php:`notPermitted`
    The user the reaction impersonates may not write the field.

:php:`placeholderUnresolved`
    The payload carries no value under the path the placeholder names, or none
    that can be written into a field.

:php:`resolvedToNothing`
    The payload carries the path but nothing under it.

:php:`valueNotHoldable`
    A checkbox can hold neither a wording it understands nor a number of the
    range its bitmask spans.

:php:`valueNotOffered`
    A select or radio does not offer the value as one of its items.

Where the target table rejected a value the reaction did pass on, a
:php:`warnings` key says that it did without naming the field. What
:php:`DataHandler` logs is written for the backend log rather than for whoever
holds the secret of a webhook — it names the page the record went to, the value
another record already holds where a field has to be unique, and the message of
the database driver — so it stays in that log.

..  code-block:: json

    {
        "success": true,
        "warnings": [
            "A value of the record was rejected by the target table and not written"
        ]
    }

An :php:`error` key stays what it was: the one reason a call failed, present
only where it did.

A call that resolves no value for any field creates no record and is answered
with :php:`400`, so a reaction that is not configured yet, or one called without
the payload it expects, does not fill the storage page with empty records.

What a reaction stores stays the flat map of field name to string that reaction
records hold, so existing reactions need no migration and a field map written by
hand keeps being valid. Every field type the field map offered before is still
offered, so no field of an existing reaction stops being written.

..  note::

    One answer does change for an existing reaction. A call that used to create
    a record out of nothing but empty values and unresolved placeholders is now
    answered with :php:`400` instead of :php:`201`, because none of its fields
    can be written. A caller watching for :php:`201` sees that; the record it
    used to create was untitled and carried the literal :php:`${...}` of every
    placeholder the payload did not answer.

..  note::

    Relations are not mappable yet. A mapped value is checked against the items
    the field declares, and a relation declares none, so nothing would say which
    uids of the foreign table a caller may name. Selects submitting more than
    one value are out for the same reason a row holds one control. A select
    composing its items from a folder is offered, but a value only that folder
    contributes is not written, since the reaction composes the items of a field
    without reading the file system.

    A checkbox displaying its state inverted, such as :sql:`pages.hidden`, is
    labelled for what it displays while a payload value is written to the column
    as it is stored. The column name is shown beside the label of the payload
    input for that reason: :php:`${hidden}` resolving to :php:`true` hides the
    page the row reads as "Page visible".

Impact
======

A reaction now creates records that are ready to use. Until now only free text
could be mapped.

A webhook could fill in a title, but it could not create a hidden page, a
content element of a particular type, or a record scheduled to appear next
Monday.

That gap is closed, and configuring it no longer requires knowing what
the database stores. The integrator picks a page type from the list the page
tree uses, ticks the checkbox that hides a page, picks a date from a calendar
and a colour from a colour picker. The very controls the editing form of that
table shows. A value only valid on the storage page, such as a backend layout
defined there or an item added through :typoscript:`TCEFORM`, is part of that
list too.

This makes reactions usable for cases that previously needed an extension:

*   A form service posts a submission and gets a content element of the right
    :sql:`CType`, in the right column, hidden until someone reviews it.
*   A product feed creates pages of a given doktype, each with the backend
    layout its section uses.
*   An editorial system schedules what it sends by mapping
    :sql:`starttime` and :sql:`endtime` from the payload.

What the caller gets back is more useful as well. A value the target table
cannot hold is left out instead of written, a call carrying none of the expected
payload creates no record at all, and every field the reaction did not write is
named in the response together with a reason code — so a caller can tell a typo
in a payload key from a value the table rejected, without reading prose.

Existing reactions keep working unchanged, and what is stored is still the flat
map of field name to string, so a field map written by hand stays valid.

..  index:: Backend, JavaScript, ext:reactions
