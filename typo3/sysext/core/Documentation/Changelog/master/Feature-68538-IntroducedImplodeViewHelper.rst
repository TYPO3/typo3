==============================================
Feature: #68538 - Introduced ImplodeViewHelper
==============================================

Description
===========

To be able to glue a array of strings together (f.i. to build a class attribute value) the ``f:format.implode`` ViewHelper is added.

ViewHelper parameters:
- array $values: array of elements to join
- string $glue: String used as glue between elements (default: space)
- bool $excludeEmptyValues: Remove empty elements (default: TRUE)


Examples
========

Some examples of using ``fe:format.implode``:

.. code-block:: html

    <fe:format.implode values="{0: 'className-1', 1: 'className-2'}" />

Output: ``className-1 className-2``


.. code-block:: html

    <div class="{fe:format.implode(values:'{0:\'className-1\', 1:\'className-2\'}')}">Foo</div>

Output: ``<div class="className-1 className-2">Foo</div>``