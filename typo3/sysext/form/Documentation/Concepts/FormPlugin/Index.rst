.. include:: ../../Includes.txt


.. _concepts-formplugin:

Form plugin
===========


.. _concepts-formelugin-general:

What does it do?
----------------

The form plugin allows you to assign a form - created with the ``form
editor`` or shipped with your extension - to a specific page. This enables
you to re-use forms throughout the whole TYPO3 installation. Furthermore, it
offers the backend editor the possibility to override certain aspects of the
form definition. At the moment, only finisher options can be overridden. The
possibilities depend on the configuration of the underlying prototype.

Imagine, your form contains a redirect finisher. The redirect target is set
globally and valid for the whole ``form definition`` . While adding the form
to a specific page, the backend editor can define a different redirect targeting. This
setting is only valid for the page containing the plugin.

Read more about changing the :ref:`general<typo3.cms.form.prototypes.\<prototypeidentifier>.formengine>`
and :ref:`aspect-specific form plugin configuration<typo3.cms.form.prototypes.\<prototypeIdentifier>.finishersdefinition.\<finisheridentifier>.formengine>`.


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
               translationFile:
                 # translation files for the form plugin (finisher overrides)
                 10: 'EXT:form/Resources/Private/Language/Database.xlf'
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
20: 'EXT:my_site_package/Resources/Private/Language/Database.xlf' and after
it inside the file 10: 'EXT:form/Resources/Private/Language/Database.xlf'.
If nothing is found, the option value will be displayed unmodified.

Due to compatibility issues, the setting ``translationFile`` is not defined
as an array in the default configuration. To load your own translation files,
you should define an array containing 'EXT:form/Resources/Private/Language/Database.xlf'
as first entry (key ``10``) followed by your own file (key ``20``) as
displayed in the example above.
