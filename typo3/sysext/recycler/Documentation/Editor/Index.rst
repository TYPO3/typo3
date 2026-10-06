..  include:: /Includes.rst.txt

..  _for-editors:

===========
For editors
===========

Target group: **Editors**

When a record in TYPO3 is deleted via the backend, it is marked for deletion
in the database and not visible anymore in the backend or displayed on
the website. Deleted records can be restored via the Recycler backend module.

To use the backend module navigate to the :guilabel:`Content > Recycler` module:

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
*   Select a language in the language drop-down in the module header. See
    :ref:`Filter by language <recycler-filter-language>`.

..  _recycler-filter-language:

Filter by language
------------------

..  versionadded:: 15.0
    :changelog: feature-107345-1783604196

If you have access to at least two languages of the site of the selected page,
the module header shows a language drop-down. The drop-down shows the selected
language:

*   Select a language to list only the deleted records in this language.
    Records of tables without language support are not listed.
*   Select :guilabel:`[All]` to list all deleted records, independent of their
    language.

The module remembers the selected language for your next visit.

The list shows the flag and the name of the language below the page title of
each record that has a language. Records for all languages show **[All]**.

..  figure:: /Images/LanguageFilter.png
    :class: with-shadow
    :alt: Recycler module with the opened language drop-down and the language of each record

    The language drop-down and the language of each record


Details of a record
===================

To view more details, click on the :guilabel:`i` button ("Expand record"):

..  figure:: /Images/ExpandRecord.png
    :class: with-shadow
    :alt: More details of a record

    More details of a record

The following details are displayed:

*   Date when the record was created.
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
