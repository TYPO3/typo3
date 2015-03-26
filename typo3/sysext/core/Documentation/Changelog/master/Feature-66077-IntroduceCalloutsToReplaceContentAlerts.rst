==============================================================
Feature - #66077: Introduce callouts to replace content alerts
==============================================================

Description
===========

In several places alerts (flashmessages) were used to display context information.
We introduce content info boxes and replace all occurences where flashmessages were used.


Impact
======

We introduced a new layout for context information and added a ViewHelper to render the markup.


Examples
========

Simple infobox with a title

.. code-block:: html

	<f:be.infobox title="Message title">your box content</f:be.infobox>

All options

	<f:be.infobox title="Message title" message="your box content" state="-2" iconName="check" disableIcon="TRUE" />