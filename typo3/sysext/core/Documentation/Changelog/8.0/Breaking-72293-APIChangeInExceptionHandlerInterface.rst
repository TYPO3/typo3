
.. include:: /Includes.rst.txt

==========================================================
Breaking: #72293 - API change in ExceptionHandlerInterface
==========================================================

See :issue:`72293`

Description
===========

The class `\Throwable` was added in PHP7 as new parent of `\Exceptions`. So we
were in need to remove the type hint in :issue:`72117`, which we now read as `\Throwable`
instead of `\Exception` as we only support PHP 7.0 or newer.
See https://php.net/manual/en/migration70.incompatible.php


Impact
======

A fatal error will be thrown if you use own ExceptionHandlers implementing
`TYPO3\CMS\Core\Error\ExceptionHandlerInterface` "Fatal error: Declaration of ...
must be compatible with ..."


Affected Installations
======================

Installations which use an own ExceptionHandler implementing TYPO3s
`ExceptionHandlerInterface`.


Migration
=========

Add `\Throwable` as type hinting in your implementation of
`ExceptionHandlerInterface`. Check the API/type hinting of the method where you
process the exception.

.. index:: PHP-API
