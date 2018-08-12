.. include:: ../../Includes.txt

============================================================
Deprecation: #85822 - Deprecate PageGenerator::renderContent
============================================================

See :issue:`85822`

Description
===========

The PSR-15 RequestHandler should be responsible for compiling content, avoiding
a call to a static method which uses global objects again, which are available
already in the RequestHandler.

Therefore this logic is moved into RequestHandler and :php:`PageGenerator::renderContent` is marked as deprecated.


Impact
======

Calling the :php:`PageGenerator::renderContent` method will trigger a deprecation message.


Affected Installations
======================

Any TYPO3 installation with a custom extension calling the static method above.


Migration
=========

Move the render logic to your own extension.

.. index:: Frontend, FullyScanned, ext:frontend