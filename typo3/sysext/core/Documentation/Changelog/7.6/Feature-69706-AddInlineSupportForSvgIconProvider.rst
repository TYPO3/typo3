
.. include:: ../../Includes.txt

==================================================================
Feature: #69706 - Add support for alternative (inline) icon markup
==================================================================

See :issue:`69706`

Description
===========

It is now possible to set alternative markups for an `Icon`.
By default icon is rendered as `<img src="..."/>` tag with path to the icon file in the src
attribute. With this change it's possible to render svg icon inline in the html e.g.
`<svg>...</svg>`.

Placing SVG images inline allows to manipulate them using CSS or JS.

.. code-block:: php

	$icon->setAlternativeMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE, '<svg>...</svg>');

Impact
======

An IconProvider can now add multiple markup variants for an icon.


.. index:: PHP-API, Backend
