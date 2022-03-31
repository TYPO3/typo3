
.. include:: /Includes.rst.txt

================================================================================================
Breaking: #75760 - Return type of LocalizationRepository::getRecordsToCopyDatabaseResult changed
================================================================================================

See :issue:`75760`

Description
===========

The return type of :php:`LocalizationRepository::getRecordsToCopyDatabaseResult()`
has changed. Instead of returning either :php:`bool`, :php:`\mysqli_result`
or :php:`object` the return value always is a :php:`\Doctrine\DBAL\Driver\Statement`.


Impact
======

Using the mentioned method will not yield the expected result type.


Affected Installations
======================

Any installation with a 3rd party extension that uses the named method.


Migration
=========

Change the way the result is being used to conform to the Doctrine API:

.. code-block:: php

    $result = $this->localizationRepository->getRecordsToCopyDatabaseResult(...);
    while ($row = $result->fetch()) {
        // Do something here
    }

.. index:: Database, Frontend, Backend, PHP-API
