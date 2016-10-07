
.. include:: ../../Includes.txt

==================================================================================
Deprecation: #75575 - TranslateViewHelper htmlEscape argument marked as deprecated
==================================================================================

See :issue:`75575`

Description
===========

The htmlEscape argument of the TranslateViewHelper has been marked as deprecated.

This ViewHelper now HTML escapes the translation by default. The argument value has no effect anymore.


Impact
======

Usages of `<f:translate>` view helper with argument set to `false` will have the label HTML escaped anyway.

Usages of `<f:translate>` view helper with argument set to `true` will have the label HTML escaped like before unless the view helper is wrapped with a `<f:format.raw>`


Affected Installations
======================

Installations with usages of `<f:translate>` in a context where HTML escaping is not desired (e.g. JavaScript).


Migration
=========

`<f:translate>` needs to be wrapped by `<f:format.raw>` if the view helper result is needed in a different context than HTML

.. index:: Fluid, Frontend