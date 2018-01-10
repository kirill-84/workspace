<?php
class DB {
  protected $connection;
  
  public function __construct($host, $user, $password, $db_name) {
    $this->connection = new mysqli($host, $user, $password, $db_name);
    
    $this->query("SET NAMES UTF8");
    
    if( !$this->connection ) {
      throw new Exception('Could not connect to DB');
    }
  }
}
?>
