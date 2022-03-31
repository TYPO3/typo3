.. include:: /Includes.rst.txt

==========================================================================
Feature: #84781 - Added scheduler task to anonymize IP addresses of tables
==========================================================================

See :issue:`84781`

Description
===========

A new scheduler task has been added which makes it possible to anonymize IP addresses stored in database tables.

The task *Anonymize IP addresses in database tables* is configured in the :file:`ext_localconf.php`.

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\IpAnonymizationTask::class]['options']['tables']['<tableName>'] = [
        'dateField' => '<dateFieldName>',
        'ipField' => '<ipFieldName>'
    ];

After the base configuration the table is available in the scheduler task with the following configuration options:

- Table
- Minimum age an entry must have to be anonymized
- IP mask level


Impact
======

The following tables are available by default:

- index_stat_search
- sys_log

.. index:: CLI, ext:scheduler
