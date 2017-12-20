
.. include:: ../../Includes.txt

============================================================================
Breaking: #72421 - Removed deprecated code from database and query functions
============================================================================

See :issue:`72421`

Description
===========

Removed deprecated code from database and query functions

The following methods have been removed:

`DatabaseConnection::splitGroupOrderLimit`
`QueryGenerator::formatQ`
`QueryGenerator::JSbottom`
`ReferenceIndex::error`
`RelationHandler::convertPosNeg`

The following properties have been removed:

`QueryGenerator::$extJSCODE`
`ReferenceIndex::$errorLog`

The option to set soft reference parsers has been removed.

Impact
======

Using the one of the methods or properties above will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to the methods or classes above.

.. index:: PHP-API, Database
