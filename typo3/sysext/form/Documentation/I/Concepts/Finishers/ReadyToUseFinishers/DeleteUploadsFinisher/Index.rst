..  include:: /Includes.rst.txt
..  _concepts-finishers-deleteuploadsfinisher:

=======================
DeleteUploads finishers
=======================

The "DeleteUploads finisher" removes submitted files. Use this finisher,
for example, after the email finisher if you do not want to keep the files
within your TYPO3 installation.

..  note::

    Finishers are only executed on successfully submitted forms. If a user uploads
    a file but does not finish the form successfully, the uploaded files will not
    be deleted.

..  include:: /Includes/_NoteFinisher.rst
