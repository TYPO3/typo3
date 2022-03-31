
.. include:: /Includes.rst.txt

===============================================
Feature: #69389 - Add spinning feature for icon
===============================================

See :issue:`69389`

Description
===========

The Icon API has now a support for spinning icons. While registering an icon a new property `spinning` is available:


.. code-block:: php

	$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
	$iconRegistry->registerIcon(
		'my-spinning-icon',
		\TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
		array(
			'name' => 'times',
			'spinning' => TRUE
		)
	);


Impact
======

Icons can now be animated.


.. index:: PHP-API, Backend
