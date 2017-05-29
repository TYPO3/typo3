.. include:: ../../Includes.txt

=======================================================================
Feature: #81363 - EXT:form - support form element translation arguments
=======================================================================

See :issue:`81363`

Description
===========

Passing arguments to form element property translations is now supported to enrich
translations with variable values:

.. code-block:: yaml

    renderables:
      fieldWithTranslationArguments:
        identifier: field-with-translation-arguments
        type: Checkbox
        label: This is a %s feature
        renderingOptions:
          translation:
            translationFile: path/to/locallang.xlf
            arguments:
              label:
                - useful

Alternatively, translation arguments can be set via :typoscript:`formDefinitionOverrides`
in TypoScript:

.. code-block: typoscript

    plugin.tx_form {
      settings {
        formDefinitionOverrides {
          <form-id> {
            renderables {
              0 { # Page
                renderables {
                  fieldWithTranslationArguments {
                    renderingOptions {
                      translation {
                        arguments {
                          label {
                            0 = TEXT
                            0.typolink {
                              parameter = 42
                              returnLast = url
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

.. important::
   There must be at least one translation file with a translation for the configured form element property. Arguments are not inserted into default values defined in a form definition.

The same goes for finisher options:

.. code-block:: yaml


    finishers:
      finisherWithTranslationArguments:
        identifier: EmailToReceiver
        options:
          subject: My %s subject
          recipientAddress: foo@example.org
          senderAddress: bar@example.org
          translation:
            translationFile: path/to/locallang.xlf
            arguments:
              subject:
                - awesome


Impact
======

Form element property translations and finisher option translations can now use placeholders
to output translation arguments.

.. index:: Frontend, TypoScript, NotScanned
