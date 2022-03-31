.. include:: /Includes.rst.txt

=======================================================================
Feature: #89573 - Allow flexible base url for slug fields in FormEngine
=======================================================================

See :issue:`89573`

Description
===========

It is now possible to add a custom base url for TCA columns of type :php:`slug`. The
base url is displayed in front of the input field in FormEngine.

To add a custom base url a :php:`userFunc` can be assigned to the new setting
:php:`prefix` which is available under :php:`['columns'][*]['config']['appearance']` at the fields TCA definition.

.. code-block:: php

   'config' => [
       'type' => 'slug',
       'appearance' => [
           'prefix' => \Vendor\Extension\UserFunctions\FormEngine\SlugPrefix::class . '->getPrefix'
       ]
   ]

The :php:`userFunc` receives two parameters. The first parameter is the parameters
array containing the site object, the language id, the current table and the
current row. The second parameter is the reference object :php:`TcaSlug`. The
:php:`userFunc` should return the string which is then used as the base url in
FormEngine.

.. code-block:: php

   <?php
   declare(strict_types = 1);

   namespace Vendor\Extension\UserFunctions\FormEngine

   use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSlug;

   class SlugPrefix
   {
       public function getPrefix(array $parameters, TcaSlug $reference): string
       {
           return 'custom base url';
       }
   }


Impact
======

Developers are enabled to provide custom base urls for their slug fields. If you
are already using slug fields in your TCA, nothing changes as the current
behaviour is still used as the default.

.. index:: Backend, PHP-API, TCA, ext:backend
