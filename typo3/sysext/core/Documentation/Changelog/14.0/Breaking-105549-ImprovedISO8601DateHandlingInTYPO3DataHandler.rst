..  include:: /Includes.rst.txt

..  _breaking-105549-1742214899:

=======================================================================
Breaking: #105549 - Improved ISO8601 Date Handling in TYPO3 DataHandler
=======================================================================

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

Previously, TYPO3 incorrectly used qualified ISO8601 with `Z` (`UTC+00:00`) to
denote "LOCALTIME" and applied the server's timezone offset, leading to
misinterpretations when any other timezone offset was given, or if real UTC-0
was intended instead of LOCALTIME. Now, timezone offsets are accurately applied
if supplied and based on server localtime if omitted.

TYPO3 will internally use unqualified ISO8601 dates for communication between
FormEngine and DataHandler API from now on, allowing to correctly process
timezone offsets – instead of shifting them – if supplied to the
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` PHP API.

In essence this means that existing workarounds for the previously applied
timezone offsets need to be revised and removed.


Impact
======

TYPO3 now provides accurate and consistent handling of ISO8601 dates,
eliminating previous issues related to timezone interpretation and LOCALTIME
representation.


Affected installations
======================

Installations with custom TYPO3 extensions that invoke
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` API with data for
`type="datetime"` fields.


Migration
=========

Qualified ISO8601 dates with intended timezone offsets, and `\DateTimeInterface`
objects can now be passed directly to the
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` without requiring manual
timezone adjustments.

Example for a previous workaround timezone offsets for DataHandler:

..  code-block:: php
    :caption: Passing datetime data via DataHandler PHP API

    $myDate = new \DateTime('yesterday');
    $this->dataHandler->start([
        'tx_myextension_mytable' => [
            'NEW-1' => [
                'pid' => 2,
                // A previous workaround add localtime offset to supplied dates,
                //  as it was subtracted by DataHandler persistence layer
                'mydatefield_1' => gmdate('c', $myDate->getTimestamp() + (int)date('Z')),
            ],
        ],
    ]);

Previous timezone shifting workarounds can be removed and replaced by intuitive
formats.

..  code-block:: php
    :caption: Passing datetime data via DataHandler PHP API

    $myDate = new \DateTime('yesterday');
    $this->dataHandler->start([
        'tx_myextension_mytable' => [
            'NEW-1' => [
                'pid' => 2,
                // pass \DateTimeInterface object directly
                'mydatefield_1' => $myDate,
                // format as LOCALTIME
                'mydatefield_2' => $myDate->format('Y-m-dTH:i:s'),
                // format with timezone information
                // (offsets will be normalized to persistence timezone format,
                // UTC for integer fields, LOCALTIME for native DATETIME fields)
                'mydatefield_3' => $myDate->format('c'),
            ],
        ],
    ]);


..  index:: Database, PHP-API, NotScanned, ext:core
