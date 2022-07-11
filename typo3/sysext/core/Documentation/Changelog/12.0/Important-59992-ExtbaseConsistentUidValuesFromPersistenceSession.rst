.. include:: /Includes.rst.txt

.. _important-59992-1657551957:

===========================================================================
Important: #59992 - Extbase: Consistent uid values from Persistence Session
===========================================================================

See :issue:`59992`

Description
===========

Extbase's Domain Models now always return the default language's "uid" property
when accessing :php:`$myModel->getUid()`.

Previously the property was sometimes filled with the "language overlay" ID for
translated records, and sometimes filled with the ID of the "translation origin"
ID depending on the query settings' language configuration.

Under the hood, Extbase now always checks if a record from the database,
which should be constituted as object, has a "translation parent" ("l10n_parent")
and uses this value for the "uid". The field `_localizedUid` then contains
the uid value of the translated record.

.. index:: PHP-API, ext:extbase
