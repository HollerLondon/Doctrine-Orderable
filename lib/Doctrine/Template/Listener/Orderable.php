<?php

class Doctrine_Orderable_Listener extends Doctrine_Record_Listener
{
  /**
   * Array of timestampable options
   *
   * @var string
   */
  protected $_options = array();


  /**
   * __construct
   *
   * @param string $array
   * @return void
   */
  public function __construct(array $options)
  {
    $this->_options = $options;
  }


  /**
   * Add the item on at the end
   *
   * @param Doctrine_Event $event
   * @return void
   */
  public function preInsert(Doctrine_Event $event)
  {
    $record = $event->getInvoker();

    if (!$record->position)
    {
      $record->position = ($this->getMaxPosition($record) + 1);
    }
  }


  /**
   * When a sortable object is deleted, promote all objects positioned lower than itself
   *
   * @param string $Doctrine_Event
   * @return void
   */
  public function postDelete(Doctrine_Event $event)
  {
    $object     = $event->getInvoker();
    $className  = get_class($object);
    $position   = $object->position;
    $options    = $this->_options;

    $q          = $object->getTable()->createQuery()
                                     ->update($className)
                                     ->set('position', 'position - ?', '1')
                                     ->where('position > ?', $position)
                                     ->orderBy('position');

    if (isset($options['groupBy']) && !empty($options['groupBy'])) 
    {
      foreach ($options['groupBy'] as $idx => $field) 
      {
        $value = $object->$field;
        if ($value && is_object($value) && false !== strstr($field, 'id')) $value = $value->getPrimaryKey();
          
        if (!is_null($value)) $q->andWhere($className.'.' . $field . ' = ?', array($value));
        else $q->andWhere($className.'.' . $field . ' IS NULL');
      }
    }

    $q->execute();
  }


  /**
   * Get the current maximum position
   * Used to set position of new objects
   *
   * @param Doctrine_Record $record
   * @return int
   */
  public function getMaxPosition(Doctrine_Record $record)
  {
    $className  = get_class($record);
    $select     = 'MAX(' . $className . '.position) max_version';
    $options    = $this->_options;

    $q          = Doctrine_Query::create()->select($select)
                                          ->from($className);

    if (isset($options['groupBy']) && !empty($options['groupBy'])) 
    {
      foreach ($options['groupBy'] as $idx => $field) 
      {
        $value = $record->$field;
        if ($value && is_object($value) && false !== strstr($field, 'id')) $value = $value->getPrimaryKey();
          
        if (!is_null($value)) $q->andWhere($className.'.' . $field . ' = ?', array($value));
        else $q->andWhere($className.'.' . $field . ' IS NULL');
      }
    }

    $result = $q->execute(array(), Doctrine::HYDRATE_ARRAY);
    
    return isset($result[0]['max_version']) ? $result[0]['max_version'] : 0;
  }
  
  /**
   * @deprecated with column rename
   */
  public function getMaxOrder(Doctrine_Record $record)
  {
    return $this->getMaxPosition($record);
  }
}