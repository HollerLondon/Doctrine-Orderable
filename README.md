Orderable
=========

author:    Jo Carter <jocarter@holler.co.uk>

version:   3.0


Introduction
------------

Behaviour to make a doctrine model orderable/ sortable.
 
Adds a column called `position` to the model, and automatically handles adding and deleting of items within the list


Usage
----

Add as an external in `lib/doctrine_extensions`

In the schema.yml
 
    actAs:
      Orderable:
        groupBy:      columnname       # optional parameter to order within a subset
    
In the code

 To reorder an item:
 
  * $item->moveUp()
  * $item->moveDown()
  * $item->moveTop()
  * $item->moveBottom()
  
 To find the maximum position:
 
  * $item->getMaxPosition()
  
 To reset the order:
 
  * $item->resetOrder()
  
NOTE: Reset order is very useful to call if you add Orderable behaviour after there are already items in the database.
It will add orders to the items based on their position in the DB (within the group as specified, specifically using created_at if it exists)
It can also be used to correct ordering if items are maunually deleting from the database


Changelog
---------

Version 3.0 - renamed 'ordr' to 'position' to reflect other Sortable/ Orderable extensions (and to avoid misspelling problems)

Version 2.0 - added resetOrder(), groupBy functions correctly
