..  include:: /Includes.rst.txt

..  _breaking-108148-1763288414:

========================================================================
Breaking: #108148 - Disallow Fluid variable names with underscore prefix
========================================================================

See :issue:`108148`

Description
===========

With Fluid 5, it is no longer possible to define custom template variables
that start with an underscore (`_`). These variable names are reserved for
future internal use by Fluid itself, similarly to the already existing
`{_all}`.

Impact
======

This change affects ViewHelpers that define new variables, such as:

*   `<f:variable> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-variable>`_
*   `<f:for> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-for>`_
*   `<f:render> <https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-render>`_

It also affects Fluid's PHP APIs, namely :php:`$view->assign()` and
:php:`$view->assignMultiple()`.

Affected installations
======================

Installations with Fluid templates that use custom variable names starting
with an underscore (`_`) will encounter exceptions when such a template is
rendered. A deprecation has been written to the deprecation log since
TYPO3 13.4.21 if this is encountered in a Fluid template during rendering.

Migration
=========

The following examples no longer work with Fluid 5:

..  code-block:: html

     <f:variable name="_temp" value="a temporary value" />
     {_temp}

..  code-block:: html

     <f:for each="{myArray}" as="_item">
         {_item}
     </f:for>

..  code-block:: html

     <f:render partial="Footer" arguments="{_data: myData}" />

..  code-block:: php

     $view->assign('_data', $myData);
     $view->assignMultiple([
         '_data' => $myData,
     ]);

All examples lead to the following exception:

..  code-block::

     #1756622558 TYPO3Fluid\Fluid\Core\Variables\InvalidVariableIdentifierException
     Variable identifiers cannot start with a "_": _myVariable

In all cases, the variable name must be changed to no longer start with an
underscore (`_`).

Note that this only affects variable names, not property names in objects or
array keys that are accessed within a Fluid template. The following examples
are **not** affected by this change:

..  code-block:: html

     {myArray._myKey}
     {myObject._myProperty}

Also note that the existing `{_all}` (and any further internal variables added
by Fluid) are **not affected**. This code will continue to work:

..  code-block:: html

     <f:render partial="Footer" arguments="{_all}"/>

..  index:: Fluid, NotScanned, ext:fluid
