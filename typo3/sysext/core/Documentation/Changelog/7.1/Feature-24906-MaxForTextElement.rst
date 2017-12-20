
.. include:: ../../Includes.txt

================================================================
Feature: #24906 - Configuration for maximum chars in TextElement
================================================================

See :issue:`24906`

Description
===========

TCA type `text` now supports the HTML5 attribute `maxlength` to restrict
text to a given maximum length. Line breaks are usually counted as two
characters.

Not every browser supports this, see sites like
`w3schools.com <http://www.w3schools.com/tags/att_textarea_maxlength.asp>`_
for details.

The new option can be used like this:

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


.. index:: TCA, Backend
