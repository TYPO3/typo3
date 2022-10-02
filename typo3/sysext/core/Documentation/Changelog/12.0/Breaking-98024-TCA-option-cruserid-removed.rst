.. include:: /Includes.rst.txt

.. _breaking-98024:

=================================================
Breaking: #98024 - TCA option `cruser_id` removed
=================================================

See :issue:`98024`

Description
===========

The TCA option in the :php:`ctrl` section of each TCA table :php:`cruser_id` has been
removed, along with populating this system-related information within DataHandler
and the auto-creation of the database field.

The setting was used to fill the UID of the Backend User who originally created
the affected row. However, this information is also available through TYPO3's
History functionality, and does not need to be persisted twice.

Several drawbacks came with this feature, which is why it was removed entirely:

* Extbase did not support this functionality
* When a record was created via the Frontend in the plugin, the userid was not available

Information about a record ("Info Popup" or within Workspaces) is now fetched
through TYPO3's History functionality.

Impact
======

When creating new records, the value of the database field is not auto-populated.

Also, when upgrading to TYPO3 v12, the database field is prepared to be removed.

The option :php:`$GLOBALS['TCA'][$tableName]['ctrl']['cruser_id']` is also
automatically removed during cache warmup from the final TCA listing.

Affected Installations
======================

TYPO3 installations actively using this field for querying or filling, not using
TYPO3 API, and accessing the database directly.

Migration
=========

If the need for the information – that is who created the record – is needed,
use the History functionality to fetch the creation details of a record.

If this field is actively queried, it is recommended to add this field as a
regular TCA column with a custom hook or PSR-14 event to fill this information.

.. index:: Database, TCA, NotScanned, ext:core
