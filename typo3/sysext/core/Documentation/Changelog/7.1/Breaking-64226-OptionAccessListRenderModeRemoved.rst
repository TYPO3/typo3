
.. include:: ../../Includes.txt

============================================================================
Breaking: #64226 - Option $TYPO3_CONF_VARS[BE][accessListRenderMode] removed
============================================================================

See :issue:`64226`

Description
===========

The `$GLOBALS[TYPO3_CONF_VARS][BE][accessListRenderMode]` option, which acted as a shorthand function
to set permission-related fields for modules and excludeFields, has been removed.

All corresponding fields in TCA tables be_users and be_groups are set to the already-default value `checkbox`
given in the DefaultConfiguration.php file.

Impact
======

All respective fields will show up as a checkbox selection within FormEngine (implying the value
`renderMethod=checkbox`).


Affected installations
======================

Installations having this option set to something different than `checkbox` will result in a having the fields
displayed as checkboxes.

Migration
=========

Choose between the default value `checkbox` (no change required then) or set the following values inside the
`Configuration/TCA/Overrides` files of your project specific extension to the option of your needs.

.. code-block:: php

	$GLOBALS['TCA']['be_users']['columns']['file_permissions']['config']['renderMode'] = 'singlebox';
	$GLOBALS['TCA']['be_users']['columns']['userMods']['config']['renderMode'] = 'singlebox';
	$GLOBALS['TCA']['be_groups']['columns']['file_permissions']['config']['renderMode'] = 'singlebox';
	$GLOBALS['TCA']['be_groups']['columns']['pagetypes_select']['config']['renderMode'] = 'singlebox';
	$GLOBALS['TCA']['be_groups']['columns']['tables_select']['config']['renderMode'] = 'singlebox';
	$GLOBALS['TCA']['be_groups']['columns']['tables_modify']['config']['renderMode'] = 'singlebox';
	$GLOBALS['TCA']['be_groups']['columns']['non_exclude_fields']['config']['renderMode'] = 'singlebox';
	$GLOBALS['TCA']['be_groups']['columns']['userMods']['config']['renderMode'] = 'singlebox';
