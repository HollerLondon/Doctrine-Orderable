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

        if ( ! $record->ordr) {
        	$record->ordr = ($this->getMaxOrder($record) + 1);
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
      	$object = $event->getInvoker();
      	$className = get_class($object);
      	$position = $object->ordr;
      	$options = $this->_options;
  
      	$q = $object->getTable()->createQuery()
                              	->update($className)
                              	->set('ordr', 'ordr - ?', '1')
                              	->where('ordr > ?', $position)
                              	->orderBy('ordr');
  
      	if (isset($options['groupBy']) && !empty($options['groupBy'])) {
      		foreach ($options['groupBy'] as $idx => $field) {
          		$q->andWhere($className.'.' . $field . ' = ?', array($object->$field));
        	}
      	}
  
      	$q->execute();
    } 

    
	  /**
	   * Get the current maximum order
	   * Used to set position of new objects
	   * 
	   * @param Doctrine_Record $record
	   * @return int
	   */
    public function getMaxOrder(Doctrine_Record $record) 
    {
        $className = get_class($record);
        $select = 'MAX(' . $className . '.ordr) max_version';
        $options = $this->_options;

        $q = Doctrine_Query::create()
                ->select($select)
                ->from($className);

        if (isset($options['groupBy']) && !empty($options['groupBy'])) {
          foreach ($options['groupBy'] as $idx => $field) {
            $q->andWhere($className.'.' . $field . ' = ?', array($record->$field));
          }
        }
        
        $result = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        return isset($result[0]['max_version']) ? $result[0]['max_version'] : 0;
    }
}