.. include:: /Includes.rst.txt

===================================================
Important: #93121 - Workspace records are discarded
===================================================

See :issue:`93121`

Description
===========

A record in workspaces that has been
changed in comparison to live - if it is a new, a moved
or a changed workspace record - is subject to change in deletion behavior:
When a user in a non-live workspace uses the delete button
(waste bin symbol in list or page module) on a record that has a workspace
overlay, those records are discarded now.

Technically, the record in question, plus directly attached 'child' records
like inline relations are now fully deleted from the database with this
operation. They are not 'soft-deleted' anymore, as it happens with records
of soft-delete-enabled tables in a live workspace.

This is good and bad from a UX point of view: The delete behavior in workspaces
page and list module and the 'discard' behavior in workspace module are now
identical, which simplifies things for users. On the other hand, discarded
workspace records can not be 'undeleted' anymore using the recycler module.
The recycler and history modules however did not work well with workspaces,
only very simple scenarios did sometimes lead to the expected result. The
recycler module is now hidden for users in non-live workspace.

Note there is a second scenario: When deleting a record in page or list module
that has NOT been changed in this workspace, this record is marked as
to be deleted in live during publish. Technically a 'delete placeholder'
is created in this case. This important difference is currently not reflected
well in page and list module. Further TYPO3 v11 changes will improve this
situation usability wise. The change of the delete behavior allows us to
work in this area to ultimately end up with a satisfying user experience.

.. index:: Backend, Database, ext:workspaces
