..  include:: /Includes.rst.txt
..  _concepts-finishers-closurefinisher:

================
Closure finisher
================

The "Closure finisher" can only be used in programmatically-created forms. It allows
you to execute your own finisher code without implementing/ declaring a finisher.

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst

..  _apireference-finisheroptions-closurefinisher-options:

Closure finisher option
=======================

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

This finisher can only be used in programmatically-created forms. It allows
you to execute your own finisher code without implementing/ declaring a finisher.

Code example:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher`.
