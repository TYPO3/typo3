================================================================
Feature: #24906 - Configuration for maximum chars in TextElement
================================================================

Description
===========

The Textelement supports now the option ``max`` to render the attribute maxlength for textelements,

The new option can be set like this:

.. code-block:: php

	'teaser' => array(
		'label' => 'Teaser',
		'config' => array(
			'type' => 'text',
			'cols' => 60,
			'rows' => 2,
			'max' => '30',
		)
	),