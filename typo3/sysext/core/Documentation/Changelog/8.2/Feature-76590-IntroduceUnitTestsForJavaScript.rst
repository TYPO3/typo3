
.. include:: ../../Includes.txt

====================================================
Feature: #76590 - Introduce UnitTests for JavaScript
====================================================

See :issue:`76590`

Description
===========

The core uses karma as test runner and jasmine as testing framework for JavaScript UnitTests.
The tests will be run on travis with PhantomJS.
Locally other browsers like Chrome, Firefox, Safari or IE can be used as well.

To run the UnitTests on a local system the following steps are necessary:

1. Install dependencies

.. code-block:: bash

   cd Build
   npm install
   cd ..

2. Run the tests from your terminal

.. code-block:: bash

   # Execute the tests only once
   ./Build/node_modules/karma/bin/karma start typo3/sysext/core/Build/Configuration/JSUnit/karma.conf.js --single-run

   # Execute the tests for every change (file watcher mode)
   ./Build/node_modules/karma/bin/karma start typo3/sysext/core/Build/Configuration/JSUnit/karma.conf.js

   # Execute the tests for different browser
   ./Build/node_modules/karma/bin/karma start typo3/sysext/core/Build/Configuration/JSUnit/karma.conf.js --single-run --browsers Chrome,Safari,Firefox


Test-Files
==========

Any test file must be located in extension folder `typo3/sysext/<EXTKEY>/Tests/JavaScript/`
The filename must end with Test.js, e.g. `GridEditorTest.js`
Each test file must be implemented as AMD module, must use strict mode and has to use :javascript:`describe` with module name as outer wrap for each test.
The following code block shows a good example:

.. code-block:: javascript

   define(['jquery', 'TYPO3/CMS/Backend/AnyModule'], function($, AnyModule) {
      'use strict';
      // first and outer wrap describe the test class name
      describe('TYPO3/CMS/Backend/AnyModuleTest:', function() {
         // second wrap describe the method to test
         describe('tests for fooAction', function() {
            // the first parameter of each 'it' method describe the test-case.
            it('works for parameter a and b', function() {});
         });
         describe('tests for barAction', function() {
            it('works for parameter a and b', function() {});
         });
      }
   }

Please take a look at the existing test files and read the jasmine documentation for further information.

DataProvider for tests
----------------------

For testing a set of values, the core implement a kind of DataProvider. To use the DataProvider you have to use the function :javascript:`using`.
Please take a look at `FormEngineValidationTest.js` for an example.

.. index:: JavaScript
