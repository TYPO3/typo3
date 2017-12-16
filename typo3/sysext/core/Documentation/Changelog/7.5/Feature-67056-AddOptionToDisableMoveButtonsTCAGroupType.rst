
.. include:: ../../Includes.txt

===================================================================
Feature: #67056 - Add option to disable move buttons TCA group type
===================================================================

See :issue:`67056`

Description
===========

The move buttons of the TCA type `group` can now be explicitly disabled with the
`hideMoveIcons` option. Before these icons where only automatically removed if
`maxitems` was set to 1.

.. code-block:: php

	'options' => array(
		'label' => 'Options',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'pages',
			'maxitems' => 9999,
			'hideMoveIcons' => TRUE,
		),
	),


Impact
======

Move buttons can now always be hidden for `group` fields
