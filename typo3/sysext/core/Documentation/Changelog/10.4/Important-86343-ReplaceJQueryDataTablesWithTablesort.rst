.. include:: /Includes.rst.txt

============================================================
Important: #86343 - Replace jQuery.datatables with tablesort
============================================================

See :issue:`86343`

Description
===========

In our effort to reduce the dependency to jQuery, the internally used JavaScript
library ``jQuery.datatables`` has been replaced with ``tablesort``.

Extensions relying on that internal library may be dysfunctional now.

.. important::

   Extension authors are encouraged to not use libraries that are not explicitly
   marked as public API.

.. index:: Backend, JavaScript, ext:backend
