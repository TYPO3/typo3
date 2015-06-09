==================================================================
Feature: #67372 - Allow empty attribute syntax in fluid TagBuilder
==================================================================

Description
===========

Tags built with the TagBuilder may have empty attributes as they are allowed in the `HTML specifications`_ and are broadly supported by web browsers.
The way to create empty attributes is to use ``->addAttribute($key, $value)`` or ``->addAttributes($array)`` just like before and provide an empty string as attribute value.


Impact
======

If someone used an empty string as attribute value before, it will now be rendered with the empty attribute syntax which is exactly the same (according to the HTML specification).


Examples
========

Usage example:

.. code-block:: php

	$this->tag->addAttribute('disabled', ''); // results in a tag like: <input disabled />

.. _HTML specifications: http://www.w3.org/TR/html-markup/syntax.html#syntax-attributes
