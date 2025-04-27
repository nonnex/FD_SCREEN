<?php
// "UID=U0;PWD=***;Server=LXDBSRV;DBN=F2;ASTART=No;host=FERROSRV"
class DB_LX
{
	protected $connection;

	function __construct($LX_HST = "FERROSRV", $LX_SRV="LXDBSRV", $LX_DBN="F2", $LX_UID="U0", $LX_PWD="ef41959cd6c24908") {
		$this->connection = @sasql_connect("Host=".$LX_HST.";ServerName=".$LX_SRV.";DBN=".$LX_DBN.";UID=".$LX_UID.";PWD=".$LX_PWD);
		if(!$this->connection) die ('
			<center><br><br>Die Applikation wird derzeit gewartet, oder ein Backup findet statt.<br>Bitte in einigen Minuten diese Seite neu laden (F5)</center>
			<script>setInterval(function() { window.location.reload(1); }, 60000); // Def 30000</script>
			');
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