..  include:: /Includes.rst.txt
..  _concepts-finishers-savetodatabasefinisher:

=======================
SaveToDatabase finisher
=======================

The "SaveToDatabase finisher" saves the data of a submitted form into a
database table.

..  include:: /Includes/_NoteFinisher.rst

Example for adding uploads to ext:news (fal_related_files and fal_media):

..  literalinclude:: _codesnippets/_example-fal-uploads_news.yaml
    :linenos:
    :caption: public/fileadmin/forms/my_form_with_multiple_finishers.yaml
