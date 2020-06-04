
.. include:: ../../Includes.txt

==========================================================
Breaking: #72117 - API change in ExceptionHandlerInterface
==========================================================

See :issue:`72117`

Description
===========

The class \Throwable was added in PHP7 as new parent of \Exceptions. This leads to the issue that
ExceptionHandlers need to change the API of their exception handling method. To support PHP 5.5, 5.6 and 7.0
we need to remove the type hint. It will later be set to \Throwable if we only support PHP 7.0 and newer.
See https://php.net/manual/en/migration70.incompatible.php


Impact
======

A fatal error will be thrown if you use own ExceptionHandlers implementing
TYPO3\CMS\Core\Error\ExceptionHandlerInterface "Fatal error: Declaration of ... must be compatible with ..."


Affected Installations
======================

Installations which use an own ExceptionHandler implementing TYPO3s ExceptionHandlerInterface.


Migration
=========

Remove the type hinting in your implementation of ExceptionHandlerInterface. If you switch to PHP 7 you may
also get instances from \Throwable, so check the API/type hinting of the function were you process the exception.


.. index:: PHP-API
