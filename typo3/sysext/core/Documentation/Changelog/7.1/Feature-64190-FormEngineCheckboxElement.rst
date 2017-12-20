
.. include:: ../../Includes.txt

==================================================================
Feature: #64190 - Inline rendering for FormEngine Checkbox Element
==================================================================

See :issue:`64190`

Description
===========

The checkbox setting `inline` for cols can be used to render the checkboxes
directly next to each other to reduce the amount of used space.

Example Configuration:

::

	'weekdays' => array(
		'label' => 'Weekdays',
		'config' => array(
			'type' => 'check',
			'items' => array(
				array('Mo', ''),
				array('Tu', ''),
				array('We', ''),
				array('Th', ''),
				array('Fr', ''),
				array('Sa', ''),
				array('Su', ''),
			),
			'cols' => 'inline',
		),
	),

..

Impact
======

Checkboxes will be placed directly next to each other to reduce the amount of used space.


.. index:: TCA, Backend
