.. include:: ../../Includes.txt

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
               'fields' => ['nav_title', 'title']
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
               'fields' => [['nav_title', 'title'], 'other_field']
            ]
         ]
      ],
   ]

Examples
--------

+---------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
| Configuration value                                     | Values of an example page record                                                                                                     | Resulting slug                    |
+=========================================================+======================================================================================================================================+===================================+
|:php:`['nav_title', 'title']`                            | :php:`['title' => 'Products', 'nav_title' => '', 'subtitle' => '']`                                                                  | `/products`                       |
+---------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`['nav_title', 'title']`                            | :php:`['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => '']`                                                     | `/best-products`                  |
+---------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`['subtitle', 'nav_title', 'title']`                | :php:`['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle']`                                     | `/product-subtitle`               |
+---------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`['nav_title', 'title'], 'subtitle'`                | :php:`['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle']`                                     | `/best-products/product-subtitle` |
+---------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+
|:php:`['seo_title', 'title'], ['nav_title', 'subtitle']` | :php:`['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle', 'seo_title' => 'SEO product title']` | `/seo-product-title/products`     |
+---------------------------------------------------------+--------------------------------------------------------------------------------------------------------------------------------------+-----------------------------------+

.. index:: TCA
