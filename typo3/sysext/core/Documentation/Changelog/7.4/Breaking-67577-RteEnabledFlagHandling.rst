
.. include:: ../../Includes.txt

================================================
Breaking: #67577 - rte_enabled and flag handling
================================================

See :issue:`67577`

Description
===========

Content elements of type `text` and `text with image` contained a field "RTE enabled" that
could be unchecked to disable the rich text editor. This field has been removed together with the
`TCA` richtext `flag` handling.


Impact
======

The field is removed from database and the flag information is lost.


Affected Installations
======================

All instances will no longer show the "RTE enabled" field below `text` and `text with image`
content elements below the text field, the `TCA` `flag` is obsolete, see example below.


Migration
=========

A typical rich text configuration in `TCA` looked like:

.. code-block:: php

	'content' => array(
		'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.text',
		'config' => array(
			'type' => 'text',
			'cols' => '48',
			'rows' => '5',
			'wizards' => array(
				'RTE' => array(
					...
				)
			)
		),
		'defaultExtras' => 'richtext:rte_transform[flag=otherField|mode=ts_css]',
	),


With this configuration RTE was only rendered if `otherField` had the value 1. This flag is obsolete now:

.. code-block:: php

	'defaultExtras' => 'richtext:rte_transform[mode=ts_css]',


.. index:: TCA, RTE, Backend
