=================================================
Breaking: #74375 - fe_users.image migrated to FAL
=================================================

Description
===========

The Frontend User field "image" was previously handled via images located under uploads/pics/, as simple file references
not able to handle duplicate images etc.

The field is now set up as adding references from the File Abstraction Layer avoiding the need to copy all images to uploads/pics/.


Impact
======

Using the ``fe_users.image`` field in the frontend or backend will result in unexpected behaviour.


Affected Installations
======================

Any TYPO3 installation using the field "image" within the database table "fe_users", common in third-party extensions using
the field for storing images for frontend users (like mm_forum).


Migration
=========

Use the File Abstraction Layer for outputting and dealing with rendering or changing images for frontend users.

Use the migration wizard provided in the install tool to migrate existing code to proper file references.