.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt



.. _classes:

classes:
""""""""

Properties of each class available in the RTE.


.. _classes-classname:

classes.[ *classname* ]
~~~~~~~~~~~~~~~~~~~~~~~

.. container:: table-row

   Property
         classes.[ *classname* ]
   
   Description
         Defines the classes available in the RTE.  *classname* is the actual
         name of the style-class you are configuring. Notice you must
         specifically assign the classes to the various facilities also. See
         later.
         
         Properties:
         
         ::
         
            .name = label of the class (may be a reference to an entry in a localization file of the form LLL:EXT:[fileref]:[labelkey])
            .value = the style for the class
            .noShow = boolean; if set, the style of the class is not used to render it in the pop-up selector.
            
            # specification of alternating classes for rows and/or columns of a table
            .alternating { 
                rows {
                    startAt = int+ (default = 1)
                    oddClass = class-name
                    evenClass = class-name
                    oddHeaderClass = class-name
                    evenHeaderClass = class-name
                }
                columns {
                    startAt = int+ (default = 1)
                    oddClass = class-name
                    evenClass = class-name
                    oddHeaderClass = class-name
                    evenHeaderClass = class-name
                }
            }
            
            # specification of counting classes for rows and/or columns of a table
            .counting {
                    rows {
                            startAt = int (default = 1)
                            rowClass = class-name (should not be a substring of other class names)
                            rowLastClass = class-name
                            rowHeaderClass = class-name (should not be a substring of other class names)
                            rowHeaderLastClass = class-name
                    }
                    columns {
                            startAt = int (default = 1)
                            columnClass = class-name(should not be a substring of other class names)
                            columnLastClass = class-name
                            columnHeaderClass = class-name(should not be a substring of other class names)
                            columnHeaderLastClass = class-name
                    }
            }

         Example:
         
         ::
         
            # Hidding an allowed class in the class selector dropped downlist
            RTE.classes.class-name.value = display: none;
         
         Example:
         
         ::
         
            # Configuration of an alternating and counting class
            RTE.classes.countingtable {
                    name = Counting class
                    alternating {
                            rows {
                                    startAt = 1
                                    oddClass = tr-odd
                                    evenClass = tr-even
                                    oddHeaderClass = thead-odd
                                    evenHeaderClass = thead-even
                            }
                            columns {
                                    startAt = 1
                                    oddClass = td-odd
                                    evenClass = td-even
                                    oddHeaderClass = th-odd
                                    evenHeaderClass = th-even
                            }
                    }
                    counting {
                            rows {
                                    startAt = 1
                                    rowClass = tr-count-
                                    rowLastClass = tr-last
                                    rowHeaderClass = thead-count-
                                    rowHeaderLastClass = thead-last
                            }
                            columns {
                                    startAt = 1
                                    columnClass = td-count-
                                    columnLastClass = td-last
                                    columnHeaderClass = th-count-
                                    columnHeaderLastClass = th-last
                            }
                    }
            }
         
         Example:
         
         ::
         
            # Hidding an allowed counting class in the class selector dropped downlist
            # Note the ending hyphen « - »
            # The class name string should be as specified in the counting property
            RTE.classes.counting-class-name-.value = display: none;


[page:RTE]

