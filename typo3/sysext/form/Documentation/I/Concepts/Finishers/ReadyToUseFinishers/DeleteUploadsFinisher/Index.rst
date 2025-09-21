..  include:: /Includes.rst.txt
..  _concepts-finishers-deleteuploadsfinisher:
..  _finishers-delete-uploads:

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

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst

..  _concepts-finishers-deleteuploadsfinisher-yaml:

DeleteUploads finisher in the YAML form definition
==================================================

For example: use this finisher after the email finisher if you do not want
to keep the files online.

The finishers are executed in the order they are listed in the form definition
YAML file:

..  literalinclude:: _codesnippets/_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _apireference-finisheroptions-deleteuploadsfinisher:

Usage of the DeleteUploads finisher in PHP code
===============================================

Developers can create a confirmation finisher by using the key `DeleteUploads`:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\DeleteUploadsFinisher`.
