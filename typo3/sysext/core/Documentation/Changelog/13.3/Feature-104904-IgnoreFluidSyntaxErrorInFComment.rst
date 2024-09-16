.. include:: /Includes.rst.txt

.. _feature-104904-1726049662:

===========================================================
Feature: #104904 - Ignore Fluid syntax error in <f:comment>
===========================================================

See :issue:`104904`

Description
===========

Fluid 4 brings a new template processor
:php:`\TYPO3Fluid\Fluid\Core\Parser\TemplateProcessor\RemoveCommentsTemplateProcessor`
which removes Fluid comments created with the
:ref:`Debug ViewHelper <f:debug> <t3viewhelper:typo3-fluid-debug>` from the template source
string before the parsing process starts. It retains the original line breaks to ensure
that error messages still refer to the correct line in the template.

By applying this template processor to all Fluid instances in the Core, it is now
possible to use invalid Fluid code inside :fluid:`<f:comment>` without triggering a Fluid error.


Impact
======

This feature is helpful during template development because developers don't need to
take care for commented-out code being valid Fluid code.

.. index:: Fluid, ext:fluid
