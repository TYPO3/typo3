
.. include:: ../../Includes.txt

==============================================================================
Important: #67401 - Dependency Injection is now done before initializeObject()
==============================================================================

See :issue:`67401`

Description
===========

Formerly `initializeObject()` was called before the dependencies were injected when retrieving an Extbase Domain
Model. This behavior didn't match either the documentation nor the behavior when using the `ObjectManager`.

With TYPO3 CMS 7.3 this has been changed, dependency injection using `@inject` annotations and `inject*()` methods
is now performed **before** calling `initializeObject()` when retrieving Domain Models.

This may have impact on extensions that are relying on the reversed call order. In these cases adjustments are
required to take into account that the injected objects are available.

.. _documentation: http://wiki.typo3.org/Dependency_Injection#initializeObject.28.29_as_object_lifecycle_method


.. index:: PHP-API, ext:extbase
