.. include:: /Includes.rst.txt

==============================================================================
Breaking: #88527 - Overriding custom values in User Authentication derivatives
==============================================================================

See :issue:`88527`

Description
===========

Due to some restructuring of :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication` and its direct sub-classes
:php:`TYPO3\CMS\Core\Authentication\BackendUserAuthentication` (a.k.a. :php:`$BE_USER`) and :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication`,
various settings are now directly initiated and set in the respective constructor of each PHP class.

Following this, the properties :php:`sessionTimeout`, :php:`gc_time` and :php:`sessionDataLifetime` are set already
when the constructor is called. Before this was the case when :php:`start()` was called.

In addition, the property :php:`loginType` must be set for any subclass on instantiation. Previously
this was possible to be set just before :php:`start()` was called.

The previous behavior allowed to override certain parameters to be evaluated just before :php:`start()`.


Impact
======

Setting any global variables between the constructor method and :php:`start()` will have no effect, as
this is transferred and evaluated at the public properties already when the constructor is called.

Subclassing :php:`AbstractUserAuthentication` without setting :php:`loginType` will trigger an exception
on instantiation.


Affected Installations
======================

Any TYPO3 installation where a custom UserAuthentication instantiation or sub-class is in place, and the setting
order was changed between calling the constructor and the method :php:`start()`, which is considered a very rare case.


Migration
=========

Consider using a proper subclass and a custom constructor method, or set all properties properly before
the constructor is called (default values of class members).

.. index:: PHP-API, NotScanned
