.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _some-field-names-are-too-long:

Some field names are too long
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Oracle for example has a restriction on table and field names, they
can only be 30 bytes long. Some fields, especially of extensions
adding fields to existing tables may violate that restriction. A way
to work around this is to configure a field name mapping for those
long names onto a shorter one before creating them (through the
Extension Manager or Install Tool).
