
.. include:: /Includes.rst.txt

=================================================================
Breaking: #68092 - TCA: Remove wizard hideParent and _HIDDENFIELD
=================================================================

See :issue:`68092`

Description
===========

Wizards defined in `TCA` for display in `FormEngine` allowed to hide the "parent"
field with the configuration options `_HIDDENFIELD` on main wizard level, and with
the `hideParent` option for single wizards.

Both options have been dropped.


Impact
======

The configuration options have no effect anymore, the main field will show up.


Affected Installations
======================

A search through the TER code showed not a single extension that used the above options.
A 3rd party extension is affected if a `TCA` column configuration is used like:

.. code-block:: php

	'aField' => array(
		'config' => array(
			...
			'wizards' => array(
				'_HIDDENFIELD' => TRUE,
				'aWizard' => array(
					'hideParent' => array(
						...
					),
				),
			),
		),
	),


Migration
=========

Wizards can not trigger that a main field is not rendered anymore. If this kind of functionality
is needed, it is recommended to register an own `renderType` in the `NodeFactory` for this
type of field instead to route the element rendering to an own class.


.. index:: TCA, Backend
