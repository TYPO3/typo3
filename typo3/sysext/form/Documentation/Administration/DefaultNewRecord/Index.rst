.. include:: ../../Includes.txt


.. _default-new-record:

==================
Default new record
==================

When an editor creates a new FORM record, the bodytext will be filled by
default with some simple form settings which are displayed below. The
integrator can change this setting to specific needs, but this string
**cannot be big due to some core limitations**. It is impossible to add
the configuration for a complete form, although it might be a simple one.
This restriction is caused by the fact that the whole string is put in
a URI as parameter.

Furthermore this only works when the editor is using the web module, i.e.
the functionality is not supported by the list module.

.. code-block:: typoscript

  mod.wizards {
    newContentElement.wizardItems {
      forms.elements {
        mailform {
          tt_content_defValues {
            bodytext (
  enctype = multipart/form-data
  method = post
  prefix = tx_form
            )
          }
        }
      }
    }
  }

