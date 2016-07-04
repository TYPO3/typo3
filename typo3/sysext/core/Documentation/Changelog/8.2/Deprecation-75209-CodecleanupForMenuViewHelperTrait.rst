==========================================================
Deprecation: #75209 - Code cleanup for MenuViewHelperTrait
==========================================================

Description
===========

The ``MenuViewHelperTrait`` has been marked as deprecated.
All methods of the Trait have been implemented in a new ``AbstractMenuViewHelper`` class.


Impact
======

Using the methods of the ``MenuViewHelperTrait`` will trigger a deprecation log entry.


Affected Installations
======================

Instances with custom extensions that use the ``MenuViewHelperTrait``.


Migration
=========

Extend the new ``AbstractMenuViewHelper`` which contains all methods instead of using the trait.
