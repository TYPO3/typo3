.. include:: /Includes.rst.txt

.. _deprecation-100581-1681396349:

=====================================================================
Deprecation: #100581 - Avoid constructor argument in FormDataCompiler
=====================================================================

See :issue:`100581`

Description
===========

When instantiating the backend FormEngine related :php:`FormDataCompiler`,
the constructor argument :php:`FormDataGroupInterface` should be omitted,
the form data group should be provided as second argument to :php:`compile()`
instead.


Impact
======

Handing over the form data group as second argument to :php:`compile()`
allows injecting :php:`FormDataCompiler` into controllers with TYPO3 v13
since the manual constructor argument will be removed.


Affected installations
======================

Instances with own backend modules that use FormEngine to render records
may be affected. Handing over the form data group as constructor argument
to :php:`FormDataCompiler` will trigger a deprecation level log warning
with TYPO3 v12. With TYPO3 v13, the form data group must be provided as
second argument to :php:`compile()` and will not be optional anymore.


Migration
=========

..  code-block:: php

    // before
    $formDataCompiler = GeneralUtility::makeInstance(
        FormDataCompiler::class, GeneralUtility::makeInstance(MyDataGroup::class)
    );
    $formData = $formDataCompiler->compile($myFormDataCompilerInput);

    // after
    $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);
    $formData = $formDataCompiler->compile(
        $myFormDataCompilerInput,
        GeneralUtility::makeInstance(MyDataGroup::class)
    );

.. index:: Backend, PHP-API, NotScanned, ext:backend
