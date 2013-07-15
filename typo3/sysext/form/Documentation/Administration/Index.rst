.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _administration:

Administration
--------------

Upon installation, the extension will set default properties in the
page TSconfig in the variable :code:`mod.wizards`.

These properties may be modified for any particular page, BE user or
BE user group and are detailed below.


.. _default-new-record:

Default new record
^^^^^^^^^^^^^^^^^^

When a user makes a new FORM record, the bodytext will be filled by
default with some simple form settings which are displayed below. You
can change this setting to your specific needs, but remember this
string can't be big due to some core limitations for the bodytext of
new records. It's impossible to add the configuration for a complete
form, although it might be a simple one. This restriction is caused by
the fact the whole string is put in a URI as parameter.

.. code-block:: typoscript

   mod.wizards {
         newContentElement.wizardItems {
                forms.elements {
                       mailform {
                             tt_content_defValues {
                                         bodytext (
   enctype = application/x-www-form-urlencoded
   method = post
   prefix = tx_form
                                         )
                              }
                      }
              }
      }
   }


.. _wizard-settings:

Wizard settings
^^^^^^^^^^^^^^^

The wizard basically consists of two parts on the screen, the left
'settings' part and the right 'form' part. With TSconfig settings it
is possible to configure the contents of the left 'settings' part. You
can remove tabs, accordions or a specific setting for a single type of
form element, or for all element types at once.

The basic configuration has two settings: defaults and elements, which are described below.

.. _wizard-settings-defaults:

Defaults reference
""""""""""""""""""

Describe the settings for the visible tabs, the accordions available in
these tabs and the default configuration for all element types.


.. _wizard-settings-defaults-showtabs:

showTabs
~~~~~~~~

(:code:`mod.wizards.form.defaults.showTabs`)

.. container:: table-row

   Property
         showTabs

   Data type
         string

   Description
         Comma-separated list of the tabs that will be shown in the wizard

   Default
         elements, options, form



.. _wizard-settings-defaults-tabs:

tabs
~~~~

(:code:`mod.wizards.form.defaults.showTabs.tabs`)

.. container:: table-row

   Property
         tabs

   Data type
         [array of objects]

         ->tabs.[tabName]

   Description
         Configuration for the each tab.

         Example:

         ::

            mod.wizards {
				form {
					defaults {
						showTabs = elements, options, form
							tabs {
								elements {
									...
								}
								options {
									...
								}
								form {
									...
								}
							}
						}
					}
				}
            }


.. _wizard-settings-defaults-elements-tab:

Elements tab
~~~~~~~~~~~~

The elements tab contains an accordion with buttons, grouped by their
type. These buttons identify a form element, like a text field,
password field or submit button. When dragging a button to the form
and dropping it at a certain point in the form, the element will be
added to the form at that point. A user can also double click a
button. When doing so, the element will be added at the bottom of the
form.


.. _wizard-settings-defaults-elements-showaccordions:

showAccordions
''''''''''''''

(:code:`mod.wizards.form.defaults.showTabs.tabs.elements.showAccordions`)

.. container:: table-row

   Property
         showAccordions

   Data type
         string

   Description
         Comma-separated list of the accordions that will be shown in the
         wizard. Each of the three accordions contain a single showButton
         property which defines which form elements will be shown in a
         given accordion.

   Default
         basic, predefined, content


.. _wizard-settings-defaults-elements-accordions-showbuttons:

showButtons
'''''''''''

(:code:`mod.wizards.form.defaults.showTabs.tabs.elements.accordions.showButtons`)

.. container:: table-row

   Property
         showButtons

   Data type
         string

   Description
         Comma-separated list of the buttons that will be shown in the
         accordion

   Default
         for "basic":
         button, captcha, checkbox, fieldset, hidden, password, radio, reset,
         select, submit, textarea, textline

         |

         for "predefined":
         email, radiogroup, checkboxgroup, name

         |

         for "content":
         header


.. _wizard-settings-defaults-elements-tab-configuration:

Default configuration
'''''''''''''''''''''

The default configuration of the elements tab looks like:

.. code-block:: typoscript

   ...
       elements {
               showAccordions = basic, predefined, content
               accordions {
                       basic {
                               showButtons = button, captcha, checkbox, fieldset, hidden, password, radio, reset, select, submit, textarea, textline
                       }
                       predefined {
                               showButtons = email, radiogroup, checkboxgroup, name
                       }
                       content {
                               showButtons = header
                       }
               }
       }
   ...


.. _wizard-settings-defaults-options-tab:

Options tab
~~~~~~~~~~~

The options tab will show the configuration of a particular element in
the form. When no element has been selected, it will show a message
that you have to select an element in the form.

The content of this tab depends on the type of element you've chosen
in the form.


.. _wizard-settings-defaults-options-showaccordions:

showAccordions
''''''''''''''

(:code:`mod.wizards.form.defaults.showTabs.tabs.options.showAccordions`)

.. container:: table-row

   Property
         showAccordions

   Data type
         string

   Description
         Comma-separated list of the accordions that are allowed to be shown in
         the wizard. This does not mean they are all shown by default, but
         depends on the choosen element type.

         Some tabs have further configuration which is described below.

   Default
         legend, label, attributes, options, validation, filters, various


.. _wizard-settings-defaults-options-attributes:

Attributes accordion
''''''''''''''''''''


.. _wizard-settings-defaults-options-attributes-showproperties:

showProperties
**************

(:code:`mod.wizards.form.defaults.showTabs.tabs.options.attributes.showProperties`)

.. container:: table-row

   Property
         showProperties

   Data type
         string

   Description
         Comma-separated list of the attributes that are allowed to be shown in
         the accordion. The appearance of an attribute depends on the choosen
         element type. If an element type does not support an attribute, it
         will not be shown.

   Default
         accept, acceptcharset, accesskey, action, alt, checked, class, cols,
         dir, disabled, enctype, id, label, lang, maxlength, method, multiple,
         name, readonly, rows, selected, size, src, style, tabindex, title,
         type, value


.. _wizard-settings-defaults-options-label:

Label accordion
'''''''''''''''


.. _wizard-settings-defaults-options-label-showproperties:

showProperties
**************

(:code:`mod.wizards.form.defaults.showTabs.tabs.options.label.showProperties`)

.. container:: table-row

   Property
         showProperties

   Data type
         string

   Description
         Comma-separated list of the label options that are allowed to be shown
         in the accordion. The appearance of an option depends on the choosen
         element type. If an element type does not support an option, it will
         not be shown.

   Default
         label, layout


.. _wizard-settings-defaults-validation-label:

Validation accordion
''''''''''''''''''''


.. _wizard-settings-defaults-options-validation-showrules:

showRules
*********

(:code:`mod.wizards.form.defaults.showTabs.tabs.options.validation.showRules`)

.. container:: table-row

   Property
         showRules

   Data type
         string

   Description
         Comma-separated list of rules that are allowed to be shown in the
         wizard.

   Default
         alphabetic, alphanumeric, between, captcha, date, digit, email,
         equals, float, greaterthan, inarray, integer, ip, length, lessthan,
         regexp, required, uri


.. _wizard-settings-defaults-options-validation-rules:

rules.[rule].showProperties
***************************

(:code:`mod.wizards.form.defaults.showTabs.tabs.options.validation.rules.[rule].showProperties`)

.. container:: table-row

   Property
         rules.[rule].showProperties

   Data type
         [array of objects]

   Description
         For each rule we can define which properties should appear.
         The syntax is :code:`rules.[name of the rule].showProperties`.

   Default
         For "alphabetic":
         message, error, breakOnError, showMessage, allowWhiteSpace

         |

         For "alphanumeric":
         message, error, breakOnError, showMessage, allowWhiteSpace

         |

         For "between":
         message, error, breakOnError, showMessage, minimum, maximum, inclusive

         |

         For "date":
         message, error, breakOnError, showMessage, format

         |

         For "digit":
         message, error, breakOnError, showMessage

         |

         For "email":
         message, error, breakOnError, showMessage

         |

         For "equals":
         message, error, breakOnError, showMessage, field

         |

         For "fileallowedtypes":
         message, error, breakOnError, showMessage, types

         |

         For "filemaximumsize":
         message, error, breakOnError, showMessage, maximum

         |

         For "fileminimumsize":
         message, error, breakOnError, showMessage, minimum

         |

         For "float":
         message, error, breakOnError, showMessage

         |

         For "greaterthan":
         message, error, breakOnError, showMessage, minimum

         |

         For "inarray":
         message, error, breakOnError, showMessage, array, strict

         |

         For "integer":
         message, error, breakOnError, showMessage

         |

         For "ip":
         message, error, breakOnError, showMessage

         |

         For "length":
         message, error, breakOnError, showMessage, minimum, maximum

         |

         For "lessthan":
         message, error, breakOnError, showMessage, maximum

         |

         For "regexp":
         message, error, breakOnError, showMessage, expression

         |

         For "required":
         message, error, breakOnError, showMessage

         |

         For "uri":
         message, error, breakOnError, showMessage

         |


.. _wizard-settings-defaults-filters-label:

Filters accordion
'''''''''''''''''


.. _wizard-settings-defaults-options-filtering-showfilters:

showFilters
***********

(:code:`mod.wizards.form.defaults.showTabs.tabs.options.filters.showFilters`)

.. container:: table-row

   Property
         showFilters

   Data type
         string

   Description
         Comma-separated list of the filters that are allowed to be shown in
         the wizard.

         For each filter a list of properties to be shown can be defined.

   Default
         alphabetic, alphanumeric, currency, digit, integer, lowercase, regexp,
         removexss, stripnewlines, titlecase, trim, uppercase



.. _wizard-settings-defaults-options-filtering-filters:

filters.[filter].showProperties
*******************************

(:code:`mod.wizards.form.defaults.showTabs.tabs.options.filtering.filters.[filter].showProperties`)

.. container:: table-row

   Property
         filters.[filter].showProperties

   Data type
         string

   Description
         Configuration for the filters individually. Not all filters have a
         configuration. Only the filters who have are mentioned in the list of
         default values below.

         The syntax is :code:`filters.[name of the filter].showProperties`.

   Default
         For "alphabetic":
         allowWhiteSpace

         |

         For "alphanumeric":
         allowWhiteSpace

         |

         For "currency":
         decimalPoint, thousandSeparator

         |

         For "regexp":
         expression

         |

         For "trim":
         characterList


.. _wizard-settings-defaults-options-tab-configuration:

Default configuration
'''''''''''''''''''''

The default configuration of the options tab looks like this:

.. code-block:: typoscript

   ...
		options {
			showAccordions = legend, label, attributes, options, validation, filters, various
			accordions {
				attributes {
					showProperties = accept, acceptcharset, accesskey, action, alt, checked, class, cols, dir, disabled, enctype, id, label, lang, maxlength, method, multiple, name, readonly, rows, selected, size, src, style, tabindex, title, type, value
				}
				label {
					showProperties = label
				}
				validation {
					showRules = alphabetic, alphanumeric, between, date, digit, email, equals, fileallowedtypes, filemaximumsize, fileminimumsize, float, greaterthan, inarray, integer, ip, length, lessthan, regexp, required, uri
					rules {
						alphabetic {
							showProperties = message, error, breakOnError, showMessage, allowWhiteSpace
						}
						alphanumeric {
							showProperties = message, error, breakOnError, showMessage, allowWhiteSpace
						}
						between {
							showProperties = message, error, breakOnError, showMessage, minimum, maximum, inclusive
						}
						date {
							showProperties = message, error, breakOnError, showMessage, format
						}
						digit {
							showProperties = message, error, breakOnError, showMessage
						}
						email {
							showProperties = message, error, breakOnError, showMessage
						}
						equals {
							showProperties = message, error, breakOnError, showMessage, field
						}
						fileallowedtypes {
							showProperties = message, error, breakOnError, showMessage, types
						}
						filemaximumsize {
							showProperties = message, error, breakOnError, showMessage, maximum
						}
						fileminimumsize {
							showProperties = message, error, breakOnError, showMessage, minimum
						}
						float {
							showProperties = message, error, breakOnError, showMessage
						}
						greaterthan {
							showProperties = message, error, breakOnError, showMessage, minimum
						}
						inarray {
							showProperties = message, error, breakOnError, showMessage, array, strict
						}
						integer {
							showProperties = message, error, breakOnError, showMessage
						}
						ip {
							showProperties = message, error, breakOnError, showMessage
						}
						length {
							showProperties = message, error, breakOnError, showMessage, minimum, maximum
						}
						lessthan {
							showProperties = message, error, breakOnError, showMessage, maximum
						}
						regexp {
							showProperties = message, error, breakOnError, showMessage, expression
						}
						required {
							showProperties = message, error, breakOnError, showMessage
						}
						uri {
							showProperties = message, error, breakOnError, showMessage
						}
					}
				}
				filtering {
					showFilters = alphabetic, alphanumeric, currency, digit, integer, lowercase, regexp, removexss, stripnewlines, titlecase, trim, uppercase
					filters {
						alphabetic {
							showProperties = allowWhiteSpace
						}
						alphanumeric {
							showProperties = allowWhiteSpace
						}
						currency {
							showProperties = decimalPoint, thousandSeparator
						}
						digit {
							showProperties =
						}
						integer {
							showProperties =
						}
						lowercase {
							showProperties =
						}
						regexp {
							showProperties = expression
						}
						removexss {
							showProperties =
						}
						stripnewlines {
							showProperties =
						}
						titlecase {
							showProperties =
						}
						trim {
							showProperties = characterList
						}
						uppercase {
							showProperties =
						}
					}
				}
			}
		}
   ...


.. _wizard-settings-defaults-form-tab:

Form tab
~~~~~~~~

The form tab shows the configuration of the outer form, like the
attributes of the form or the prefix.


.. _wizard-settings-defaults-form-showaccordions:

showAccordions
''''''''''''''

(:code:`mod.wizards.form.defaults.showTabs.tabs.form.showAccordions`)

.. container:: table-row

   Property
         showAccordions

   Data type
         string

   Description
         Comma-separated list of the accordions that are allowed to be shown in
         the wizard. This does not mean they are all shown by default, but
         depends on the choosen element type.

         Some accordions have further properties, which are described below.

   Default
         behaviour, prefix, attributes, postProcessor


.. _wizard-settings-defaults-form-attributes:

Attributes accordion
''''''''''''''''''''


.. _wizard-settings-defaults-form-attributes-showproperties:

showProperties
**************

(:code:`mod.wizards.form.defaults.showTabs.tabs.form.accordions.attributes.showProperties`)

.. container:: table-row

   Property
         showProperties

   Data type
         string

   Description
         Comma-separated list of the form attributes that are allowed to be
         shown in the accordion.

   Default
         accept, acceptcharset, action, class, dir, enctype, id, lang, method,
         name, style, title


.. _wizard-settings-defaults-form-postprocessor:

Post-processors accordion
'''''''''''''''''''''''''


.. _wizard-settings-defaults-form-postprocessor-showpostprocessors:

showPostProcessors
******************

(:code:`mod.wizards.form.defaults.showTabs.tabs.form.accordions.postProcessor.showPostProcessors`)

.. container:: table-row

   Property
         showPostProcessors

   Data type
         string

   Description
         Comma-separated list of the post-processors that are allowed to be shown in
         the wizard.

         For each post-processors a list of properties to be shown can be defined.

   Default
         mail



.. _wizard-settings-defaults-options-postprocessor-postprocessors:

postProcessors.[post-processor].showProperties
**********************************************

(:code:`mod.wizards.form.defaults.showTabs.tabs.form.accordions.postProcessor.postProcessors.[post-processor].showProperties`)

.. container:: table-row

   Property
         postProcessors.[post-processor].showProperties

   Data type
         string

   Description
         Configuration for the post-processors individually.

         The syntax is :code:`postProcessors.[name of the post-processor].showProperties`.

   Default
         For "mail":
         recipientEmail, senderEmail, subject


.. _wizard-settings-defaults-form-tab-configuration:

Default configuration
'''''''''''''''''''''

The default configuration of the form tab looks like this:

.. code-block:: typoscript

   ...
		form {
			showAccordions = behaviour, prefix, attributes, postProcessor
			accordions {
				attributes {
					showProperties = accept, acceptcharset, action, class, dir, enctype, id, lang, method, name, style, title
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



.. _wizard-settings-elements:

Elements reference
""""""""""""""""""

Overrule the default settings of the :ref:`Option <wizard-settings-defaults-options-tab>`
tab for specific element types.

In the left "settings" part there is a tab called "options". The
contents of this tab will adapt itself to the selected element type in
the form. If no elements configuration is used, the default settings
will be used.


.. _overriding-element-settings:

Overriding element settings
~~~~~~~~~~~~~~~~~~~~~~~~~~~

It's possible to override the default option tab settings for each
element individually. This is done by using the same configuration as
in :code:`mod.wizards.form.defaults.tabs.options`, but using this
configuration in :code:`mod.wizards.form.elements.[elementName]`.

The example below will hide all the accordions within the option tab
for a fieldset element, except the legend:

.. code-block:: typoscript

   mod.wizards.form.element {
    fieldset {
               showAccordions = legend
       }
   }

By using this setting you can show or hide accordions, attributes,
validation rules or filters, for each and every individual element.

