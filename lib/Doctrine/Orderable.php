<?php
/**
 * Behaviour to make a doctrine model orderable.
 * 
 * Adds a column called 'ordr' to the model
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
		$diff = $record->get('ordr') - $position;
		$className = get_class($record);
		$options = $this->options;

		if ($diff > 0) {
			// we are moving up, eg. 10 to 5
			$low = $position;
			$high = $record->get('ordr');

	    $q = Doctrine_Query::create()
								->update("$className t")
								->set('t.ordr', 't.ordr + 1')
								->where('t.ordr >= ? AND t.ordr < ?', array($low, $high));

			if (isset($options['groupBy']) && !empty($options['groupBy'])) {
        foreach ($options['groupBy'] as $idx => $field) {
          $q->andWhere('t.' . $field . ' = ?', array($record->$field));
        }
      }
			
      $q->execute();
		}
		else {
			// we are moving down, eg. 5 to 10
			$low = $record->get('ordr');
			$high = $position;

		  $q = Doctrine_Query::create()
								->update("$className t")
								->set('t.ordr', 't.ordr - 1')
								->where('t.ordr > ? AND t.ordr <= ?', array($low, $high));

      if (isset($options['groupBy']) && !empty($options['groupBy'])) {
        foreach ($options['groupBy'] as $idx => $field) {
          $q->andWhere('t.' . $field . ' = ?', array($record->$field));
        }
      }
        
      $q->execute();
		}

		$record->set('ordr', $position);
		return $record->save();
  }
}