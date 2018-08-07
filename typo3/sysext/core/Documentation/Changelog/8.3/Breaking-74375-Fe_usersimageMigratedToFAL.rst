
.. include:: ../../Includes.txt

=================================================
Breaking: #74375 - fe_users.image migrated to FAL
=================================================

See :issue:`74375`

Description
===========

The Frontend User field "image" was previously handled via images located under
:file:`uploads/pics/`, as simple file references, not able to handle duplicate images etc.

The field is now set up to add references of the File Abstraction Layer avoiding
the need to copy all images to :file:`uploads/pics/`.


Impact
======

Using the `fe_users.image` field in the frontend or backend may result in unexpected
behaviour.


Affected Installations
======================

Any TYPO3 installation using the field "image" within the database table "fe_users",
common in third-party extensions using the field for storing images for frontend
users (like mm_forum).


Migration
=========

Use the File Abstraction Layer for output and deal with rendering or changing images
for frontend users.

Use the migration wizard provided in the install tool to migrate existing data to
proper file references.

.. index:: Frontend, Database, FAL
