..  include:: /Includes.rst.txt

..  _important-87675-1737627592:

====================================================================================
Important: #87675 - Translation behavior of overridden form finisher options changed
====================================================================================

See :issue:`87675`

Description
===========

EXT:form finisher options provide configuration for rendering them as
FormEngine elements inside FlexForm overrides of the form plugin. This
configuration resides in
:yaml:`prototypes.<prototype>.finishersDefinition.<finisherIdentifier>.FormEngine`.

Previously, the whole configuration was passed through the translation of
EXT:form. Technical values were translated as well whenever a label with the
same key existed. The item value :yaml:`default` of the
:yaml:`translation.language` option of the email finishers was stored as its
backend label, for example "Default" or "Standard", depending on the language
of the editor. Such a value does not match any configured item, and emails
used the default language regardless of the language of the website.

Only the following configuration paths are translated now:

*   :yaml:`label`: finisher and element labels, including the labels of
    associative select items (:yaml:`items.*.label`)
*   :yaml:`description`: element descriptions
*   :yaml:`config.placeholder`: element placeholders
*   :yaml:`title`: section titles, for example the recipients of email
    finishers
*   :yaml:`items.*.0`: labels of items in the legacy numeric notation
*   :yaml:`config.itemGroups.*`: labels of item groups

All other values are used as configured. Custom finishers that rely on the
translation of other configuration paths need to use full :yaml:`LLL:`
references in properties FormEngine translates itself, or move the text to
one of the paths above.

The form editor translated the values of select options in the same way and
stored the label "Default" or "Standard" in the form definition. Such a value
was also preselected in the FlexForm override. The form editor now translates
the labels of select options only and keeps their values
(:yaml:`selectOptions.*.value`) as configured.

The upgrade wizard "Migrate translated "translation.language" values of form
email finishers" restores the value :yaml:`default` in form definitions stored
in the database and in existing form plugins. It replaces every non-empty value
that is not a valid language key, which covers the labels of :yaml:`default` in
all languages and custom translation files. Such values already rendered emails
in the default language. The wizard checks the option
:yaml:`translation.language` of all finishers, including custom finishers. A
custom finisher using this option for values other than language keys needs to
restore them after the migration. The wizard is repeatable: run it again after
migrating file-based forms to the database.
Form definitions stored in files, in file mounts or shipped by extensions, are
not migrated. Adjust the value of
:yaml:`finishers.*.options.translation.language` in these files manually.
Migrated forms keep sending emails in the default language. Select "Auto" in
the plugin to send emails in the language of the website instead.

..  index:: Backend, FlexForm, ext:form
