..  include:: /Includes.rst.txt

..  _important-106467-1743452295:

==================================================================================
Important: #106467 - Align Extbase DateTime handling to FormEngine and DataHandler
==================================================================================

See :issue:`106467`

Description
===========

Extbase handling of :php:`\DateTimeInterface` domain model properties has been
aligned with the persistence and database value interpretation behavior of the
TYPO3 Core Engine (FormEngine and DataHandler).

Since this change addresses bugs and value interpretation differences that
existed since the introduction of Extbase and there are many workarounds in use,
a feature flag :php:`'extbase.consistentDateTimeHandling'` is introduced which
allows to enable the new behavior.

Existing TYPO3 v13 instances will use the old behavior by default and are
advised to enable the new feature flag via InstallTool or via:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['extbase.consistentDateTimeHandling'] = true;

TYPO3 v14 (and new v13 instances) enable the consistent DateTime handling
by default, but the feature can still be disabled manually, if needed for the
time being.

There are four different behavioural changes that will be activated and are
explained in the following sections.


Align persistence to database to match DataHandler algorithm
------------------------------------------------------------

Use the DataHandler algorithm for the mapping of DateTime objects
to database values.
This causes non-localtime timezone offsets in :php:`\DateTime` objects
(e.g. supplied by a frontend datepicker) to be respected for native
datetime fields, like already done for integer based datetime fields.
Note that the offset is not stored as-is, but mapped to PHP localtime,
but the offset is no longer cropped off.

That means there is no need to force the server timezone on :php:`\DateTime`
objects before persisting an extbase model, since all dates will be
normalized to localtime (for native datetime fields) or UTC (for interger based
datetime fields) within the persistence layer.

Before:

..  code-block:: php

    public function setDatetime(\DateTime $datetime): void
    {
        // Force local datetime zone in order to avoid
        // cropping non localtime offsets during persistence
        $datetime->setTimezone(
            new\DateTimeZone(date_default_timezone_get())
        );
        $this->datetime = $datetime;
    }

After:

..  code-block:: php

    public function setDatetime(\DateTime $datetime): void
    {
        // No timezone enforcement needed, persistence layer will
        // persist correct point in time (UTC for integer, LOCALTIME for native
        // fields)
        $this->datetime = $datetime;
    }


Map date and datetime with named timezone instead of offset
-----------------------------------------------------------

Extbase DataMapper converts dates of integer based database fields to
:php:`\DateTime` instances that use the current server date timezone
(e.g., Europe/Berlin) and not just the time offset of the current server
timezone (e.g., +01:00).

This prevents timezone shifts when modifying the resulting :php:`\DateTime`
object across daylight saving time boundaries.

Previous workarounds that explicitly added the server timezone for properties
can be removed:

Before:

..  code-block:: php

    public function getDatetime(): ?\DateTime
    {
        // object(DateTimeZone)#1 (2) {
        //   ["timezone_type"]=>
        //   int(1)
        //   ["timezone"]=>
        //   string(6) "+01:00"
        // }
        var_dump($this->datetime);

        $this->datetime->setTimezone(
            new\DateTimeZone(date_default_timezone_get())
        );

        return $this->datetime;
    }

After:

..  code-block:: php

    public function getDatetime(): ?\DateTime
    {
        // object(DateTimeZone)#2 (2) {
        //   ["timezone_type"]=>
        //   int(3)
        //   ["timezone"]=>
        //   string(13) "Europe/Berlin"
        // }
        var_dump($this->datetime);

        // No explicit timezone needed for a proper named timezone
        return $this->datetime;
    }


Interpret integer based time fields as seconds without timezone offset
----------------------------------------------------------------------

The Extbase DataMapper will interpret `format=time` or `format=timesec`
datetime fields as seconds without timezone offset, like FormEngine and
DataHandler do. The database value is no longer considered as a UNIX timestamp,
but as offset from midnight mapped on 1970-01-01T00:00:00 in PHP localtime.

For european timezones where Central Europe Time (CET) was active on 1970-01-01
that means an integer field value like `7200` (=`02:00`) will be mapped to
`1970-01-01T02:00:00+01:00` instead of `1970-01-01T02:00:00+00:00` and the
:php:`DateTime::$timezone` property of the :php:`DateTime` object will be set to
the named timezone that is configured in PHP ini setting `date.timezone` instead
of UTC.

That means the datetime value can be combined with explicit dates and is always
using the server timezone.


Interpret 00:00:00 as non empty time value for nullable time properties
-----------------------------------------------------------------------

Nullable `format=time`, `format=timesec` or `dbType=time` fields can now
use 00:00:00 to represent midnight (this value has been used in
non-nullable fields to represent an empty value). The DateTime mapper now
understands this value instead of misinterpreting it as an empty value.

This behaviour could not be worked around before, that means existing
implementations do not need to change or remove workarounds, but can basically
support 00:00 as a value time field now.


Construct `format=time` and `dbType=time` properties based on 1970-01-01
------------------------------------------------------------------------

DateTime objects that map to native TIME fields or integer based fields
configured with `format=time` are now initialized with 1970-01-01 as day-part
instead of the current day which results in consistent mapped values independent
from the day where the mapping is performed.

Before:

..  code-block:: php

    public function getDatetime(): ?\DateTime
    {
        //object(DateTime)#2 (3) {
        //  ["date"]=>
        //  string(26) "2025-04-11 11:44:00.000000"
        //  ["timezone_type"]=>
        //  int(1)
        //  ["timezone"]=>
        //  string(6) "+02:00"
        //}

        var_dump($this->datetime);

        return $this->datetime;
    }

After:

..  code-block:: php

    public function getDatetime(): ?\DateTime
    {
        //object(DateTime)#2 (3) {
        //  ["date"]=>
        //  string(26) "1970-01-01 11:44:00.000000"
        //  ["timezone_type"]=>
        //  int(3)
        //  ["timezone"]=>
        //  string(13) "Europe/Berlin"
        //}
        var_dump($this->datetime);

        return $this->datetime;
    }


..  index:: Database, PHP-API, ext:extbase
