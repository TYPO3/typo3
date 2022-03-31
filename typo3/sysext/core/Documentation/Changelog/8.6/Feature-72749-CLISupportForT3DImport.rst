.. include:: /Includes.rst.txt

============================================
Feature: #72749 - CLI support for T3D import
============================================

See :issue:`72749`


Description
===========

EXT:impexp now allows to import data files (T3D or XML) via the command line interface through a Symfony
Command.


Impact
======

The command line allows the following options:

.. code-block:: text

    Imports a T3D file into a page.

    USAGE:
     ./typo3/sysext/core/bin/typo3 impexp:import [<options>] <file> <pageId>

    ARGUMENTS:
      --file      The path / filename to import (.t3d or .xml), the EXT: syntax can be used as well
      --pageId    The page id where the page should be started from, defaults to "0" if not set

    OPTIONS:
      --updateRecords   Force updating existing records
      --ignorePid       Don't correct page ids of updated records
      --enableLog       log all database action

.. index:: CLI, ext:impexp
