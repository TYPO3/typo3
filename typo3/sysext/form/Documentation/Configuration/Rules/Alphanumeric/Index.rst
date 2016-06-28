.. include:: ../../../Includes.txt


.. _reference-rules-alphanumeric:

============
alphanumeric
============

Checks if the submitted value only has the characters a-z, A-Z or 0-9.


.. _reference-rules-alphanumeric-allowwhitespace:

allowWhiteSpace
===============

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-allowwhitespace`.


.. _reference-rules-alphanumeric-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-alphanumeric-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

:aspect:`Default:`
    *local language:*"The value contains not only alphanumeric characters"


.. _reference-rules-alphanumeric-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    For this specific rule the default message consists of two parts, the
    second one will only be added when **allowWhiteSpace** is set. This
    functionality is not possible when defining an own message as shown
    below.

:aspect:`Default:`
    *local language:*"Use alphanumeric characters(, whitespace allowed)"


.. _reference-rules-alphanumeric-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.alphanumeric]

