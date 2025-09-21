..  include:: /Includes.rst.txt
..  _concepts-finishers-confirmationfinisher:
..  _finishers-confirmation-message:

=====================
Confirmation finisher
=====================

A basic finisher that outputs a given text or a content element, respectively.

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst

..  _apireference-finisheroptions-confirmationfinisher-options:

Options of the confirmation finisher
====================================

This finisher outputs a given text after the form has been submitted.

The settings of the finisher are as follows:

..  _apireference-finisheroptions-confirmationfinisher-options-message:

..  confval:: message
    :name: confirmationfinisher-message
    :type: string
    :default: `The form has been submitted.`

    Displays this message if the `contentElementUid` is not set.

..  confval:: contentElementUid
    :name: confirmationfinisher-contentElementUid
    :type: int
    :default: 0

    Renders the content element with the ID supplied here.

..  _concepts-finishers-confirmationfinisher-yaml:

Confirmation finisher in the YAML form definition
=================================================

A basic finisher that outputs a given text or a content element, respectively.

Usage within form definition for the case, you want to use a given text.

..  literalinclude:: _codesnippets/_form_with_confirmation_finisher.yaml
    :caption: public/fileadmin/forms/my_form.yaml

Usage within form definition for the case, you want to output a content element.

..  literalinclude:: _codesnippets/_form_with_confirmation_content_element.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _apireference-finisheroptions-confirmationfinisher:

Usage of the confirmation finisher in PHP code
==============================================

Developers can create a confirmation finisher by using the key `Confirmation`:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\ConfirmationFinisher`.
