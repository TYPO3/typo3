
.. include:: /Includes.rst.txt

============================================
Feature: #67229 - FormEngine NodeFactory API
============================================

See :issue:`67229`

Description
===========

The FormEngine class construct was moved to a tree approach with container classes as inner nodes and
element classes (the rendering widgets) as leaves. Finding, instantiation and preparation of those
classes is done via `TYPO3\CMS\Backend\Form\NodeFactory`.

This class was extended with an API to allow flexible overriding and adding of containers and elements:


Registration of new nodes and overwriting existing nodes
--------------------------------------------------------

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1433196792] = array(
		'nodeName' => 'input',
		'priority' => 40,
		'class' => \MyVendor\MyExtension\Form\Element\T3editorElement::class,
	);


This registers the class `MyVendor\MyExtension\Form\Element\T3editorElement` as render class for
the type `input`. It will be called to render elements of this type and must implement the interface
`TYPO3\CMS\Backend\Form\NodeInterface`. The array key is the unix timestamp of the date when an registry
element is added and is just used to have a unique key that is very unlikely to collide with others - this
is the same logic that is used for exception codes. If more than one registry element for the same type
is registered, the element with highest priority wins. Priority must be set between 0 and 100. Two elements
with same priority for the same type will throw an exception.

The core extension t3editor uses this API to substitute a `type=text` field with `renderType=t3editor`
from the default `TextElement` to its own `T3editorElement`.

This registry both allows completely overriding existing implementations of any existing given type as well as
registration of a new `renderType` for own fancy elements. A TCA configuration for a new renderType
and its nodeRegistry could look like:

.. code-block:: php

	'columns' => array(
		'bodytext' => array(
			'config' => array(
				'type' => 'text',
				'renderType' => '3dCloud',
			),
		),
	),

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1433197759] = array(
		'nodeName' => '3dCloud',
		'priority' => 40,
		'class' => \MyVendor\MyExtension\Form\Element\ShowTextAs3dCloudElement::class,
	);


Resolve class resolution to different render classes
----------------------------------------------------

In case the above API is not flexible enough, another class can be registered to resolve the final
class that renders a certain element or container differently:

.. code-block:: php

	// Register FormEngine node type resolver hook to render RTE in FormEngine if enabled
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'][1433198160] = array(
		'nodeName' => 'text',
		'priority' => 50,
		'class' => \MyVendor\MyExtension\Form\Resolver\MyTextNodeResolver::class,
	);


This registers a resolver class at priority 50 if the type `text` should be rendered. This class must
implement `TYPO3\CMS\Backend\Form\NodeResolverInterface` and can return a different class name that is
called as render class. The render class in turn must implement `TYPO3\CMS\Backend\Form\NodeInterface`.

The array key is a unix timestamp of the date when this resolver code is registered. Multiple resolvers
are a chain, the resolver with highest priority is asked first, and the chain is called until one resolver
returns a new class name. If no resolver returns anything, the default class name will be instantiated and rendered.

Priority is between 0 and 100 and two resolvers for the same type and same priority will throw an exception.

The resolver will receive the full `globalOptions` array with all settings to make a resolve decision
on all incoming values.

This API is used by core extension rtehtmlarea to route the rendering of `type=text` to its own
`RichTextElement` class in case the editor is enabled for this field and for the user.

This API allows fine grained resolution of render-nodes based on any need, for instance it would be
easily possible to call another different richtext implementation (eg. TinyMCE) for specific fields
of own extensions based on moon phases or your fathers birthday, by adding a resolver class with a higher priority.


Warning
-------

The internal data given to the resolver class still may change. Both the `globalOptions` and the current
`renderType` values are subject to change without further notice until TYPO3 CMS 7 LTS.


.. index:: PHP-API, TCA, Backend
