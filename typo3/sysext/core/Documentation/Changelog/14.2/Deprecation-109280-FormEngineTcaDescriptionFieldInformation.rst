..  include:: /Includes.rst.txt

..  _deprecation-109280-1742109280:

=================================================================
Deprecation: #109280 - FormEngine TcaDescription fieldInformation
=================================================================

See :issue:`109280`

Description
===========

The :php:`\TYPO3\CMS\Backend\Form\FieldInformation\TcaDescription` field
information render type has been deprecated. Field descriptions configured
via TCA :php:`['columns']['fieldName']['description']` are now rendered
automatically next to the field label by
:php:`\TYPO3\CMS\Backend\Form\Element\AbstractFormElement::renderDescription()`
and
:php:`\TYPO3\CMS\Backend\Form\Container\AbstractContainer::renderDescription()`.

Previously, every FormEngine element and container registered
`tcaDescription` as a default field information node, which rendered the
description inside the element body. The description is now rendered
after the label or legend element, providing more consistent positioning
across all field types.

Additionally, the :php:`$defaultFieldInformation` property has been removed
from all Core FormEngine elements and containers. Custom elements that extend
Core elements and rely on `tcaDescription` being present in
:php:`$defaultFieldInformation` are also affected.

Impact
======

Using the `tcaDescription` render type in a custom `fieldInformation`
configuration will trigger a PHP :php:`E_USER_DEPRECATED` level error. The
render type still exists but will return empty output during the deprecation
period, since descriptions are now rendered at the label level.

Custom FormEngine nodes that extend core elements and override
:php:`$defaultFieldInformation` to include `tcaDescription` will still work,
but the `tcaDescription` entry will trigger a deprecation warning.

Affected installations
======================

*   Installations with extensions that explicitly configure `tcaDescription`
    as a field information node in TCA:

    ..  code-block:: php

        'fieldInformation' => [
            'tcaDescription' => [
                'renderType' => 'tcaDescription',
            ],
        ],

*   Custom FormEngine elements and containers that set `tcaDescription` in
    their :php:`$defaultFieldInformation` property:

    ..  code-block:: php

        protected $defaultFieldInformation = [
            'tcaDescription' => [
                'renderType' => 'tcaDescription',
            ],
        ];

Extensions that only use the standard TCA `description` property are not
affected — descriptions will continue to be rendered.

Migration
=========

Remove any explicit `tcaDescription` field information configuration from
TCA and from custom FormEngine node classes. Field descriptions are now
rendered automatically next to the label and no longer require a field
information node.

**TCA configuration**

..  code-block:: diff

     'columns' => [
         'my_field' => [
             'label' => 'My field',
             'description' => 'Help text for this field',
             'config' => [
                 'type' => 'input',
    -            'fieldInformation' => [
    -                'tcaDescription' => [
    -                    'renderType' => 'tcaDescription',
    -                ],
    -            ],
             ],
         ],
     ],

**Custom FormEngine nodes with defaultFieldInformation**

If your custom element had a `tcaDescription` in
:php:`$defaultFieldInformation`, remove the property entirely:

..  code-block:: diff

     class MyCustomElement extends AbstractFormElement
     {
     -    protected $defaultFieldInformation = [
     -        'tcaDescription' => [
     -            'renderType' => 'tcaDescription',
     -        ],
     -    ];
     +    // tcaDescription is no longer needed; descriptions are
     +    // rendered automatically next to the label.
     }

If your custom element has other field information entries alongside
`tcaDescription`, remove only the `tcaDescription` entry:

..  code-block:: diff

     class MyCustomElement extends AbstractFormElement
     {
         protected $defaultFieldInformation = [
     -       'tcaDescription' => [
     -           'renderType' => 'tcaDescription',
     -       ],
             'myCustomInfo' => [
                 'renderType' => 'myCustomInfo',
             ],
         ];
     }

..  index:: Backend, PHP-API, TCA, NotScanned, ext:backend
