..  include:: /Includes.rst.txt

..  _breaking-105549-1742214899:

=======================================================================
Breaking: #105549 - Improved ISO8601 Date Handling in TYPO3 DataHandler
=======================================================================

See :issue:`105549`

Description
===========

The :php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` PHP API has been
extended to support both qualified and unqualified ISO8601 date formats, in
order to correctly process supplied timezone offsets when provided.

*   *Qualified ISO8601:* Includes an explicit timezone offset (for example,
    1999-12-11T10:09:00+01:00 or 1999-12-11T10:09:00Z)
*   *Unqualified ISO8601:* Omits timezone offsets, representing *LOCALTIME*
    (for example, 1999-12-11T10:09:00)

Previously, TYPO3 incorrectly used qualified ISO8601 with `Z` (`UTC+00:00`) to
denote *LOCALTIME* and applied the server's timezone offset, which led to
misinterpretations when another timezone offset was provided, or when real
UTC-0 was intended instead of LOCALTIME. Now, timezone offsets are accurately
applied if supplied, and are based on server localtime if omitted.

TYPO3 will use unqualified ISO8601 dates internally for communication between
FormEngine and the DataHandler API, ensuring timezone offsets are correctly
processed – instead of being shifted – when supplied to the
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` API.

In essence, this means that existing workarounds for previously applied
timezone offsets should be reviewed and removed.

Impact
======

TYPO3 now provides accurate and consistent handling of ISO8601 dates,
eliminating previous issues related to timezone interpretation and LOCALTIME
representation.

Affected installations
======================

Installations with custom TYPO3 extensions that invoke the
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` API with data for
`type="datetime"` fields are affected.

Migration
=========

Qualified ISO8601 dates with intended timezone offsets and
:php:`\DateTimeInterface` objects can now be passed directly to the
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` without requiring manual
timezone adjustments.

An example of a previous workaround that added timezone offsets for the
DataHandler:

..  code-block:: php
    :caption: Passing datetime data via DataHandler PHP API (before)

    $myDate = new \DateTime('yesterday');
    $this->dataHandler->start([
        'tx_myextension_mytable' => [
            'NEW-1' => [
                'pid' => 2,
                // A previous workaround added the localtime offset to supplied
                // dates, as it was subtracted by the DataHandler persistence
                // layer
                'mydatefield_1' => gmdate('c', $myDate->getTimestamp() + (int)date('Z')),
            ],
        ],
    ]);

Previous timezone-shifting workarounds can be removed and replaced with more
intuitive formats.

..  code-block:: php
    :caption: Passing datetime data via DataHandler PHP API (after)

    $myDate = new \DateTime('yesterday');
    $this->dataHandler->start([
        'tx_myextension_mytable' => [
            'NEW-1' => [
                'pid' => 2,
                // Pass \DateTimeInterface object directly
                'mydatefield_1' => $myDate,
                // Format as LOCALTIME
                'mydatefield_2' => $myDate->format('Y-m-d\TH:i:s'),
                // Format with timezone information
                // (offsets will be normalized to the persistence timezone
                // format: UTC for integer fields, LOCALTIME for native
                // DATETIME fields)
                'mydatefield_3' => $myDate->format('c'),
            ],
        ],
    ]);

..  index:: Database, PHP-API, NotScanned, ext:core
