.. include:: ../../Includes.txt

==============================================================
Feature: #81601 - Add possibility to write tests in typeScript
==============================================================

See :issue:`81601`

Description
===========

The core is using TypeScript as language already - now writing unit tests in TypeScript is also possible. 
The `tsconfig.json` now contains both JavaScript and TypeScript module path mappings, the Grunt file will
take care of adjusting the paths for TypeScript as well.


Impact
======

Tests can now be written directly in TypeScript, making it easier to test TypeScript components, having auto-completion and IDE support.
Tests written in TypeScript will be converted to JavaScript via the grunt task `scripts` and executed via the existing Karma configuration.

.. index:: Backend, JavaScript