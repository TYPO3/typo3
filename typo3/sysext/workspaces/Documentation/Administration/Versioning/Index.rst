.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _versioning:

Versioning
^^^^^^^^^^

TYPO3 offers versioning of the database elements it manages. This
versioning system allows you to work on future versions of content
without affecting the live content. It is used by workflow systems to
offer a process for such content, going from creation, editing to
review and publishing.

Versioning is available in the core API by default, but to gain access
to management tools you must install the system extensions versioning
and workspaces.


.. _technical-details:

Technical Details
"""""""""""""""""

Versioning must be enabled on a per-table basis in the [ctrl] section
of the $TCA array entry for a table. In addition a fixed set of fields
has to exist for the management of versions. All of these technical
details are specified in the document "TYPO3 Core API" where you will
also find other in-depth information.

Future and past versions of records in TYPO3 remain in the same table
as the live version. However, all "offline" versions will have a pid
value of "-1" which identifies them as "offline". Further they have a
field, "t3ver\_oid" which points to their live ("online") version.

When a future/past version is swapped with the live version it is done
by  *swapping all field values except the uid and pid* fields (and of
course versioning related fields are manipulated according to their
function). It means that online content is always identified by the
same id as before and therefore all references are kept intact.

Versioning is easy for existing elements. However, moving, creating
and deleting poses other problems. This is solved the following way:

- Deleting elements is done by actually creating a new version of the
  element  *and* setting a flag in the new version (t3ver\_state=2) that
  indicates the live element must be deleted upon swapping the versions.
  Thus deletion is "scheduled" to happen when the versions are swapped.

- Creating elements is done by first creating a placeholder element
  which is in fact live but carrying a flag (t3ver\_state=1) that makes
  it invisible online. Then a new version of this placeholder is made
  which is what is modified until published.

- Moving elements is done by first creating a placeholder element which
  is in fact live but carrying a flag (t3ver\_state=3) that makes it
  invisible online. It also has a field, "t3ver\_move\_id", holding the
  uid of the record to move (source record). In addition, a new version
  of the source record is made and has "t3ver\_state" = 4 (move-to
  pointer). This version is simply necessary in order for the versioning
  system to have something to publish for the move operation. So in
  summary, two records are created for a move operation in a workspace:
  The placeholder (online, with state=3 and t3ver\_move\_id set) and a
  new version (state=4) of the online source record (the one being
  moved).


.. _unique-fields:

Unique fields
~~~~~~~~~~~~~

- Unique fields like a page alias or user name are tricky in a
  versioning scenario because the publication process must perform a
  check if the field is unique in the "Live" situation. The implications
  of implementing this means that we have chosen a solution where unique
  fields are simply not swapped at all! It means that publication of a
  new version of a page cannot and will not alter the alias of the live
  version. The "Live" unique value will remain until changed in the live
  version.

- You can hide fields with the "unique" keyword when there are offline
  versions. This is done with the display condition:

::

   'displayCond' => 'VERSION:IS:false',


.. _life-cycle:

Life cycle
~~~~~~~~~~

- When a new version of an element is created, its publishing counter
  (t3ver\_count) is set to zero. This means its life cycle state is
  interpreted as "Draft"; the element is in the pipeline to be
  published. When the element is published the life cycle state is
  "Live" and when it is swapped out again the publishing counter will be
  incremented and the life cycle is interpreted as "Archive". Yet, the
  element can be continously published and unpublished and for each time
  the publishing counter will be incremented, telling how many times an
  element has been online.


.. _permissions:

Permissions
~~~~~~~~~~~

- This is an overview of how permissions are handled in relation to
  versioning:


.. _display:

Display
~~~~~~~

- Read permissions are evaluated based on the live version of pages (as
  the basic rule). The read permissions of the offline page version in a
  workspace is not observed.

- The ID of the live record is used so the live records display-
  permissions get evaluated.


.. _versioning-records:

Versioning records
~~~~~~~~~~~~~~~~~~

- To create a new version the user must have read permission to the live
  record he requests to version

- A new version of a page will inherit the owner user, group and
  permission settings from the live record


.. _publishing-version:

Publishing version
~~~~~~~~~~~~~~~~~~

- To publish, a user must have general publishing permission in the
  workspace, for instance be the owner of it or have access to the Live
  workspace.

- In addition, the user must have read and edit access to the offline
  version being published plus edit access to the  *live version* that a
  publishing action will substitute!

- The permissions of a new version of a page follows the page when
  published.


.. _editing-records:

Editing records
~~~~~~~~~~~~~~~

- For all editing it is required that the stage of the versioned record
  (or root point) allows editing.

- Page records:
  
  - Permission to edit is always evaluated based on the pages own
    permission settings and not the live records.

- Records from non-pages tables:
  
  - Always based on the live parent page.


.. _new-records:

New records
~~~~~~~~~~~

- When new records are created with a version and live place holder the
  permissions depend on the live page under which the record is created.


.. _moving-records:

Moving records
~~~~~~~~~~~~~~

- Records can be moved as long as the source and destination root points
  has a stage that allows it.

- New records created with a place holder element can be moved freely
  around.

- Generally, the stage of a moved record has to allow for editing plus
  regular permissions for moving are observed.


.. _deleting-records:

Deleting records
~~~~~~~~~~~~~~~~

- If a record supports versioning it will be marked for deletion if all
  usual requirements are fulfilled at the time of the delete request:
  Delete access to record, no subpages if recursive deletion is not
  enabled and no disallowed table records are found. As soon as the
  record is marked for deletion any change to the record and subpages
  that would otherwise prevent deletion for the user will not be
  effective: The record  *will* be deleted upon publication!

- If you try to delete a Live record for which a version is found in the
  workspace, that version is deleted instead.

- Detaching versions from a workspace and raising stage of versions can
  be done as long as the user has edit permission to the record.

