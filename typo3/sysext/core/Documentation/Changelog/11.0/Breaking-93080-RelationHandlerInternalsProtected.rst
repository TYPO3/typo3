.. include:: /Includes.rst.txt

======================================================
Breaking: #93080 - RelationHandler internals protected
======================================================

See :issue:`93080`

Description
===========

Various properties and methods of class
:php:`TYPO3\CMS\Core\Database\RelationHandler` have been set to protected:

* :php:`$firstTable` - internal
* :php:`$secondTable` - internal
* :php:`$MM_is_foreign` - internal
* :php:`$MM_oppositeField` - internal
* :php:`$MM_oppositeTable` - internal
* :php:`$MM_oppositeFieldConf` - internal
* :php:`$MM_isMultiTableRelationship` - internal
* :php:`$currentTable` - internal
* :php:`$MM_match_fields` - internal
* :php:`$MM_hasUidField` - internal
* :php:`$MM_insert_fields` - internal
* :php:`$MM_table_where` - internal


* :php:`getWorkspaceId()` - internal
* :php:`setUpdateReferenceIndex()` - still public but deprecated, logs deprecation on use.
* :php:`readList()` - use class state after calling start()
* :php:`sortList()` - use class state after calling start()
* :php:`readMM()` - use class state after calling start()
* :php:`readForeignField()` - use class state after calling start()
* :php:`updateRefIndex()` - internal
* :php:`isOnSymmetricSide()` - internal


Impact
======

Calling above properties or methods will raise a PHP fatal error.


Affected Installations
======================

It is quite unlikely many extensions are affected by this API change.
The extension scanner finds affected extensions as weak matches.


Migration
=========

Above properties and methods are considered internal, there shouldn't be any
need to call them. Instances with extensions using those should be refactored
to for instance call :php:`start()` instead of an additional call to :php:`readList()`.


.. index:: PHP-API, FullyScanned, ext:core
