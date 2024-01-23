.. include:: /Includes.rst.txt

.. _feature-100926-1685267155:

==================================================================
Feature: #100926 - Introduce `RotatingFileWriter` for log rotation
==================================================================

See :issue:`100926`

Description
===========

TYPO3 log files tend to grow over time, if not manually cleaned on a regular
basis, potentially leading to full disks. Also, reading its contents may be
hard when several weeks of log entries are printed as a wall of text.

To circumvent such issues, established tools like `logrotate` are available for
a long time already. However, TYPO3 may be installed on a hosting environment
where `logrotate` is not available and cannot be installed by the customer.
To cover such cases, a simple log rotation approach has been implemented,
following the "copytruncate" approach: when rotating files, the currently
opened log file is copied (for example, to `typo3_[hash].log.20230616094812`) and
the original log file is emptied. This saves the hassle with properly
closing and re-creating open file handles.

A new file writer :php:`\TYPO3\CMS\Core\Log\Writer\RotatingFileWriter` has been
added, which extends the already existing
:php:`\TYPO3\CMS\Core\Log\Writer\FileWriter` class. The
:php:`RotatingFileWriter` accepts all options of :php:`FileWriter` in addition
of the following:

* `interval` - how often logs should be rotated, can be any of

  * :php:`daily` or :php:`\TYPO3\CMS\Core\Log\Writer\Enum\Interval::DAILY` (default)
  * :php:`weekly` or :php:`\TYPO3\CMS\Core\Log\Writer\Enum\Interval::WEEKLY`
  * :php:`monthly` or :php:`\TYPO3\CMS\Core\Log\Writer\Enum\Interval::MONTHLY`
  * :php:`yearly` or :php:`\TYPO3\CMS\Core\Log\Writer\Enum\Interval::YEARLY`

* `maxFiles` - how many files should be retained (by default `5` files, `0` never deletes any file)

The :php:`RotatingFileWriter` is configured like any other log writer.

..  note::

    When configuring :php:`RotatingFileWriter` in :file:`system/settings.php`,
    the string representations of the :php:`Interval` must be used for the
    `interval` option, as otherwise this might break the Install Tool.

Example
-------

The following example introduces log rotation for the "main" log file.

..  code-block:: php
    :caption: system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['Core']['Resource']['ResourceStorage']['writerConfiguration'][\Psr\Log\LogLevel::ERROR] = [
        \TYPO3\CMS\Core\Log\Writer\RotatingFileWriter::class => [
            'interval' => \TYPO3\CMS\Core\Log\Writer\Enum\Interval::DAILY,
            'maxFiles' => 5,
        ],
        \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [], // this is part of the default configuration
    ];

The following example introduces log rotation for the "deprecation" log file.

..  code-block:: php
    :caption: system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']['writerConfiguration'][\Psr\Log\LogLevel::NOTICE] = [
        \TYPO3\CMS\Core\Log\Writer\RotatingFileWriter::class => [
            'logFileInfix' => 'deprecations',
            'interval' => \TYPO3\CMS\Core\Log\Writer\Enum\Interval::WEEKLY,
            'maxFiles' => 4,
            'disabled' => false,
        ],
        \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [], // this is part of the default configuration
    ];

Impact
======

When configured, log files may be rotated before writing a new log entry,
depending on the configured `interval`, where :php:`Interval::DAILY` is the
default. When rotating, the log files are suffixed with a rotation incremental
value.

Example:

..  code-block:: console
    :caption: Directory listing of :file:`var/log/` with rotated logs

    $ ls -1 var/log
    typo3_[hash].log
    typo3_[hash].log.20230613065902
    typo3_[hash].log.20230614084723
    typo3_[hash].log.20230615084756
    typo3_[hash].log.20230616094812

If :php:`maxFiles` is configured with a value greater than `0`, any exceeding
log file is removed.

.. index:: LocalConfiguration, ext:core
