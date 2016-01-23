.. include:: ../../../../Includes.txt


.. _wizard-settings-defaults-form-tab:

========
Form tab
========

The form tab shows the configuration of the outer form, like the
attributes of the form or the prefix.

.. contents::
    :local:
    :depth: 1


.. _wizard-settings-defaults-form-showaccordions:

showAccordions
==============

(:ts:`mod.wizards.form.defaults.tabs.form.showAccordions`)

:aspect:`Property:`
    showAccordions

:aspect:`Data type:`
    string

:aspect:`Description:`
    Comma-separated list of the accordions that are allowed to be shown in
    the wizard. This does not mean they are all shown by default, but
    depends on the chosen element type.

    Some accordions have further properties, which are described below.

:aspect:`Default:`
    The following accordions are available in the form tab:

    * behaviour
    * prefix
    * attributes :ref:`> to section <wizard-settings-defaults-form-attributes>`
    * postProcessor :ref:`> to section <wizard-settings-defaults-form-postprocessor>`


.. _wizard-settings-defaults-form-attributes:

Attributes accordion
====================


.. _wizard-settings-defaults-form-attributes-showproperties:

.. attention::

    The whole configuration of the attributes accordion is not working
    correctly and has to be fixed in a coming version of TYPO3. There is a
    workaround which can be found :ref:`below <wizard-settings-defaults-form-workaround>`.

showProperties
--------------

(:ts:`mod.wizards.form.defaults.tabs.form.accordions.attributes.showProperties`)

:aspect:`Property:`
    showProperties

:aspect:`Data type:`
    string

:aspect:`Description:`
    Comma-separated list of the form attributes that are allowed to be shown
    in the accordion.

:aspect:`Default:`
    accept, accept-charset, action, class, dir, enctype, id, lang, method,
    name, style, title


.. _wizard-settings-defaults-form-postprocessor:

Post-processors accordion
=========================


.. _wizard-settings-defaults-form-postprocessor-showpostprocessors:

showPostProcessors
------------------

(:ts:`mod.wizards.form.defaults.tabs.form.accordions.postProcessor.showPostProcessors`)

:aspect:`Property:`
    showPostProcessors

:aspect:`Data type:`
    string

:aspect:`Description:`
   Comma-separated list of the post-processors that are allowed to be shown
   in the wizard.

   For each post-processors a list of properties to be shown can be defined.

:aspect:`Default:`
    mail, redirect


.. _wizard-settings-defaults-options-postprocessor-postprocessors:

postProcessors.[post-processor].showProperties
----------------------------------------------

(:ts:`mod.wizards.form.defaults.tabs.form.accordions.postProcessor.postProcessors.[post-processor].showProperties`)

:aspect:`Property:`
    postProcessors.[post-processor].showProperties

:aspect:`Data type:`
    string

:aspect:`Description:`
    Configuration for the post-processors individually.

    The syntax is :ts:`postProcessors.[name of the post-processor].showProperties`.

:aspect:`Default:`
    The following element properties are available:

    .. t3-field-list-table::
        :header-rows: 1

        - :Field:
                Element:
          :Description:
                Properties:
        - :Field:
                mail
          :Description:
                recipientEmail, senderEmail, subject
        - :Field:
                redirect
          :Description:
                destination


.. _wizard-settings-defaults-form-tab-configuration:

Default configuration
=====================

The default configuration of the form tab looks as follows:

.. code-block:: typoscript

  ...
  form {
    showAccordions = behaviour, prefix, attributes, postProcessor
    accordions {
      attributes {
        showProperties = accept, accept-charset, action, class, dir, enctype, id, lang, method, name, style, title
      }
      postProcessor {
        showPostProcessors = mail
        postProcessors {
          mail {
            showProperties = recipientEmail, senderEmail, subject
          }
        }
      }
    }
  }
  ...


.. _wizard-settings-defaults-form-workaround:

Workaround for configuration of the attributes accordion
========================================================

Since the above mentioned configuration of the attributes accordion is not
working as expected the following workaround is possible. Addressing (:ts:`mod.wizards.form.elements.form.accordions.attributes`)
allows to modify the attributes accordion. The example below illustrates
the procedure.

.. code-block:: typoscript

  mod.wizards {
    form {
      elements {
        form {
          accordions {
            attributes {
              showProperties = accept, accept-charset, action, class, dir, enctype, id, lang, method, name, style, title
            }
          }
        }
      }
    }
  }

