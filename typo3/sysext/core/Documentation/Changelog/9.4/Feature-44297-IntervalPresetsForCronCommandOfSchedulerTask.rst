.. include:: ../../Includes.txt

=====================================================================
Feature: #44297 - Interval presets for cron command of scheduler task
=====================================================================

See :issue:`44297`

Description
===========

To support administrators creating scheduler tasks, presets have been added to the frequency field.

The default presets are:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions'] = [
      '0 9,15 * * 1-5' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example1',
      '0 */2 * * *' =>  'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example2',
      '*/20 * * * *' =>  'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example3',
      '0 7 * * 2' =>  'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example4',
   ];

.. index:: Backend, ext:scheduler
