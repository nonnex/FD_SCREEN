<?php
require_once __DIR__ . '/../../config.php'; // config.php einbinden

class DB_LX
{
    protected $connection;

    function __construct() {
        $dbConfig = DATABASES['sybase_erp'];
        $this->connection = @sasql_connect($dbConfig['dsn']);
        if (!$this->connection) {
            die('
                <center><br><br>Die Applikation wird derzeit gewartet, oder ein Backup findet statt.<br>Bitte in einigen Minuten diese Seite neu laden (F5)</center>
                <script>setInterval(function() { window.location.reload(1); }, 60000);</script>
            ');
        }
    }

    public function query($query) {
        $result = sasql_query($this->connection, $query);
        return $result;
    }
    
    public function fetchArray($res, $mode = SASQL_ASSOC) {
        $data = sasql_fetch_array($res, $mode);
        return $data;
    }
}
?>