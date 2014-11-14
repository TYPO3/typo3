.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _faq:

FAQ
---


.. _how-to-import-db-dumps-with-the-install-tool:

How to import DB dumps with the install tool
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to move a site from a MySQL setup to a DBAL setup with
another database, you can either use the Import/Export extension
(impexp) or create a dump to be imported by the install tool. For this
to work a few hints should be followed:

#. Create the MySQL using mysqldump with thses
   options:--compatible=mysql40 --complete-insert --skip-opt --skip-
   quote-names --skip-comments

   Empty tables as well as cache\_\*, session\_\* and maybe the sys\_log
   and sys\_history (according to your preference) can be left out of the
   dump.

#. Make sure the dump has no backticks around table or field names
   (during testing there still were some, despite the options above being
   used). Don't do an unconditional replace over the whole dump, though,
   as there may be backticks inside the actual data. Be careful!

#. Put the dump in ``typo3conf/`` and import it using the install tool.

#. Now use the "Update required tables COMPARE" in the install tool to
   create the remaining missing or changed tables and fields.


.. _can-i-put-the-cache-tables-in-a-different-database:

Can I put the cache tables in a different database?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Yes, you can map the cache tables somewhere else. There is one caveat,
though – if you put the ``cache_pages`` table into a different database
than the pages table the FE will throw an error. The ``\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`` class
uses a join over those two tables, which cannot work. Never.

You can do two things to work around this: Use the file-based caching
that is available since TYPO3 4.0.0 or apply the patch
``class.tslib_fe.php.diff`` found in the ``doc/`` directory of the DBAL
extension ( **beware:** this patch is not maintained anymore and may
not apply cleanly to new versions of TYPO3).


.. _what-happens-to-the-table-definitions-in-mysql-format:

What happens to the table definitions in MySQL format?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Right, all table definitions in TYPO3 and it's extensions come in a
format compatible to what mysqldump produces. Still, the DBAL can
handle this, and here are the rules that apply when the ADOdb handler
is used:

- MySQL field types are mapped onto meta types, taken from ADOdb, but
  adapted to better fit the needs of TYPO3. From those field types the
  actual types for the target database are generated with the ADOdb
  library.

- If we need to map the actual field type in the database back onto a
  MySQL type, we use the same system backwards. This explains why most
  field type comparisons don't match exactly. An example: a TINYINT is
  mapped to the meta type I2, this is mapped to some DB-specific type.
  Later the actual type is mapped back to I2 and then to SMALLINT. Bang,
  the types do not match.

- If a field has no default value assigned in the dump, it is assigned
  either 0 or an empty string as default (depending on it's type). This
  is done to fake the implicit default values MySQL assigns to fields
  that have no explicit default.

- The UNSIGNED attribute for integer fields is dropped for all databases
  except MySQL when using the ADOdb handler, as it is MySQL specific.

- The AUTO\_INCREMENT attribute is never used for the ADOdb handler,
  (emulated) sequences are used instead.

- If a field is declared NOT NULL in the MySQL dump, this will be
  changed to allow NULL if running on Oracle..

Notice: For the native handler no conversion is done.


.. _why-drop-not-null-constraints-and-add-default-values:

Why drop NOT NULL constraints and add default values?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

All the NOT NULL fields in the database dumps only work on MySQL,
because *all* fields *always* have a default value in MySQL, even if
none is given explicitly, if they are NOT NULL. Yes, MySQL always
assigns a default value – there are no fields without a default value.

In other databases this is not the case, so that any NOT NULL field
not being assigned a value during an INSERT triggers an error. This in
itself is perfectly fine, were TYPO3 not to omit a lot of fields in a
lot INSERT queries. This is why a default value is always added – it
avoids errors during INSERT queries.

Now, we still drop the NOT NULL when running on Oracle, why is that
the case? Oracle treats an empty string as being null, so you cannot
insert an empty string into a field being NOT NULL. Furthermore,
having an empty string as default value is the same as having null as
default value. The bottom line is: it does not work having a NOT NULL
field with NULL as default, as you would get errors during INSERT. Now
go and complain to the folks over at Oracle...

