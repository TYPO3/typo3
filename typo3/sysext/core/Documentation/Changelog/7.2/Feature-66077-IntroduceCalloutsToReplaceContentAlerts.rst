
.. include:: ../../Includes.txt

==============================================================
Feature: #66077 - Introduce callouts to replace content alerts
==============================================================

See :issue:`66077`

Description
===========

In several places alerts (flash messages) were used to display context information.
We introduce content info boxes and replace all occurrences where flash messages were used.


Impact
======

We introduced a new layout for context information and added a ViewHelper to render the markup.


Examples
========

Simple info box with a title. Please note that the title will always be HTML encoded by the ViewHelper

.. code-block:: html

	<f:be.infobox title="Message title">your box content</f:be.infobox>

All options of the ViewHelper. If you pass your message as ViewHelper argument, it will also be HTML encoded.

.. code-block:: html

	<f:be.infobox title="Message title" message="your box content" state="-2" iconName="check" disableIcon="TRUE" />

If you really need to output HTML in your message, use the closing variant. All children of the ViewHelper will be used as message.

.. code-block:: html

	<f:be.infobox title="Message title" state="-2" iconName="check" disableIcon="TRUE">
		<h1>{AlertMessage}</h1>
	</f:be.infobox>


.. index:: Fluid, Backend
