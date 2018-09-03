.. include:: ../../Includes.txt

=======================================================================
Feature: #85236 - Infix option to default log file names for FileWriter
=======================================================================

See :issue:`85236`

Description
===========

A new option :php:`logFileInfix` for the :php:`FileWriter` has been introduced.
This allows to set a different name for the log file that is created by the :php:`FileWriter`
without having to define a full path to the file.

The example configuration will use the log file named :file:`typo3\_special\_\<hash>.log`
for any log message stemming from a class from the :php:`Vendor\ExtName` namespace.

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['LOG']['Vendor']['ExtName']['writerConfiguration'] = [
     \TYPO3\CMS\Core\Log\LogLevel::INFO => [
       \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
         'logFileInfix' => 'special'
       ]
     ]
   ];


Impact
======

The behaviour for existing :php:`FileWriter` configurations is not changed.

.. index:: LocalConfiguration, ext:core
