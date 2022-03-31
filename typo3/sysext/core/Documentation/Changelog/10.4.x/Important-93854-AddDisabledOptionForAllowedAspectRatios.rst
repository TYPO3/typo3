.. include:: /Includes.rst.txt

=================================================================
Important: #93854 - Add disabled option for allowed aspect ratios
=================================================================

See :issue:`93854`

Description
===========

Like for crop variants it is now possible to add the option to disable aspect ratios by adding a "disabled" key to the array.

.. code-block:: php

   $GLOBALS['TCA']['tt_content']['types']['textmedia']['columnsOverrides']
         ['assets']['config']['overrideChildTca']['columns']['crop']['config'] = [
      'cropVariants' => [
         'default' => [
            'allowedAspectRatios' => [
               '4:3' => [
                  'disabled' => true,
               ],
            ],
         ],
      ],
   ];

This works for each field, that defines crop variants for any
:sql:`sys_file_reference` usage.

Impact
======

This will optionally let you disable aspect ratios for a specific field or
:sql:`CType`, which is sometimes necessary because the ratio will not fit in
the frontend.

.. index:: Backend, TCA, ext:backend
