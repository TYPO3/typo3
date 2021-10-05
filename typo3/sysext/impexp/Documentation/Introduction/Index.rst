.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

The Core System extension `impexp` allows content to be exported
from one installation of TYPO3 and then imported into another. Exported data
includes content from multiple tables including `tt_content` as well as images
and other files stored in `fileadmin`.

This system extension is often used to manage content for distributions
and also training and demonstration purposes.

.. _what-doesnt-it-do:

What doesn't it do?
===================

*   Exported content does not include code from any installed extensions or sitepackages.
*   The extension :file:`impexp` is not used for the :guilabel:`Download`
    feature in the :guilabel:`List` module.

.. _merging_multiple_sets_of_dats:

Merging multiple sets of data
=============================

By default the identifiers are changed when importing data, making it possible to
merge several projects into one installation. The table identifiers are automatically
changed in such a way that content elements remain attached to their pages and
images to their content elements.

It is also possible to keep the identifiers (`uids`) to allow the reproduction of the exact same
page and content tree.

.. _backward_compatibility:

Backward compatibility
======================

The data structure for content exports have seen very little changes since their original inception.
It is possible to export content from a fifteen year old TYPO3 installation straight
into a current installation of TYPO3.

The following image shows the export dialog of TYPO3 installation running
version 3.8.0.

It is often more feasible to use the Import / Export tool
than it is to attempt to update old installations of TYPO3.

.. include:: /Images/ManualScreenshots/ImpExpV3.8.rst.txt
