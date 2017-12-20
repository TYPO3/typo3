.. include:: ../../Includes.txt

============================================================================
Breaking: #77750 - Return value of ContentObjectRenderer::exec_Query changed
============================================================================

See :issue:`77750`

Description
===========

The return type of :php:`ContentObjectRenderer::exec_getQuery()` has changed.
Instead of returning either :php:`bool`, :php:`\mysqli_result`
or :php:`object` the return value always is a :php:`\Doctrine\DBAL\Driver\Statement`.


Impact
======

Using the mentioned method will no longer yield the expected result type.


Affected Installations
======================

Any installation with a 3rd party extension that uses the named method.


Migration
=========

Change the way the result is being used to conform to the Doctrine API:

.. code-block:: php

    $result = $this->cObj->exec_getQuery(...);
    while ($row = $result->fetch()) {
        // Do something here
    }

.. index:: PHP-API, Frontend, Database
