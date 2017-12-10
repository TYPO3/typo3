.. include:: ../../Includes.txt

====================================================================================
Important: #79610 - Change Signature And Return Value For doesRecordExist_pageLookUp
====================================================================================

See :issue:`79610`

Description
===========

Method :php:`doesRecordExist_pageLookUp()` of class :php:`DataHandler` has been
changed. The signature and return value has been changed as a preparation for
more optimisations in the class :php:`DataHandler`.

Impact
======

None since the usages of doesRecordExist_pageLookUp should be limited
to the class :php:`DataHandler` - see also :issue:`77391`.

.. index:: PHP-API