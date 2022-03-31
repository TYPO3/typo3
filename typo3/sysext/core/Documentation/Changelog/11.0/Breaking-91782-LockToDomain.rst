.. include:: /Includes.rst.txt

======================================================================================================
Breaking: #91782 - lockToDomain feature for frontend users / groups and backend users / groups removed
======================================================================================================

See :issue:`91782`

Description
===========

TYPO3 Core shipped with a feature called "lockToDomain" for frontend and backend users which made the user login only valid if
the exact given HTTP_HOST matches the filled domain.

A similar functionality with the same name for groups existed, which only added the group to a specific user during a session,
if the user was accessing a TYPO3 site under a specific domain.

Both features have been removed.

Impact
======

Frontend users or backend users that have this option set previously, will now be able to login independent of the defined HTTP_HOST
header sent with the login page.

Regardless of any setting of the "lockToDomain" setting of a specific group, all groups added
to a user are now applied during login of a user, both for frontend and backend.


Affected Installations
======================

TYPO3 Installations using this feature in their database records are affected. Following SQL SELECT statements help to identify records
with a value for the features, which indicates those users and groups will now be able to log in without the domain restriction.

Frontend Users:

.. code-block:: sql

   SELECT uid, pid, username FROM fe_users WHERE lockToDomain != '' AND lockToDomain IS NOT NULL;

Backend Users:

.. code-block:: sql

   SELECT uid, pid, username FROM be_users WHERE lockToDomain != '' AND lockToDomain IS NOT NULL;

Frontend Groups:

.. code-block:: sql

   SELECT uid, pid, username FROM fe_groups WHERE lockToDomain != '' AND lockToDomain IS NOT NULL;

Backend Groups:

.. code-block:: sql

   SELECT uid, pid, username FROM be_groups WHERE lockToDomain != '' AND lockToDomain IS NOT NULL;


Migration
=========

Any installations needing this feature should build this in
custom extensions extending TCA and a custom Authentication Service.

In addition, if such a feature is needed for frontend users
or groups, it is recommended to use the storagePid option to limit
frontend user login by Storage Folders.

.. index:: Database, TCA, NotScanned, ext:core
