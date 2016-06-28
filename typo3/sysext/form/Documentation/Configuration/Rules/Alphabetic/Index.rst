.. include:: ../../../Includes.txt


.. _reference-rules-alphabetic:

==========
alphabetic
==========

Checks if the submitted value only has the characters a-z or A-Z.


.. _reference-rules-alphabetic-allowwhitespace:

allowWhiteSpace
===============

:aspect:`Description:`
    See general information for  :ref:`reference-validation-attributes-allowwhitespace`.


.. _reference-rules-alphabetic-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-alphabetic-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

:aspect:`Default:`
    *local language:*"The value contains not only alphabetic characters"


.. _reference-rules-alphabetic-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

    For this specific rule the default message consists of two parts, the
    second one will only be added when **allowWhiteSpace** is set. This
    functionality is not possible when defining an own message as shown
    below.

:aspect:`Default:`
    *local language:*"Use alphabetic characters(, whitespace allowed)"


.. _reference-rules-alphabetic-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.alphabetic]

