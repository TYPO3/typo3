.. include:: /Includes.rst.txt

================================================
Deprecation: #92386 - Extbase property injection
================================================

See :issue:`92386`

Description
===========

Since core dependency injection is in place and is about to replace the extbase dependency injection completely,
using property injection via the :php:`@Extbase\Inject` annotation has been marked as deprecated.


Impact
======

Classes that use extbase property injection will experience non injected services for properties that have a :php:`@Extbase\Inject` annotation.


Affected Installations
======================

All installations that use extbase property injection via annotation :php:`@Extbase\Inject`.


Migration
=========

Extbase property injection can be replaced by one of the following methods:

- constructor injection: works both with core and extbase dependency injection and is well suited to make extensions compatible for multiple TYPO3 versions.
- setter injection: Basically the same as constructor injection. Both the core and extbase DI can handle setter injection and both are supported in different TYPO3 versions.
- (core) property injection: This kind of injection can be used but it requires the configuration of services via a :file:`Services.yaml` in the :file:`Configuration` folder of an extension.


Given the following example for a :php:`@Extbase\Inject` annotation based injection:

.. code-block:: php

   /**
    * @var MyService
    * @Extbase\Inject
    */
   protected $myService;


This service injection can be changed to constructor injection by adding the
service as constructor argument and removing the :php:`@Extbase\Inject` annotation:

.. code-block:: php

   /**
    @var MyService
    */
   protected $MyService;

   public function __construct(MyService $MyService) {
      $this->myService = $myService;
   }

Please consult the dependency-injection_ documentation for more information.

.. _dependency-injection: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/DependencyInjection/Index.html

.. index:: PHP-API, FullyScanned, ext:extbase
