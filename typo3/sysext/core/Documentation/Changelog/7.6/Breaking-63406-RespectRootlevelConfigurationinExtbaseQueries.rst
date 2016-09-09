
.. include:: ../../Includes.txt

=====================================================================
Breaking: #63406 - Respect rootLevel configuration in extbase queries
=====================================================================

See :issue:`63406`

Description
===========

The rootLevel of a table can be configured to 0, 1 or -1 in TCA, to define where records of a table can be found in the system:

* 0: In the page tree only
* 1: Only on the root page (pid 0)
* -1: Both, on the root page and in the page tree

Currently only 0 and 1 are respected by the `Typo3DbQueryParser` when building the pageId statement. This means that a rootLevel of -1
does not get any pageId statement at all and therefore ignores any `storagePid` configuration for extbase plugins.


Impact
======

Custom records that have a configuration like this `$GLOBALS['TCA']['tx_myext_domain_model_record']['ctrl']['rootLevel'] = -1` and
are used in extbase plugins might have trouble finding the records if `plugin.tx_myext.persistence.storagePid` is not configured properly.


Affected Installations
======================

Third party code using `$GLOBALS['TCA']['tx_myext_domain_model_record']['ctrl']['rootLevel'] = -1` with records within the
page tree and without a proper `storagePid` configuration.


Migration
=========

Set `plugin.tx_myext.persistence.storagePid` to the page ids you want to find records from. 0 does not need to be included as
it is added to the statement automatically.
