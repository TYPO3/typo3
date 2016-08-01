========================================================================
Feature: #77336 - Allow passing an own unit collection to ByteViewHelper
========================================================================

Description
===========

The viewhelper accepts a new parameter named units. It must be a comma separated list of units.

First example: Use the translation VH

.. code-block::
{fileSize -> f:format.bytes(units: '{f:translate(\'viewhelper.format.bytes.units\', \'fluid\')}'}

Second example: Provide a plain list

.. code-block::
<f:format.bytes units="byte, kilo, mega, husel, pusel">{size}</f:format.bytes>

results in the currently used collection, provided by the core.


Impact
======

A custom list of units can be passed to the viewHelper and will be used for formatting. Existing behaviour is not changed.