..  include:: /Includes.rst.txt

..  _user-tsconfig:

User TSconfig
=============

The module can be configured via :ref:`user TSconfig <setting-user-tsconfig>`
for backend users or groups:

..  _recordspagelimit:

..  option:: recordsPageLimit

    :Type: integer
    :Default: 25

    The number of records displayed per page.

    ..  rubric:: Example

    ..  code-block:: typoscript
        :caption: EXT:my_sitepackage/Configuration/user.tsconfig

        # Display 100 records per page
        mod.recycler.recordsPageLimit = 100


..  _allowdelete:

..  option:: allowDelete

    :Type: boolean
    :Default: 0

    By default, editors are not allowed to delete records. Enabling this
    option allows the editors to delete records from the database permanently.

    ..  rubric:: Example

    ..  code-block:: typoscript
        :caption: EXT:my_sitepackage/Configuration/user.tsconfig

        # Allow the editors to delete records
        mod.recycler.allowDelete = 1
