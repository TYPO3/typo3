..  include:: /Includes.rst.txt

..  _deprecation-105252-1728471144:

==============================================================
Deprecation: #105252 - DataProviderContext getters and setters
==============================================================

See :issue:`105252`

Description
===========

The backend layout related data object class :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext`
has been turned into a data object using public constructor property promotion (PCPP).
All :php:`setX()` and :php:`getX()` methods have been marked as deprecated in TYPO3 v13.4 and
will be removed with TYPO3 v14.0. The class will be declared :php:`readonly` in TYPO3 v14.0
which will enforce instantiation using PCPP. The class has been declared final since it is
an API contract that must never be changed or extended. The constructor arguments will be
declared non-optional in TYPO3 v14.0.

* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageId()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setTableName()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setFieldName()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setData()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->setPageTsConfig()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageId()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getTableName()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getFieldName()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getData()`
* :php:`TYPO3\CMS\Backend\View\BackendLayout\DataProviderContext->getPageTsConfig()`


Impact
======

Calling the getters or setters raises deprecation level log errors and will stop working
in TYPO3 v14.0.


Affected installations
======================

This data object is only relevant for instances with extensions that add custom backend layout
data providers using :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`.
There are few known extensions that do this. The extension scanner is not configured to find
possible usages since the method names are too generic and would lead to too many false positives.


Migration
=========

Create new objects using PCPP with named arguments instead of the setters.
Instances should be created using :php:`new()`:

.. code-block:: php

    // Before
    $dataProviderContext = GeneralUtility::makeInstance(DataProviderContext::class);
    $dataProviderContext
        ->setPageId($pageId)
        ->setData($parameters['row'])
        ->setTableName($parameters['table'])
        ->setFieldName($parameters['field'])
        ->setPageTsConfig($pageTsConfig);

    // After
    $dataProviderContext = new DataProviderContext(
        pageId: $pageId,
        tableName: $parameters['table'],
        fieldName: $parameters['field'],
        data: $parameters['row'],
        pageTsConfig: $pageTsConfig,
    );

Use the properties instead of the getters, example:

.. code-block:: php

    // Before
    $pageId = $dataProviderContext->getPageId()
    // After
    $pageId = $dataProviderContext->pageId


..  index:: Backend, PHP-API, NotScanned, ext:backend
