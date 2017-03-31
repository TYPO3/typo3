.. include:: ../../Includes.txt

==========================================================
Important: #80241 - EXT:form simplify translation handling
==========================================================

See :issue:`80241`

Description
===========

If an integrator wants to add translations for new form elements he can only
define a new translation file which must contain all translation keys.
This patch makes it possible to define multiple translation files.

Before this patch:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Form:
                  renderingOptions:
                    translation:
                      translationFile: 'EXT:form/Resources/Private/Language/locallang.xlf'

After this patch:

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formElementsDefinition:
                Form:
                  renderingOptions:
                    translation:
                      translationFile:
                        10: 'EXT:form/Resources/Private/Language/locallang.xlf'
                        20: 'EXT:my_ext/Resources/Private/Language/locallang.xlf'

The translation keys will be searched within the referenced files.
The search order is from the key with the highest number to the lowest.
If a translation key is found within one of these files the search will stop.

This makes it possible to only define new keys within the custom translations
and use the default form translations as well.
The default settings keep the translationFile property as string because
of backward compatibility.

Before this patch the "BaseFormElementMixin" inherits the "translationSettingsMixin".
Thus, the "renderingOptions.translation..." are copied to each form element.
This is inconvenient if an integrator defines his own prototype which inherits from
the standard prototype because he must redefine the "renderingOptions.translation..."
options for each form element.

Since there already is a fallback strategy to the "renderingOptions.translation..."
options from the root form element - if this option is not set within the
child form elements - we can simply apply the "translationSettingsMixin"
to the "Form" element and remove it from the "BaseFormElementMixin".
Now, the rendering options are only set for the "Form" element and rules
as a prototype wide frontend translation setting.

This patch adds a fallback for the form engine translation if there is no
"translationFile" setting within the "FormEngine" option.

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formEngine:
                translationFile:
                  10: 'EXT:form/Resources/Private/Language/Database.xlf'
                  20: 'EXT:ext_form_example1484232130/Resources/Private/Language/Database.xlf'

Now, there is one prototype wide form engine (plugin settings) translation setting.


Summary
-------

With this patch, an integrator has prototype wide translation settings
for the 4 aspects of the form framework. Furthermore, the integrator is
able to define multiple translation files to avoid copying the whole
default translation files or using locallangXMLOverride.

.. code-block:: yaml

    TYPO3:
      CMS:
        Form:
          formManager:
            selectablePrototypesConfiguration:
              1484232130:
                translationFile:
                  # translations for the form managers "new form" modal
                  10: 'EXT:form/Resources/Private/Language/Database.xlf'
                  20: 'EXT:my_ext/Resources/Private/Language/Database.xlf'

          prototypes:
            <prototypeName>:
              formEditor:
                translationFile:
                  # translations for the form editor
                  10: 'EXT:form/Resources/Private/Language/Database.xlf'
                  20: 'EXT:my_ext/Resources/Private/Language/Database.xlf'

              formEngine:
                translationFile:
                  # translations for the form plugin (finisher overrides)
                  10: 'EXT:form/Resources/Private/Language/Database.xlf'
                  20: 'EXT:my_ext/Resources/Private/Language/Database.xlf'

              formElementsDefinition:
                Form:
                  renderingOptions:
                    translation:
                      translationFile:
                        # translations for the frontend
                        10: 'EXT:form/Resources/Private/Language/locallang.xlf'
                        20: 'EXT:my_ext/Resources/Private/Language/locallang.xlf'


Impact
======

Easier to use, less maintenance.

.. index:: Backend, Frontend, ext:form
