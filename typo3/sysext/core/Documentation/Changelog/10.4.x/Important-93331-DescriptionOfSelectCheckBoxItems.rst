.. include:: /Includes.rst.txt

=======================================================
Important: #93331 - Description of SelectCheckBox items
=======================================================

See :issue:`93331`

Description
===========

Due to the introduction of grouping and sorting for TCA columns of type
`select` in #91008, the position of the items description, also referred
as "Help text" has changed in the corresponding TCA configuration. This
previously led to misbehaviour when using `renderType=selectCheckBox`
since the old position was still checked by this FormEngine element.

Adding descriptions is now working again and it will be used when configured
at the correct position:

.. code-block:: php

   'items' => [
       ...,
       [
           'the label',
           'the value',
           'iconIdentifier',
           'groupIdentifier',
            // The item description must be added as the fifth argument
           'item description'
       ],
   ]

It's furthermore still possible to define an array with the `title` and
`description` keys:

.. code-block:: php

   'items' => [
       ...,
       [
           'the label',
           'the value',
           'iconIdentifier',
           'groupIdentifier',
            // The item description must be added as the fifth argument
           [
              'title' => 'Help title',
              'description' => 'Help description'
           ]
       ]
   ]

In case you are using :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions']`
for defining custom permission options, nothing changes. The description has
still to be placed at the third position in each items configuration.

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'] => [
      'my_custom_field' => [
         'items' => [
            'someKey' => [
               'the label',
               'anIconIdentifier',
               'item description',
            ]
         ]
      ]
   ]

.. index:: Backend, TCA
