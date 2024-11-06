..  include:: /Includes.rst.txt

..  _feature-105549-1742215066:

=================================================================================
Feature: #105549 - Support qualified and unqualified ISO8601 dates in DataHandler
=================================================================================

See :issue:`105549`

Description
===========

The :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` PHP API has been
extended to support qualified and unqualified ISO8601 date formats in order to
correctly process supplied timezone offsets, if supplied.

* *Qualified ISO8601:* Includes an explicit timezone offset (e.g.,
  1999-12-11T10:09:00+01:00 or 1999-12-11T10:09:00Z)
* *Unqualified ISO8601*: Omits timezone offsets, representing "LOCALTIME"
  (e.g., 1999-12-11T10:09:00)

TYPO3 :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` now accepts five
different formats:

+----------------------------+---------------------------+-----------------------------------+
|                            | Format                    | Examples                          |
+============================+===========================+===================================+
| **Unqualified ISO8601**    | :php:`'Y-m-d\\TH:i:s'`    | `1999-11-11T11:11:11`             |
| (LOCALTIME)                |                           |                                   |
+----------------------------+---------------------------+-----------------------------------+
| **Qualified ISO8601**      | :php:`'Y-m-d\\TH:i:sP'`   | `1999-11-11T10:11:11Z`            |
|                            |                           |                                   |
|                            |                           | `1999-11-11T11:11:11+01:00`       |
+----------------------------+---------------------------+-----------------------------------+
| **DateTime objects**       | :php:`\DateTimeInterface` | :php:`new \DateTime('yesterday')` |
|                            |                           |                                   |
|                            |                           | :php:`new \DateTimeImmutable()`   |
+----------------------------+---------------------------+-----------------------------------+
| **SQL flavored dates**     | :php:`'Y-m-d H:i:s'`      | `1999-11-11 11:11:11`             |
| *(internal)*               |                           |                                   |
+----------------------------+---------------------------+-----------------------------------+
| **Unix timestamps**        | :php:`'U'`                | `942315071`                       |
| *(internal)*               |                           |                                   |
+----------------------------+---------------------------+-----------------------------------+


The ISO8601 variants and :php:`\DateTimeInterface` objects are intended to be
used as API. The SQL flavored variant and unix timestamps are mainly targeted
for copy and import operations of native datetime and unix timestamp database
fields and are considered internal API.


..  code-block:: php
    :caption: Passing datetime data via DataHandler PHP API

    $myDate = new \DateTime('yesterday');
    $this->dataHandler->start([
        'tx_myextension_mytable' => [
            'NEW-1' => [
                'pid' => 2,
                // Format as LOCALTIME
                'mydatefield_1' => $myDate->format('Y-m-dTH:i:s'),
                // Format with timezone information
                // (offsets will be normalized to persistence timezone format,
                // UTC for integer fields, LOCALTIME for native DATETIME fields)
                'mydatefield_2' => $myDate->format('c'),
                // Pass \DateTimeInterface objects directly
                'mydatefield_3' => $myDate,
            ],
        ],
    ]);


Impact
======

TYPO3 now provides accurate and consistent handling of ISO8601 dates,
eliminating previous issues related to timezone interpretation and LOCALTIME
representation.

..  index:: Database, PHP-API, ext:core
