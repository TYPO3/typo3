===============================================
Feature: #27471 - Allow asterisk for hideTables
===============================================

Description
===========

It is now possible to hide all tables from list view via PageTS-Config.

You want to show only a specific table, you can hide all tables and unhide only the specific one.

.. code-block:: typoscript

   mod.web_list {
      hideTables = *
      table.tx_cal_event.hideTable = 0
   }

