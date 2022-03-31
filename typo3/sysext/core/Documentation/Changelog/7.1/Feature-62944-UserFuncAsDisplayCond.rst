
.. include:: /Includes.rst.txt

=========================================================
Feature: #62944 - UserFunc available as Display Condition
=========================================================

See :issue:`62944`

Description
===========

Being able to use userFunc as `displayCondition` makes it possible to
check on any imaginable condition or state. If any situation can not
be evaluated with any of the existing checks the developer is free
to add an own user function which provides a boolean result whether
to show or hide the TCA field.

.. code-block:: php

	$GLOBALS['TCA']['tt_content']['columns']['bodytext']['displayCond'] = 'USER:Evoweb\\Example\\User\\ElementConditionMatcher->checkHeaderGiven:any:more:information';

Any parameters can be added (separated by colons) that are sent to the ConditionMatcher class.


.. index:: PHP-API, TCA, Backend
