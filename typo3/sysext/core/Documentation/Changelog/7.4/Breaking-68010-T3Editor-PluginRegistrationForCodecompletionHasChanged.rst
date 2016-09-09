
.. include:: ../../Includes.txt

================================================================================
Breaking: #68010 - T3Editor - Plugin registration for codecompletion has changed
================================================================================

See :issue:`68010`

Description
===========

Due to the rewrite of T3Editor to jQuery, the plugin registration for codecompletion has changed.


Impact
======

Plugins for codecompletion written in Prototype will not work anymore.


Affected Installations
======================

Every third-party extension providing a T3Editor plugin extending the codecompletion.


Migration
=========

Port the plugin to an AMD module. The plugin must have an `init` method with a configuration object as only parameter. Every parameter that was passed to the old Prototype function must be in that configuration object. Please see the example code below or consult :file:`EXT:t3editor/Resources/Public/JavaScript/Plugins/CodeCompletion/DescriptionPlugin.js`.

Example code:

.. code-block:: javascript

	define('Awesome/Extension/Plugins/CodeCompletion/CoolPlugin', [
		'jquery',
		'TYPO3/CMS/T3editor/Plugins/CodeCompletion/TsRef',
		'TYPO3/CMS/T3editor/Plugins/CodeCompletion/TsParser'
	], function ($, TsRef, TsParser) {
		var CoolPlugin = {
			codeCompleteBox: null,
			codemirror: null
		};

		CoolPlugin.init = function(configuration) {
			DescriptionPlugin.codeCompleteBox = configuration.codeCompleteBox;
			DescriptionPlugin.codemirror = configuration.codemirror;

			DescriptionPlugin.codeCompleteBox.parent().append($('<div />', {class: 'foomatic}));
		};

		return CoolPlugin;
	});
