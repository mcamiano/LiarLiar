<?php

namespace Liar\Liar;

class Table {
   public $name;
   protected $columns;

   /** 
     * @param string $tablename 
     * @param array[] $columns { name => { 'column_name'=> name, 'data_type'=>primitive-type, 'column_type'=>specific-type, 'size'=>size }, ...  }
     */
   public function __construct($tablename, $columns=NULL) {
      $this->name=$tablename;
      $this->columns=array();

      if (is_null($columns)) {
      } else if (!is_array($columns)) {
         throw new \InvalidArgumentException("Table: invalid columns argument");
      } else { 

          $elements = array_values($columns); // check if first element is an array or not
          $firstValue = array_shift($elements); 

          if ( ! is_array( $firstValue ) ) $columns = array($columns);  // assume it is a single field spec if not

          foreach ($columns as $field) {
              $this->declareColumn($field['column_name'], $field['data_type'], $field['column_type'], $field['size'] );
          }
      }
      if (empty($this->name)) throw new \InvalidArgumentException("Table: no tablename specified");
   }
   
   /**
     * declare a column
     * @param string $column_name
     * @param string $data_type
     * @param string $column_type
     * @param int $size
     */
   public function declareColumn( $column_name, $data_type, $column_type, $size ) {
      $this->columns[$column_name] = get_defined_vars();
   }

   /**
     * create a single SQL insert statement to insert one row of values; this is more for dumping the sample SQL than creating test data
     * @param array $values   a singleton list of field values, indexed by column name
     */
   public function formatSQLInsert($values) {
      $insertableValues = array();
      foreach( $this->columnNames() as $name ) {
         $insertableValues[$name] = preg_replace("/'/", "''", $values[$name]);
      }
      return "INSERT INTO {$this->name} ({$this->formatColumnList()}) VALUES ({$this->formatValueList($values)})\n";
   }

   public function columns() { return $this->columns; }

   public function columnNames() { return array_keys($this->columns()); }

   public function formatColumnList() { return implode(',', $this->columnNames()); }

   public function formatValueList($values) { 
      $insertables=array();
      foreach( $this->columns() as $name => $definition ) {
         $insertableValue = preg_replace("/'/", "''", $values[$name]);
         if ($definition['data_type'] == 'int' || $definition['data_type'] == 'decimal') {
            $insertables[$name] = $insertableValue;
         } else {
            $insertables[$name] = "'".$insertableValue."'";
         }
      }
      return implode(",",$insertables);
   }
}
