<?php
include 'lx_artikel.php';
include 'lx_lager.php';

class Lx_Orders 
{
    private $db_lx;
    private $db_fd;
    private $Lx_Lager;
    private $Lx_Artikel;
    private $AuftragsNr;
    private $KundenMatchcode;
    private $OrderData = array();
    
    public function __construct() {
        $this->db_lx = new DB_LX();
        $this->db_fd = new DB_FD();
        $this->Lx_Lager = new Lx_Lager();
        $this->Lx_Artikel = new Lx_Artikel();
    }
    
    public function GetAllOpenOrdersFromLX($Kennung = 1) {
        $data = array();
        $query = "
        SELECT 
            a.SheetNr as AuftragId,
            a.AuftragsNr as AuftragsNr, 
            a.AuftragsKennung as AuftragsKennung, 
            a.BestellNr,
            a.Datum_Erfassung as Datum_Erfassung, 
            a.tsLieferTermin as Liefertermin,
            a.KundenNr, 
            a.KundenMatchcode,
            a.szUserdefined1,
            a.szUserdefined2,
            a.szUserdefined3,
            a.szUserdefined4,
            a.szUserdefined5,
            a.szUserdefined6,
            p.PosNr as PosNr,
            p.szPosNr as szPosNr,
            p.PosTyp as PosTyp,
            p.lLagerId as LagerId,
            p.lArtikelID as ArtikelId,
            p.ArtikelNr as ArtikelNr, 
            p.Artikel_Bezeichnung as Artikel_Bezeichnung,
            p.PosText as PosText,
            p.Artikel_Menge as Menge 
        FROM F1.FK_AuftragPos as p 
        JOIN F1.FK_Auftrag as a on p.AuftragsNr = a.AuftragsNr AND p.AuftragsKennung = a.AuftragsKennung
        WHERE 
            a.AuftragsKennung = ".$Kennung." AND 
            a.bStatus_Weitergefuehrt = 0 AND
            a.bStatus_storniert = 0 AND 
            a.bStatus_obsolet = 0 AND
            a.bStatus_liefer_fakturiert = 0 AND
            p.PosTyp <= 1 AND 
            a.Datum_Erfassung >= '2002-01-01'
        ";
        $result = $this->db_lx->query($query);

        while ($row = $this->db_lx->fetchArray($result)) {
            $auftragsNr = $row['AuftragId'];
            if (!$auftragsNr || $auftragsNr === '0' || intval($auftragsNr) >= 99999) {
                error_log("Invalid or reserved AuftragId: $auftragsNr, Row: " . print_r($row, true));
                continue;
            }
            $data[$auftragsNr]['AuftragId'] = (string)$row['AuftragId'];
            $data[$auftragsNr]['AuftragsNr'] = $row['AuftragsNr'];
            $data[$auftragsNr]['AuftragsKennung'] = $row['AuftragsKennung'] ?: 1;
            $data[$auftragsNr]['Datum_Erfassung'] = $row['Datum_Erfassung'];
            $data[$auftragsNr]['BestellNr'] = utf8_encode($row['BestellNr']);
            $data[$auftragsNr]['Liefertermin'] = $row['Liefertermin'];    
            $data[$auftragsNr]['KundenNr'] = $row['KundenNr'];
            $data[$auftragsNr]['KundenMatchcode'] = utf8_encode($row['KundenMatchcode']);
            $data[$auftragsNr]['szUserdefined1'] = utf8_encode($row['szUserdefined1']);
            $data[$auftragsNr]['szUserdefined2'] = utf8_encode($row['szUserdefined2']);
            $data[$auftragsNr]['szUserdefined3'] = utf8_encode($row['szUserdefined3']);
            
            // Default Status and ShowPos
            $data[$auftragsNr]['Status'] = $Kennung === 2 ? 4 : 1;
            $data[$auftragsNr]['ShowPos'] = 1;
            $data[$auftragsNr]['Tags'] = $Kennung === 2 ? [['lTagId' => 1, 'szName' => 'Versendet']] : [['lTagId' => 4, 'szName' => 'Neu']];
            
            // Merge with ERP Tags if available
            $erpTags = $this->GetOrderTags($row['AuftragId']);
            if (!empty($erpTags)) {
                $data[$auftragsNr]['Tags'] = $erpTags;
                foreach ($erpTags as $tag) {
                    if ($tag['lTagId'] == 4) $data[$auftragsNr]['Status'] = 1;
                    elseif ($tag['lTagId'] == 2) $data[$auftragsNr]['Status'] = 2;
                    elseif ($tag['lTagId'] == 5) $data[$auftragsNr]['Status'] = 3;
                    elseif ($tag['lTagId'] == 1) $data[$auftragsNr]['Status'] = 4;
                    elseif ($tag['lTagId'] == 6) $data[$auftragsNr]['Status'] = 5;
                }
            }
            
            // Add Positionen
            $ABez_tmp = $row['Artikel_Bezeichnung'] ?: ($row['PosText'] ?: '');
            $data[$auftragsNr]['Positionen'][$row['PosNr']] = array(
                'PosNr' => $row['PosNr'], 
                'ArtikelId' => $row['ArtikelId'],
                'ArtikelNr' => $row['ArtikelNr'],
                'Artikel_Bezeichnung' => utf8_encode($ABez_tmp),
                'Artikel_Menge' => $row['Menge'],
                'Artikel_LagerId' => $row['LagerId']
            );
            ksort($data[$auftragsNr]['Positionen']);
        }
        
        return $data;
    }
    
    public function GetOrderInfo($AuftragsNr) {
        $query = "
        SELECT 
            a.SheetNr as AuftragId,
            a.AuftragsNr as AuftragsNr, 
            a.AuftragsKennung as AuftragsKennung, 
            a.BestellNr,
            a.Datum_Erfassung as Datum_Erfassung, 
            a.tsLieferTermin as Liefertermin,
            a.KundenNr, 
            a.KundenMatchcode,
            p.PosNr as PosNr,
            p.szPosNr as szPosNr,
            p.PosTyp as PosTyp,
            p.lLagerId as LagerId,
            p.lArtikelID as ArtikelId,
            p.ArtikelNr as ArtikelNr, 
            p.Artikel_Bezeichnung as Artikel_Bezeichnung,
            p.PosText as PosText,
            p.Artikel_Menge as Menge 
        FROM F1.FK_AuftragPos as p 
        JOIN F1.FK_Auftrag as a on p.AuftragsNr = a.AuftragsNr AND p.AuftragsKennung = a.AuftragsKennung
        WHERE 
            p.PosTyp <= 1 AND
            a.AuftragsNr = '".$AuftragsNr."' AND 
            a.Datum_Erfassung >= '2002-01-01'
        ";
        
        $result = $this->db_lx->query($query);
        $data = [];
        
        while ($row = $this->db_lx->fetchArray($result)) {
            $auftragsNr = $row['AuftragId'];
            if (!$auftragsNr || $auftragsNr === '0' || intval($auftragsNr) >= 99999) {
                error_log("Invalid or reserved AuftragId in GetOrderInfo: $auftragsNr, Row: " . print_r($row, true));
                continue;
            }
            $data[$auftragsNr]['AuftragId'] = $row['AuftragId'];
            $data[$auftragsNr]['AuftragsNr'] = $row['AuftragsNr'];
            $data[$auftragsNr]['AuftragsKennung'] = $row['AuftragsKennung'] ?: 1;
            $data[$auftragsNr]['Datum_Erfassung'] = $row['Datum_Erfassung'];
            $data[$auftragsNr]['BestellNr'] = utf8_encode($row['BestellNr']);
            $data[$auftragsNr]['Liefertermin'] = $row['Liefertermin'];    
            $data[$auftragsNr]['KundenNr'] = $row['KundenNr'];
            $data[$auftragsNr]['KundenMatchcode'] = utf8_encode($row['KundenMatchcode']);
            
            // Default Status and ShowPos
            $data[$auftragsNr]['Status'] = $row['AuftragsKennung'] === 2 ? 4 : 1;
            $data[$auftragsNr]['ShowPos'] = 1;
            $data[$auftragsNr]['Tags'] = $row['AuftragsKennung'] === 2 ? [['lTagId' => 1, 'szName' => 'Versendet']] : [['lTagId' => 4, 'szName' => 'Neu']];
            
            // Merge with ERP Tags if available
            $erpTags = $this->GetOrderTags($row['AuftragId']);
            if (!empty($erpTags)) {
                $data[$auftragsNr]['Tags'] = $erpTags;
                foreach ($erpTags as $tag) {
                    if ($tag['lTagId'] == 4) $data[$auftragsNr]['Status'] = 1;
                    elseif ($tag['lTagId'] == 2) $data[$auftragsNr]['Status'] = 2;
                    elseif ($tag['lTagId'] == 5) $data[$auftragsNr]['Status'] = 3;
                    elseif ($tag['lTagId'] == 1) $data[$auftragsNr]['Status'] = 4;
                    elseif ($tag['lTagId'] == 6) $data[$auftragsNr]['Status'] = 5;
                }
            }
            
            // Add Positionen
            $ABez_tmp = $row['Artikel_Bezeichnung'] ?: ($row['PosText'] ?: '');
            $data[$auftragsNr]['Positionen'][$row['PosNr']] = array(
                'PosNr' => $row['PosNr'], 
                'ArtikelId' => $row['ArtikelId'],
                'ArtikelNr' => $row['ArtikelNr'],
                'Artikel_Bezeichnung' => utf8_encode($ABez_tmp),
                'Artikel_Menge' => $row['Menge'],
                'Artikel_LagerId' => $row['LagerId']
            );
            ksort($data[$auftragsNr]['Positionen']);
        }
        
        return $data[$AuftragsNr] ?? [];
    }
    
    public function GetOrderPosition($AuftragsNr, $Pos, $AuftragsKennung) {
        $query = "SELECT PosNr, AuftragsNr, ArtikelNr, Artikel_Bezeichnung, PosText, Artikel_Einheit, Artikel_Menge, Artikel_Preisfaktor, lArtikelReservierungID 
        FROM F1.FK_AuftragPos 
        WHERE AuftragsKennung = ".$AuftragsKennung." AND AuftragsNr='".$AuftragsNr."' AND PosNr='".$Pos."'
        ";
        $res_1 = $this->db_lx->query($query);
        return $this->db_lx->fetchArray($res_1);
    }
    
    public function UpdateOrderPosition($AuftragsNr, $Pos, $Artikel_Menge, $lArtikelReservierungID, $AuftragsKennung) {
        $query = "UPDATE F1.FK_AuftragPos SET Artikel_Menge='".$Artikel_Menge."', Artikel_Preisfaktor='".$Artikel_Menge."' 
            WHERE AuftragsNr='".$AuftragsNr."' AND AuftragsKennung = ".$AuftragsKennung." AND PosNr='".$Pos."'";
// Deactivated in DEV phase
//$this->db_lx->query($query);
        
        $query = "SELECT * FROM F1.FK_Artikelreservierung WHERE lID = '".$lArtikelReservierungID."'";
        $res = $this->db_lx->query($query);
        $row = $this->db_lx->fetchArray($res);
        
        if ($row) {
            $query = "UPDATE F1.FK_Artikelreservierung SET dftResMenge = '".$Artikel_Menge."' WHERE lID = '".$lArtikelReservierungID."'";        
// Deactivated in DEV phase
//$this->db_lx->query($query);
        }
    }
    
    public function Get_Tracking_Status($SST) {
        $data = array('Status' => '');
        $query = "SELECT * FROM fd_schenker_tracking WHERE SST = '".$SST."'";
        $res = $this->db_fd->query($query);
        $data = $this->db_fd->fetchArray($res);
        return (count($data)) ? $data : null;
    }
        
    public function CreateMindestbestandOrder() {
        $data = $this->Lx_Lager->Get_Mindestbestand_All();
        $Tmp = array();
        $ret = array();
        
        foreach ($data as $key => $val) {
            if ($val['lArtikelId']) {
                $Tmp[$key]['lArtikelId'] = $val['lArtikelId'];
                $Tmp[$key]['Lager'] = $this->Lx_Lager->Get_LagerInfo_By_Id($val['lArtikelId']);
                
                if ($Tmp[$key]['Lager']['Verfuegbar'] < $Tmp[$key]['Lager']['Mindestbestand']) {
                    $ret[] = $Tmp[$key];
                }
            }
        }
        
        $Liefertermin = new DateTime();
        $Liefertermin = $Liefertermin->modify('+1 month +3 day')->format('Y-m-d');
        
        $MinOrder = array(
            'AuftragId' => 'V_99999',
            'AuftragsNr' => '99999',
            'AuftragsKennung' => 1,
            'Datum_Erfassung' => date('Y-m-d H:i:s'),
            'BestellNr' => 'Mindestbestand',
            'Liefertermin' => $Liefertermin,
            'KundenNr' => '',
            'KundenMatchcode' => 'FERRODOM',
            'Status' => 2,
            'ShowPos' => 1,
            'Tags' => array(
                0 => array(
                    'lTagId' => 2,
                    'szName' => 'Produktion',
                ),
            ),
        );
        
        $p = 2;
        $Positionen = array();
        
        foreach ($ret as $key => $val) {
            $MinData = $this->Lx_Artikel->Get_Artikel_Info($val['lArtikelId']);
            $Positionen[$p] = array(
                'PosNr' => $p,
                'ArtikelId' => $MinData['SheetNr'],
                'ArtikelNr' => $MinData['ArtikelNr'],
                'Artikel_Bezeichnung' => $MinData['Bezeichnung'],
                'Artikel_Menge' => $val['Lager']['Mindestbestand'] - $val['Lager']['Verfuegbar'],
                'Artikel_LagerId' => 1,
            );
            $p++;
        }
        
        $MinOrder['Positionen'] = $Positionen;
        return $MinOrder;
    }
    
    public function FilterByStatus($Data, $Status) {
        $Data_f = array();
        foreach ($Data as $k => &$v) {
            if ($Data[$k]['Status'] == $Status) {
                $Data_f[$k] = $Data[$k];
            }
        }
        return $Data_f;
    }
    
    public function GetOrderTags($AuftragId) {
        if (!$AuftragId || intval($AuftragId) >= 99999 || strpos($AuftragId, 'V_') === 0 || strpos($AuftragId, 'E_') === 0) return [];
        
        $query = "SELECT 
            z.lTagId as lTagId,
            t.szName as szName
            FROM F1.FK_Tag_Zuordnung as z
            JOIN F1.FK_Tag as t on z.lTagId = t.lID
            WHERE z.lAuftragId = '".$AuftragId."'
        ";
        $res = $this->db_lx->query($query);
        
        $Tags = array();
        while ($row = $this->db_lx->fetchArray($res)) {
            $Tags[] = $row;
        }
        
        return $Tags;
    }
    
    public function SetOrderTags($AuftragId, $Status) {
        if (!$AuftragId || intval($AuftragId) >= 99999 || strpos($AuftragId, 'V_') === 0 || strpos($AuftragId, 'E_') === 0) return;
        
        $TagArray = array(
            1 => 4, // Neu
            2 => 2, // Produktion
            3 => 5, // Versandbereit
            4 => 1, // Versendet
            5 => 6, // Fakturieren
        );
        
        $Tags = $this->GetOrderTags($AuftragId);
        $TagId = $TagArray[$Status];
        
        if (!$Tags) {
            $query = "INSERT INTO F1.FK_Tag_Zuordnung
            (
                `lID`, 
                `lTagId`, 
                `lAuftragId`,
                `lKundeId`,
                `lLieferantId`,
                `lArtikelId`
            )
            VALUES 
            (
                null,
                '".$TagId."', 
                '".$AuftragId."', 
                null, 
                null,
                null
            )";
        } else {
            $query = "UPDATE F1.FK_Tag_Zuordnung SET lTagId ='".$TagId."' WHERE lAuftragId = '".$AuftragId."'";
        }
// Deactivated in DEV phase
// $res = $this->db_lx->query($query); //Deactivated for now
        return $res;
    }
    
    public function SetDeliveryTime($AuftragId, $DeliveryTime) {
        if (!$AuftragId || intval($AuftragId) >= 99999 || strpos($AuftragId, 'V_') === 0 || strpos($AuftragId, 'E_') === 0) return;
        $query = "UPDATE F1.FK_Auftrag SET szUserdefined1 = '".$DeliveryTime."' WHERE SheetNr = '".$AuftragId."'";
// Deactivated in DEV phase
//$res = $this->db_lx->query($query);
        return $res;
    }
    
    public function GetOrderContainer($Data, $Status = 0) {
        $key_values = array_column($Data, 'Liefertermin'); 
        array_multisort($key_values, SORT_ASC, $Data);
        
        if ($Status) {
            $Data = $this->FilterByStatus($Data, $Status);
        }
        
        $content = '';
        
        foreach ($Data as &$val) {
            if (!$val['AuftragId'] || $val['AuftragId'] === '0') {
                error_log("Invalid AuftragId in GetOrderContainer: " . print_r($val, true));
                continue;
            }
            $Liefertermin = new DateTimeImmutable($val['Liefertermin']);
            $Erfassungsdatum = new DateTimeImmutable($val['Datum_Erfassung']);
            
            $bc = match ($val['Status']) {
                1 => 'fb7d44', // Neu
                2 => '2a92bf', // Produktion
                3 => 'f4ce46', // Versandbereit
                4 => '00b961', // Versendet
                default => '00b961'
            };
            
            if (file_exists('img/clients/'.$val['KundenNr'].'.png')) {
                $StrKunde = '<img src="img/clients/'.$val['KundenNr'].'.png" height="16px"/>';
            } else {
                $StrKunde = $val['KundenMatchcode'];
            }
            
            $ShowPos = $val['ShowPos'] ? '' : 'display:none;';
            $StateImg = $val['ShowPos'] ? 'up.png' : 'dn.png';
            
            $StrTags = '';
            foreach ($val['Tags'] as $tags) {
                $StrTags .= '['.$tags['lTagId'].':'. $tags['szName'].']';
            }
            
            $StrVersand = '';
            $TagIcon = '';
            $StrSpedi = '';
            $DeliveryStatus['Status'] = '';
            
            if ((isset($val['szUserdefined2']) && stristr($val['szUserdefined2'], 'Schenker')) && (isset($val['szUserdefined3']) && $val['szUserdefined3'])) {
                $DeliveryStatus = $this->Get_Tracking_Status(trim($val['szUserdefined3']));
                $DeliveryStatus = (isset($DeliveryStatus['Status'])) ? $DeliveryStatus : array('Status' => '') ;
                $StrSpedi = '<a class="spedi" href="schenkerAPI/tracking.php?SST_Nr='.trim($val['szUserdefined3']).'"><img src="img/UI/DB.png" height="12px" style="padding-right:5px;"/></a>';
            }
            
            if (@$val['Tags'][0]['lTagId']) {
                if ($val['Tags'][0]['lTagId'] == 5 && !$DeliveryStatus['Status'])               $TagIcon = 'vorb.svg';
                if ($val['Tags'][0]['lTagId'] == 5 && $DeliveryStatus['Status'])                $TagIcon = 'delivery_0.svg';
                if ($val['Tags'][0]['lTagId'] == 5 && $DeliveryStatus['Status'] == 'Booked')    $TagIcon = 'delivery_2.svg';
                if ($val['Tags'][0]['lTagId'] == 1 && $DeliveryStatus['Status'] != 'Delivered') $TagIcon = 'delivery_0.svg';
                if ($val['Tags'][0]['lTagId'] == 1 && $DeliveryStatus['Status'] == 'Delivered') $TagIcon = 'delivery_1.svg';
                if ($val['Tags'][0]['lTagId'] == 2)                                             $TagIcon = 'inprod.svg';
                if ($val['Tags'][0]['lTagId'] == 4)                                             $TagIcon = 'neu.svg';
                if ($val['Tags'][0]['lTagId'] == 6)                                             $TagIcon = 'fakturieren.svg';
            }
            
            $TagIconEn = ($val['AuftragsKennung'] == 2 && $val['Tags'][0]['lTagId'] == 5 && stristr($val['szUserdefined2'], 'Schenker') != 'Schenker') ? '' : 'disabled';
            $KennungStr = ($val['AuftragsKennung'] == 2) ? 'LS' : 'AB';
                
            $StrVersendetAm = '';
            if ((isset($val['szUserdefined2']) && $val['szUserdefined2'])) {
                $StrVersendetAm = ''.@$val['szUserdefined1'].'('.$val['szUserdefined2'].') '.$val['szUserdefined3'];
            }
            
            $StrVersand .= '<form id="f_'.$val['AuftragId'].'" name="f_'.$val['AuftragId'].'" method="POST" class="delivery-form">
                <input type="hidden" class="form-control" id="AuftragId" name="AuftragId" value="'.$val['AuftragId'].'" />
                <input type="hidden" class="form-control" id="Tag" name="Tag" value="'.$val['Tags'][0]['lTagId'].'" />
                <input type="image" src="img/UI/'.$TagIcon.'" class="delivery-button confirm" value="" id="delivery_button" name="delivery_button" '.$TagIconEn.' />
            </form>';    
            
            $style_LT = '';
            $today = new DateTime(date('Y-m-d'));
            $dif_days = date_diff($today, $Liefertermin)->format('%r%a');
            if ($dif_days <= 0)                    $style_LT = 'color:red;';
            elseif ($dif_days > 0 && $dif_days <= 3)    $style_LT = 'color:#e55f00;';

            $content .= '<li class="no-drag" id="'.$val['AuftragId'].'" style="min-height:34px;" data-status="'.$val['Status'].'">';    
            $content .= '<div style="height:5px;background-color:#'.$bc.'"></div>
                <div class="order-container">
                    <div class="table-orderinfo">
                        <div class="table-row-orderinfo">
                            <div class="table-cell-kunde">'.$StrKunde.'</div>
                            <div class="table-cell-delivery">'.$StrSpedi.'</div>
                            <div class="table-cell-delivery">'.$StrVersand.'</div>
                            <div class="table-cell-liefertermin" style="padding-right:5px;'.$style_LT.'">'.$Liefertermin->format('d.m.y').'</div>
                        </div>
                        <div class="table-row-orderinfo" style="position: relative; top:-4px;">
                            <div class="table-cell-AuftragsNr">'.$KennungStr.' '.$val['AuftragsNr'].' '.$Erfassungsdatum->format('d.m.y').'</div>
                            <div class="table-cell-delivery" style="font-size:6px;">'.$StrVersendetAm.'</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-BestellNr">Bst: '.$val['BestellNr'].'</div>
                        </div>
                    </div>';
                    
            $content .= '<div style="width:auto;height:auto;text-align:center;border:0px solid blue;margin-top:-26px;">';
            
            if ($val['AuftragId']) {
                $content .= '<img style="position: relative; top:-3px;" class="apply4job" id="'.$val['AuftragId'].'" height="12px" src="img/UI/'.$StateImg.'">';
            } else {
                $content .= '<img style="position: relative; top:-3px;" class="" id="'.$val['AuftragId'].'" height="12px" src="img/UI/'.$StateImg.'">';
            }
                
            $state_check = '';
            $style_v = '';
            $str_verfueg = '';
                        
            $content .= '<!-- Show/Hide -->
                    <div style="'.$ShowPos.'">
                        <div class="table-positions" style="margin-top:6px;">';
                        
            if (is_array($val['Positionen'])) {
                foreach ($val['Positionen'] as &$pos) {
                    if ($pos['ArtikelId']) {
                        $LInfo = $this->Lx_Lager->Get_LagerInfo_By_Id($pos['ArtikelId']);
                        $ArtInfo = $this->Lx_Artikel->Get_Artikel_Info($pos['ArtikelId']);
                        
                        if ($LInfo['Verfuegbar'] >= 0) {
                            $state_src = 'check_done';
                        } else {
                            $state_src = 'check_inproc';
                        }
                            
                        if ($ArtInfo['bStatus_lager']) {
                            $state_src = '';
                            $style_v = '';
                            
                            if ($val['AuftragId']) {
                                if ($LInfo['Verfuegbar'] >= 0) {
                                    $state_src = 'check_done';
                                    $style_v = 'color:green;';
                                } elseif ($LInfo['Verfuegbar'] + $pos['Artikel_Menge'] <= 0) {
                                    $state_src = 'check_red';
                                    $style_v = 'color:red;';
                                } elseif ($LInfo['Verfuegbar'] + $pos['Artikel_Menge'] > 0) {
                                    $state_src = 'check_inproc';
                                    $style_v = 'color:#e55f00;';
                                }
                            } else {
                                if ($LInfo['Verfuegbar'] < $LInfo['Mindestbestand'] && $LInfo['Verfuegbar'] == 0) {
                                    $state_src = 'check_red';
                                    $style_v = 'color:red;';
                                } else {
                                    $state_src = 'check_inproc';
                                    $style_v = 'color:#e55f00;';
                                }
                            }
                            
                            $str_verfueg = '('.number_format($LInfo['Verfuegbar'],0,',','.').')';
                                
                            $state_check = '<a id="foo" href="lager_artikel.php?ArtikelId='.$pos['ArtikelId'].'&AuftragsNr='.$val['AuftragsNr'].'&PosNr='.$pos['PosNr'].'&AuftragsKennung='.$val['AuftragsKennung'].'&Ref=index.php">
                            <img src="img/UI/'.$state_src.'.png" width="8px" height=8px" style="margin-top:-1px;" class="apply4job2" />
                            </a>';
                        }
                        
                        $checkArr[$pos['PosNr']] = NULL;
                        
                        $checked = "";

                        $content .= '<div style="'.$style_v.'" class="table-row-artikelpos" ArtikelId="'.$pos['ArtikelId'].'">
                            <div class="artikel-pos-artikelnr">'.$pos['ArtikelNr'].'</div>
                            <div class="artikel-pos-bez">'.$pos['Artikel_Bezeichnung'].'</div>
                            <div class="artikel-pos-verfuegbar" style="'.$style_v.'">'.$str_verfueg.'</div>
                            <div class="artikel-pos-menge">'.number_format($pos['Artikel_Menge'],0,',','.').'</div>
                            <div class="artikel-pos-check">'.$state_check.'</div>
                        </div>';
                    } else {
                        $content .= '<div style="'.$style_v.'" class="table-row-artikelpos" ArtikelId="'.$pos['ArtikelId'].'">
                            <div class="artikel-pos-bez">'.$pos['Artikel_Bezeichnung'].'</div>
                            <div class="artikel-pos-verfuegbar" style="'.$style_v.'">'.$str_verfueg.'</div>
                            <div class="artikel-pos-menge">'.number_format($pos['Artikel_Menge'],0,',','.').'  </div>
                        </div>';
                    }
                }
            }
            
            $content .= '</div>
                        </div>
                    </div>
                </div>
            </li>';
        }
                    
        return $content;
    }
}