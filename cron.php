<?php
/*
include 'inc/db/db_lx.php';
include 'inc/db/db_fd.php';
include 'inc/lx_orders.php';
include 'inc/lx_lager.php';
*/

class Cron
{
	private $db_lx;
	private $db_fd;
	
	function __construct() {
		$this->db_lx = new DB_LX();
		$this->db_fd = new DB_FD();
	}
	
	public function CheckLxTags()
	{	
		$this->SetAuftragNeu();
		$this->SetLsVersandbereit();
		$this->SetAbVersendet();
	}
	
	/***
	Set "NEU" als default für alle neuen Aufträge wenn tags leer
	***/
	private function SetAuftragNeu()
	{
//echo "SetAuftragNeu()\n";
		$Lx_Orders 	= new Lx_Orders();
		$Orders = $Lx_Orders->GetAllOpenOrdersFromLX(1);
		
		foreach($Orders as $key => $value)
		{
			if(empty($value['Tags']))
			{
//echo "SetAuftragNeu()->".$value['AuftragId']."\n";
				$Lx_Orders->SetOrderTags($value['AuftragId'], 1);
			}
		}
	}
	
	/***
	Set "Versandvorbereitung" als default für alle neu generierten weitergeführten AB->lieferscheine wenn tags leer
	***/
	private function SetLsVersandbereit()
	{
		$Lx_Orders 	= new Lx_Orders();
		$Orders = $Lx_Orders->GetAllOpenOrdersFromLX(2);
		
		foreach($Orders as $key => $value)
		{
			if(empty($value['Tags']))
			{
//echo "SetLsVersandbereit()->".$value['AuftragId']."\n";
				$Lx_Orders->SetOrderTags($value['AuftragId'], 3, 2);
			}
		}
	}
	
	/***
	Set "Versendet" Tag für alle AB(Kennung = 1) mit bStatus_Weitergefuehrt = 1 und bStatus_geliefert = 1
	***/
	private function SetAbVersendet()
	{
		$Lx_Orders 	= new Lx_Orders();
		
		$query = "SELECT 
			a.SheetNr, a.AuftragsNr, a.Auftragskennung, a.Kundenmatchcode, a.VorgangNr, a.bStatus_geliefert, a.bStatus_Weitergefuehrt, a.lTagsAnzahl, a.Verweis_weiter_aus_nr, a.Verweis_weiter_in_nr, t.lTagId 
		FROM F1.FK_Auftrag as a
		JOIN F1.FK_Tag_Zuordnung as t on a.SheetNr = t.lAuftragId
		WHERE a.Auftragskennung <= '2' AND a.lTagsAnzahl > 0 AND t.lTagId = 5 OR t.lTagId = 1";
		
		$res = $this->db_lx->query($query);

		while ($row = $this->db_lx->fetchArray($res)) {
			$data[$row['VorgangNr']][$row['Auftragskennung']] = $row;
		}
				
		foreach($data as $val)
		{
			if(count($val) > 1)
			{
				if($val[1]['lTagId'] == 5 && $val[2]['lTagId'] == 1)
				{
					$Lx_Orders->SetOrderTags($val[1]['SheetNr'], 4);
				}
			}
		}
	}
	
} // Class end

$Cron = new Cron();
$Cron->CheckLxTags();
?>