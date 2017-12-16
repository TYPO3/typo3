
.. include:: ../../Includes.txt

=====================================================================
Feature: #63913 - Allow ContainerViewHelper to load RequireJS modules
=====================================================================

See :issue:`63913`

Description
===========

The ContainerViewHelper can load RequireJS modules via the `includeRequireJsModules` attribute. The scripts are passed
as array.

.. code-block:: html

	<f:be.container pageTitle="Extension Module" loadJQuery="true"
		includeRequireJsModules="{
		0:'TYPO3/CMS/Extension/Module',
		1:'TYPO3/CMS/Extension/Module2',
		2:'TYPO3/CMS/Extension/Module3',
		3:'TYPO3/CMS/Extension/Module4'
	}">
