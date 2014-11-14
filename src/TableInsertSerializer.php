<?php

namespace Liar\Liar;

class TableInsertSerializer implements Serializer {
   public $table;

   /** 
     * @param string $tablename 
     * @param array[] $columns { name => { 'column_name'=> name, 'data_type'=>primitive-type, 'column_type'=>specific-type, 'size'=>size }, ...  }
     */
   public function __construct($table) {
      $this->table =$table;
      if (empty($this->table)) throw new \InvalidArgumentException("TableInsertSerializer: no table specified");
   }
   
   /**
     * create a single SQL insert statement to insert one row of values; this is more for dumping the sample SQL than creating test data
     * @param array $values   a singleton list of field values, indexed by column name
     */
   public function render() {
      $that = $this;
      $table = $this->table;
      return implode("\n", array_map( function($row) use ($that, $table) { return "INSERT INTO {$table->name} ({$that->formatColumnList()}) VALUES ({$that->formatValueList($row)});"; }, $table->rows()));
   }

   protected function formatColumnList() { return implode(',', $this->table->columnNames()); }

   protected function formatValueList($row) { 
      $insertables=array();
      foreach( $this->table->columns() as $name => $definition ) {

         $insertableValue = preg_replace("/'/", "''", $row[$name]);

         if ($definition['data_type'] == 'int' || $definition['data_type'] == 'decimal') {
            $insertables[$name] = $insertableValue;
         } else {
            $insertables[$name] = "'".$insertableValue."'";
         }
      }
      return implode(",",$insertables);
   }
}
