:navigation-title: Introduction

.. include:: /Includes.rst.txt
.. _known-problems:
..  _introduction:

======================================================
Introduction into the system extension "Import/Export"
======================================================

The system extension "Import/Export" (EXT:impexp) allows content to be exported
from one installation of TYPO3 and then imported into another. Exported data
includes content from multiple tables including :sql:`tt_content` as well as
images and other files stored in :directory:`fileadmin/`.

This extension is often used to manage content for :ref:`distributions <t3coreapi:distribution>`
and also training and demonstration purposes.

..  _merging_multiple_sets_of_data:

Merging multiple sets of data
=============================

By default the identifiers are changed when importing data, making it possible to
merge several projects into one installation. The table identifiers are
automatically changed in such a way that content elements remain attached to
their pages and images to their content elements.

It is also possible to keep the identifiers (`uids`) to allow the reproduction
of the exact same page and content tree.

..  _what-doesnt-it-do:

What doesn't it do?
===================

*   Exported content does not include code from any installed extensions or
    sitepackages.
*   This extension is not used for the :guilabel:`Download`
    feature in the :guilabel:`List` module.

..  _backward_compatibility:

Backward compatibility
======================

The data structure for content exports have seen very little changes since their
original inception. It is sometimes possible to export content from a fifteen
year old TYPO3 installation straight into a current installation of TYPO3.

It is often more feasible to use the import/export tool
than it is to attempt to update old installations of TYPO3.

The following images show the export dialog of a current TYPO3 installation and
TYPO3 v3.8.0: They correspond pretty much.

However, several details may change due to Deprecations and Breaking Changes,
which can lead to issues with old import data. In cases where the import fails,
it is recommended to try to manually export assets/files, and re-create the
reference Index after import. The older an installation is, the more manual
rework is expected.

Ongoing improvements to the Import/Export code base can only made, when
legacy considerations are not the first priority. A "guaranteed" fully-working
export and re-import is only given for T3D structures within the same
major version.

..  include:: /Images/AutomaticScreenshots/ImpExp.rst.txt

..  include:: /Images/ManualScreenshots/ImpExpV3.8.rst.txt
