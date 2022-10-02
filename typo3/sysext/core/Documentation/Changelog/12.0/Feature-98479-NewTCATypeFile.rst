.. include:: /Includes.rst.txt

.. _feature-98479-1664537749:

=====================================
Feature: #98479 - New TCA type "file"
=====================================

See :issue:`98479`

Description
===========

A new TCA field type called :php:`file` has been added to TYPO3 Core. Its
main purpose is to simplify the TCA configuration for adding file reference
fields to records. It therefore supersedes the usage of TCA type :php:`inline`
with :php:`foreign_table` set to :php:`sys_file_reference`, which had previously
usually been configured using the now deprecated API method
:php:`ExtensionManagementUtility->getFileFieldTCAConfig()` for this use case.

This helps on determination of the semantic meaning and also allows to
reduce internal cross dependencies between TCA type `inline` and FAL.

The new TCA type :php:`file` features the following column configuration:

*   :php:`allowed`
*   :php:`appearance`: :php:`collapseAll`, :php:`expandSingle`,
    :php:`createNewRelationLinkTitle`, :php:`useSortable`, :php:`enabledControls`,
    :php:`headerThumbnail`, :php:`fileUploadAllowed`, :php:`fileByUrlAllowed`,
    :php:`elementBrowserEnabled`, :php:`showPossibleLocalizationRecords`,
    :php:`showAllLocalizationLink`, :php:`showSynchronizationLink`,
    :php:`showFileSelectors`
*   :php:`behaviour`: :php:`allowLanguageSynchronization`,
    :php:`disableMovingChildrenWithParent`, :php:`enableCascadingDelete`
*   :php:`disallowed`
*   :php:`fieldInformation`
*   :php:`fieldWizard`
*   :php:`maxitems`
*   :php:`minitems`
*   :php:`overrideChildTca`
*   :php:`readOnly`

.. note::

    The option :php:`showFileSelectors` can be used to define whether the
    file selectors, such as "Select & upload files" are displayed. This is
    similar to the the :php:`showPossibleRecordsSelector` option, available
    for TCA type :php:`inline`.

The following column configuration can be overwritten by Page TSconfig:

- :typoscript:`appearance`
- :typoscript:`behaviour`
- :typoscript:`maxitems`
- :typoscript:`minitems`
- :typoscript:`readOnly`

A possible migration using the API method therefore looks like the following:

..  code-block:: php

    // Before
    'columns' => [
        'image' => [
            'label' => 'My image',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'image',
                [
                    'maxitems' => 6,
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            ),
        ],
    ],

    // After
    'columns' => [
        'image' => [
            'label' => 'My image',
            'config' => [
                'type' => 'file',
                'maxitems' => 6,
                'allowed' => 'common-image-types'
            ],
        ],
    ],

The example uses the :php:`common-image-types` placeholder for the
:php:`allowed` option. This placeholder is internally replaced and
helps to further reduce the usage of :php:`$GLOBALS`. Further placeholders
are :php:`common-text-types` and :php:`common-media-types`. It's possible
to use multiple placeholders. It's also possible to mix them with single
file extensions. Additionally, it's also possible to define the file
extensions as `array`.

Another example without usage of the API method would therefore look like this:

..  code-block:: php

    // Before
    'columns' => [
        'image' => [
            'label' => 'My image',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_file_reference',
                'foreign_field' => 'uid_foreign',
                'foreign_sortby' => 'sorting_foreign',
                'foreign_table_field' => 'tablenames',
                'foreign_match_fields' => [
                    'fieldname' => 'image',
                ],
                'foreign_label' => 'uid_local',
                'foreign_selector' => 'uid_local',
                'overrideChildTca' => [
                    'columns' => [
                        'uid_local' => [
                            'config' => [
                                'appearance' => [
                                    'elementBrowserType' => 'file',
                                    'elementBrowserAllowed' => 'jpg,png,gif',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ],
    ],

    // After
    'columns' => [
        'image' => [
            'label' => 'My image',
            'config' => [
                'type' => 'file',
                'allowed' => ['jpg','png','gif'],
            ],
        ],
    ],

Together with the new TCA type, three new PSR-14 events have been introduced:

* :php:`TYPO3\CMS\Backend\Form\Event\CustomFileControlsEvent`
* :php:`TYPO3\CMS\Backend\Form\Event\ModifyFileReferenceControlsEvent`
* :php:`TYPO3\CMS\Backend\Form\Event\ModifyFileReferenceEnabledControlsEvent`

CustomFileControlsEvent
=======================

Listeners to this event will be able to add custom controls to a TCA type
:php:`file` field in FormEngine. This replaces the :php:`customControls`
hook option, which is only available for TCA type :php:`inline`.

The new event provides the following methods:

*   :php:`getResultArray()`: Returns the whole result array
*   :php:`setResultArray(array $resultArray)`: Allows to overwrite the result
    array, e.g. to add additional JS modules
*   :php:`getControls()`: Returns all configured custom controls
*   :php:`setControls()`: Overwrites the custom controls
*   :php:`addControl()`: Adds a custom control. It's recommended to set the
    optional :php:`$identifier` argument.
*   :php:`removeControl()`: Removes a custom control. This only works in case
    the custom control was added with an identifier.
*   :php:`getTableName()`: Returns the table name in question
*   :php:`getFieldName()`: Returns the field name in question
*   :php:`getDatabaseRow()`: Returns the database row of the record in question
*   :php:`getFieldConfig()`: Returns the fields' TCA configuration
*   :php:`getFormFieldIdentifier()`: Returns the form elements' identifier
*   :php:`getFormFieldName()`: Returns the form elements' name

.. note::

    Custom controls are always displayed below the file references. In contrast
    to the selectors, e.g. "Select & upload files" are custom controls
    independent of the :php:`readonly` and :php:`showFileSelectors` options.
    This means, you have full control in which scenario your custom controls
    are being displayed.

ModifyFileReferenceControlsEvent
================================

Listeners to this event will be able to modify the controls of a single
file reference of a TCA type `file` field. This event is similar to the
:php:`ModifyInlineElementControlsEvent`, which is only available for TCA
type `inline`. See corresponding PHP class or the other
:doc:`changelog <../12.0/Feature-97231-PSR-14EventsForModifyingInlineElementControls>`
for more information about available methods and their usage.

ModifyFileReferenceEnabledControlsEvent
=======================================

Listeners to this event will be able to modify the state (enabled or disabled)
for the controls of a single file reference of a TCA type `file` field. This
event is similar to the :php:`ModifyInlineElementEnabledControlsEvent`, which
is only available for TCA type `inline`. See corresponding PHP class or the
other :doc:`changelog <../12.0/Feature-97231-PSR-14EventsForModifyingInlineElementControls>`
for more information about available methods and their usage.

Impact
======

It's now possible to simplify the TCA configuration for file reference
fields, using the new TCA type `file`. Three new PSR-14 events allow to
modify available controls of the TCA field as well as the related file
references.

.. index:: Backend, FAL, PHP-API, TCA, ext:backend
