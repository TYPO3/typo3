..  include:: /Includes.rst.txt

..  _cli-commands:

===========
CLI command
===========

Target group: **Integrators**

A :ref:`console command <t3coreapi:symfony-console-commands>` is provided by the
:doc:`lowlevel <ext_lowlevel:Index>` system extension. It
permanently deletes records from the database. Options can be added to the
command as below.

The command can be executed on the console:

..  tabs::

    ..  group-tab:: Composer-based installation

        ..  code-block:: bash

            # Remove all deleted records
            vendor/bin/typo3 cleanup:deletedrecords

    ..  group-tab:: Legacy installation

        ..  code-block:: bash

            # Remove all deleted records
            typo3/sysext/core/bin/typo3 cleanup:deletedrecords

It provides the following options:

..  _console-command-option-depth:

..  option:: --depth

    :Shortcut: -d
    :Type: integer
    :Default: `1000`

    Sets the traversal depth. `0` (zero) will only analyze the start page (see
    :ref:`--pid <console-command-option-pid>`), `1` will traverse one level of
    subpages, etc.

    ..  rubric:: Example

    ..  tabs::

        ..  group-tab:: Composer-based installation

            ..  code-block:: bash

                # Remove all deleted records, starting from the page tree root
                # with a maximum level of 3
                vendor/bin/typo3 cleanup:deletedrecords --depth=3
                vendor/bin/typo3 cleanup:deletedrecords -d=3

        ..  group-tab:: Legacy installation

            ..  code-block:: bash

                # Remove all deleted records, starting from the page tree root
                # with a maximum level of 3
                typo3/sysext/core/bin/typo3 cleanup:deletedrecords --depth=3
                typo3/sysext/core/bin/typo3 cleanup:deletedrecords -d=3

..  _console-command-option-dryrun:

..  option:: --dry-run

    :Default: `null`

    If this option is set the records will not actually be deleted but records
    which would be deleted are shown.

    ..  rubric:: Example

    ..  tabs::

        ..  group-tab:: Composer-based installation

            ..  code-block:: bash

                vendor/bin/typo3 cleanup:deletedrecords --dry-run

        ..  group-tab:: Legacy installation

            ..  code-block:: bash

                typo3/sysext/core/bin/typo3 cleanup:deletedrecords --dry-run

..  _console-command-option-minage:

..  option:: --min-age

    ..  versionadded:: 12.3

    :Shortcut: -m
    :Type: integer
    :Default: `0`

    The minimum age in days records need to be marked for deletion before
    actually removing them.

    ..  rubric:: Example

    ..  tabs::

        ..  group-tab:: Composer-based installation

            ..  code-block:: bash

                # Remove all deleted records older than 90 days
                vendor/bin/typo3 cleanup:deletedrecords --min-age=90
                vendor/bin/typo3 cleanup:deletedrecords -m=90

        ..  group-tab:: Legacy installation

            ..  code-block:: bash

                # Remove all deleted records older than 90 days
                typo3/sysext/core/bin/typo3 cleanup:deletedrecords --min-age=90
                typo3/sysext/core/bin/typo3 cleanup:deletedrecords -m=90


..  _console-command-option-pid:

..  option:: --pid

    :Shortcut: -p
    :Type: integer
    :Default: `0` (page tree root)

    Sets the start page in the page tree.

    ..  rubric:: Example

    ..  tabs::

        ..  group-tab:: Composer-based installation

            ..  code-block:: bash

                # Remove all deleted records from the page with UID 42
                vendor/bin/typo3 cleanup:deletedrecords --pid=42
                vendor/bin/typo3 cleanup:deletedrecords -p=42

        ..  group-tab:: Legacy installation

            ..  code-block:: bash

                # Remove all deleted records from the page with UID 42
                typo3/sysext/core/bin/typo3 cleanup:deletedrecords --pid=42
                typo3/sysext/core/bin/typo3 cleanup:deletedrecords -p=42

..  seealso::
    :ref:`scheduler-tasks`
