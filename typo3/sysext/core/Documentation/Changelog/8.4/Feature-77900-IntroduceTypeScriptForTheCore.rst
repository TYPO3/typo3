.. include:: /Includes.rst.txt

===================================================
Feature: #77900 - Introduce TypeScript for the core
===================================================

See :issue:`77900`

Description
===========

The TYPO3 core has introduced TypeScript for the internal JavaScript handling.


Why we use TypeScript in the core?
==================================

TypeScript is a free and open source programming language developed and maintained by Microsoft. It is a strict superset of JavaScript, and adds optional static typing and class-based object-oriented programming to the language.

With TypeScript it is possible to compile JavaScript. TypeScript supports definition files which can contain type information of existing JavaScript libraries.

At the moment the core uses AMD modules for any JavaScript logic in the backend, but what is in 5 years? maybe we want switch to CommonJS, with TypeScript we can recompile all modules with a simple change in a configuration file.

But the main reason to switch to TypeScript is the strict typing and oop structure of the language. We can make use of Interfaces, which is still a missing feature in JavaScript.


Coding Guidelines & Best practice
=================================

:js:`/// <amd-dependency path="x" />` informs the compiler about a non-TS module dependency that needs to be injected in the resulting module's require call.

The amd-dependency has a property name which allows passing an optional name for an amd-dependency: :js:`/// <amd-dependency path="x" name="fooBar" />`

An example:

:js:`/// <amd-dependency path="TYPO3/CMS/Core/Contrib/jquery.minicolors" name="minicolors">`

will be compiled to:

:js:`define(["require", "exports", "TYPO3/CMS/Core/Contrib/jquery.minicolors"], function (require, exports, minicolors) {`

A very simple example is the `EXT:backend/Resources/Private/TypeScript/ColorPicker.ts` file.

TypeScript Linter
=================

The most rules for TypeScript are defined in the rulesets which are checked by the TypeScript Linter.
The core provides a configuration file and grunt tasks to ensure a better code quality. For this reason we introduce a new grunt task, which first run the Linter on each TypeScript file before starting the compiler.
So if your TypeScript does not follow the rules, the task will fail. The idea is to write clean code, else it will not be compiled.

Additional Rules
================

For the core we have defined some additional rules which you should know, because not all of them can be checked by the Linter yet:

#. Always define types and return types, also if TypeScript provides a default type. [checked by Linter]
#. Variable scoping: Prefer :js:`let` instead of :js:`var`. [checked by Linter]
#. Optional properties in interfaces are possible but a bad style, this is not allowed for the core. [NOT checked by Linter]
#. An interface will never extend a class. [NOT checked by Linter]
#. Iterables: Use :js:`for (i of list)` if possible instead of :typoscript:`for (i in list)` [NOT checked by Linter]
#. The :js:`implements` keyword is required for any usage, also if TypeScript does not require it. [NOT checked by Linter]
#. Any class or interface must be declared with "export" to ensure re-use or export an instance of the object for existing code which can't be updated now. [NOT checked by Linter]


Contribution workflow
=====================

.. code-block:: shell

	# Change to Build directory
	cd Build

	# Install dependencies
	npm install

	# Install typings for the core
	grunt typings

	# Check with Linter and compile ts files from sysext/*/Resources/Private/TypeScript/*.ts
	grunt scripts

	# File watcher, the watch task also check for *.ts files
	grunt watch

The grunt task compiles each TypeScript file (*.ts) to a JavaScript file (*.js) and produces an AMD module.


Impact
======

All AMD modules must be ported to TypeScript to ensure a future proof concept of JavaScript handling.
The goal is to migrate all AMD modules to a TypeScript file until CMS 8 LTS is released.

.. index:: Backend, JavaScript
