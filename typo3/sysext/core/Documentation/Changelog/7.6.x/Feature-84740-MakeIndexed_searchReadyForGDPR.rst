.. include:: ../../Includes.txt

====================================================
Feature: #84740 - Make indexed_search ready for GDPR
====================================================

See :issue:`84740`

Description
===========

The following features have been added to the extension `indexed_search` to make it compatible with the GDPR law:

**Add table `index_stat_search` to the available garbage collector tasks**

Entries of the table `index_stat_search` can now be deleted after a given amount of days by using
the scheduler task *Table garbage collection* of the extension `scheduler`.

**Make the IP tracking configurable**

Every successful search is tracked in the table `index_stat_search` which includes the IP address of the client as well.
The :php:`\TYPO3\CMS\Core\Utility\IpAnonymizationUtility` is now used to mask the IP.
The level of privacy can be configured in the extension configuration in the Install Tool with
the setting `trackIpInStatistic`. By default it is set to `2`, which means that the host and subnet are masked.


Impact
======

Configure your installation as needed. Define the tracking of the IP address and the removal of not needed search statistics.

.. index:: ext:indexed_search