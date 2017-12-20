
.. include:: ../../Includes.txt

====================================================================================
Deprecation: #67670 - Deprecate custom singleton logic in GeneralUtility::getUserObj
====================================================================================

See :issue:`67670`

Description
===========

The functionality of instantiating classes only once by calling `GeneralUtility::getUserObj($className)` multiple times
while having a `$className` that is prepended with a ampersand ("&") has been marked as deprecated.


An example of the deprecated behaviour in the ext_localconf.php of an extension:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks']['getResultRows_SQLpointer'] = '&Acme\\MyExtension\\Hooks\\MysqlFulltextIndexHook';


Impact
======

Any calls to `GeneralUtility::getUserObj()` with a prefixed ampersand will throw a deprecation message.


Affected Installations
======================

TYPO3 Instances with extensions that use `getUserObj()` themselves and/or use hooks built with `getUserObj()` and use references.


Migration
=========

Check if the classes that hook into certain parts of your custom extensions really need to be referenced / instantiated once.
If so, implement the `SingletonInterface` of the TYPO3 Core, so the underlying function `GeneralUtility::makeInstance()`
will register the SingletonInterface only once.

The modified example from above now looks like this:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks']['getResultRows_SQLpointer'] = \Acme\MyExtension\Hooks\MysqlFulltextIndexHook::class;


While the class itself implements the SingletonInterface of the TYPO3 Core to only be instantiated once during a single request:

.. code-block:: php

	<?php
	namespace \Acme\MyExtension\Hooks;

	class MysqlFulltextIndexHook implements \TYPO3\CMS\Core\Core\SingletonInterface {
		...
	}


.. index:: PHP-API
