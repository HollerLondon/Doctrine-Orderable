<?php

class Doctrine_Template_Orderable extends Doctrine_Template
{
  /**
   * __construct
   *
   * @param array $options
   * @return void
   */
  public function __construct(array $options = array())
  {
    $this->_plugin = new Doctrine_Orderable($options);
  }


  /**
   * Setup the Versionable behavior for the template
   *
   * @return void
   */
  public function setUp()
  {
    $this->_plugin->initialize($this->_table);

    $this->hasColumn('position', 'integer', 8);
    $this->addListener(new Doctrine_Orderable_Listener($this->_plugin->options));
  }


  /**
   * Get plugin for Versionable template
   *
   * @return void
   */
  public function getOrderable()
  {
    return $this->_plugin;
  }


  /**
   * Move this item up (ie. lower position by one).
   */
  public function moveUp()
  {
    $record = $this->getInvoker();

    if ($record->position <= 1)
    {
      // record is already at the top
      return true;
    }

    return $this->moveToPosition($record->get('position') - 1);
  }


  /**
   * Move item down (i. add 1 to position)
   */
  public function moveDown()
  {
    $record = $this->getInvoker();
    $maxPosition = $this->getMaxPosition($record);

    if ($maxPosition == $record->position) 
    {
      // we are already at the bottom
      return true;
    }

    return $this->moveToPosition($record->get('position') + 1);
  }


  /**
   * Move this item to the top
   */
  public function moveTop()
  {
    $record = $this->getInvoker();

    if ($record->position <= 1) 
    {
      // record is already at the top
      return true;
    }

    return $this->moveToPosition(1);
  }


  /**
   * Move this item to the top
   */
  public function moveBottom()
  {
    $record = $this->getInvoker();
    $max    = $this->getMaxPosition($record);

    if ($record->position == $max) 
    {
      // record is already at the bottom
      return true;
    }

    return $this->moveToPosition($max);
  }


  /**
   * Move item to specified position
   *
   * @param int $position
   */
  public function moveToPosition($position)
  {
    $ordering = $this->_plugin;
    $record   = $this->getInvoker();

    return $this->_plugin->moveToPosition($position, $record);
  }


  /**
   * Get the current maximum order
   * Used to set position of new objects
   *
   * @param Doctrine_Record $record
   * @return int
   */
  public function getMaxPosition(Doctrine_Record $record = null)
  {
    if (is_null($record))
    {
      $record = $this->getInvoker();
    }

    $className  = get_class($record);
    $select     = 'MAX(' . $className . '.position) max_version';
    $options    = $this->_plugin->options;

    $q = Doctrine_Query::create()
                      ->select($select)
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
  public function getMaxOrder(Doctrine_Record $record = null)
  {
    return $this->getMaxPosition($record);
  }


  /**
   * Reset counters - useful especially if ordering has just been turned on after content added
   *
   * SET @counter = 0;
   * UPDATE $className SET `position` = @counter:= @counter+1;
   */
  public function resetOrder()
  {
    $object     = $this->getInvoker();
    $className  = get_class($object);
    $options    = $this->_plugin->options;

    $query      = 'SET @counter = 0;';
    Doctrine_Manager::getInstance()->getCurrentConnection()->exec($query);

    $orderBy = 'position';
     
    if ($object->offsetExists('created_at')) $orderBy .= ', created_at';

    $q = $object->getTable()->createQuery()
                ->update($className)
                ->set('position', '@counter:= @counter+1')
                ->orderBy($orderBy);

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
}