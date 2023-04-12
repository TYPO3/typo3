.. include:: /Includes.rst.txt

.. _deprecation-100577-1681384407:

======================================================
Deprecation: #100577 - FormEngine needs request object
======================================================

See :issue:`100577`

Description
===========

The backend FormEngine construct (editing records in the backend)
now expects the current :php:`ServerRequestInterface` object to
be hand over as initial data.


Impact
======

Backend modules that use the FormEngine data provider construct to
render records should provide the current request object. Failing
to do so will trigger a deprecation level log message and the system
will fall back to :php:`$GLOBALS['TYPO3_REQUEST']`. This will stop
working with TYPO3 v13.


Affected installations
======================

Instances with extensions that provide custom modules using the FormEngine
construct are affected. This is a relatively seldom case.


Migration
=========

Provide the request object as "initial data" when using the
:php:`FormDataCompiler`:

.. code-block:: php

    $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $myFormDataGroup);
    $formDataCompilerInput = [
        'request' => $request,
        // further data, for example:
        'tableName' => $table,
        'vanillaUid' => $uid,
    ];
    $formData = $formDataCompiler->compile($formDataCompilerInput);

.. index:: Backend, PHP-API, NotScanned, ext:backend
