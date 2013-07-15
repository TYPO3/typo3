.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


.. _workspaces:

Workspaces
^^^^^^^^^^


.. _usability:

Problems with versioning usability
""""""""""""""""""""""""""""""""""

One problem with raw versioning is that it easily requires a lot of
administration and awareness from users. For instance, an author has
to consciously create a new version of a page or content element
before he must edit it. Maybe he forgets. So either he makes a change
live or - if TYPO3 is configured for it - he will be denied access to
editing but frustrated over an error message. Further, keeping track
of versions across the system might be difficult since changes are
often made at various places and should be published together.

Some of these problems are fixed when elements are always processed
with a workflow that keeps track of them. But a workflow process might
be too rigid for scenarios where a group of editors are concerned with
the site content broadly and not the narrow scope of an element
centred workflow.

Furthermore, the preview of future content is hard to implement unless
you require people to request a preview of each individual new version
- but most often they like to see the combined impact of all future
versions!


.. _solution:

The perfect solution
""""""""""""""""""""

The concept of workspaces is the answer. Workspaces puts versioning
into action in a very usable and transparent way offering all the
flexibility from live editing but without sacrificing the important
control of content publishing and review.

A workspace is a state in the backend of TYPO3. Basically there are
two types of workspaces:

- LIVE workspace: This is exactly the state TYPO3 has always been in.
  Any change you make will be instantly live. Nothing has changed, it
  just got a name.

- Custom workspaces: When a user selects a custom workspace new rules
  apply to anything he does in the backend:

  - Any change he tries to make will not affect the live website. It's a
    safe playground.

  - Transparent versioning: He can edit pages and elements because a new
    version is made automatically and attached to the workspace. No
    training needed, no administrative overhead!

  - Previewing: Visiting the frontend website will display it as it will
    appear when all versions in the workspace is eventually published.

  - Overview of changes: The workspace manager module gives a clear
    overview of all changes that has been made inside the workspace across
    the site. This gives unparalleled control before publishing the
    content live.

  - Constraints: Only tables that support versioning can be edited. All
    management of files in fileadmin/ is disabled by default because they
    may affect the live website and thus would break the principle of
    "safe playground". Records that do not support versioning can be
    allowed to be edited explicitly and file mounts can be defined inside
    of which files  *can* be managed.

  - Can be configured additionally with owners, members and reviewers plus
    database- and file mounts plus other settings. A custom workspace is a
    great way to do team-based maintenance of (a section of) the website
    including a basic implementation of workflow states with editor-
    reviewer-publisher.


.. tip::

   **Analogy**

   The concept of workspaces can be compared with how SVN works for
   programmers; You check out the current source to your local computer
   (= entering a draft workspace), then you can change any script you
   like on your local computer (= transparent editing in the workspace),
   run tests of the changed code scripts (= previewing the workspace in
   the frontend), compare the changes you made with the source in SVN (=
   using the Workspace Manager modules overview to review the complete
   amount of changes made) and eventually you commit the changes to SVN
   (= publishing the content of the workspace).


.. _publishing-and-swapping:

Publishing and swapping
"""""""""""""""""""""""

There are two ways to publish an element in a workspace; publish or
swap. In both cases the draft content is published live. But when
swapping it means the current live element is attached to the
workspace when taken offline. This is contrary to the publish mode
which pushes the live item out of any workspace and "into the
archive".

The swapping mode is useful if you have a temporary campaign, say a
christmas special frontpage and website section. You create the
christmas edition in a custom workspace and two weeks before christmas
you swap in the christmas edition. All normal pages and elements that
were unpublished are now in the workspace, waiting for christmas to
pass by and eventually the old frontpage etc. will be swapped back in.
The christmas edition is now back in the workspace and ready for next
year.


.. _more-on-workspace-types:

More on Workspace types
"""""""""""""""""""""""

Here is a clearer description of the various workspace types, their
differences and applications:



.. _access:

**Access**
~~~~~~~~~~

.. container:: table-row

   Topic
         **Access**

   Live workspace
         Users and groups must be specifically allowed access to the Live
         workspace.

         (Checkboxes in user/group record)

   Custom workspaces
         Granted through the workspace configuration which includes:

         \- A list of editors (users and/or groups)

         \- A list of reviewers (users and/or groups)

         \- Owner users (initial creator)



.. _editing:

**Editing**
~~~~~~~~~~~

.. container:: table-row

   Topic
         **Editing**

   Live workspace
         Live content

   Custom workspaces
         Draft versions of live content

         *Option: To allow editing of tables without versioning available.*



.. _db-mounts:

**DB mounts**
~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **DB mounts**

   Live workspace
         From user profile

   Custom workspaces
         Specific DB mounts can be specified in which case they will overrule
         DB mounts from user profiles.

         Specific DB mounts are required to be within the DB mounts from the
         user profile (for security reasons)

         If no DB mounts specified for workspace, user profile mounts are used
         (default)



.. _file-mounts:

**File mounts**
~~~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **File mounts**

   Live workspace
         From user profile

   Custom workspaces
         By default, all file manipulation access is banned! (Otherwise
         violation of "draft principle")

         Optionally, file mounts can be specified for convenience reasons.



.. _scheduled-publishing:

**Scheduled publishing**
~~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Scheduled publishing**

   Live workspace
         N/A

   Custom workspaces
         The workspaces extension comes with a scheduler task that allows the
         use of the scheduler to publish your whole workspace on a certain day
         and time. You can also specify an unpublish time which requires the
         use of swapping as publishing type.



.. _reviewing:

**Reviewing**
~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Reviewing**

   Live workspace
         Only through a separate workflow system.

   Custom workspaces
         **Members** can raise content from "Editing" stage to the next
         configured stage. If no custom stages are configured the next stage is
         "Ready to publish". Members can only edit content when its in "Editing
         stage".

         **Persons responsible for a certain stage (Reviewers)** can edit
         content in the "Editing" stage and additionally in the stage they are
         responsible for. They can push content from "Editing" and from their
         stage to the next stage.

         **Owners** can operate all states of course. Owners are the only ones
         to edit content when in "Publish" mode. Thus "Publish" mode provides
         protection for content awaiting publication.

         Options available for automatic email notification between the roles.



.. _publishing:

**Publishing**
~~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Publishing**

         (For all: Requires edit access to live element)

   Live workspace
         No limitations. Content can be edited live and even content from other
         workspaces can be published through the versioning API regardless of
         stage.

   Custom workspaces
         Workspace owners can publish (even without access to Live workspace).

         Reviewers/Members cannot publish  *unless* they have access to online
         workspace as well (this default behavior can be disabled).

         Option: Restrict publishing to elements in "Publish" stage only.

         Option: Restrict publishing to workspace owner.



.. _settings:

**Settings**
~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Settings**

   Live workspace
         N/A

   Custom workspaces
         Users with permission to create a custom workspace can do so.

         Workspace owners can add other owners, reviewers and editors and
         change all workspace properties.



.. _auto-versioning:

**Auto versioning**
~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Auto versioning**

   Live workspace
         N/A

   Custom workspaces
         Yes, but can be disabled so a conscious versioning actions is
         required.



.. _swapping:

**Swapping**
~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Swapping**

   Live workspace
         N/A

   Custom workspaces
         Yes, but can be disabled.



.. _other-notes:

**Other notes**
~~~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Other notes**

   Live workspace


   Custom workspaces
         Custom workspaces have a freeze flag that will shut down any
         update/edit/copy/move/delete etc. command in the workspace until it is
         unset again.



.. _module-access:

**Module access**
~~~~~~~~~~~~~~~~~

.. container:: table-row

   Topic
         **Module access**

   Live workspace
         All backend modules can specify $MCONF['workspaces'] =
         "[online,offline,custom]" to limit access based on the current
         workspace of user.



.. _usage:

**Usage**
~~~~~~~~~

.. container:: table-row

   Topic
         **Usage**

   Live workspace
         Administrative purposes. First creation of site.

   Custom workspaces
         Specific projects on a site branch. Simple review-cycles. Informal
         team-work on site maintenance.



Generally, "admin" users have access to all functionality as usual.

.. note::

   **Supporting workspaces in extensions**

   Since workspaces imply transparent support all over the backend and
   frontend it means that extensions must be programmed with this in
   mind. Although the ideal is complete transparency in backend and
   perfect previews in the frontend this is almost impossible to obtain.
   But a high level of consistency can be obtained by using API functions
   in TYPO3. These functions and the challenges they are designed to
   address are discussed in :ref:`TYPO3 Core API <t3api:workspaces>`.

