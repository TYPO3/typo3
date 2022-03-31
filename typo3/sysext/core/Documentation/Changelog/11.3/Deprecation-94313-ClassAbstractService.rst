.. include:: /Includes.rst.txt

===========================================
Deprecation: #94313 - AbstractService class
===========================================

See :issue:`94313`

Description
===========

The :php:`TYPO3\CMS\Core\Service\AbstractService` class is part of the ancient
:ref:`Service API <t3coreapi:services-developer-service-api>`.
This API did not really prevail, except it's usage for the authentication process.

Since the authentication service related functionality was already
decoupled over the last years, the :php:`AbstractService` got finally
unused in Core since :issue:`88646`. Therefore it has now been marked
as deprecated.

Impact
======

Extending this class does *not* raise a deprecation error level log entry.
The class contains only a `@deprecated` class annotation. Extension classes
can still extend this class in v11 without impact, it will raise a PHP fatal
error in v12, when the class is dropped.


Affected Installations
======================

As mentioned, the Service API never found many usages in casual extensions.
It is therefore pretty unlikely that well maintained projects are affected.
The extension scanner will find any class usages as a strong match.

Migration
=========

Remove any usage of this class in your extension. In case you currently
extend :php:`AbstractService` for use in an authentication service, which
might be the most common scenario, you have to change your service class
to extend from :php:`AbstractAuthenticationService` instead.

In case you currently extend :php:`AbstractService` for another kind of
service, which is rather unlikely, you have to implement the necessary
methods in your service class yourself. Please see `Service Implementation
<https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Services/Developer/ServiceApi.html#service-implementation>`__
for more details about the required methods. However, even better would be to
completely migrate away from the Service API (look for :php:`GeneralUtility::makeInstanceService()`),
since the Core will deprecate these related methods as well.

.. index:: PHP-API, FullyScanned, ext:core
