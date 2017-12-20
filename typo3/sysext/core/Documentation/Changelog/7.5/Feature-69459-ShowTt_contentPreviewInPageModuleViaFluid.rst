
.. include:: ../../Includes.txt

==================================================================
Feature: #69459 - Show tt_content preview in page module via Fluid
==================================================================

See :issue:`69459`

Description
===========

A new PageTSconfig option allows to render a preview of a single content element in the Backend via Fluid.

The following option allows to override the default output of a content element via PageTSconfig:

.. code-block:: typoscript

	mod.web_layout.tt_content.preview.media = EXT:site_mysite/Resources/Private/Templates/Preview/Media.html

All content elements with CType "media" will then be rendered by the Fluid Template which can be rendered like:

.. code-block:: html

	<h4>{header}</h4>
	<f:format.crop length="200">{bodytext}</f:format.crop>

All properties of the tt_content record are available in the template directly.
Any data of the flexform field `pi_flexform` is available with the property `pi_flexform_transformed` as an array.

.. note::

	If a PHP hook already is set to render the element, it will take precedence over the Fluid-based preview.


.. index:: TSConfig, Fluid, FlexForm
