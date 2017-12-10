.. include:: ../../Includes.txt

===============================================================
Feature: #75161 - Create uri/link to backend modules viewhelper
===============================================================

See :issue:`75161`

Description
===========

Adds viewhelpers to build an uri or a link to a certain backend module.

Can be used to generate only an URI:

.. code-block:: html

	<f:be.uri route="web_ts" parameters="{id: 92}"/>

Or a full link tag:

.. code-block:: html

	<f:be.link route="web_ts" parameters="{id: 92}">Go to template module on page 92</f:be.link>

Both viewhelpers can also be used inline:

.. code-block:: none

	{f:be.uri(route: 'web_ts', parameters: '{id: 92}')}
	{f:be.link(route: 'web_ts', parameters: '{id: 92}')}

.. index:: Backend, Fluid