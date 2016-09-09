
.. include:: ../../Includes.txt

=============================================
Feature: #67293 - Dependency ordering service
=============================================

See :issue:`67293`

Description
===========

In many cases it is necessary to establish a sorted list of items from a set of "dependencies".
The ordered list is then used to execute actions in the given order.

Some examples from the Core are:
- Hook execution order
- Extension loading order
- Listing of menu items

The dependencies are therefore specified in a relative manner, outlining that an item has to be executed/loaded/listed
"before" or "after" some other item.

Typical use case:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['someExt']['someHook'][<some id>] = [
		'handler' => someClass::class,
		'runBefore' => [ <some other ID> ],
		'runAfter' => [ ... ],
		...
	];

In order to evaluate such relative dependencies to finally have a sorted list for `['someHook']`, we introduced a new
helper class `\TYPO3\CMS\Core\Service\DependencyOrderingService`, which does the evaluation work for you.

Example usage:

.. code-block:: php

	$hooks = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['someExt']['someHook'];
	$sortedHooks = GeneralUtility:makeInstance(DependencyOrderingService::class)->orderByDependencies($hooks , 'runBefore', 'runAfter');

`$sortedHooks` will then contain the content of `$hooks`, but sorted according to the dependencies.

The `DependencyOrderingService` class also detects cycles in the dependencies and will throw an Exception in case
conflicting dependencies have been defined.

In case the initial list does not specify a dependency for an item, those items will be put last in the final sorted list.
