.. include:: /Includes.rst.txt


.. _concepts-formplugin:

Form plugin
===========


.. _concepts-formplugin-general:

What does it do?
----------------

The ``form plugin`` allows you to assign a form - created with the ``form
editor`` or shipped with your extension - to a specific page. This enables
you to re-use forms throughout the whole TYPO3 installation. Furthermore, it
offers the backend editor the possibility to override certain aspects of the
form definition. At the moment, only finisher options can be overridden. The
possibilities depend on the configuration of the underlying prototype.

Imagine, your form contains a redirect finisher. The redirect target is set
globally and valid for the whole ``form definition`` . While adding the form
to a specific page, the backend editor can define a different redirect target. This
setting is only valid for the page containing the plugin.

Read more about changing the :ref:`general<typo3.cms.form.prototypes.\<prototypeidentifier>.formengine>`
and :ref:`aspect-specific form plugin configuration<typo3.cms.form.prototypes.\<prototypeIdentifier>.finishersdefinition.\<finisheridentifier>.formengine>`.


.. _concepts-formplugin-exclude-override:

Exclude options from overrides
------------------------------

Sometimes, it is useful to exclude specific options from being overridden via the
form plugin. This can be achieved by unsetting the options concerned in your
custom YAML configuration. For unsetting options use the YAML NULL (:yaml:`~`) value.

The following example unsets four fields of the ``EmailToReceiver`` finisher. The
options will only be removed from the form plugin. The Form editor is not affected
by this.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             finishersDefinition:
               EmailToReceiver:
                 FormEngine:
                   elements:
                     senderAddress: ~
                     senderName: ~
                     replyToRecipients: ~
                     translation: ~


.. _concepts-formplugin-translation-formengine:

Translation of form plugin
--------------------------

All option values which reside below the following configuration keys can be
translated:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             finishersDefinition:
               <finisherIdentifier>
                 formEngine:

The translation files of the ``form plugin`` are loaded as follows:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formEngine:
               translationFiles:
                 # custom translation file
                 20: 'EXT:my_site_package/Resources/Private/Language/Database.xlf'

The process searches for each option value within all of the defined
translation files. If a translation is found, the translated option value
will be used in preference.

Imagine, the following is defined for an option value:

.. code-block:: yaml

   ...
   label: 'tt_content.finishersDefinition.EmailToReceiver.label'
   ...

First of all, the process searches for the translation key
``tt_content.finishersDefinition.EmailToReceiver.label`` within the file
``20: 'EXT:my_site_package/Resources/Private/Language/Database.xlf'`` and
afterwards inside the file 10: 'EXT:form/Resources/Private/Language/Database.xlf'
(loaded by default). If nothing is found, the option value will be
displayed unmodified.
