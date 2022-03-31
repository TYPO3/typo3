.. include:: /Includes.rst.txt

===================================================================================
Breaking: #90799 - Dependency injection with non-public properties has been removed
===================================================================================

See :issue:`90799`

Description
===========

In TYPO3 v9, the (dependency) injection via :php:`@Inject` has been marked as deprecated for
non-public properties. The reason was to avoid having the core use the PHP reflection
api to make non-public properties writable from outside the class scope. Since there
are other methods for dependency injection (constructor/setter injection), injection
into non-public properties has now been removed.


Impact
======

Non-public properties with :php:`@Inject` annotations will no longer trigger extbase
dependency injection. Those properties will have their default state after object
instantiation.


Affected Installations
======================

All installations that use non-public properties for extbase dependency injection
as seen in this example:

.. code-block:: php

   class Foo
   {
       /**
        * @var Service
        * @TYPO3\CMS\Extbase\Annotation\Inject
        */
       private $service;
   }


Migration
=========

When not using constructor/setter injection instead, switch to inject methods
(recommended for compatibility with symfony dependency injection) or mark the
property public (works with extbase dependency injection only):

.. code-block:: php

   class Foo
   {
       /**
        * @var Service
        * @TYPO3\CMS\Extbase\Annotation\Inject
        */
       public $service;
   }

.. index:: PHP-API, NotScanned, ext:extbase
