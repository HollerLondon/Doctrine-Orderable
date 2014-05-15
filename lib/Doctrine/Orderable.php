<?php
/**
 * Behaviour to make a doctrine model orderable.
 *
 * Adds a column called 'position' to the model
 *
 * Usage:
 *
 * actAs:
 *  Orderable:
 *    groupBy:      category_id       # optional parameter to order within a subset
 *
 * @author Jo Carter
 */
class Doctrine_Orderable
{
  /**
   * @var Doctrine_Table
   */
  protected $table;

  /**
   * __construct
   *
   * @param array $options
   * @return void
   */
  public function __construct(array $options = array())
  {
    $this->options = $options;
  }


  /**
   * Set up
   *
   * @param Doctrine_Table $table
   */
  public function initialize($table)
  {
    $this->table = $table;
  }


  /**
   * Move item to specified position
   *
   * @param int $position
   * @param Doctrine_Record $record
   */
  public function moveToPosition($position, $record)
  {
    $diff       = $record->get('position') - $position;
    $className  = get_class($record);
    $options    = $this->options;

    if ($diff > 0) 
    {
      // we are moving up, eg. 10 to 5
      $low      = $position;
      $high     = $record->get('position');

      $q = Doctrine_Query::create()
                          ->update("$className t")
                          ->set('t.position', 't.position + 1')
                          ->where('t.position >= ? AND t.position < ?', array($low, $high));

      if (isset($options['groupBy']) && !empty($options['groupBy'])) 
      {
        foreach ($options['groupBy'] as $idx => $field) 
        {
          $value = $record->$field;
          if ($value && is_object($value) && false !== strstr($field, 'id')) $value = $value->getPrimaryKey();
            
          if (!is_null($value)) $q->andWhere('t.' . $field . ' = ?', array($value));
          else $q->andWhere('t.' . $field . ' IS NULL');
        }
      }
      	
      $q->execute();
    }
    else 
    {
      // we are moving down, eg. 5 to 10
      $low    = $record->get('position');
      $high   = $position;

      $q = Doctrine_Query::create()
                        ->update("$className t")
                        ->set('t.position', 't.position - 1')
                        ->where('t.position > ? AND t.position <= ?', array($low, $high));

      if (isset($options['groupBy']) && !empty($options['groupBy'])) 
      {
        foreach ($options['groupBy'] as $idx => $field) 
        {
          $value = $record->$field;
          if ($value && is_object($value) && false !== strstr($field, 'id')) $value = $value->getPrimaryKey();
            
          if (!is_null($value)) $q->andWhere('t.' . $field . ' = ?', array($value));
          else $q->andWhere('t.' . $field . ' IS NULL');
        }
      }

      $q->execute();
    }

    $record->set('position', $position);
    
    return $record->save();
  }
}