
.. include:: ../../Includes.txt

=================================================================================
Breaking: #72931 - SearchFormController::pi_list_browseresults() has been renamed
=================================================================================

See :issue:`72931`

Description
===========

In order to make Indexed Search pi-based plugin PHP7 compatible, the `SearchFormController::pi_list_browseresults()`
method has been renamed to `SearchFormController::renderPagination()`.
Parameter types, order and count have been preserved. However the methods visibility has been changed to protected.


Impact
======

Call to old method name will result in fatal error "Call to undefined method".


Affected Installations
======================

Any installation of TYPO3 7.6 or TYPO3 8 where `SearchFormController` is overloaded (XCLASSed) and the new class contains
a call to the old method name and any code that called the public method from outside the class.


Migration
=========

Rename :php:`pi_list_browseresults()` to :php:`renderPagination()`.

Calling the method from outside the class is no longer possible.

.. index:: PHP-API, Frontend, ext:indexed_search
