:navigation-title: IP Anonymization task

..  include:: /Includes.rst.txt

..  _ip-anonymization-task:

=====================
IP anonymization task
=====================

The IP Anonymization task can take a more elaborate
configuration which is detailed below.

The task anonymizes the IP addresses to enforce the privacy of the persisted data.

..  contents:: Table of contents

..  _ip-anonymization-task-example:

Example: Configure additional tables for the "IP Anonymization" task
====================================================================

..  deprecated:: 14.0
    The previous configuration method using
    :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\IpAnonymizationTask::class]['options']['tables']`
    has been deprecated and will be removed in TYPO3 v15.

    See also: `Changelog Deprecation: #107562 - Ip Anonymization Task configuration via $GLOBALS <https://docs.typo3.org/permalink/changelog:deprecation-107562-1736193200>`_

..  literalinclude:: _codesnippets/_tx_scheduler_ip_anonymization.php.inc
    :language: php
    :caption: packages/my_extension/Configuration/TCA/Overrides/tx_scheduler_ip_anonymization.php

..  include:: /_Includes/_ExtendingSchedulerTca.rst.txt

This entry configures that the field `private_ip` of table
`tx_myextension_my_table` can be anonymized after a chosen number of days.

The field `tstamp` will be taken into account to determine when the database
record was last changed.

..  _ip-anonymization-task-migration:

Migration: Supporting custom tables for "IP Anonymization" tasks for both TYPO3 13 and 14
=========================================================================================

If your extension supports both TYPO3 13 (or below) and 14 keep the registration
of additional tables in the extensions :file:`ext_localconf.php` until support
for TYPO3 13 is removed:

..  literalinclude:: _codesnippets/_ext_localconf_ip_anonymization.php.inc
    :language: php
    :caption: packages/my_extension/ext_localconf.php
