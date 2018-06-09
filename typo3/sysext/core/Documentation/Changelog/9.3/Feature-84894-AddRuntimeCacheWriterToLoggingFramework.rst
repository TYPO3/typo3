.. include:: ../../Includes.txt

=============================================================
Feature: #84894 - Add RuntimeCacheWriter to Logging Framework
=============================================================

See :issue:`84894`

Description
===========

A new log writer has been added with the ability to write log entries to the TYPO3 runtime cache.
The writer can be configured via the normal logging framework writer configuration.
It logs the full log record in the database and uses the given component as cache tag.

Usage
======

Example usage ::

   $GLOBALS['TYPO3_CONF_VARS']['LOG']['mycomponent'] = [
       'writerConfiguration' => [
           \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
               \TYPO3\CMS\Core\Log\Writer\RuntimeCacheWriter::class => [],
           ],
       ],
   ];

.. index:: Backend, Frontend, PHP-API, ext:core
