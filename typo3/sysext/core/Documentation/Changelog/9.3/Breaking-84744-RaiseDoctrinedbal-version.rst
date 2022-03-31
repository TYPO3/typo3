.. include:: /Includes.rst.txt

==============================================
Breaking: #84744 - Raise doctrine/dbal-version
==============================================

See :issue:`84744`

Description
===========

The upgrade doctrine/dbal to 2.7.1 came with a potentially breaking change that makes
it necessary to adjust the format of DateInterval fields.

Those fields can now be negative as well and thus are expected to begin with either
`+` or `-`.


Impact
======

Users not having prefixes in DateInterval fields will be greeted by a :php:`ConversionException`.

We do not expect many (if any) TYPO3 users to be affected by this change.

You can read up on the topic at https://github.com/doctrine/dbal/pull/2579 and https://github.com/doctrine/dbal/releases/tag/v2.7.0.


Affected Installations
======================

Users with existing DateInterval fields that don't yet use the proper field prefix.


Migration
=========

Manually provide the proper prefixes in your DateInterval fields.

If you didn't use negative DateIntervals yet, you can safely prefix your data with `+`.

.. index:: Database, PHP-API, NotScanned
