.. include:: /Includes.rst.txt

.. _feature-87085:

==================================================
Feature: #87085 - Fallback options for slug fields
==================================================

See :issue:`87085`

Description
===========

In case of SEO optimizations and the daily work of an editor, now it is possible to define a list of fields in the slug
configuration as nested array:

.. code-block:: php

   'columns' => [
      'slug' => [
         'config' => [
            'generatorOptions' => [
               // use value of 'nav_title'. If 'nav_title' is empty, use value of 'title'
               'fields' => [['nav_title', 'title']]
            ]
         ]
      ],
   ]

The fallback field can also be combined with other fields:

.. code-block:: php

   'columns' => [
      'slug' => [
         'config' => [
            'generatorOptions' => [
               // Concatenate path segments. In first segment, use 'nav_title' or 'title'.
               'fields' => [['nav_title', 'title'], 'other_field']
            ]
         ]
      ],
   ]

Hint
----

In this context:

*   :php:`['nav_title', 'title']` is the same as :php:`[['nav_title'], ['title']]`
*   :php:`['nav_title', 'title']` is **not** the same as :php:`[['nav_title', 'title']]`

Examples
--------

+-----------------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
| Configuration value                                             | Values of an example page record                                                                                                     | Resulting slug                    |
+=================================================================+======================================================================================================================================+===================================+
|:php:`[['nav_title', 'title']]`                                  | :php:`['title' => 'Products', 'nav_title' => '']`                                                                                    | `/products`                       |
+-----------------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`[['title', 'subtitle']]`                                   | :php:`['title' => 'Products', 'subtitle' => 'Product subtitle']`                                                                     | `/products`                       |
+-----------------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`['title', 'subtitle']` or :php:`[['title'], ['subtitle']]` | :php:`['title' => 'Products', 'subtitle' => 'Product subtitle']`                                                                     | `/products/product-subtitle`      |
+-----------------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`['nav_title', 'title'], 'subtitle'`                        | :php:`['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle']`                                     | `/best-products/product-subtitle` |
+-----------------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`['seo_title', 'title'], ['nav_title', 'subtitle']`         | :php:`['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle', 'seo_title' => 'SEO product title']` | `/seo-product-title/products`     |
+-----------------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+

.. index:: TCA
