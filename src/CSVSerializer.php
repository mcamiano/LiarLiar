<?php

namespace Liar\Liar;

class CSVSerializer implements Serializer {
   public $table;

   /** 
     * @param string $tablename 
     * @param array[] $columns { name => { 'column_name'=> name, 'data_type'=>primitive-type, 'column_type'=>specific-type, 'size'=>size }, ...  }
     */
   public function __construct($table) {
      $this->table =$table;
      if (empty($this->table)) throw new \InvalidArgumentException("CSVSerializer: no table specified");
   }
   
   /**
     * create a single SQL insert statement to insert one row of values; this is more for dumping the sample SQL than creating test data
     * @param array $values   a singleton list of field values, indexed by column name
     */
   public function render() {
      $that = $this;
      $table = $this->table;
      
      return $this->formatColumnList() . "\n" . implode("\n", array_map( function($row) use ($that, $table) { return $that->formatValueList($row); }, $table->rows()));
   }

   protected function formatColumnList() { return implode(',', $this->table->columnNames()); }

   protected function formatValueList($row) { 
      $insertables=array();
      foreach( $this->table->columns() as $name => $definition ) {
         $insertables[$name] = ($definition['data_type'] == 'int' || $definition['data_type'] == 'decimal') 
           ? $row[$name] 
           : $this->enquote($row[$name])
         ;
      }
      return implode(",",$insertables);
   }

   protected function enquote($v) { return '"'.preg_replace('/"/', '""', $v).'"'; }
}
