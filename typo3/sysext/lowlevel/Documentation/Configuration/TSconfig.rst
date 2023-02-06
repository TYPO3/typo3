.. include:: /Includes.rst.txt

.. _configuration-tsconfig:

========
TSconfig
========

.. _configuration-full-search:

User TSconfig of the module "Full Search"
=========================================

The module :ref:`System > DB Check > Full Search <module-db-check-full-search>`
can be configured with the following
:ref:`User TSconfig <t3tsconfig:usertsconfig>`.

See also the chapter of :ref:`Setting user TSconfig
<t3tsconfig:setting-page-tsconfig>`.

.. include:: /Images/AutomaticScreenshots/Modules/DB_Check_Advanced_query_tt_content.rst.txt

.. confval:: disableStoreControl

    :Path: mod.dbint
    :type: bool
    :Default: false

    By default administrators can store and load their search configurations.

    Disable the display of the load and save configuration controls:

    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableStoreControl = 1
            disableSelectATable = 0
        }

.. confval:: disableShowSQLQuery

    :Path: mod.dbint
    :type: bool
    :Default: false

    By default the SQL query used for searching the records is displayed
    for successful searches and in case there are errors.

    If this option is set to true the raw SQL query of the search is
    not displayed in either case:

    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableShowSQLQuery = 1
        }

.. confval:: disableSelectATable

    :Path: mod.dbint
    :type: bool
    :Default: false

    If this option is set to true the affected administrators cannot select a
    table in which to search. This makes sense when there are prepared saved
    queries that should be used by the administrator but she should not be able
    to create new ones. If set to true :confval:`disableStoreControl` should be
    set to false, otherwise the module
    :guilabel:`System > DB Check > Full Search > Advanced query` cannot be used
    at all by the affected administrator.

    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableStoreControl = 0
            disableSelectATable = 1
        }

.. confval:: disableSelectFields

    :Path: mod.dbint
    :type: bool
    :Default: false

    By default the fields :sql:`uid` and the field specified as
    :ref:`label in the TCA <t3tca:columns-properties-label>` are selected by
    default. The users can choose different fields to be selected.

    By setting this configuration value to true the user can only use the
    default fields or the fields specified by a saved query and cannot change
    them.

    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableSelectFields = 1
        }

.. confval:: disableMakeQuery

    :Path: mod.dbint
    :type: bool
    :Default: false

    Disables the :guilabel:`Make Query` section. Results cannot be filtered
    by fields then, unless saved queries are provided.

    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableMakeQuery = 1
        }

.. confval:: disableGroupBy

    :Path: mod.dbint
    :type: bool
    :Default: false

    Disables the group by functionality.

    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableGroupBy = 1
        }

.. confval:: disableOrderBy

    :Path: mod.dbint
    :type: bool
    :Default: false

    Disables the order by functionality.


    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableOrderBy = 1
        }

.. confval:: disableLimit

    :Path: mod.dbint
    :type: bool
    :Default: false

    Disables changing the limit. The default limit is 100 records.

    .. code-block:: typoscript
        :caption: User TSconfig

        mod.dbint {
            disableLimit = 1
        }
