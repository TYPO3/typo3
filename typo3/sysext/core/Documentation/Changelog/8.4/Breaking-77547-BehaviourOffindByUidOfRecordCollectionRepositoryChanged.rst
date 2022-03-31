.. include:: /Includes.rst.txt

=============================================================================
Breaking: #77547 - Behaviour of RecordCollectionRepository::findByUid changed
=============================================================================

See :issue:`77547`

Description
===========

The behaviour of :php:`RecordCollectionRepository::findByUid()` has changed.
When TYPO3 is in Frontend mode, the method will now respect the configured enable fields.
Instead of returning an object that is supposed to be disabled due to being hidden or
having a start date in the future, or an end date in the past, it will now return :php:`null`.

Impact
======

Using the `RecordCollectionRepository` expecting to fetch disabled records while TYPO3 is
in Frontend mode will not yield the expected result.


Affected Installations
======================

Any installation that uses the :typoscript:`FILES` cObject, e.g. via the `uploads` CType, as well as
any installation with a 3rd party extension that uses the named method.

Migration
=========

If the previous behaviour is wanted, the TCA of the used collection table needs to
be overridden to not use the configured enable columns.

.. index:: PHP-API, Frontend
