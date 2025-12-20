.. include:: /Includes.rst.txt


.. _concepts-formplugin:

Form plugin
===========


.. _concepts-formplugin-general:

What does it do?
----------------

The ``form plugin`` allows you to assign a form to a page and view it in the
frontend. The form can have been created via the ``form editor`` or shipped with
your extension. Forms can be re-used throughout the TYPO3 installation and backend editors
can override form definitions. At the moment, only finisher options can be overridden but the
possibilities depend on the configuration of the underlying prototype.

Imagine that your form contains a redirect finisher. The redirect target is set
globally and valid for the whole ``form definition``. When they are adding the form
to a page, a backend editor can define a redirect target that is different to the
'global' form definition. This setting is only valid on the page containing the plugin.

Read more about changing :ref:`general<prototypes.prototypeIdentifier.formengine>`
and :ref:`specific form plugin configuration<prototypes.prototypeIdentifier.finishersdefinition.finisheridentifier.formengine>`.


.. _concepts-formplugin-exclude-override:

Exclude options from overrides
------------------------------

Sometimes it is useful to prevent options from being overridden by the
form plugin. You can do this by unsetting the options in your
general forms configuration YAML. To unset options use the YAML NULL (:yaml:`~`) value.

In this example, four ``EmailToReceiver`` finisher fields are unset. The
options will be removed from the form plugin but not the form editor.

.. code-block:: yaml

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

All option values under the following configuration keys can be
translated:

.. code-block:: yaml

   prototypes:
     standard:
       finishersDefinition:
         <finisherIdentifier>
           formEngine:

``Form plugin`` translation files are loaded as follows:

.. code-block:: yaml

   prototypes:
     standard:
       formEngine:
         translationFiles:
           # custom translation file
           20: 'EXT:my_site_package/Resources/Private/Language/Database.xlf'

Each option value is searched for in the defined
translation files. If a translation is found, the translated option value
will be used.

Imagine that the following option value is defined:

.. code-block:: yaml

   ...
   label: 'tt_content.finishersDefinition.EmailToReceiver.label'
   ...

The translation key
``tt_content.finishersDefinition.EmailToReceiver.label`` is first searched for in the file
``20: 'EXT:my_site_package/Resources/Private/Language/Database.xlf'`` and
then in the file 10: 'EXT:form/Resources/Private/Language/Database.xlf'
(loaded by EXT:form by default). If nothing is found, the option value will be
displayed unmodified.
