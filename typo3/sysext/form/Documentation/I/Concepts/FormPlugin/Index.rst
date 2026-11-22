..  include:: /Includes.rst.txt


..  _concepts-formplugin:

===========
Form plugin
===========


..  _concepts-formplugin-general:

What does it do?
================

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

..  _concepts-formplugin-edit-in-form-editor:

Editing the selected form
=========================

..  versionadded:: 15.0
    :changelog: feature-111457-1794680900

    Before, the form plugin only allowed selecting a form definition.

The button :guilabel:`Edit in form editor` below the form definition opens
the selected form in the form editor. Closing the form editor returns to the
content element. If the content element was edited in the contextual edit
sheet of the page module, it returns to the page module. Before the form
editor opens, TYPO3 asks to confirm unsaved changes of the content element.
If an editor selects another form, TYPO3 saves and reloads the content
element, and the button then opens the new form.

TYPO3 shows the button in content elements that are already saved, to
backend users who may edit the selected form:

*   The user has access to the form editor module.
*   The user has write access to the storage of the form. For the
    `database storage <https://docs.typo3.org/permalink/typo3/cms-form:concepts-form-storages-database>`_,
    that is write access to the table `form_definition`.
*   For a form stored in an extension, the option
    `persistenceManager.allowSaveToExtensionPaths` is enabled.

If more than one content element uses the form, a notice below the button
says how many. In a workspace, a notice says that forms are not versioned.
A change in the form editor affects all content elements that use the form,
in every workspace.

Editors find the steps in the
`quick start for editors <https://docs.typo3.org/permalink/typo3/cms-form:quickstarteditors-edit-in-form-editor>`_.


..  _concepts-formplugin-exclude-override:

Exclude options from overrides
==============================

Sometimes it is useful to prevent options from being overridden by the
form plugin. You can do this by unsetting the options in your
general forms configuration YAML. To unset options use the YAML NULL (:yaml:`~`) value.

In this example, four ``EmailToReceiver`` finisher fields are unset. The
options will be removed from the form plugin but not the form editor.

..  code-block:: yaml

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


..  _concepts-formplugin-translation-formengine:

Translation of form plugin
==========================

Labels, descriptions, placeholders, section titles and item labels of the
finisher options under the following configuration key can be translated:

..  code-block:: yaml

    prototypes:
      standard:
        finishersDefinition:
          <finisherIdentifier>
            formEngine:

``Form plugin`` translation files are loaded as follows:

..  code-block:: yaml

    prototypes:
      standard:
        formEngine:
          translationFiles:
            # custom translation file
            20: 'EXT:my_site_package/Resources/Private/Language/Database.xlf'

The values of :yaml:`label`, :yaml:`description`,
:yaml:`config.placeholder`, :yaml:`title`, :yaml:`items.*.0` and
:yaml:`config.itemGroups.*` are searched
for in the defined translation files. If a translation is found, the translated
value will be used. All other values, for example the values of select items,
are used as configured.

Imagine that the following option value is defined:

..  code-block:: yaml

    ...
    label: 'tt_content.finishersDefinition.EmailToReceiver.label'
    ...

The translation key
``tt_content.finishersDefinition.EmailToReceiver.label`` is first searched for in the file
``20: 'EXT:my_site_package/Resources/Private/Language/Database.xlf'`` and
then in the file 10: 'EXT:form/Resources/Private/Language/Database.xlf'
(loaded by EXT:form by default). If nothing is found, the option value will be
displayed unmodified.
