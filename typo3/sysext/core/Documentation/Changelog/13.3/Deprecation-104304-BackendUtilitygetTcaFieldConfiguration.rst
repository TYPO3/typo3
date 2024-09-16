.. include:: /Includes.rst.txt

.. _deprecation-104304-1720084447:

===============================================================
Deprecation: #104304 - BackendUtility::getTcaFieldConfiguration
===============================================================

See :issue:`104304`

Description
===========

The method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getTcaFieldConfiguration` was introduced back
in 2010 to add a simple abstraction to access "TCA" definitions of a field.

However, apart from the set up that it is not part of a flexible API without
knowing the context, it was used seldom in TYPO3 Core.

The method has now been deprecated, as one could and can easily write the same
PHP code with :php:`$GLOBALS['TCA']` in mind already (which the TYPO3 Core already did
in several other places).

Now that Schema API was introduced, the last parts have been migrated to use
the new API.


Impact
======

Calling the PHP method :php:`BackendUtility::getTcaFieldConfiguration` will
trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations with custom extensions using this method.


Migration
=========

Either access :php:`$GLOBALS['TCA']` directly (in order to support TYPO3 v12 and TYPO3 v13),
or migrate to the new Schema API:

.. code-block:: php

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory
    ) {}

    private function getFieldConfiguration(string $table, string $fieldName): array
    {
        return $this->tcaSchemaFactory
            ->get($table)
            ->getField($fieldName)
            ->getConfiguration();
    }

.. index:: PHP-API, TCA, FullyScanned, ext:backend
