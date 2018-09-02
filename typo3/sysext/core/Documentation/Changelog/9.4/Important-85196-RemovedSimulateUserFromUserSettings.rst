.. include:: ../../Includes.txt

============================================================
Important: #85196 - Removed simulate user from user settings
============================================================

See :issue:`85196`

Description
===========

The user settings module that can be reached from the backend toolbar in
the drop down behind the logged in user name had a functionality to
show user settings of another backend user for admins.

This simulate user switch has been dropped from the interface.

Admins who want to check or change settings of other users should fully
switch to the target user using the "Switch to user" of the "Backend users"
module and then navigate to its user settings.

Backend administrators who often need to change other backend user settings
should consider using adapted User TSconfig :ts:`setup.` details to
streamline this workflow.


.. index:: Backend, ext:setup
