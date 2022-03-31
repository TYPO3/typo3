
.. include:: /Includes.rst.txt

=====================================================================
Breaking: #76259 - Signature of getResult() in PageLayoutView changed
=====================================================================

See :issue:`76259`

Description
===========

As part of migrating the core code to use Doctrine DBAL the signature of the method
:php:`PageLayoutView::getResult()` has changed.

Instead of accepting :php:`bool`, :php:`\mysqli_result` or :php:`object` as a
result provider only :php:`\Doctrine\DBAL\Driver\Statement` objects are accepted.

The new signature is:

.. code-block:: php

    public function getResult(\Doctrine\DBAL\Driver\Statement $result, string $table = 'tt_content') : array
    {
    }


Impact
======

3rd party extensions using :php:`PageLayoutView::getResult()` need to provide the correct
input type, otherwise exceptions of type :php:`InvalidArgumentException` will be thrown.


Affected Installations
======================

Installations using 3rd party extensions that use :php:`PageLayoutView::getResult()`.


Migration
=========

Refactor all code that works with :php:`PageLayoutView::getResult()` to provide the expected
Doctrine Statement object.

.. index:: Database, PHP-API, Backend
