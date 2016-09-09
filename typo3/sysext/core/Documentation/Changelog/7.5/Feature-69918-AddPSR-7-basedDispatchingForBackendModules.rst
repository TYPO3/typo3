
.. include:: ../../Includes.txt

=================================================================
Feature: #69918 - Add PSR-7-based dispatching for Backend Modules
=================================================================

See :issue:`69918`

Description
===========

Built on the PSR-7 principle and the routing concepts, it is now possible to
register backend modules which are dispatched to a callable string instead of
pointing to an index.php file in `EXT:myextension/Modules/MyModule/index.php`.

The method which is called, receives a PSR-compatible request and response object
and must return a response object which is outputted to the browser.

An example registration uses the option `routeTarget` to resolve the method to
be called when rendering the module:

.. code-block:: php

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'layout',
		'top',
		'',
		array(
			'routeTarget' => \TYPO3\CMS\Backend\Controller\PageLayoutController::class . '::mainAction',
			'access' => 'user,group',
			'name' => 'web_layout',
			'labels' => array(
				'tabs_images' => array(
					'tab' => 'EXT:backend/Resources/Public/Icons/module-page.svg',
				),
				'll_ref' => 'LLL:EXT:backend/Resources/Private/Language/locallang_mod.xlf',
			),
		)
	);
