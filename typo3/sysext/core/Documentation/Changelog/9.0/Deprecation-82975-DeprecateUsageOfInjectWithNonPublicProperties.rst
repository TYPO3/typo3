.. include:: ../../Includes.txt

===========================================================================
Deprecation: #82975 - Deprecate usage of @inject with non-public properties
===========================================================================

See :issue:`82975`

Description
===========

When using private or protected properties for Dependency Injection via :php:`@inject`, Extbase needs to
use the object reflection API to make these properties settable from the outside,
which is quite slow and cannot be cached in any way. Therefore property injection should
only work for public properties.


Impact
======

Using :php:`@inject` with a non-public property will trigger a deprecation warning and will
not work any longer in TYPO3 version 10.


Affected Installations
======================

All installations, that use property injection via :php:`@inject` with non-public properties


Migration
=========

You have the following options to migrate:

 - Introduce an explicit :php:`inject*()` method (e.g. :php:`injectMyProperty()`)
 - Use constructor injection
 - Make the property public (think about whether this is desired in terms of software design)


An inject method would look like this:

.. code-block:: php

   /**
    * @var MyFancyProperty $myFancyProperty
    */
   private $myFancyProperty;

   /**
    * @param MyFancyProperty $myFancyProperty
    */
   public function injectMyFancyProperty(MyFancyProperty $myFancyProperty): void
   {
      $this->myFancyProperty = $myFancyProperty;
   }

.. index:: PHP-API, ext:extbase, NotScanned
