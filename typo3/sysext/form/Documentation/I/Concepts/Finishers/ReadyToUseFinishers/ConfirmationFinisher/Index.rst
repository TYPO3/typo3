..  include:: /Includes.rst.txt
..  _concepts-finishers-confirmationfinisher:
..  _finishers-confirmation-message:

=====================
Confirmation finisher
=====================

A basic finisher that outputs a text or content element.

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst

..  _apireference-finisheroptions-confirmationfinisher-options:

Confirmation finisher options
=============================

This finisher outputs a text or a content element after the form has been submitted.

The settings of the finisher are:

..  _apireference-finisheroptions-confirmationfinisher-options-message:

..  confval:: message
    :name: confirmationfinisher-message
    :type: string
    :default: `The form has been submitted.`

    Displays this text if the `contentElementUid` is not set.

..  confval:: contentElementUid
    :name: confirmationfinisher-contentElementUid
    :type: int
    :default: 0

    Renders the content element with the supplied ID.

..  confval:: translation.propertiesExcludedFromTranslation
    :name: confirmationfinisher-translation-propertiesExcludedFromTranslation
    :type: array
    :default: `[]`

    Defines a list of finisher option properties that should be excluded from
    translation.

    When specified, the listed properties are not processed by the
    :php-short:`\TYPO3\CMS\Form\Service\TranslationService` during translation
    of finisher options. This prevents their values from being replaced by
    translated equivalents, even if translations exist for those options.

    This option is usually generated automatically as soon as FlexForm overrides
    are in place and normally does not need to be set manually in the form
    definition.

    See `Skip translation of overridden form finisher options <https://docs.typo3.org/permalink/typo3/cms-form:concepts-finishers-confirmationfinisher-yaml-propertiesexcludedfromtranslation>`_
    for an example.

..  _concepts-finishers-confirmationfinisher-yaml:

Confirmation finisher in the YAML form definition
=================================================

A basic finisher that outputs text or a content element.

Outputs text ``message``:

..  literalinclude:: _codesnippets/_form_with_confirmation_finisher.yaml
    :caption: public/fileadmin/forms/my_form.yaml

Outputs content element with id 42:

..  literalinclude:: _codesnippets/_form_with_confirmation_content_element.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _concepts-finishers-confirmationfinisher-yaml-propertiesExcludedFromTranslation:

Skip translation of overridden form finisher options
====================================================

The following is an example of the `translation.propertiesExcludedFromTranslation <https://docs.typo3.org/permalink/typo3/cms-form:confval-confirmationfinisher-translation-propertiesexcludedfromtranslation>`_
option being used to exclude  three properties (subject, recipients and
format) from translation.

Using this translation option, the properties can only be overridden by a FlexForm, not by the
:php-short:`\TYPO3\CMS\Form\Service\TranslationService`.

This option is automatically generated as soon as FlexForm overrides are in place.

The following syntax is only documented for completeness. Nonetheless, it can
also be added to a form definition YAML file.

..  literalinclude:: _codesnippets/_form_with_propertiesExcludedFromTranslation.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _apireference-finisheroptions-confirmationfinisher:

Using the confirmation finisher in PHP code
===========================================

Developers can use the finisher key `Confirmation` to create
confirmation finishers in their own classes:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

Th confirmation finisher is implemented in
:php:`TYPO3\CMS\Form\Domain\Finishers\ConfirmationFinisher`.
