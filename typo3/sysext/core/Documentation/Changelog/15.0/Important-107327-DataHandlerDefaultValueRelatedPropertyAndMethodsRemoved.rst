..  include:: /Includes.rst.txt

..  _important-107327-1770297680:

====================================================================================
Important: #107327 - DataHandler: Default value related property and methods removed
====================================================================================

See :issue:`107327`

Description
===========

The resolving of default values for new records has been streamlined.
:typoscript:`TCAdefaults` from page TSconfig and user TSconfig as well as
TCA default values are now resolved for each new record individually,
without keeping any state in the :php:`DataHandler` instance.

Default values are now applied to all fields of a new record, not only to
the fields shown for its record type. Page TSconfig takes precedence over
user TSconfig, and type-specific values take precedence over field-level
values of the same source. This is the same order as used when creating a
record in the backend form. Previously, type-specific
:typoscript:`TCAdefaults` of user TSconfig took precedence over field-level
:typoscript:`TCAdefaults` of page TSconfig.

Resolved default values are not available via the internal property
:php:`DataHandler->defaultValues` anymore, which has been removed.

The following internal methods to calculate default values have been
removed:

- :php:`DataHandler->setDefaultsFromUserTS()`
- :php:`DataHandler->processTypeSpecificTcaDefaults()`
- :php:`DataHandler->newFieldArray()`
- :php:`DataHandler->getTypeSpecificDefault()`

..  index:: PHP-API, TSConfig, ext:core
