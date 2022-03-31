.. include:: /Includes.rst.txt

=====================================================================
Feature: #79462 - Introduce scheduler task to execute console command
=====================================================================

See :issue:`79462`

Description
===========

A scheduler task has been introduced to execute (symfony) console commands. In the past this was
already possible for Extbase command controller commands but as the core migrates all command
controllers to native symfony commands, the scheduler needs to be able to execute them.


Impact
======

Symfony commands can be executed via the scheduler which provides a migration path away from
command controllers to native symfony commands.

.. index:: CLI, ext:scheduler
