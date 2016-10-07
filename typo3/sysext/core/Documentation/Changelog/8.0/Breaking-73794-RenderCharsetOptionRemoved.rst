
.. include:: ../../Includes.txt

===============================================
Breaking: #73794 - renderCharset option removed
===============================================

See :issue:`73794`

Description
===========

The TypoScript option `config.renderCharset` which was used as character set for internal conversion within a frontend
request has been removed, as internal conversions are always set to UTF-8 now.
The property `$TSFE->renderCharset` is now always set to `utf-8`, and is not used within the TYPO3 Core anymore.

Please note that it is still possible to define the character set of the returned content via `config.metaCharset`.


Impact
======

Using TYPO3 with a different datasource which is not UTF-8 (e.g. a database with latin1) might return unexpected results.
Non-UTF-8 databases work as expected if the connection charset is still UTF-8, as the DBMS takes
care of converting the data to UTF-8.


Affected Installations
======================

Any installation that uses the `config.renderCharset` TypoScript option with a different value than `utf-8`.


Migration
=========

Remove the TypoScript option from any TypoScript settings. If data sources (files, database input) are used that are
different than UTF-8, a manual conversion via the CharsetConverter PHP class is needed.

.. index:: TypoScript, Frontend
