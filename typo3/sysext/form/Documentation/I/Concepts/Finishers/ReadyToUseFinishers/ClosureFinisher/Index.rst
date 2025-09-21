..  include:: /Includes.rst.txt
..  _concepts-finishers-closurefinisher:

================
Closure finisher
================

The "Closure finisher" can only be used within forms that are created
programmatically. It allows you to execute your own finisher code without
implementing/ declaring a finisher.

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst

..  _apireference-finisheroptions-closurefinisher-options:

Options of the closure finisher
===============================

..  _apireference-finisheroptions-closurefinisher-options-closure:

..  confval:: closure
    :name: closurefinisher-closure
    :required: true
    :type: `?\Closure`
    :default: `null`

    The name of the field as shown in the form.

..  _apireference-finisheroptions-closurefinisher:

Using the closure finisher programmatically
===========================================

This finisher can only be used in programmatically-created forms. It makes it
possible to execute one's own finisher code without having to implement/
declare this finisher.

Usage through code:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher`.
