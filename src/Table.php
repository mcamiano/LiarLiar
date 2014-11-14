<?php

namespace Liar\Liar;

class Table {
   public $name;
   protected $columns;
   protected $rows;

   /** 
     * @param string $tablename 
     * @param array[] $columns { name => { 'column_name'=> name, 'data_type'=>primitive-type, 'column_type'=>specific-type, 'size'=>size }, ...  }
     */
   public function __construct($tablename, $columns=NULL) {
      $this->name=$tablename;
      $this->columns=array();
      $this->rows=array();

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
    *  Insert a row into the table; requires only that column names be consistent with defined names; others are ignored
    *  defined columns without values are assigned NULL by default
    */
   public function insert( $hash ) {
      $row = array();
      foreach ($this->columnNames() as $name) {
         $row[$name] = isset($hash[$name]) ? $hash[$name] : 'NULL';
      }
      array_push($this->rows,$row);
   }

   public function columns() { return $this->columns; }

   public function columnNames() { return array_keys($this->columns()); }

   public function formatColumnList() { return implode(',', $this->columnNames()); }

   public function rows() { return $this->rows; }
}
