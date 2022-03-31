.. include:: /Includes.rst.txt

===================================================
Important: #75232 - Spread TypeConverter priorities
===================================================

See :issue:`75232`

Description
===========

The priorities of the "TypeConverter" classes were quite packed. To be able to
register own type converters between, before and after the core converters the
priorities were spread from 0, 1 and 2 to 10 and 20.

If you register your own TypeConverter(s) make sure they are using the right priority.

.. index:: PHP-API
