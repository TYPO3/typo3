..  include:: /Includes.rst.txt

..  _for-editors:

===========
For editors
===========

Target group: **Editors**

When a record in TYPO3 is deleted via the backend, it is marked for deletion
in the database and not visible anymore in the backend or displayed on
the website. Deleted records can be restored via the Recycler backend module.

To use the backend module navigate to the :guilabel:`Web > Recycler` module:

..  figure:: /Images/Module.png
    :class: with-shadow
    :alt: Recycler backend module

    Recycler backend module

The records displayed depends on which page is selected in the page tree.


Filter records
==============

The records can be filtered:

..  figure:: /Images/Filter.png
    :class: with-shadow
    :alt: Available filter of the Recycler module

    Available filter of the Recycler module

*   Enter a search term in the search box if you are looking for a specific record.
*   Select the depth (number of levels from the selected page).
*   Select the type of record you are searching for.


Details of a record
===================

To view more details, click on the :guilabel:`i` button ("Expand record"):

..  figure:: /Images/ExpandRecord.png
    :class: with-shadow
    :alt: More details of a record

    More details of a record

The following details are displayed:

*   Date when the the record was created.
*   The user who originally created this record (creator).
*   The user who deleted this record.
*   The path to the record in the page tree.


Recovery of records
===================

If you want to recover one or more records, select the relevant records by
checking the checkbox in the first column. A new button
:guilabel:`Recover x records` is displayed.  Clicking on it "undeletes" the
records and they are available again in the :guilabel:`Page` or
:guilabel:`List` view.

..  note::
    A record is recovered with its previous visibility. If it was not hidden
    when it was deleted, it is visible in the frontend without any further action.


Delete records permanently
==========================

Records can be deleted permanently when
:ref:`user permission <allowdelete>` is granted. Select the relevant
records by checking the checkbox in the first column. A new button
:guilabel:`Delete x records` is displayed that when you click on it removes the
records permanently from the database.
