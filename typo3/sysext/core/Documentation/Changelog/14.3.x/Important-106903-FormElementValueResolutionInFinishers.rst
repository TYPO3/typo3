..  include:: /Includes.rst.txt

..  _important-106903-1787754756:

===============================================================
Important: #106903 - Form element value resolution in finishers
===============================================================

See :issue:`106903`

Description
===========

Finisher options substitute :yaml:`{elementIdentifier}` placeholders with the
submitted value of that form element. Until now the raw submitted value was
used everywhere, so a select, radio button, multi checkbox or country select
element contributed its stored option key instead of the label an editor had
configured and translated - the subject of an email read
:yaml:`Request from mr` where the summary table below it read
:yaml:`Request from Mister`.

Options that are read by a human now resolve the placeholder through the form
element itself and therefore use the translated option label:

*   :yaml:`EmailFinisher`: :yaml:`subject`, :yaml:`title`, :yaml:`message` and
    :yaml:`senderName`
*   :yaml:`ConfirmationFinisher`: :yaml:`message`
*   :yaml:`FlashMessageFinisher`: :yaml:`messageBody` and :yaml:`messageTitle`

The same resolution already backed the summary page and the email body through
the :php:`RenderFormValue` ViewHelper, so both places now agree on what a
submitted value looks like.

Every other finisher option keeps the submitted value, because it ends up in a
stored record, a query or a URL, where the option key is what matters. That
includes the e-mail addresses next to the sender name -
:yaml:`senderAddress`, :yaml:`recipients`, :yaml:`replyToRecipients`,
:yaml:`carbonCopyRecipients` and :yaml:`blindCarbonCopyRecipients` - and the
redirect targets :yaml:`pageUid`, :yaml:`additionalParameters` and
:yaml:`fragment`.

The :yaml:`SaveToDatabaseFinisher` in particular never resolves labels. Both of
its mapping routes store the submitted value: :yaml:`elements.<element>` writes
it directly, and :yaml:`databaseColumnMappings.<column>.value` resolves a
:yaml:`{elementIdentifier}` placeholder to the same value, so a column filled
through either route carries the same content. :yaml:`whereClause` matches
against it accordingly. A stored option key stays independent of the site
language the form was submitted in and of later label changes, and remains
usable for queries and joins - resolve labels when reading the records instead.

A custom finisher opts a single option into label resolution by reading it with
:php:`AbstractFinisher->parseOptionAsDisplayValue()` instead of
:php:`parseOption()`.

Two further behaviours changed along the way:

*   An array value interpolated into a longer string - a multi select inside
    :yaml:`Request for {salutation} {name}` - previously aborted the finisher
    with a :php:`FinisherException` carrying code :php:`1519239265`. Such a value
    is now joined with :php:`, ` instead. An array that contains an object which
    cannot be converted to a string still throws, now with code
    :php:`1787754756`.
*   The :yaml:`implementationClassName` of the :yaml:`SingleSelect`,
    :yaml:`MultiSelect`, :yaml:`RadioButton` and :yaml:`MultiCheckbox` elements
    changed to :php:`GenericOptionableFormElement`, and the one of
    :yaml:`CountrySelect` to :php:`CountrySelect`. Both extend
    :php:`GenericFormElement`, so an :php:`instanceof` check against it keeps
    matching.

Affected installations
======================

Installations using EXT:form whose email subject, confirmation message or flash
message references a select, radio button, multi checkbox or country select
element, and installations that catch :php:`FinisherException` with code
:php:`1519239265`.

Migration
=========

No migration is required. Code catching :php:`1519239265` no longer needs that
branch.

..  index:: Frontend, PHP-API, YAML, ext:form
