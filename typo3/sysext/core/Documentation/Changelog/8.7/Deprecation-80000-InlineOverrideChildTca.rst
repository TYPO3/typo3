.. include:: /Includes.rst.txt

============================================
Deprecation: #80000 - InlineOverrideChildTca
============================================

See :issue:`80000`

Description
===========

These :php:`TCA` :php:`type=inline` properties have been deprecated and superseded with the more
general property :php:`overrideChildTca`:

* foreign_types
* foreign_selector_fieldTcaOverride
* foreign_record_defaults


Impact
======

It is now possible to override display (FormEngine) related columns properties and the types section of
child :php:`TCA` from within the parent :php:`TCA`. This is also allowed in a
parents :php:`['types']['columnsOverrides']` section.


Affected Installations
======================

Instances using one of the above inline properties should adapt to the new :php:`overrideChildTca` property.


Migration
=========

A :php:`TCA` auto-migration is in place. It will transfer the old settings to the new property as
shown below and logs deprecation entries if there is no :php:`overrideChildTca` defined. This allows extension
authors to keep both the old and the new settings to support CMS v7 and v8 at the same time without
having deprecations logged.

foreign_types before and after transition to overrideChildTca:

.. code-block:: php

    'columns' => [
        'aField' => [
            'config' => [
                'type' => 'inline',
                'foreign_types' => [
                    'aForeignType' => [
                        'showitem' => 'aChildField',
                    ],
                ],
                ...
            ],
        ],
        ...
    ],

.. code-block:: php

    'columns' => [
        'aField' => [
            'config' => [
                'type' => 'inline',
                'overrideChildTca' => [
                    'types' => [
                        'aForeignType' => [
                            'showitem' => 'aChildField',
                        ],
                    ],
                ],
                ...
            ],
        ],
        ...
    ],

foreign_selector_fieldTcaOverride before and after transition to overrideChildTca:

.. code-block:: php

    'columns' => [
        'aField' => [
            'config' => [
                'type' => 'inline',
                'foreign_selector' => 'uid_local',
                'foreign_selector_fieldTcaOverride' => [
                    'config' => [
                        'appearance' => [
                            'elementBrowserType' => 'file',
                        ],
                    ],
                ],
                ...
            ],
        ],
        ...
    ],

.. code-block:: php

    'columns' => [
        'aField' => [
            'config' => [
                'type' => 'inline',
                'foreign_selector' => 'uid_local',
                'overrideChildTca' => [
                    'columns' => [
                        'uid_local' => [
                            'config' => [
                                'appearance' => [
                                    'elementBrowserType' => 'file',
                                ],
                            ],
                        ],
                    ],
                ],
                ...
            ],
        ],
        ...
    ],


foreign_record_defaults before and after transition to overrideChildTca:

.. code-block:: php

    'columns' => [
        'aField' => [
            'config' => [
                'type' => 'inline',
                'foreign_record_defaults' => [
                    'aChildField' => 42,
                ],
                ...
            ],
        ],
        ...
    ],

.. code-block:: php

    'columns' => [
        'aField' => [
            'config' => [
                'type' => 'inline',
                'overrideChildTca' => [
                    'columns' => [
                        'aChildField' => [
                            'config' => [
                                'default' => 42,
                            ],
                        ],
                    ],
                ],
                ...
            ],
        ],
        ...
    ],


.. index:: Backend, TCA
