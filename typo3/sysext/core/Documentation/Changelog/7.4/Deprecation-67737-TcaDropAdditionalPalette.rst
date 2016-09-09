
.. include:: ../../Includes.txt

==================================================
Deprecation: #67737 - TCA: Drop additional palette
==================================================

See :issue:`67737`

Description
===========

The `showitem` string of `TCA` `types` allowed to define an "additional palette" as third
semicolon separated name of a field. Such a palette was then rendered after the main field.
This handling has been dropped and existing "additional palettes" were migrated to a "normal" palette definition
directly after the field.

Before:

.. code-block:: php

	'types' => array(
		'aType' => array(
			'showitem' => 'aField;aLabel;anAdditionalPaletteName',
		),
	),


The behavior before was: If the field `aField` is rendered, then the
palette `anAdditionalPaletteName` is rendered, too. This functionality has been dropped, the migrated field now looks
like this:

.. code-block:: php

	'types' => array(
		'aType' => array(
			'showitem' => 'aField;aLabel, --palette--;;anAdditionalPaletteName',
		),
	),


A casual field name in `showitem` now only has a label override as additional
information, like `aField;aLabel`, while a palette is referenced as
`--palette--;aLabel;paletteName`.


Impact
======

All extensions that use "additional palette" syntax are migrated to the new syntax, but will
throw a deprecation message.

The "additional palette" handling was sometimes misused as "poor-mans-access-control":
If access to the main field was not allowed, the palette fields were not rendered either. This
changed, the main field and the palette are decoupled, it may happen that additional fields
are now rendered for users that should not have access to it. Adapting the `exclude` config
definition of the palette fields and user or group access records is necessary in those cases.



Affected Installations
======================

Extensions that use the "additional palette" handling.


Migration
=========

An automatic migration is in place and logged to `typo3conf/deprecation_*`. The migration code
will be dropped with TYPO3 CMS 8, a manual fix of the according `showitem` field is required,
the deprecation log gives detailed information on how the definition should look like.
