.. include:: /Includes.rst.txt

.. _important-99660-1674251294:

==============================================================
Important: #99660 - Remove content area from new record wizard
==============================================================

See :issue:`99660`

Description
===========

The TYPO3 backend comes with a distinction between "content elements" and
other records: While content is managed using the specialized :guilabel:`Page`
module, the :guilabel:`List` module is the main management interface for
other types of records.

Managing content elements from within the :guilabel:`List` module is not
a good choice for editors, the :guilabel:`Page` module should be used.

To foster this separation, the :guilabel:`Create new record` view reachable
from within the :guilabel:`List` module no longer allows to add content
elements. As a side effect, this avoids wrong or invalid default values
of the :guilabel:`Column` (colPos) field.

.. index:: Backend, ext:backend
