.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #94115 - Parameter type evaluation via DocBlock comments
=====================================================================

See :issue:`94115`

Description
===========

Extbase had a long support for detecting the actual
target type of a method argument by parsing the DocBlock
annotations like

.. code-block:: php

   /**
    * @param \MyVendor\MyExtension\MyModel $item
    */
   public function myAction($item);

However, since PHP 7 supports to define the target type
by specifying the type directly in the language, which is
much faster, the "legacy" way of handling type detection for
arguments are marked as deprecated.


Impact
======

When a DocBlock annotation like :php:`@param \MyClass $item` is added, but
the actual type is not added to the method argument via native PHP type
declarations, a deprecation message is now triggered.


Affected Installations
======================

TYPO3 installations with custom Extbase extensions which
were never upgraded to support latest PHP language constructs.


Migration
=========

Use native PHP type declarations instead - this can be achieved since TYPO3 v10:

.. code-block:: php

   public function myAction(\MyVendor\MyExtension\MyModel $item);

.. index:: PHP-API, NotScanned, ext:extbase
