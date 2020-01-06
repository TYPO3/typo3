.. include:: ../../Includes.txt

=================================
Deprecation: #79440 - TCA Changes
=================================

See :issue:`79440`

Description
===========

The :code:`TCA` on field level has been changed. Nearly all column types are affected.
In general, the sub-section :code:`wizards` is gone and replaced by a combination of new
:code:`renderType's` and a new set of configuration options. Wizards are now divided into
three different kinds:

* :code:`fieldInformation` - Informational HTML, typically displayed between the element label
  and the element itself.
* :code:`fieldControl` - Icons, typically displayed right next to the element, used to trigger
  certain actions, for instance to jump to the link view.
* :code:`fieldWizard` - HTML typically shown below the element to enrich the element with further
  functionality. Example is the rendering of thumbnails below a type=group element.

Other wizards like the "suggest" functionality have been merged into the affected elements itself.

Additionally, the config option :code:`defaultExtras`, which was often set within :code:`columnsOverrides` has
been removed. The options were transferred to config options of the elements itself and can be set
within :code:`columnsOverrides` directly.

A :code:`TCA` migration transforms old configuration options to new ones and throws descriptive log entries.

The first list covers all former existing wizards and shows where and how the functionality is now placed.

The second list below gives examples from before and after based on :code:`type`, together with detail
information on certain configuration options.


Wizard list
-----------


Add wizard, edit wizard and list wizard
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The three wizards :code:`wizard_add`, :code:`wizard_edit` and :code:`wizard_list` usually used in :code:`type=group`
and :code:`type=select` with :code:`renderType=selectMultipleSideBySide` are now default controls of these two elements
and just need to be enabled. :code:`options` are optional, the render engine selects a fallback title, a default pid and
table name if needed.

Example before:

.. code-block:: php

    'wizards' => [
        '_VERTICAL' => 1,
        'edit' => [
            'type' => 'popup',
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup_edit_title',
            'module' => [
                'name' => 'wizard_edit',
            'popup_onlyOpenIfSelected' => true,
            'icon' => 'actions-open',
            'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
        ],
        'add' => [
            'type' => 'script',
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup_add_title',
            'icon' => 'actions-add',
            'params' => [
                'table' => 'be_groups',
                'pid' => 0,
                'setValue' => 'prepend'
            ],
            'module' => [
                'name' => 'wizard_add'
            ]
        ],
        'list' => [
            'type' => 'script',
            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup_list_title',
            'icon' => 'actions-system-list-open',
            'params' => [
                'table' => 'be_groups',
                'pid' => 0
            ],
            'module' => [
                'name' => 'wizard_list'
            ]
        ]
    ]


Example after:

.. code-block:: php

    'fieldControl' => [
        'editPopup' => [
            'disabled' => false,
        ],
        'addRecord' => [
            'disabled' => false,
            'options' => [
                'setValue' => 'prepend',
            ],
        'listModule' => [
            'disabled' => false,
        ],
    ],


Color picker
^^^^^^^^^^^^

The color picker wizard has been changed to :code:`type=input` element with :code:`renderType=colorpicker`

Example before:

.. code-block:: php

    'input_34' => [
        'label' => 'input_34',
        'config' => [
            'type' => 'input',
            'wizards' => [
                'colorChoice' => [
                   'type' => 'colorbox',
                   'title' => 'LLL:EXT:examples/Resources/Private/Language/locallang_db.xlf:tx_examples_haiku.colorPick',
                   'module' => [
                      'name' => 'wizard_colorpicker',
                   ],
                   'JSopenParams' => 'height=600,width=380,status=0,menubar=0,scrollbars=1',
                   'exampleImg' => 'EXT:examples/res/images/japanese_garden.jpg',
                ]
            ],
        ],
    ],


Example after:

.. code-block:: php

    'input_34' => [
        'label' => 'input_34',
        'config' => [
            'type' => 'input',
            'renderType' => 'colorpicker',
        ],
    ],


Table wizard
^^^^^^^^^^^^

The table wizard has been embedded in :code:`type=text` element with :code:`renderType=textTable`

Example before:

.. code-block:: php

    'text_17' => [
        'label' => 'text_17',
        'config' => [
            'type' => 'text',
            'cols' => '40',
            'rows' => '5',
            'wizards' => [
                'table' => [
                    'notNewRecords' => 1,
                    'type' => 'script',
                    'title' => 'LLL:EXT:cms/locallang_ttc.xlf:bodytext.W.table',
                    'icon' => 'content-table',
                    'module' => [
                        'name' => 'wizard_table'
                    ],
                    'params' => [
                        'xmlOutput' => 0
                    ]
                ],
            ],
        ],
    ],


Example after:

.. code-block:: php

    'text_17' => [
        'label' => 'text_17',
        'config' => [
            'type' => 'text',
            'renderType' => 'textTable',
            'cols' => '40',
            'rows' => '5',
        ],
    ],


RTE wizard
^^^^^^^^^^

The RTE wizard that jumps to a full screen view of a text field has been embedded into `EXT:rtehtmlarea`
directly and just needs to be turned on. This additionally obsoletes the :code:`defaultExtras=rte_only` setting.

Example before:

.. code-block:: php

    'rte_1' => [
        'label' => 'rte_1',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'RTE' => [
                'notNewRecords' => 1,
                'RTEonly' => 1,
                'type' => 'script',
                'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                'icon' => 'actions-wizard-rte',
                'module' => [
                    'name' => 'wizard_rte'
                ]
            ],
        ],
    ],


Example after:

.. code-block:: php

    'rte_1' => [
        'label' => 'rte_1',
        'config' => [
            'type' => 'text',
            'enableRichtext' => true,
            'fieldControl' => [
                'fullScreenRichtext' => [
                    'disabled' => false,
                ],
            ],
        ],
    ],


Link browser
^^^^^^^^^^^^

The link browser icon wizard has been embedded in new :code:`'renderType' => 'inputLink'` directly. The parameters
:code:`blindLinkOptions`, :code:`blindLinkFields` and :code:`allowedExtensions` are now all optional and options of
section :code:`fieldControl`.

Example before:

.. code-block:: php

    'input_29' => [
        'label' => 'input_29 link',
        'config' => [
            'type' => 'input',
            'wizards' => [
                'link' => [
                'type' => 'popup',
                'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel',
                'icon' => 'actions-wizard-link',
                'module' => [
                   'name' => 'wizard_link',
                ],
                'JSopenParams' => 'height=800,width=600,status=0,menubar=0,scrollbars=1',
                'params' => [
                    'blindLinkOptions' => 'folder',
                    'blindLinkFields' => 'class, target',
                    'allowedExtensions' => 'jpg',
                ],
            ],
        ],
    ],


Example after:

.. code-block:: php

    'input_29' => [
        'label' => 'input_29',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputLink',
            'fieldControl' => [
                'linkPopup' => [
                    'options' => [
                        'blindLinkOptions' => 'folder',
                        'blindLinkFields' => 'class, target',
                        'allowedExtensions' => 'jpg',
                    ],
                ],
            ],
        ],
    ],


Select wizard
^^^^^^^^^^^^^

The select wizard has been directly embedded in :code:`type=input` and :code:`type=text` elements, only works with
a static list of items and is called :code:`valuePicker`.

Example before:

.. code-block:: php

    'input_33' => [
        'label' => 'input_33',
        'config' => [
            'type' => 'input',
            'wizards' => [
                'select' => [
                    'items' => [
                        [ 'spring', 'Spring', ],
                        [ 'summer', 'Summer', ],
                        [ 'autumn', 'Autumn', ],
                        [ 'winter', 'Winter', ],
                    ],
                ],
            ],
        ],
    ],



Example after:

.. code-block:: php

    'input_33' => [
        'label' => 'input_33',
        'config' => [
            'type' => 'input',
            'valuePicker' => [
                'items' => [
                    [ 'spring', 'Spring', ],
                    [ 'summer', 'Summer', ],
                    [ 'autumn', 'Autumn', ],
                    [ 'winter', 'Winter', ],
                ],
            ],
        ],
    ],


Suggest wizard
^^^^^^^^^^^^^^

The suggest wizard has been directly embedded in :code:`type=group` element and is enabled by default. It
can be disabled by setting :code:`hideSuggest=true` in config section and suggest options can be added in
:code:`suggestOptions`.

Example before:

.. code-block:: php

    'group_db_8' => [
        'label' => 'group_db_8',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tx_styleguide_staticdata',
            'wizards' => [
                '_POSITION' => 'top',
                    'suggest' => [
                        'type' => 'suggest',
                        'default' => [
                            'pidList' => 42,
                        ],
                    ],
                ],
            ],
        ],
    ],


Example after:

.. code-block:: php

    'group_db_8' => [
        'label' => 'group_db_8',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tx_styleguide_staticdata',
            'suggestOptions' => [
                'default' => [
                    'pidList' => 42,
                ]
            ],
        ],
    ],


Slider wizard
^^^^^^^^^^^^^

The slider wizard has been embedded in :code:`type=text` as option :code:`slider` within the
config section.

Example before:

.. code-block:: php

    'input_30' => [
        'label' => 'input_30',
        'config' => [
            'type' => 'input',
            'size' => 5,
            'eval' => 'trim,int',
            'range' => [
                'lower' => -90,
                'upper' => 90,
            ],
            'default' => 0,
            'wizards' => [
                'angle' => [
                    'type' => 'slider',
                    'step' => 10,
                    'width' => 200,
                ],
            ],
        ],
    ],


Example after:

.. code-block:: php

    'input_30' => [
        'label' => 'input_30',
        'config' => [
            'type' => 'input',
            'size' => 5,
            'eval' => 'trim,int',
            'range' => [
                'lower' => -90,
                'upper' => 90,
            ],
            'default' => 0,
            'slider' => [
                'step' => 10,
                'width' => 200,
            ],
        ],
    ],



Type list
---------

type=input
^^^^^^^^^^

* The wizard :code:`slider` has been directly embedded in this element. The new config option :code:`slider`
  can be used to configure the slider. The slider appears if the option :code:`slider` exists and is an arary.
* The wizard :code:`select` has been directly embedded in this element. The new config option :code:`valuePicker`
  has been introduced to configure items of the drop down.
* The four date related :code:`eval` options :code:`date`, :code:`datetime`, :code:`time` and :code:`timesec` have
  been moved to :code:`renderType=inputDateTime`.
* The wizard :code:`wizard_link` has been removed a field now displays the link wizard by setting :code:`renderType=inputLink`
  for a :code:`type=input` element.


type=text
^^^^^^^^^

* The wizard :code:`select` has been directly embedded in this element. The new config option :code:`valuePicker`
  has been introduced to configure items of the drop down.
* The wizard :code:`wizard_table` has been given the own :code:`renderType=textTable`.
* The wizard :code:`RTE` has been changed to a `fieldControl` of the richtext element implemented by
  extension `rtehtmlarea`.
* :code:`defaultExtras=enable-tab` has been moved to config option :code:`enableTabulator`
* :code:`defaultExtrasfixed-font` has been moved to config option :code:`fixedFont`
* :code:`defaultExtras=nowrap` has been moved to config option :code:`wrap=off`


type=select with renderType=selectSingle
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* Config option :code:`showIconTable` has been dropped. Showing assigned images below the select field has
  been migrated to a :code:`fieldWizard` and is disabled by default.
* Config option :code:`selicon_cols` has been dropped without substitution, the render engine now shows as
  many images in a row as fit into the view.

Example to enable the icon display:

.. code-block:: php

    'select_single_5' => [
        'label' => 'select_single_5',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['foo 1', 'foo1', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
                ['foo 2', 'foo2', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
            ],
            'fieldWizard' => [
                'selectIcons' => [
                    'disabled' => false,
                ],
            ],
        ],
    ],


type=select with renderType=multipleSideBySide
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Configuration option changes of the :code:`renderType=multipleSideBySide` element are similar to
the :code:`type=group` changes. In detail, the following changes have been applied:

* Config option :code:`selectedListStyle` has been dropped without substitution.
* Wizards :code:`wizard_add`, :code:`wizard_edit` and :code:`wizard_list` have been changed to :code:`fieldControl`
  and must be enabled per element. All options are still valid, but the system tries to determine sane fallback
  values if no options are given. For example, the `table` option of :code:`wizard_add` and :code:`wizard_list`
  fall back to the value of :code:`foreign_table` if not explicitly given.

Example configuration of a multipleSideBySide field before change:

.. code-block:: php

    'select_multiplesidebyside_6' => [
        'exclude' => 1,
        'label' => 'select_multiplesidebyside_6',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'foreign_table' => 'tx_styleguide_staticdata',
            'rootLevel' => 1,
            'size' => 5,
            'autoSizeMax' => 20,
            'wizards' => [
                '_VERTICAL' => 1,
                'edit' => [
                    'type' => 'popup',
                    'title' => 'edit',
                    'module' => [
                        'name' => 'wizard_edit',
                    ],
                    'icon' => 'actions-open',
                    'popup_onlyOpenIfSelected' => 1,
                    'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                ],
                'add' => [
                    'type' => 'script',
                    'title' => 'add',
                    'icon' => 'actions-add',
                    'module' => [
                        'name' => 'wizard_add',
                    ],
                    'params' => [
                        'table' => 'tx_styleguide_staticdata',
                        'pid' => '0',
                        'setValue' => 'prepend',
                    ],
                ],
                'list' => [
                    'type' => 'script',
                    'title' => 'list',
                    'icon' => 'actions-system-list-open',
                    'module' => [
                        'name' => 'wizard_list',
                    ],
                    'params' => [
                        'table' => 'tx_styleguide_staticdata',
                        'pid' => '0',
                    ],
                ],
            ],
        ],
    ],


Example after:

.. code-block:: php

     'select_multiplesidebyside_6' => [
         'exclude' => 1,
         'label' => 'select_multiplesidebyside_6 wizards',
         'config' => [
             'type' => 'select',
             'renderType' => 'selectMultipleSideBySide',
             'foreign_table' => 'tx_styleguide_staticdata',
             'rootLevel' => 1,
             'size' => 5,
             'autoSizeMax' => 20,
             'fieldControl' => [
                 'editPopup' => [
                     'disabled' => false,
                 ],
                 'addRecord' => [
                     'disabled' => false,
                 ],
                 'listModule' => [
                     'disabled' => false,
                 ],
             ],
         ],
     ],


type=group
^^^^^^^^^^

This element got most changes in this series. A number of options have been fine-tuned
and got better default values:

* Similar to :code:`type=select` with :code:`type=multipleSideBySide`, the three wizards
  :code:`wizard_add`, :code:`wizard_edit` and :code:`wizard_list` have been changed to :code:`fieldControl`
  and are disabled by default.
* Config option :code:`selectedListStyle` has been dropped without substitution.
* The suggest wizard has been directly embedded in :code:`type=group` and has been enabled by default for
  :code:`internal_type=db` elements. It can be disabled with :code:`hideSuggest=true` and options of the
  `suggest` can be hand over in config option :code:`suggestOptions`.
* The config option :code:`show_thumbs` showed two different things in the past: With :code:`internal_type=db`, it
  displayed the list of selected records as a table below the element, with :code:`internal_type=file` it rendered
  thumbnails of selected files and displayed the below the element. :code:`show_thumbs` has been dropped, the
  functionality has been transferred to :code:`fieldWizard` as :code:`recordsOverview` and :code:`fileThumbnails`
  respectively and are enabled by default. They can be disabled by setting `disabled=true`.
* The config option :code:`disable_controls` has been obsoleted, single parts of the group element can be disabled
  by adding :code:`disabled=true` to the according :code:`fieldControl` or :code:`fieldWizard`.


Example of a typical group field before:

.. code-block:: php

    'group_db_1' => [
        'label' => 'group_db_1',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'be_users,be_groups',
            'wizards' => [
                'edit' => [
                    'type' => 'popup',
                    'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.edit',
                    'module' => [
                        'name' => 'wizard_edit',
                    ],
                    'popup_onlyOpenIfSelected' => 1,
                    'icon' => 'actions-open',
                    'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1'
                ],
                'add' => [
                    'type' => 'script',
                    'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.createNewPage',
                    'icon' => 'actions-add',
                    'params' => [
                        'table' => 'be_users',
                        'pid' => 0,
                        'setValue' => 'append'
                    ],
                    'module' => [
                    'name' => 'wizard_add'
                    ],
                ],
                'list' => [
                    'type' => 'script',
                    'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.list',
                    'icon' => 'actions-system-list-open',
                    'params' => [
                        'table' => 'be_groups',
                        'pid' => '0'
                    ],
                    'module' => [
                        'name' => 'wizard_list'
                    ]
                ]
            ],
        ],
    ],


Example after:

.. code-block:: php

    'group_db_1' => [
        'label' => 'group_db_1',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'be_users,be_groups',
            'fieldControl' => [
                'editPopup' => [
                    'disabled' => false,
                ],
                'addRecord' => [
                    'disabled' => false,
                ],
                'listModule' => [
                    'renderType' => 'listModule',
                    'options' => [
                    'disabled' => false,
                ],
            ],
        ],
    ],


Disable other parts of type=group:

.. code-block:: php

    'group_db_1' => [
        'label' => 'group_db_1',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'be_users,be_groups',
            'fieldControl' => [
                // Disable element browser icon
                'elementBrowser' => [
                    'disabled' => true,
                ],
                // Disable insert from clipboard icon
                'insertClipboard' => [
                    'disabled' => true,
                ],
            ],
            'fieldWizard => [
                // Disable button list of allowed tables
                'tableList' => [
                    'disabled' => true,
                ],
                // Disable list of allowed file types
                'fileTypeList' => [
                    'disabled' => true,
                ],
                // Disable thumbnail view of selected files
                'fileThumbnails' => [
                    'disabled' => true,
                ],
                // Disable table view of selected records
                'recordsOverview' => [
                    'disabled' => true,
                ],
                // Disable direct file upload button
                'fileUpload' => [
                    'disabled' => true,
                ],
            ],
        ],
    ],


Impact
======

Using old TCA settings as outlined above will throw a deprecation warnings.


Affected Installations
======================

Most installations are affected by this change.


Migration
=========

An automatic TCA migration transfers from old TCA settings to new ones and throws deprecation log entries with
hints which changes should be incorporated. For flex form data structure definitions, the TCA migration is called
when opening an according record and logs, too.

.. index:: Backend, TCA, RTE
