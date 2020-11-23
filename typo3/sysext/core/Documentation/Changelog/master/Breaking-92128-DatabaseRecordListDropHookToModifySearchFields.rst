.. include:: ../../Includes.txt

=======================================================================
Breaking: #92128 - DatabaseRecordList: Drop hook to modify searchFields
=======================================================================

See :issue:`92128`

Description
===========

The TCA configuration `searchFields` in the `ctrl` section has been introduced in TYPO3 4.6.
This configuration allows setting search columns.
Those columns are taken into account by the search in the TYPO3 backend.

To enable a smooth transition between TYPO3 4.5 and 4.6, the hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']
['getSearchFieldList']` has been introduced as well. It allows
to set the `searchFields` for the list module's search.

As this transition should be finished now, the hook has been
removed.

Impact
======

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']['getSearchFieldList']`
isn't evaluated anymore.


Affected Installations
======================

All installations using this hook.


Migration
=========

Set the search fields in the following TCA configuration:
:php:`['ctrl']['searchFields'] = 'list, of, search, columns'`

.. index:: Backend, PHP-API, FullyScanned, ext:recordlist
