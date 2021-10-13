.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

The system extension "Import/Export" (EXT:impexp) allows content to be exported
from one installation of TYPO3 and then imported into another. Exported data
includes content from multiple tables including :sql:`tt_content` as well as
images and other files stored in :file:`fileadmin/`.

This extension is often used to manage content for :ref:`distributions<t3coreapi:distribution>`
and also training and demonstration purposes.

.. _merging_multiple_sets_of_data:

Merging multiple sets of data
=============================

By default the identifiers are changed when importing data, making it possible to
merge several projects into one installation. The table identifiers are
automatically changed in such a way that content elements remain attached to
their pages and images to their content elements.

It is also possible to keep the identifiers (`uids`) to allow the reproduction
of the exact same page and content tree.

.. _what-doesnt-it-do:

What doesn't it do?
===================

*   Exported content does not include code from any installed extensions or
    sitepackages.
*   This extension is not used for the :guilabel:`Download`
    feature in the :guilabel:`List` module.

.. _backward_compatibility:

Backward compatibility
======================

The data structure for content exports have seen very little changes since their
original inception. It is sometimes possible to export content from a fifteen
year old TYPO3 installation straight into a current installation of TYPO3.

It is often more feasible to use the import/export tool
than it is to attempt to update old installations of TYPO3.

The following images show the export dialog of a current TYPO3 installation and
TYPO3 v3.8.0: They correspond pretty much.

.. include:: /Images/AutomaticScreenshots/ImpExp.rst.txt

.. include:: /Images/ManualScreenshots/ImpExpV3.8.rst.txt
