.. include:: /Includes.rst.txt

=========================================
Feature: #86051 - Show extensions via CLI
=========================================

See :issue:`86051`

Description
===========

A new command :bash:`extension:list` is added, which can be executed via Command Line
:bash:`typo3/sysext/core/bin/typo3 extension:list`.

This command shows all currently installed (= active) extensions. The option :bash:`--all`
also includes all inactive extensions. If the list of inactive extensions should
be shown, the command :bash:`--inactive` will show only the extensions available for installation.

Additional description of the extensions can be shown by :bash:`--verbose` / :bash:`-v`.


Impact
======

In order to show which extensions can be uninstalled or installed via CLI, the new command
is a good companion for the existing commands :bash:`extension:activate` and :bash:`extension:deactivate`.

.. index:: CLI, ext:core
