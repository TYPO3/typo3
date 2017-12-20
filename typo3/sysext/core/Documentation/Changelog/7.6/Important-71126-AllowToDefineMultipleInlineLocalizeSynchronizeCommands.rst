
.. include:: ../../Includes.txt

===============================================================================
Important: #71126 - Allow to define multiple inlineLocalizeSynchronize commands
===============================================================================

See :issue:`71126`

Description
===========

The inlineLocalizeSynchronize command in DataHandler currently supports these formats:

* [parent][13][inlineLocalizeSynchronize] = field,14
* [parent][13][inlineLocalizeSynchronize] = field,localize
* [parent][13][inlineLocalizeSynchronize] = field,synchronize

The current string configuration format is changed to be an array, legacy configurations are converted to the new format::

    [parent][13][inlineLocalizeSynchronize] = [
      field: name of the parent field,
      language: id of the target language,
      action: either "localize" or "synchronize",
      ids: array of child-ids to be localized [1, 2, 3]
    ]

Either "action" or "ids" must be defined.

.. index:: PHP-API, Backend
