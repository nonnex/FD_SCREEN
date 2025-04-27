<?php
class Lx_Lager
{
	private $db_lx;
	
	function __construct() {
		$this->db_lx = new DB_LX();
	}
	
	public function Get_LagerBestand_All($lArtikelId)
	{
		$FK_LagerBestand_query = "SELECT lId, lArtikelId, Bestand FROM F1.FK_LagerBestand WHERE lArtikelId='".$lArtikelId."'";
		$res = $this->db_lx->query($FK_LagerBestand_query);	
		$row = $this->db_lx->fetchArray($res);
		
		$ret = "";
		if(!@$row['lId']) { 
			$ret = false;
		} else {
			$ret = $row;
		}
		return $ret;
	}
		
	public function Get_LagerBestand_By_Id($lArtikelId)
	{
		$query = "SELECT Bestand FROM F1.FK_LagerBestand WHERE lArtikelId = '".$lArtikelId."'";
		$res = $this->db_lx->query($query);	
		$row = $this->db_lx->fetchArray($res);
		
		$Bestand = (INT) @$row['Bestand'];
		if($Bestand) $ret = $Bestand; else $ret = 0;
		
		return $ret;
	}

	public function Get_LagerInfo_By_Id($lArtikelId)
	{
		$ret['Bestand'] 		= $this->Get_LagerBestand_By_Id($lArtikelId);
		$ret['Reserviert']	 	= $this->Get_Reserviert_By_Id($lArtikelId);
		$ret['Verfuegbar'] 		= $ret['Bestand'] - $ret['Reserviert'];
		$ret['Mindestbestand'] 	= $this->Get_Mindestbestand_By_Id($lArtikelId);
		
		return $ret;
	}
	
	public function Get_Next_lId() {
		$query = "SELECT lId FROM F1.FK_LagerBestand ORDER BY lId DESC";
		$res = $this->db_lx->query($query);	
		$row = $this->db_lx->fetchArray($res);
		
		return $row['lId'] + 1;
	}

	public function Get_Reserviert_By_Id($lArtikelId)
	{
		$tsBis = date("Y-m-d");
		
		$query = "SELECT szVorgang, dftResMenge FROM F1.FK_Artikelreservierung WHERE lArtikelId = ".$lArtikelId." AND dftGeliefertMenge = 0 AND fAbgeschlossen = 0";
		
//echo "$query<br>\n";
		$res = $this->db_lx->query($query);

		$AB_Nr = NULL;
		$mg = array();
		
		while($row = $this->db_lx->fetchArray($res)) {
//echo '<pre>'; var_export($row); echo '</pre>';
			preg_match('/< AB (.*) >/', $row['szVorgang'], $AB_Nr);
			$AB_Nr = @$AB_Nr[1];
			$mg[] = $row['dftResMenge'];
		}
		
		$ret = array_sum($mg);
		
		return $ret;
	}
	
	public function Get_Mindestbestand_All()
	{
		$query = "SELECT lArtikelId, Mindestbestand FROM F1.FK_ArtikelBestandOptionen WHERE Mindestbestand > 0";
		$res = $this->db_lx->query($query);
		$row = $this->db_lx->fetchArray($res);
		
		while($row = $this->db_lx->fetchArray($res)) { 
			if(!$row['Mindestbestand']) $row['Mindestbestand'] = (INT) $row['Mindestbestand'];
			$Data[] = $row;
		}
		
		return $Data;
	}
	
	public function Get_Mindestbestand_By_Id($lArtikelId)
	{
		$query = "SELECT Mindestbestand FROM F1.FK_ArtikelBestandOptionen WHERE lArtikelId='".$lArtikelId."'";
		$res = $this->db_lx->query($query);
		$row = $this->db_lx->fetchArray($res);
		
		if(@$row['Mindestbestand']) {
			$ret = (INT) $row['Mindestbestand'];
		} else {
			$ret = 0;
		}
		
		return $ret;
	}
	
	public function Get_ArtikelNr_By_Id($lArtikelId)
	{
		$query = "SELECT ArtikelNr FROM F1.FK_Artikel WHERE SheetNr='".$lArtikelId."'";
		$res = $this->db_lx->query($query);
		$row = $this->db_lx->fetchArray($res);
		return $row['ArtikelNr'];
	}
	
	public function Get_ArtikelData_By_Id($lArtikelId)
	{
		$query = "SELECT * FROM F1.FK_Artikel WHERE SheetNr='".$lArtikelId."'";
		//$query = "SELECT *, b.Mindestbestand FROM F1.FK_Artikel as a JOIN F1.FK_ArtikelBestandOptionen as b on a.SheetNr = b.lArtikelId WHERE a.SheetNr='".$lArtikelId."'";
		
		
		$res = $this->db_lx->query($query);
		$row = $this->db_lx->fetchArray($res);
		
		$query_2 = "SELECT Mindestbestand FROM F1.FK_ArtikelBestandOptionen WHERE lArtikelId='".$lArtikelId."'";
		$res_2 = $this->db_lx->query($query_2);
		$row_2 = $this->db_lx->fetchArray($res_2);
		
		if(@$row_2['Mindestbestand']) {
			$row['Mindestbestand'] = $row_2['Mindestbestand'];
		} else {
			$row['Mindestbestand'] = 0;
		}
		
		return $row;
	}
	
	public function Get_LagerJounal() {
		$Jahr = date('Y');
		$query = "SELECT * FROM F1.FK_LagerJournal WHERE tsAktion >= '".$Jahr."-01-01' ORDER BY lNr DESC";
		$res = $this->db_lx->query($query);
		
		while($row = $this->db_lx->fetchArray($res)) {
			$data[] = $row;
		}
		return $data;
	}
	
	public function Get_LagerJounal_By_Id($lArtikelId) {
		$Jahr = date('Y');
		$ArtikelNr = $this->Get_ArtikelNr_By_Id($lArtikelId);
		$query = "SELECT * FROM F1.FK_LagerJournal WHERE szArtikelNr='".$ArtikelNr."' AND tsAktion >= '".$Jahr."-01-01' ORDER BY lNr DESC";
		$res = $this->db_lx->query($query);
		
		$data = array();
		while($row = $this->db_lx->fetchArray($res)) {
			$data[] = $row;
		}
		return $data;
	}
	
	
	public function LagerBuchung($lArtikelId, $dftMenge, $szBeschreibung)
	{
//echo "<pre>";
//echo "LagerBuchung($lArtikelId, $dftMenge, $szBeschreibung = 'Produktion Zu/Abgang'\n\n";
		/***
		LAGERBESTAND START
		***/
		// Get current Lagerbestand
		$row_2 = $this->Get_LagerBestand_All($lArtikelId);
		
		$lId = @$row_2['lId'];
		$System_updated = date("Y-m-d H:i:s");
		
		$szArtikelNr = $this->Get_ArtikelNr_By_Id($lArtikelId);
		
		if(!$szBeschreibung) {
			if($dftMenge < 0) $szBeschreibung = "Produktion Abgang";
			if($dftMenge > 0) $szBeschreibung = "Produktion Zugang";
		}
		
		if($lId == "") {
			$lId = $this->Get_Next_lId();
			
			$dftBestand = $dftMenge;
			$query_LagerBestand = "INSERT INTO F1.FK_LagerBestand(
				lId,
				lArtikelId,
				lLagerId,
				Bestand,
				System_created, 
				System_created_user,
				System_updated,
				System_updated_user,
				Menge_Bestellt
			) VALUES (
				'".$lId."',
				".$lArtikelId.",
				1,
				".$dftMenge.",
				'".$System_updated."',
				'U0',
				'".$System_updated."',
				'U0',
				0
			)
			";
		} else {			
			$lArtikelId = $row_2['lArtikelId'];
			$dftBestand = $row_2['Bestand'] + $dftMenge;
		
			$query_LagerBestand = "UPDATE F1.FK_LagerBestand SET Bestand='".$dftBestand."', System_updated='".$System_updated."' WHERE lId='".$lId."'";
		}
//echo $query_LagerBestand."\n\n";
	$this->db_lx->query($query_LagerBestand);
		/***
		LAGERBESTAND END
		***/
		
		/***
		LX_ID START
		***/
		// Get current LX_ID for Table 39 (FK_LagerJournal)
		$LX_ID_query = "SELECT NEW_ID FROM F1.LX_ID WHERE TABELLE_ID='39'";
		$res_1 = $this->db_lx->query($LX_ID_query);
//echo $LX_ID_query."\n\n";
		$row_1 = $this->db_lx->fetchArray($res_1);

		if(!$row_1['NEW_ID']) { trigger_error("LagerBuchung LX_ID == NULL", E_USER_ERROR); return; }

		$LX_NEW_ID = $row_1['NEW_ID'] + 1;
//echo "Old ID: ".$row_1['NEW_ID']."\nNew ID: ".$LX_NEW_ID."\n\n";
		
		$LX_ID_query = "UPDATE F1.LX_ID SET NEW_ID=".$LX_NEW_ID." WHERE TABELLE_ID='39'";
//echo $LX_ID_query."\n\n";
	$this->db_lx->query($LX_ID_query);
		/***
		LX_ID END
		***/
		
		/***
		LAGERJOURNAL START
		***/
		$query_LagerJournal = "INSERT INTO F1.FK_LagerJournal(
				lNr,
				szArtikelNr,
				lType,
				szBeschreibung,
				szAuftragNr,
				lAuftragsKennung,
				lPos,
				dftMenge,
				szUser,
				fSerienChargenNr,
				dftBestand,
				dftBestellt,
				lLagerId
			)VALUES(
				".$LX_NEW_ID.",
				'".$szArtikelNr."',
				3,
				'".$szBeschreibung."',
				NULL,
				0,
				0,
				".$dftMenge.",
				'Produktion',
				0,
				".$dftBestand.",
				0,
				1
			)
		";
		$this->db_lx->query($query_LagerJournal);
	}
 
 }
 ?>