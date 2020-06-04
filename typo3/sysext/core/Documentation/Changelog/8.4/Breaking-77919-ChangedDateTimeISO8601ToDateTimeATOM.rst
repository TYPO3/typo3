.. include:: ../../Includes.txt

==============================================================
Breaking: #77919 - Changed DateTime::ISO8601 to DateTime::ATOM
==============================================================

See :issue:`77919`

Description
===========

The format `DateTime::ISO8601` is not compatible with ISO-8601, but is left this way for
backward compatibility reasons. The constant `DateTime::ATOM` or `DATE_ATOM` is used
instead when rendering JsonViews via Extbase.

See https://php.net/manual/en/class.datetime.php#datetime.constants.iso8601 for more information

.. index:: PHP-API
