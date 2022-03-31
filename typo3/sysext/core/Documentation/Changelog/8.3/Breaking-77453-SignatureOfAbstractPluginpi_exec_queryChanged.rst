
.. include:: /Includes.rst.txt

=====================================================================
Breaking: #77453 - Signature of AbstractPlugin::pi_exec_query changed
=====================================================================

See :issue:`77453`

Description
===========

The value returned by :php:`AbstractPlugin::pi_exec_query` has changed.

Instead of returning one of :php:`bool`, :php:`\mysqli_result` or :php:`object`
the method always returns a :php:`Doctrine\Dbal\Driver\Statement`.


Impact
======

3rd Party extensions using :php:`AbstractPlugin::pi_exec_query` need to be modified
to work with the new return type.


Affected Installations
======================

Installations using 3rd party extensions that use :php:`AbstractPlugin::pi_exec_query`.


Migration
=========

Migrate your code to use the :php:`Statement` object:

.. code-block:: php

    $statement = $this->pi_exec_query(...);
    while($row = $statement->fetch())
    {
        // ... do something here
    }

.. index:: Database, PHP-API
