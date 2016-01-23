.. include:: ../../../Includes.txt


.. _reference-filters-trim:

====
trim
====

Strips characters from the beginning and the end of the submitted value
according to the list of characters. If no character list is set, it will
only trim an ordinary space, a tab, a new line, a carriage return, the
NUL-byte and a vertical tab.


.. _reference-filters-trim-characterlist:

characterList
=============

:aspect:`Property:`
    characterList

:aspect:`Data type:`
    string

:aspect:`Description:`
    List of characters to be trimmed.

    See the PHP-manual (trim) for the options of the charlist.

[tsref:(cObject).FORM->filters.regexp]

