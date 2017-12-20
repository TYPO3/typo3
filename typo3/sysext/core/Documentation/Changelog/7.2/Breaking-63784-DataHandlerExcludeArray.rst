
.. include:: ../../Includes.txt

====================================================================
Breaking: #63784 - Visibility and type of DataHandler->exclude_array
====================================================================

See :issue:`63784`

Description
===========

The internal but formerly public property DataHandler->exclude_array is replaced by
the protected property DataHandler->excludedTablesAndFields, which contains the
combination of excluded table and field as key instead. This improves performance
especially for bulk editing since many in_array()-checks can be avoided.


Impact
======

Extensions using the DataHandler (former TCEMain) and changing the (former public)
exclude_array to change access to tables and fields cannot do so anymore. Users need
to have their access-rights set properly instead.


Affected installations
======================

Installations using extensions that read or write the undocumented array exclude_array.


Migration
=========

Remove code accessing DataHandler->exclude_array and configure the BE-User properly.


.. index:: PHP-API, Backend
