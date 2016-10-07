
.. include:: ../../Includes.txt

================================================================
Feature: #76259 - Introduce buildQueryParametersPostProcess Hook
================================================================

See :issue:`76259`

Description
===========

With the migration to Doctrine the hook `buildQueryParameters`
has been introduced in the class :php:`DatabaseRecordList`. This hook
replaces the hook `makeQueryArray` from the deprecated method
:php:`AbstractDatabaseRecordList::makeQueryArray`.

Using this hook allows modifying the parameters used to query the database
for records to be shown in the record list view.

The hook-object needs to be registered in :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class]['buildQueryParameters'][]`
and implement the public method :php:`buildQueryParametersPostProcess`.

The signature of the :php:`buildQueryParametersPostProcess` method is as following:

.. code-block:: php

    public function buildQueryParametersPostProcess(
        array $parameters,
        string $table,
        int $pageId,
        array $additionalConstraints,
        array $fieldList,
        AbstractDatabaseRecordList $parentObject
    ) : void {
    }

The following fields are part of the `$parameters` array and can be modified:

==============  ==========  ===========
Key             Type        Description
--------------  ----------  -----------
table           string      The queried tablename
fields          string[]    The columns to retrieve
groupBy         string[]    The columns to group the result by
firstResult     int|null    The offset to start retrieve rows from
maxResults      int|null    The maximum number of rows to retrieve
orderBy         array[]     Array of arrays containing fieldname/sorting pairs
where           string[]    Array of where conditions to apply to the database query.
==============  ==========  ===========

.. index:: PHP-API, Database
