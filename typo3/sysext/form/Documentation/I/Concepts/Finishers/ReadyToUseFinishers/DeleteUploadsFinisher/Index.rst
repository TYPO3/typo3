..  include:: /Includes.rst.txt
..  _concepts-finishers-deleteuploadsfinisher:
..  _finishers-delete-uploads:

=======================
DeleteUploads finishers
=======================

The "DeleteUploads finisher" removes files that have been submitted. You can use this
finisher after the email finisher if you do not want to keep the files
in your TYPO3 installation.

..  note::

    Finishers are only executed when a form is successfully submitted. If a user uploads
    a file but does not finish filling out the form, the uploaded files will not
    be deleted.

..  contents:: Table of contents

..  include:: /Includes/_NoteFinisher.rst

..  _concepts-finishers-deleteuploadsfinisher-yaml:

DeleteUploads finisher in the YAML form definition
==================================================

Use this finisher after the email finisher if you do not want to keep the files
in your TYPO3 installation.

Finishers are executed in the order they are listed in the form definition
YAML file:

..  literalinclude:: _codesnippets/_form.yaml
    :caption: public/fileadmin/forms/my_form.yaml

..  _apireference-finisheroptions-deleteuploadsfinisher:

Using the DeleteUploads finisher in PHP code
============================================

Developers can use the finisher key `DeleteUploads` to create
deleteuploads finishers in their own classes:

..  literalinclude:: _codesnippets/_finisher.php.inc
    :language: php

This finisher is implemented in :php:`TYPO3\CMS\Form\Domain\Finishers\DeleteUploadsFinisher`.
