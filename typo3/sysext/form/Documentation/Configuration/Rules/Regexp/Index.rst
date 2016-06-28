.. include:: ../../../Includes.txt


.. _reference-rules-regexp:

======
regexp
======

Checks if the submitted value matches your own regular expression, using PHP
function preg\_match().


.. _reference-rules-regexp-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-regexp-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

:aspect:`Default:`
    *local language:*"The value does not match against pattern"


.. _reference-rules-regexp-expression:

expression
==========

:aspect:`Property:`
    expression

:aspect:`Data type:`
    string

:aspect:`Description:`
    The submitted value needs to match the expression, given in your
    pattern.


.. _reference-rules-regexp-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

:aspect:`Default:`
    *local language:*"Use the right pattern"



.. _reference-rules-regexp-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.

[tsref:(cObject).FORM->rules.regexp]

