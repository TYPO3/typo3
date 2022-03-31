
.. include:: /Includes.rst.txt

========================================================================
Feature: #19157 - Add option to exclude all hidden records in EXT:impexp
========================================================================

See :issue:`19157`

Description
===========

The export configuration of EXT:impexp has been extended to allow to
completely deactivate exporting of hidden/deactivated records. This
behaviour can be controlled via a new option which is checked by default.

Furthermore, if the inclusion of hidden records is activated (which is
now an explicit choice), then an additional button is shown, allowing
users to preselect all hidden records for manual exclusion.

.. index:: Backend, ext:impexp
