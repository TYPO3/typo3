==============================================================================
Important: #67401 - Dependency Injection is now done before initializeObject()
==============================================================================

Description
===========

Formerly ``initializeObject()`` was called before the dependencies were injected. This behavior didn't match the documentation_.

With TYPO3 CMS 7.3 this has been changed. Dependency injection using ``@inject`` annotations and ``inject*()`` methods is now performed **before** calling ``initializeObject()``.

This may have impact on extensions that are relying on the reversed call order. In these cases adjustments are required to take into account that the injected objects are available.

.. _documentation: http://wiki.typo3.org/Dependency_Injection#initializeObject.28.29_as_object_lifecycle_method
