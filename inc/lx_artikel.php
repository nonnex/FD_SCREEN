<?php
class Lx_Artikel
{
	private $db_lx;
	
	function __construct() {
		$this->db_lx = new DB_LX();
	}
	
	public function Get_Artikel_All($WarengrpNr = "")
	{
		$query = "SELECT SheetNr, ArtikelNr, WarengrpNr, Bezeichnung, Menge_auftragsbestand, bStatus_lager, bStatus_Stueckliste, szUserdefined1, szUserdefined2, szUserdefined3, szUserdefined4, szUserdefined5, szUserdefined6
		FROM F1.FK_Artikel ";	
		if(!$WarengrpNr) {
			$query .= "WHERE bStatus_lager = 1 AND fGesperrt = 0 ORDER BY ArtikelNr";	
		} else {
			$query .= "WHERE bStatus_lager = 1 AND fGesperrt = 0 AND WarengrpNr=".$WarengrpNr." ORDER BY ArtikelNr";
		}
		
		$res = $this->db_lx->query($query);	
		
		$ret = array();
		
		while($row = $this->db_lx->fetchArray($res)) {
			$ret[] = $row;
		}
		
		return $ret;
	}
	
	public function Get_Artikel_Info($ArtikelId) 
	{
		$query = "SELECT * FROM F1.FK_Artikel WHERE SheetNr='".$ArtikelId."'";
		$res = $this->db_lx->query($query);	
		$row = $this->db_lx->fetchArray($res);

		return $row;
	}
	
	public function Get_ArtikelId_By_ArtikelNr($ArtikelNr) 
	{
		$query = "SELECT SheetNr FROM F1.FK_Artikel WHERE ArtikelNr='".$ArtikelNr."'";
		$res = $this->db_lx->query($query);	
		$row = $this->db_lx->fetchArray($res);

		return $row['SheetNr'];
	}
	
	public function Get_Artikel_Absatz($ArtikelId, $Year)
	{
		$query = "SELECT AuftragsNr, Artikel_Menge 
			FROM 	F1.FK_AuftragPos 
			WHERE 	lArtikelID='".$ArtikelId."' 
			AND 	Auftragskennung = 3 
			AND 	System_created >= '".$Year."-01'
			AND 	System_created <= '".$Year."-12'
		";
		
		$res = $this->db_lx->query($query);	
		
		$c = 0;
		$data['count'] = 0;
		$data['Absatz_Anno'] = 0;
		$data['Rechnungen'] = array();
		$data['Absatz_Monat'] = 0;
		
		while($row = $this->db_lx->fetchArray($res)) {
			$data['Rechnungen'][] = $row;
			$data['Absatz_Anno'] += $row['Artikel_Menge'];
			$c++;
		}
		$data['count'] = $c;
		if($c)
		$data['Absatz_Monat'] = $data['Absatz_Anno'] / $c;
		
		return $data;
	}
	
	public function Get_Artikel_Docs($ArtikelId)
	{
		$query = "SELECT * 
			FROM 	F1.FK_ArtikelDokument as a
			JOIN 	F1.FK_Dokument as b on a.lDokumentID = b.lID
			WHERE 	lArtikelID = '".$ArtikelId."' 
			
		";
		$res = $this->db_lx->query($query);	
		
		$Docs = '';
		while($row = $this->db_lx->fetchArray($res)) {
			$Docs[] = $row;
		}
		
		return $Docs;
	}
	
	public function Get_WarengruppenTree($root = 0)
	{
		$query = "SELECT WarengrpNr, Parent, Bezeichnung FROM F1.FK_Warengruppe ORDER BY Bezeichnung";
		$res = $this->db_lx->query($query);	
		
		$ret = array();
		
		while($row = $this->db_lx->fetchArray($res)) {
			$sub_data["id"] = $row["WarengrpNr"];
			$sub_data["name"] = utf8_encode($row["Bezeichnung"]);
			$sub_data["text"] = utf8_encode($row["Bezeichnung"]);
			$sub_data["icon"] = "fa fa-folder";
			$sub_data["parent_id"] = $row["Parent"];
			$data[] = $sub_data;
		}
		$tree = $this->getresultTree($data, $root); // Array output only
		//$tree = $this->Print_WarengruppenTree($data, $root);
		return $tree;
    }
	
	private function getresultTree(array $elements, $parentId = 0)
	{    
        $branch = array();
        
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {            
                $children = $this->getresultTree($elements, $element['id']);
                if ($children) {              
                    $element['nodes'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
	
	/***
	Stückliste
	***/
	public function Get_Bom($ArtikelNr)
	{
		$query = "SELECT * FROM F1.FK_Stueckliste WHERE ArtikelNr = '".$ArtikelNr."' ORDER BY Bezeichnung";
		$res = $this->db_lx->query($query);	
		
		$ret = array();
		
		while($row = $this->db_lx->fetchArray($res)) {
			$ret[] = $row;
		}
		
		return (count($ret)) ? $ret : null;
	}

}
?>