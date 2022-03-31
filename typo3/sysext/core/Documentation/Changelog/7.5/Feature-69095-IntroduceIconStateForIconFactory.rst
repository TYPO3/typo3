
.. include:: /Includes.rst.txt

======================================================
Feature: #69095 - Introduce icon state for IconFactory
======================================================

See :issue:`69095`

Description
===========

A state (default or disabled) for icons has been added. The state "disabled" marks an icon as disabled and shows the icon with 50% opacity.


Use an icon
-----------

The method `IconFactory::getIcon()` has now a fourth parameter for the state.

The `\TYPO3\CMS\Core\Type\Icon\IconState` class provides only the following constants for icon states:

* `State::STATE_DEFAULT` which currently means 100% opacity
* `State::STATE_DISABLED` which currently means 50% opacity

The states may change in future, so please make use of the constants for an unified layout.

.. code-block:: php

	$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
	$iconFactory->getIcon($identifier, Icon::SIZE_SMALL, $overlay, IconState::cast(IconState::STATE_DEFAULT))->render();


ViewHelper
----------

The core provides a Fluid ViewHelper which makes it really easy to use icons within a Fluid view.
This ViewHelper has an argument for the new state parameter.

.. code-block:: html

	{namespace core=TYPO3\CMS\Core\ViewHelpers}
	<core:icon identifier="my-icon-identifier" state="disabled" />


.. index:: PHP-API, Backend, Fluid
