
.. include:: ../../Includes.txt

=================================================
Breaking: #62039 - Removed TBE_STYLES[mainColors]
=================================================

See :issue:`62039`

Description
===========

The values within :code:`$TBE_STYLES[mainColors]` are redundant and can be completely defined via CSS nowadays. The
corresponding PHP leftovers are removed from the core and have no effect anymore.


Impact
======

Setting the variables within :code:`$TBE_STYLES[mainColors]` and using the :code:`$doc->bgColor*` and :code:`$doc->hoverColor` properties
of DocumentTemplate have no effect anymore.


Affected installations
======================

Any installation using an extension that is overriding skin info via :code:`$TBE_STYLES[mainColors]`.


Migration
=========

Use CSS directly to modify the appearance of the Backend.


.. index:: PHP-API, Backend
