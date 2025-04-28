<?php
require_once __DIR__ . '/../../config.php'; // config.php einbinden

class DB_FD
{ 
    protected $connection;
    protected $show_errors = TRUE;
    protected $query_closed = TRUE;
    public $query_count = 0;
  
    function __construct() {
        $dbConfig = DATABASES['mysql_erp'];
        
        // DSN manuell parsen, um die einzelnen Komponenten zu extrahieren
        $dsn = $dbConfig['dsn'];
        $host = '';
        $port = 3306; // Standardport
        $dbname = '';
        
        // DSN-String in Parameter aufteilen (Format: "Host=127.0.0.1;DBN=lx_fd;Port=3306")
        $parts = explode(';',$dsn);
        foreach ($parts as $part) {
            if (strpos($part, 'Host=') === 0) {
                $host = substr($part, 5); // "Host=" entfernen
            } elseif (strpos($part, 'Port=') === 0) {
                $port = (int) substr($part, 5); // "Port=" entfernen
            } elseif (strpos($part, 'DBN=') === 0) {
                $dbname = substr($part, 4); // "DBN=" entfernen
            }
        }

        // MySQLi-Verbindung mit den extrahierten Werten herstellen
        $this->connection = new mysqli(
            $host,
            $dbConfig['username'],
            $dbConfig['password'],
            $dbname,
            $port
        );
        if ($this->connection->connect_error) {
            $this->error('Failed to connect to MySQL - ' . $this->connection->connect_error);
        }
        $this->connection->set_charset($dbConfig['charset']);
    }
  
    public function query($query) {
        if (!$this->query_closed) {
            $this->query->close();
        }
        if ($this->query = $this->connection->prepare($query)) {
            if (func_num_args() > 1) {
                $x = func_get_args();
                $args = array_slice($x, 1);
                $types = '';
                $args_ref = array();
                foreach ($args as $k => &$arg) {
                    if (is_array($args[$k])) {
                        foreach ($args[$k] as $j => &$a) {
                            $types .= $this->_gettype($args[$k][$j]);
                            $args_ref[] = &$a;
                        }
                    } else {
                        $types .= $this->_gettype($args[$k]);
                        $args_ref[] = &$arg;
                    }
                }
                array_unshift($args_ref, $types);
                call_user_func_array(array($this->query, 'bind_param'), $args_ref);
            }
            $this->query->execute();
            if ($this->query->errno) {
                $this->error('Unable to process MySQL query (check your params) - ' . $this->query->error);
            }
            $this->query_closed = FALSE;
            $this->query_count++;
        } else {
            $this->error('Unable to prepare MySQL statement (check your syntax) - ' . $this->connection->error);
        }
        return $this;
    }

    public function fetchAll($callback = null) {
        $params = array();
        $row = array();
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
        while ($this->query->fetch()) {
            $r = array();
            foreach ($row as $key => $val) {
                $r[$key] = $val;
            }
            if ($callback != null && is_callable($callback)) {
                $value = call_user_func($callback, $r);
                if ($value == 'break') break;
            } else {
                $result[] = $r;
            }
        }
        $this->query->close();
        $this->query_closed = TRUE;
        return $result;
    }

    public function fetchArray() {
        $params = array();
        $row = array();
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
        while ($this->query->fetch()) {
            foreach ($row as $key => $val) {
                $result[$key] = $val;
            }
        }
        $this->query->close();
        $this->query_closed = TRUE;
        return $result;
    }

    public function close() {
        return $this->connection->close();
    }

    public function numRows() {
        $this->query->store_result();
        return $this->query->num_rows;
    }

    public function affectedRows() {
        return $this->query->affected_rows;
    }

    public function lastInsertID() {
        return $this->connection->insert_id;
    }

    public function error($error) {
        if ($this->show_errors) {
            exit($error);
        }
    }

    private function _gettype($var) {
        if (is_string($var)) return 's';
        if (is_float($var)) return 'd';
        if (is_int($var)) return 'i';
        return 'b';
    }
}
?>