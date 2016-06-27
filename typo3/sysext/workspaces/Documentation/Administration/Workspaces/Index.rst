.. include:: ../../Includes.txt


.. _workspaces:

Workspaces
^^^^^^^^^^

Workspaces are the user interface that sits on top of the
versioning concept, to make it easier for users to manage
versions.

Furthermore workspaces make it possible to create a number
of stages for validation of any given change, thus providing
a complete review process before publication. Users at all
stages can be notified about changes in the review process.

A workspace is a state in the backend of TYPO3 CMS. Basically there are
two types of workspaces:

- LIVE workspace: this is exactly the state TYPO3 CMS has always been in.
  Any change you make will be instantly live. Nothing has changed, it
  just got a name.

  Access to the Live workspace must be explicitly granted to backend
  users and groups (in the "Mounts and Workspaces" tab).

- Custom workspaces: when users select a custom workspace new rules
  apply to anything they do in the backend:

  - Safety: any change they try to make will not affect the live website. It's a
    safe playground.

  - Transparent versioning: they can edit pages and elements because a new
    version is made automatically and attached to the workspace. No
    training needed, no administrative overhead!

  - Previewing: visiting the frontend website will display it as it will
    appear when all versions in the workspace are eventually published.

  - Overview of changes: the workspace manager module gives a clear
    overview of all changes that have been made inside the workspace across
    the site. This gives unparalleled control before making the
    content live.

  - Constraints: only tables that support versioning can be edited.

  - Flexibility: custom workspaces can be configured with owners, members
    and reviewers plus database mounts and more. A custom workspace can
    thus offer a more targeted editing area than backend users and groups.
    Plus a review process as simple or complete as needed.


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
Christmas special frontpage and website section. You create the
Christmas edition in a custom workspace and two weeks before Christmas
you swap in the Christmas edition. All normal pages and elements that
were unpublished are now in the workspace, waiting for Christmas to
pass by and eventually the old frontpage etc. will be swapped back in.
The Christmas edition is now back in the workspace and ready for next
year.


.. _extensions-and-workspaces:

Extensions and workspaces
"""""""""""""""""""""""""

Workspaces imply transparent support all over the backend and
frontend and extensions must be programmed with this in
mind. Although the ideal is complete transparency in backend and
perfect previews in the frontend, this is almost impossible to obtain.
Nevertheless a high level of consistency can be obtained by using the API
provided by TYPO3 CMS. These functions and the challenges they are designed to
address are discussed in :ref:`TYPO3 Core API <t3api:workspaces>`.
