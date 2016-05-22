========================================================
Breaking: #76259 - Value passed to hook getTable changed
========================================================

Description
===========

The value for ``$additionalWhere`` passed to the method :php:``getDBlistQuery``
as part of the hook ``getTable`` in :php:``\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList``
has been changed and no longer includes the leading ``AND``.


Impact
======

3rd Party extensions implementing the hook method need to ensure the leading ``AND`` is no
longer expected to be present. The leading ``AND`` should also not be returned anymore.


Affected Installations
======================


Installations using 3rd party extensions that implement the hook method.


Migration
=========

Migrate the hook method not to expect or prepend the leading ``AND``.
