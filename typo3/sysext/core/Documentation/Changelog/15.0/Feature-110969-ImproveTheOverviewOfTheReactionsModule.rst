..  include:: /Includes.rst.txt

..  _feature-110969-1791465467:

===============================================================
Feature: #110969 - Improve the overview of the reactions module
===============================================================

See :issue:`110969`

Description
===========

The :guilabel:`Administration > Integrations > Reactions` module now
introduces itself with a short description of what reactions are for.

When no reaction exists yet, the module lists the available reaction types as
cards instead of a generic notice. Each card shows the icon and the label of
the type and a button that opens the form for a new reaction with that type
already selected.

The list of reactions shows new columns:

Target
    The table a record is created in, and below it the storage page with its
    title and uid. A storage page that does not exist is flagged.

Impersonate User
    The backend user the reaction acts as. A user that is disabled is marked as
    such. A reaction without a user, or with a user that does not exist any
    more, is flagged. The list can be sorted by this column.

The filter above the list can additionally narrow it down to enabled
reactions only, and to the reactions that impersonate a given backend user.
Only users that a reaction impersonates are offered. Sorting and paging keep
the filter.

A reaction now also remembers its last call. Three new fields of the table
:sql:`sys_reaction` hold it:

:sql:`last_called`
    The time of the last call.

:sql:`last_status`
    The HTTP status code the caller was answered with, ``0`` if the reaction has
    never been called.

:sql:`last_failure`
    The error the caller was sent if the call failed, empty after a
    successful call. Only that error is stored, never internal details.

A call is recorded once it has passed the secret check: a successful call, a
reaction type that is not available any more, a payload the reaction rejects,
and a reaction that fails unexpectedly. Nothing is written for calls without
a secret or with a wrong one, with an invalid or unknown identifier, or for a
disabled, scheduled or expired reaction, so a caller who does not know the
secret cannot change what is recorded.

The call is written directly, not through the DataHandler: the modification
date of the reaction stays the same, and no history or log entry is written.
A copy of a reaction starts without a recorded call.

The list shows a third new column, :guilabel:`Last called`, with the date and
the HTTP status of the last call, and the reason of a failed call below it. A
reaction that has never been called says so. The list can be sorted by this
column, and the filter can narrow it down to reactions that have been called
at least once or never.

Impact
======

Administrators see what the module is for, can start with the reaction type
they need, and can tell from the list which target and user each reaction
writes with, without opening each record. They can also tell whether a
reaction is used at all, whether its last call succeeded, and why it failed
if it did not.

Every call that passes the secret check now updates one row in the
database. The new fields are created by the database analyzer from the TCA.

..  index:: Backend, Database, TCA, ext:reactions
