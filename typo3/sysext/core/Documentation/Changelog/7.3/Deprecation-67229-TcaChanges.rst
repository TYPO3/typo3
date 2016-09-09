
.. include:: ../../Includes.txt

=================================
Deprecation: #65290 - TCA changes
=================================

See :issue:`65290`


Description
===========

Some details in the main `Table Configuration Array, TCA`, known on PHP side as `$GLOBALS['TCA']` changed.


Simplified `types` `showitem` configuration using `columnsOverrides`
--------------------------------------------------------------------------

If a field is configured as `type` in `TCA` `ctrl` section, the value of this database field determines
which fields are shown if opening a record in the backend. The shown fields are configured in `TCA` section
`types` `showitem` and is a comma separated list of field names. Each field name can have 4 additional
semicolon separated options, from which the last two have been dropped and moved:

Before:

.. code-block:: php

	'types' => array(
		'aType' => array(
			'showitem' => 'aField,anotherField;otherLabel;aPalette;special:configuration;a-style-indicator,thirdField',
		),
	),


If a record is opened that has the type field set to `aType`, it would show the three fields `aField`, `anotherField`
and `thirdField`. The second field `anotherField` has further configuration and shows a different label, adds an additional
palette below the field referenced as `aPalette`, adds `special:configuration` as special configuration and changes
the style with its last field. The last two parameters were changed: The style configuration is obsolete since 7.1 and has been removed.
The special configuration is identical to the `defaultExtras` field of a `columns` field section and can be added with this
name in a newly introduced array `columnsOverrides` that is parallel to `showitem` of this type:

.. code-block:: php

	'types' => array(
		'aType' => array(
			'showitem' => 'aField,anotherField;otherLabel;aPalette,thirdField',
			'columnsOverrides` => array(
				'anotherField' => array(
					'defaultExtras' => 'special:configuration',
				),
			),
		),
	),


So, the 4th parameter has been transferred to `columnsOverrides` while the 5th parameter has been removed.

This change enables more flexible overrides of column configuration based on a given type. This is currently used in
`FormEngine` only, so only view-related parameters must be overwritten here. It is not supported to change data handling
related parameters like `type=text` to `type=select` or similar, but it is possible to change for example the number
of rows shown in a `type=text` column field:

.. code-block:: php

	'types' => array(
		'aType' => array(
			'showitem' => 'aField,anotherField;otherLabel;aPalette,thirdField',
			'columnsOverrides` => array(
				'anotherField' => array(
					'config' => array(
						'rows' => 42,
					),
				),
			),
		),
	),


It is also possible to remove a given configuration from the default configuration using the `__UNSET` keyword. Again,
this is only supported for view-related configuration options. Changing for instance an `eval` option may cripple the
PHP-side validation done by the DataHandler that checks and stores values.

.. code-block:: php

	'types' => array(
		'aType' => array(
			'columnsOverrides` => array(
				'bodytext' => array(
					'config' => array(
						'rows' => '__UNSET',
					),
				),
			),
		),
	),


The above example would remove the `rows` parameter of the `bodytext` field columns configuration, so a default
value would be used instead.


Simplified t3editor configuration
---------------------------------

t3editor is no longer configured and enabled as wizard.

Configuration for a column field looked like this before:

.. code-block:: php

	'bodytext' => array(
		'config' => array(
			'type' => 'text',
			'rows' => 42,
			'wizards' => array(
				't3editor' => array(
					'type' => 'userFunc',
					'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
					'title' => 't3editor',
					'icon' => 'wizard_table.gif',
					'module' => array(
						'name' => 'wizard_table'
					),
					'params' => array(
						'format' => 'html',
						'style' => 'width:98%; height: 60%;'
					),
				),
			),
		),
	),


The new configuration is simplified to:

.. code-block:: php

	'bodytext' => array(
		'exclude' => 1,
		'label' => 'aLabel',
		'config' => array(
			'type' => 'text',
			'renderType' => 't3editor',
			'format' => 'html',
			'rows' => 42,
		),
	),


In case t3editor was only enabled for a specific type, this was previously done with
`enableByTypeConfig` within the wizard configuration and `wizards[theWizardName]` as
the 4th semicolon separated parameter of the according field in section `showitem` of the
`type` where t3editor should be enabled. Old configuration was:

.. code-block:: php

	'columns' => array(
		'bodytext' => array(
			'exclude' => 1,
			'label' => 'aLabel',
			'config' => array(
				'type' => 'text',
				'rows' => 42,
				'wizards' => array(
					't3editorHtml' => array(
						'type' => 'userFunc',
						'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
						'enableByTypeConfig' => 1,
						'title' => 't3editor',
						'icon' => 'wizard_table.gif',
						'module' => array(
							'name' => 'wizard_table'
						),
						'params' => array(
							'format' => 'html',
							'style' => 'width:98%; height: 60%;'
						),
					),
				),
			),
		),
	),
	'types' => array(
		'firstType' => array(
			'showitem' => 'bodytext;;;wizards[t3editorHtml]',
		),
	),


This now uses the new `columnsOverrides` feature parallel to `showitem`:

.. code-block:: php

	'columns' => array(
		'bodytext' => array(
			'config' => array(
				'type' => 'text',
				'rows' => 42,
			),
		),
	),
	'types' => array(
		'firstType' => array(
			'showitem' => 'bodytext',
			'columnsOverrides' => array(
				'bodytext' => array(
					'config' => array(
						'format' => 'typoscript',
						'renderType' => 't3editor',
					),
				),
			),
		),


Impact
======

TCA is automatically migrated during bootstrap of the TYPO3 core and the result is cached.
In case TCA is still registered or changed in extensions with entries in `ext_tables.php`, an automatic
migration of this part of `TCA` is only triggered if extension `compatibility6` is loaded. This has a
performance penalty since the migration in `compatibility6` is then done on every frontend and backend
script call and is not cached.
It is **strongly** advised to move remaining `TCA` changes from `ext_tables.php` to `Configuration/TCA` or
`Configuration/TCA/Overrides` of the according extension and to unload `compatibility6`.


Migration
=========

An automatic migration is in place. It throws deprecation log entries in case `TCA` had to be changed on the fly.
The migration logs give hints on what exactly has changed and the final `TCA` can be inspected in the backend
configuration module. If outdated flexforms are used, the migration is done within the FormEngine class
construct on the fly and will throw deprecation warnings as soon as a record with outdated `TCA` flexforms
is opened in the backend.

Typical migration of the 4th `showitem` parameter involves moving a RTE configuration like
`richtext:rte_transform[mode=ts_css]` or the `type=text` flags `nowrap`, `fixed-font`
and `enabled-tab` to `columnsOverrides`.
