..  include:: /Includes.rst.txt

..  _for-integrators:

===============
For integrators
===============

Properly configuring access permissions to the :sql:`sys_note` table is
essential to enable editors to effectively utilize this system extension.
Internal notes allow editors to document important information related to
specific pages, enhancing the overall usability and functionality of TYPO3
for your organization.

Administration / configuration
==============================

To ensure smooth operation of the "Internal notes" extension, it is important
to configure the necessary access permissions for editors. Editors must have
read and/or write access to the :sql:`sys_note` table in TYPO3. Without the
appropriate access permissions, editors may encounter issues when trying to
create, view, or modify notes.

Access configuration
--------------------

Access to the :sql:`sys_note` table can be configured through TYPO3's backend
user access settings. Here is how you can configure the necessary permissions:

#.  Log in to the TYPO3 backend as an administrator.

#.  In the backend, navigate to the :guilabel:`System > Backend Users` module.

#.  Create a new
    :ref:`Backend user group <t3coreapi:access-users-groups-groups>`,
    if you do not have one already.

#.  Activate the :guilabel:`Internal note` checkbox for both
    :guilabel:`Tables (listing)` and :guilabel:`Tables (modify)`.

    ..  figure:: /Images/sys_note_access.png
        :alt: Access to the sys_note table
        :class: with-shadow

        Giving access to the :sql:`sys_note` table

#.  Save the changes.

By configuring access rights in this way, editors will have the necessary
permissions to create, edit, and view notes using the "Internal notes" extension.
This ensures that they can effectively use this feature to add context and
notes to pages within your TYPO3 installation.

Remember to regularly review and update access permissions as needed to
maintain security and compliance with your organization's requirements.
