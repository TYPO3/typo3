.. include:: /Includes.rst.txt

.. _feature-101113-1687149377:

===========================================================
Feature: #101113 - Show if redirects were checked in report
===========================================================

See :issue:`101113`

Description
===========

In the status report is an entry to show, if redirect conflicts have been
found.

However, there was previously no indication if / when the last check was run.
If there were no conflicts, a status of "ok" was reported which could be quite
misleading, if a redirects check was not performed lately.

Now we write a timestamp into the registry when checking redirects,
along with the result of "checkintegrity". These are 2 separate
registry entries.

The timestamp is queried in the report generation and an additional
"info" status is displayed, if the timestamp indicates that the
check was run more than 24 hours ago or never run at all:

..  code-block:: text

    List of conflicting redirects may not be up to date!
    Regularly run the console command redirects:checkintegrity.

This can be deactivated in the extension configuration and the time (24 hours)
can be changed as well.

Impact
======

*   An additional informational message will appear in the system report, if
    `checkintegrity` was not run within the last 24 hours
*   This can be configured in the extension configuration of EXT:redirects
*   If extensions provide other means to check redirects, they should write the
    entries to the registry as well as the timestamp (see
    :php:`\TYPO3\CMS\Redirects\Command\CheckIntegrityCommand`):

.. code-block:: php

    /** @var \TYPO3\CMS\Core\Registry $registry */
    $registry->set('tx_redirects', 'conflicting_redirects', $list);
    $registry->set('tx_redirects', 'redirects_check_integrity_last_check', time());

.. index:: Backend, ext:redirects, NotScanned
