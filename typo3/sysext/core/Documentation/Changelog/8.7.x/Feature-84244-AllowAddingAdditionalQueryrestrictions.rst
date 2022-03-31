.. include:: /Includes.rst.txt

============================================================
Feature: #84244 - Allow adding additional query restrictions
============================================================

See :issue:`84244`

Description
===========

It is now possible to add additional query restrictions by adding class names as key to
:php:`$GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions']`
These restriction objects will be added to any select query executed using the QueryBuilder.

If these added restriction objects additionally implement :php:`\TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface`
and return true in the to be implemented method :php:`isEnforced()`, calling :php:`$queryBuilder->getRestrictions()->removeAll()`
such restrictions will **still** be applied to the query.

If an enforced restriction must be removed, it can still be removed with :php:`$queryBuilder->->getRestrictions()->removeByType(SomeClass::class);`

Implementers of custom restrictions can therefore have their restrictions always enforced, or even not applied at all,
by returning an empty expression in certain cases.

To add a custom restriction class, use the following snippet in a :file:`ext_localconf.php` file of your extension:

.. code-block:: php

    if (!isset($GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Vendor\ExtName\Database\Query\Restriction\CustomRestriction::class])) {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Vendor\ExtName\Database\Query\Restriction\CustomRestriction::class] = [];
    }

Please note, that the class name must be the array key and the value must always be an array, which is reserved for options
given to the restriction objects.

Impact
======

Restrictions added by third party extensions will impact the whole system. Therefore this API does not allow removing restrictions
added by the system and adding restrictions should be handled with care.

Removing third party restrictions is possible, by setting the option value :php:`disabled` for a restriction to :php:`true`
in global TYPO3 configuration or :php:`ext_localconf.php` of an extension, like shown below.

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['DB']['additionalQueryRestrictions'][\Vendor\ExtName\Database\Query\Restriction\CustomRestriction::class]['disabled'] = true;

.. index:: Backend, Database, Frontend, ext:core
