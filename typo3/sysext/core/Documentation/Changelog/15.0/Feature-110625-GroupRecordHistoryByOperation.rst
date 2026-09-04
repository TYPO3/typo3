..  include:: /Includes.rst.txt

..  _feature-110625-1788531965:

==================================================================
Feature: #110625 - Group record history entries by their operation
==================================================================

See :issue:`110625`

Description
===========

Saving a record in the backend rarely changes only that record. One
:php:`DataHandler` run can create, modify, move and delete several records at
once, and every history entry it writes shares the scope of the correlation id
of that run. The record history module did not use this information, so a
single save appeared as a number of unrelated lines.

The module now offers a :guilabel:`Group by operation` toggle next to
:guilabel:`Show differences` and :guilabel:`Show sub elements`. With it enabled,
the entries of one operation are shown next to each other and introduced by a
header stating how many records the operation changed in total - including the
records that are not part of the currently displayed changelog.

Each of those headers carries an :guilabel:`Undo this operation` button, which
rolls back every record of that operation in one go, instead of one record at a
time. It restores field values, deletes what the operation created, restores
what it deleted and moves records back to where they were.

The lookup behind it is available to extensions as well:

..  code-block:: php

    $scope = $recordHistory->getScopeOfEvent($historyEntry);
    $events = $recordHistory->findEventsForScope($scope);

:php:`RecordHistory->findEventsForScope()` returns every history entry written
during one operation, no matter which table or record it belongs to. It only
returns entries the current backend user is allowed to see, and it respects the
workspace the user is working in. :php:`RecordHistory->getScopeOfEvent()`
resolves the scope of a single entry, and returns :php:`null` for entries
written without a correlation id.

:php:`CorrelationId::scopePrefix()` exposes the serialized prefix all
correlation ids of one scope share, which is what makes such a lookup possible
in the first place.

Impact
======

Editors can see that one save changed twelve records instead of guessing it
from twelve consecutive lines, and undo all twelve at once. The setting is
stored per backend user and is disabled by default, so the module keeps its
previous appearance until the toggle is switched on.

Undoing an operation restores the records to the state they had before it, so
changes made to those records afterwards are overwritten. The confirmation
dialog of the button points this out.

Extensions can resolve all changes belonging to one operation, which is the
basis for reverting a whole operation instead of one record at a time.

..  index:: Backend, PHP-API, ext:backend
