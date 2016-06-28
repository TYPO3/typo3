.. include:: ../../../Includes.txt


.. _reference-rules-inarray:

=======
inarray
=======

Compares the submitted value with the values in the array set in TypoScript.


.. _reference-rules-inarray-array:

array
=====

:aspect:`Property:`
    array

:aspect:`Data type:`
    [array of numbers]

:aspect:`Description:`
    The array containing the values which will be compared with the incoming
    data.

    **Example:**

    .. code-block:: html

      array {
        1 = TYPO3 4.5 LTS
        2 = TYPO3 6.2 LTS
        3 = TYPO3 7 LTS
      }


.. _reference-rules-inarray-element:

element
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-element`.


.. _reference-rules-inarray-error:

error
=====

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-error`.

:aspect:`Default:`
    *local language:*"The value does not appear to be valid"


.. _reference-rules-inarray-message:

message
=======

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-message`.

:aspect:`Default:`
    *local language:*"Only a few values are possible"


.. _reference-rules-inarray-showmessage:

showMessage
===========

:aspect:`Description:`
    See general information for :ref:`reference-validation-attributes-showmessage`.


.. _reference-rules-inarray-strict:

strict
======

:aspect:`Property:`
    strict

:aspect:`Data type:`
    boolean

:aspect:`Description:`
    The types of the needle in the haystack are also checked if strict = 1.

:aspect:`Default:`
    0

[tsref:(cObject).FORM->rules.inarray]

