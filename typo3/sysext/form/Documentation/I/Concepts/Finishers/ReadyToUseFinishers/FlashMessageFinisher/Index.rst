..  include:: /Includes.rst.txt
..  _concepts-finishers-flashmessagefinisher:

=====================
FlashMessage finisher
=====================

The "FlashMessage finisher" is a basic finisher that adds a message to the
FlashMessageContainer.

..  contents:: Table of contents

..  note::

    This finisher cannot be used from the backend editor. It can only be
    inserted directly into the YAML form definition or programmatically.

..  include:: /Includes/_NoteFinisher.rst

..  _apireference-finisheroptions-flashmessagefinisher-options:

Options of the FlashMessage finisher
====================================

The following options can be set directly in the form definition YAML or
programmatically in the options array:

..  _apireference-finisheroptions-flashmessagefinisher-options-messagebody:

..  confval:: messageBody
    :name: flashmessagefinisher-messageBody
    :type: string
    :required: true

    The flash message to be displayed. May contain placeholders like `%s` that
    are replaced with the `messageArguments`.

..  _apireference-finisheroptions-flashmessagefinisher-options-messagetitle:

..  confval:: messageTitle
    :name: flashmessagefinisher-messageTitle
    :type: string
    :default: `''`

    If set is displayed as the title of the flash message.

..  _apireference-finisheroptions-flashmessagefinisher-options-messagearguments:

..  confval:: messageArguments
    :name: flashmessagefinisher-messageArguments
    :type: array
    :default: `[]`

    If the `messageBody` contains placeholders like `%s` they can be replaced
    with these arguments.

..  _apireference-finisheroptions-flashmessagefinisher-options-messagecode:

..  confval:: messageCode
    :name: flashmessagefinisher-messageCode
    :type: ?int
    :default: `null`

    A unique code to make the message recognizable. By convention the current
    unix time stamp at the time of initially creating the message is used,
    for example `1758455932`.

..  _apireference-finisheroptions-flashmessagefinisher-options-severity:

..  confval:: severity
    :name: flashmessagefinisher-severity
    :type: :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity`
    :default: `ContextualFeedbackSeverity::OK`

    The severity influences the display (color and icon) of the flash message.

..  confval:: translation.propertiesExcludedFromTranslation
    :name: flashmessagefinisher-translation-propertiesExcludedFromTranslation
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

..  _concepts-finishers-flashmessagefinisher-yaml:

FlashMessage finisher in the YAML form definition
=================================================

..  literalinclude:: _codesnippets/_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _apireference-finisheroptions-flashmessagefinisher:

Usage of the FlashMessage finisher in PHP code
==============================================

Developers can create a confirmation finisher by using the key `FlashMessage`:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\FlashMessageFinisher`.
