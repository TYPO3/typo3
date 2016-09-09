
.. include:: ../../Includes.txt

==============================================================
Important: #69909 - FAL-based Database Fields moved to integer
==============================================================

See :issue:`69909`

Description
===========

The FAL-related fields in the database tables `pages`, `pages_language_overlay` and `tt_content` that contained
a comma-separated list of paths to files were migrated at 6.2 with the shipped update wizards to IRRE fields.
The database fields `pages.media`, `pages_language_overlay.media`, `tt_content.image` and `tt_content.media`
now only contain numeric values, which are handled by the DataHandler and the Reference Index, holding
the number of references. The database fields are now changed to be only int fields, instead fields of type `text`.
