.. include:: /Includes.rst.txt

=======================================================
Feature: #79402 - New Fluid ViewHelper f:variable added
=======================================================

See :issue:`79402`


Description
===========

A new ViewHelper `f:variable` has been added in Fluid 2.2.0 which is now minimum required dependency for TYPO3.
The ViewHelper allows variables to be assigned in the template:

.. code-block:: html

    <f:variable name="myvariable">My variable's content</f:variable>
    <f:variable name="myvariable" value="My variable's content"/>
    {f:variable(name: 'myvariable', value: 'My variable\'s content')}
    {myoriginalvariable -> f:variable.set(name: 'mynewvariable')}


Impact
======

The new ViewHelper is now available in any and all Fluid templates being rendered in TYPO3.

.. index:: Fluid
