.. include:: /Includes.rst.txt

.. _feature-101843-1693895770:

======================================================================
Feature: #101843 - Allow configuration of color palettes in FormEngine
======================================================================

See :issue:`101843`

Description
===========

TYPO3 uses a color picker component that already supports color palettes, or
swatches. Integrators are now able to configure colors and assign colors to
palettes. Palettes then may be used within FormEngine.

Impact
======

In case commonly used colors or, for example, colors defined in a corporate design should
be made accessible in an easy way, integrators may configure multiple color palettes
to be used in FormEngine via page TSconfig.

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    # Configure colors and assign colors to palettes
    colorPalettes {
      colors {
        typo3 {
          value = #ff8700
        }
        blue {
          value = #0080c9
        }
        darkgray {
          value = #515151
        }
        valid {
          value = #5abc55
        }
        error {
          value = #dd123d
        }
      }
      palettes {
        main = typo3
        key_colors = typo3, blue, darkgray
        messages = valid, error
      }
    }

    # Assign palette to a specific field
    TCEFORM.[table].[field].colorPalette = messages

    # Assign palette to all color pickers used in a table
    TCEFORM.[table].colorPalette = key_colors

    # Assign global palette
    TCEFORM.colorPalette = main

Configuration allows to define the color palette either on a specific field of
a table, for all fields within a table or a global configuration affecting all
color pickers within FormEngine. If no palette is defined, FormEngine falls
back to all configured colors.

.. index:: Backend, TSConfig, ext:backend
