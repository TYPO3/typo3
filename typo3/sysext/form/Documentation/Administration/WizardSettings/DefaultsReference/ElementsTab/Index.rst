.. include:: ../../../../Includes.txt


.. _wizard-settings-defaults-elements-tab:

============
Elements tab
============

The elements tab contains an accordion with buttons, grouped by their
type. These buttons identify a form element, like a text field, password
field or submit button. When dragging a button to the form on the right
and dropping it at a certain point in the form, the element will be added
to the form at that point. A user can also double click a button. When
doing so, the element will be added at the bottom of the form.

.. contents::
    :local:
    :depth: 1


.. _wizard-settings-defaults-elements-showaccordions:

showAccordions
==============

(:ts:`mod.wizards.form.defaults.tabs.elements.showAccordions`)

:aspect:`Property:`
    showAccordions

:aspect:`Data type:`
    string

:aspect:`Description:`
    Comma-separated list of the accordions that will be shown in the
    wizard. Each of the three accordions contain a single showButton
    property which defines which form elements will be shown in a
    given accordion.

:aspect:`Default:`
    basic, predefined, content


.. _wizard-settings-defaults-elements-accordions-showbuttons:

showButtons
===========

(:ts:`mod.wizards.form.defaults.tabs.elements.accordions.[NameOfAccordion].showButtons`)

:aspect:`Property:`
    showButtons

:aspect:`Data type:`
    string

:aspect:`Description:`
    Comma-separated list of the buttons that will be shown in the
    accordion. Please note, in the shown path has [NameOfAccordion]
    to be replaced with the name of the specific accordion.

:aspect:`Default:`
    **"basic" elements**

    - checkbox (Checkbox)
    - fieldset (Fieldset)
    - fileupload (Upload Field)
    - hidden (Hidden Field)
    - password (Password Field)
    - radio (Radio Button)
    - reset (Reset Button)
    - select (Drop Down)
    - submit (Submit Button)
    - textarea (Textarea)
    - textline (Text Field)

    Additionally, there is the element "button" available which is not visible by default.

    |

    **"predefined" elements**

    - email (Email)
    - radiogroup (Radio Button Group)
    - checkboxgroup (Checkbox Group)
    - name (Full Name)

    |

    **"content" elements**

    - header (Header)
    - textblock (Text Block)


.. _wizard-settings-defaults-elements-tab-configuration:

Default configuration
=====================

The default configuration of the elements tab is as follows.

.. code-block:: typoscript

  mod.wizards {
    form {
      defaults {
        tabs {
          elements {
            showAccordions = basic, predefined, content
            accordions {
              basic {
                showButtons = checkbox, fieldset, fileupload, hidden, password, radio, reset, select, submit, textarea, textline
              }
              predefined {
                showButtons = email, radiogroup, checkboxgroup, name
              }
              content {
                showButtons = header, textblock
              }
            }
          }
        }
      }
    }
  }

