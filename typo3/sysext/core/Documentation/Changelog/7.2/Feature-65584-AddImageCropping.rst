
.. include:: ../../Includes.txt

====================================
Feature: #65584 - Add image cropping
====================================

See :issue:`65584`

Description
===========

A new functionality is introduced that allows the editor to define image cropping settings to a *sys_file_reference*.

The current support crop setting is a comma separated string defining: offsetX,offsetY,width,height


Impact
======

The value set for a *sys_file_reference* will be passed through to the image rendering of TYPO3 by default.
The new option of *sys_file_reference* is defined as exclude field in TCA so it needs to be enabled for editors.


Disable cropping of image when used with *typoscript* rendering:

.. code-block:: typoscript

	# Disable cropping for all images
	tt_content.image.20.1.file.crop =

Set custom cropping setting for when used with *typoscript* rendering:

.. code-block:: typoscript

	# Overrule/set cropping for all images
	tt_content.image.20.1.file.crop = 50,50,100,100


Disable cropping of image when used in *fluid*:

.. code-block:: html

	<f:image image="{imageObject}" crop="" />

Set custom cropping setting for image when used in *fluid*:

.. code-block:: html

	<f:image image="{imageObject}" crop="50,50,100,100" />


.. index:: Fluid, Backend, Frontend
