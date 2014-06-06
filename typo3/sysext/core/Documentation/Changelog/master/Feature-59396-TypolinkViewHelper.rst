====================================
Feature: #59396 - TypolinkViewHelper
====================================

Description
===========

Adding a ViewHelper that copes with the contents of any field that was filled with a link wizard in
TYPO3 CMS Backend.
Those fields contain various parts split by a space and being escaped to provide input for the
typoLink function.
In order to use those fields natively in Fluid without the need of TypoScript in between, this ViewHelper
was introduced.
It takes the field content as a whole and can additionally take some parameters directly from Fluid.

The full parameter usage in Fluid might look like this, where {link} is the field content:

::

<f:link.typolink parameter="{link}" target="_blank" class="ico-class" title="some title" additionalParams="" additionalAttributes="{type:'button'}">

..

Only *parameter* is required, all other parameters are optional.
While passing additional parameters to the ViewHelper, following rules apply:

- target is overridden, the value from Fluid applies
- class is merged from the values passed from the database and those of *class*
- title is overridden, the value from Fluid applies
- additionalParams is merged from the values passed from the database and those of *additionalParams*
- additionalAttributes is (as usual) added to the resulting tag as *type="button"*

{link} contains *19 _blank - "testtitle with whitespace" &X=y*.
For the given example, the output is:

::

<a href="index.php?id=19&X=y" title="some title" target="_blank" class="ico-class" type="button">

..

Impact
======

The new ViewHelper can be used in all new projects. There is no interference with any part of existing code.