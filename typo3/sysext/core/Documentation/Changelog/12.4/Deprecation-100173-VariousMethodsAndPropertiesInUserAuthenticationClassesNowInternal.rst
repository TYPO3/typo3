.. include:: /Includes.rst.txt

.. _deprecation-100173-1680696124:

================================================================================================
Deprecation: #100173 - Various methods and properties in UserAuthentication classes now internal
================================================================================================

See :issue:`100173`

Description
===========

Various methods and properties within the main classes regarding frontend
user and backend user (:php:`$GLOBALS[BE_USER]`) authentication handling
have been either marked as internal or have been deprecated for usage
outside of the classes.

This is due to the further refactorings and decoupling work, as subclasses of
:php:`AbstractUserAuthentication` deal with many more functionality nowadays,
and therefore have been moved to service classes. The tight coupling of these
classes, for example, the database fields, or login form field names are now marked as
internal, as these properties should not be modified from the outside scope.

Instead, functionality like :ref:`PSR-14 events <t3coreapi:EventDispatcher>` or
:ref:`Authentication Services <t3coreapi:authentication>` should influence the
authentication and authorization workflow.

The following properties and methods are now marked as internal in all
user authentication related classes (extending
:php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication`):

* :php:`lastLogin_column`
* :php:`formfield_uname`
* :php:`formfield_uident`
* :php:`formfield_status`
* :php:`loginSessionStarted`
* :php:`dontSetCookie`
* :php:`isSetSessionCookie()`
* :php:`isRefreshTimeBasedCookie()`
* :php:`removeCookie()`
* :php:`isCookieSet()`
* :php:`unpack_uc()`
* :php:`appendCookieToResponse()`

Additionally, the following properties of the
:php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication`
implementation are marked as internal:

* :php:`formfield_permanent`
* :php:`is_permanent`


Impact
======

The affected properties and methods have been marked as `@internal` and set to
:php:`protected`. With an additional trait, it is still possible to access them
in TYPO3 v12. In case third-party extensions call them, a PHP deprecation
warning is thrown.


Affected installations
======================

TYPO3 installations with custom extensions accessing the properties or methods.
The extension scanner reports corresponding places.


Migration
=========

Depending on the specific requirements, it is recommended to use
:ref:`PSR-14 events <t3coreapi:EventDispatcher>` or
:ref:`authentication services <t3coreapi:authentication>` to modify behaviour
of the authentication classes.

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:core
