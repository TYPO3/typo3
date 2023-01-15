.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

If the TypoScript property :t3-typoscript:`config.admPanel` is set,
the admin panel is displayed at the bottom of pages in the frontend for
logged-in backend users.

By default, the Admin Panel is displayed to logged-in admins only. This behaviour
can be changed by setting :t3-user-tsconfig:`admPanel.enable` for certain
backend users or groups.

.. contents:: **Available settings**
   :depth: 2
   :local:


TypoScript settings
===================

The following settings can be made in the project's TypoScript setup. See also
:ref:`Using and setting TypoScript <t3tsref:using-and-setting>`.

.. _typoscript-config-admpanel:

config.admPanel
---------------

..  t3-typoscript:: config.admPanel

    :Type: boolean
    :Default: false

    If set, the Admin Panel displays at the bottom of pages. This applies only
    to logged-in admins or backend users with
    :t3-user-tsconfig:`admPanel.enable` enabled.

    ..  rubric:: Example

    ..  code-block:: typoscript
        :caption: EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript

        config.admPanel = 1

User TSconfig settings
======================

The options below can be set in the user TSconfig of a backend backend user or
group. See also
:ref:`Using and setting user TSconfig <t3tsconfig:setting-user-tsconfig>`.

admPanel.enable
---------------

..  t3-user-tsconfig:: admPanel.enable

    :Type: array<string, boolean>
    :Default: For admin users, `admPanel.enable.all = 1` is default.

    Used to enable the various parts of the Admin Panel for users.
    The keyword :typoscript:`all` can be used to enable all options
    for the user:

    ..  code-block:: typoscript
        :caption: EXT:my_sitepackage/Configuration/user.tsconfig

        admPanel.enable.all = 1

    To enable or disable single parts of the Admin Panel, use the following
    array keys:

    ..  code-block:: typoscript
        :caption: EXT:my_sitepackage/Configuration/user.tsconfig

        admPanel.enable {
          cache = 1
          debug = 1
          edit = 0
          info = 1
          preview = 1
          publish = 0
          tsdebug = 1
        }

admPanel.hide
-------------

..  t3-user-tsconfig:: admPanel.hide

    :Type: boolean

    If set, the Admin Panel will not be displayed at the bottom of the page.
    This only has a visual effect.

    ..  code-block:: typoscript
        :caption: EXT:site_package/Configuration/user.tsconfig

        admPanel.hide = 1


admPanel.override
-----------------

..  t3-user-tsconfig:: admPanel.override

    :Type: object

    Override single Admin Panel settings:

    ..  code-block:: typoscript
        :caption: EXT:site_package/Configuration/user.tsconfig

        admPanel.override.[modulename].[propertyname]

    You have to activate a module first by setting

    ..  code-block:: typoscript
        :caption: EXT:site_package/Configuration/user.tsconfig

        admPanel.override.[modulename] = 1

    ..  rubric:: Most common options with example values

    ..  code-block:: typoscript
        :caption: EXT:site_package/Configuration/user.tsconfig

        admPanel.override {
            preview {
                showFluidDebug = 1
                showHiddenPages = 1
                showHiddenRecords = 1
                simulateDate = 1673688448
                simulateUserGroup = 42
            }
            cache.noCache = 1
            tsdebug.forceTemplateParsing = 1
        }
