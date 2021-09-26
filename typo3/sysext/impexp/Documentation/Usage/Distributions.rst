.. include:: /Includes.rst.txt

.. _distributions:

=============
Distributions
=============

The Import/Export tool can be used to create the initial content for distributions. It is
recommended to always include a preset of the chosen export settings in the
export itself. Any extensions that are not required by the distribution should be deactivated
before the export task is executed.

The export should be done on the root level of the page tree. It should include all tables
except :sql:`be_users` and :sql:`sys_log`. Relations to all tables should be
included. If you also need to export hidden records uncheck
:guilabel:`Exclude disabled elements`.

Choose meaningful meta data at :guilabel:`Export > File & Preset > Meta data`.
The file name has to be "data" and the file format need to be set to XML.

Export by clicking :guilabel:`Export > File & Preset > Output options > Save to filename`.

You will then find a file called :file:`/fileadmin/user_upload/_temp_/importexport/data.xml`.

If files have been exported, there will also be a folder called
:file:`data.xml.files` containing the files with hashed filenames. Copy
:file:`data.xml` and the corresponding folder into the distributions folder
called :file:`Initialisation`.

Export distribution content containing images or other files
============================================================

If you need to export any files from within :file:`fileadmin`, check
:guilabel:`Export > Advanced Options > Save files in extra folder beside the export file`.

By default each file which has an entry in table :sql:`sys_file` will be
exported, including files in path :file:`fileadmin/userupload/_temp` where
previous exports might have been stored. Delete all temporary files that you
do not want to export from the fileadmin. Use the :guilabel:`Filelist` module to
delete these files. If you delete them directly from the file system the
corresponding entries in :sql:`sys_file` do not be deleted and there will be an
error on exporting.

Make sure that the tables :sql:`sys_file` and :sql:`sys_file_*` are included in
the export.
