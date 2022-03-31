
.. include:: /Includes.rst.txt

==============================================================================
Important: #67401 - Dependency Injection is now done before initializeObject()
==============================================================================

See :issue:`67401`

Description
===========

Formerly `initializeObject()` was called before the dependencies were injected when retrieving an Extbase Domain
Model. This behavior didn't match either the documentation_ nor the behavior when using the `ObjectManager`.

With TYPO3 CMS 7.3 this has been changed, dependency injection using `@inject` annotations and `inject*()` methods
is now performed **before** calling `initializeObject()` when retrieving Domain Models.

This may have impact on extensions that are relying on the reversed call order. In these cases adjustments are
required to take into account that the injected objects are available.

.. _documentation: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/DependencyInjection/Index.html


.. index:: PHP-API, ext:extbase
