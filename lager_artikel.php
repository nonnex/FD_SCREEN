<?php
include 'inc/db/db_lx.php';
include 'inc/db/db_fd.php';
include "inc/lx_orders.php";
include "inc/lx_lager.php";

$Lx_Orders 	= new Lx_Orders();
$Lx_Lager 	= new Lx_Lager();
$Lx_Artikel	= new Lx_Artikel();

/*
if(str_contains($_SERVER['HTTP_REFERER'], 'lager_artikel')) {
	$REFERER = 'lager.php';
} else {
	$REFERER = $_SERVER['HTTP_REFERER'];
}
*/

if(@$_GET['Ref']) $REFERER = $_GET['Ref'];

if(!@$_GET['ArtikelId']) die('Keine ArtikelId');
if(!@$_GET['AuftragsKennung']) {
	$AuftragsKennung = '';
} else {
	$AuftragsKennung = $_GET['AuftragsKennung'];	
}
	
$lArtikelId = $_GET['ArtikelId'];

$Art_Data 		= $Lx_Lager->Get_ArtikelData_By_Id($lArtikelId);
$LG_Info 		= $Lx_Lager->Get_LagerInfo_By_Id($lArtikelId);
$LagerJournal 	= $Lx_Lager->Get_LagerJounal_By_Id($lArtikelId);
$Absatz 		= $Lx_Artikel->Get_Artikel_Absatz($lArtikelId, date('Y') - 1);

$sendErr = "";

/***
* Freifelder
***/
$Freifelder = array(
	'Markierung' 	=> $Art_Data['szUserdefined1'],
	'2' 			=> $Art_Data['szUserdefined2'],
	'Werkstoff'		=> $Art_Data['szUserdefined3'],
	'Zeichnung' 	=> $Art_Data['szUserdefined4'],
	'Maße' 			=> $Art_Data['szUserdefined5'],
	'VPE' 			=> $Art_Data['szUserdefined6'],
);

if($Freifelder['Werkstoff']) {
	$WS_arr = str_getcsv($Freifelder['Werkstoff'],';');
	
	if(count($WS_arr) > 0) {
		foreach($WS_arr as $val) {
			$Ws_Row = str_getcsv($val,'=');
			$Ws_Data[utf8_encode($Ws_Row[0])] = $Ws_Row[1];
		}
		$Freifelder['Werkstoff'] = $Ws_Data;
	}
}

if($Freifelder['Maße']) {
	$Dim_arr = str_getcsv($Freifelder['Maße'],';');
	
	if(count($Dim_arr) > 0) {
		foreach($Dim_arr as $val) {
			$Dim_Row = str_getcsv($val,'=');
			$Dim_Data[utf8_encode($Dim_Row[0])] = $Dim_Row[1];
		}
		$Freifelder['Maße'] = $Dim_Data;
	}
}

if($Freifelder['VPE']) {
	$VPE_arr = str_getcsv($Freifelder['VPE'],';');
	
	if(count($VPE_arr) > 0) {
		$Freifelder['VPE'] = array('Pck' => $VPE_arr[0], 'Pal' =>@$VPE_arr[1]);
	}
}

function Build_Freifelder($Freifelder)
{
	$FF_Str = '<table border="0px" style="width:100px; margin:0; padding:0; border:1px solid white;">';
	foreach($Freifelder as $key => $val) {
		if($val) {
			$FF_Str .= '<tr>';
			if(is_array($val)) {
				$FF_Str .= '<td style="margin:0; padding:0; border:0px solid white;">'.$key.':</td>';
				$FF_Str .= '<td style="margin:0; padding:0; border:0px solid white;">'.Build_Freifelder($val).'</td>';
			} else {
				$FF_Str .= '<td style="width:40px;margin:0; padding:0; border:0px solid white;">'.$key.':</td>';
				$FF_Str .= '<td style="width:60px; margin:0; padding:0; border:0px solid white;color:yellow;">'.$val.'</td>';
			}
			$FF_Str .= '</tr>';
		}
	}
	$FF_Str .= '</table>';
	
	return $FF_Str;
}

$FF_Str = Build_Freifelder($Freifelder);
//var_export($Freifelder);

/***
* Actions
***/
if(@$_POST['action'] == 'saveMg') {
	$dftMenge = $_POST['iMenge'];
	$szBeschreibung = $_POST['iKommentar'];
	if(!$_POST['iMenge'] || $_POST['iMenge'] == 0)
		$sendErr .= '<div align="center" style="color:red;">Bitte gebe eine Menge an.</div>';
	
	if(!$sendErr) {
		$Lx_Lager->LagerBuchung($lArtikelId, $dftMenge, $szBeschreibung);
		header("Refresh:0.5");
	}
}

if(@$_POST['action'] == 'savePos') {
	$Lx_Orders->UpdateOrderPosition($_POST['AuftragsNr'], $_POST['PosNr'], $_POST['Artikel_Menge'], $_POST['lArtikelReservierungID'], $AuftragsKennung);
	$Stat_Update_Menge = '<div style="text-align:center;"><span style="color:green;">Positionsmenge gespeichert</span></div>';
	header("Refresh:0.5");
}

$style['Mindestbestand'] = 'font-size:9px;';
$strLinfo = '';
$lStatus_Min = '';
if($LG_Info['Mindestbestand'] > 0 && ($LG_Info['Mindestbestand'] - $LG_Info['Verfuegbar']) > 0) 
{
	$style['Mindestbestand'] = 'color:red;font-size:9px;white-space:nowrap;';
	$lStatus_Min .= '<img src="img/UI/minot.svg" width="9px" height="9px" style="position:relative; top:50%; transform:translateY(22%);" />';
	$strLinfo .= '('.$LG_Info['Mindestbestand'] - $LG_Info['Verfuegbar'].' aufüllen)';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lagerbestand</title>
  <link href="https://fonts.googleapis.com/css?family=Inconsolata" rel="stylesheet">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/theme.min.css"/>
  <link rel="stylesheet" href="css/style.css">
  
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>
  <script src="js/fd_script.js"></script>
</head>
<body>
<script>
// Prevent Form resubmit on "F5"
if(window.history.replaceState){
	window.history.replaceState(null,null,window.location.href);
}
</script>

<div id="dialog"></div>

<table align="center" border="0">
<tr><td valign="top">

<div class="lager-artikel-container" style="width:800px;margin-top:40px;border:0px solid lime;">
	
	<div class="lager-header">
		<span class="lager-column-header"><h3><?=$Art_Data['ArtikelNr']?> - <?=utf8_encode($Art_Data['Bezeichnung'])?></h3></span>
	</div>
	
	<Table border="0" width="100%" style="background:#222222;">
		<tr>
			<td valign="top">
				<div style="min-height:239px; padding:5px; position:relative; border:0px solid white;">
				<!--
					<form action="lager_artikel.php?ArtikelId=<?=$lArtikelId?>&AuftragsNr=<?=@$_GET['AuftragsNr']?>&PosNr=<?=@$_GET['PosNr']?>&AuftragsKennung=<?=$AuftragsKennung?>" 
						  method="post" 
						  onSubmit="if(!confirm('Zu-/ Abgang jetzt Buchen?')){return false;}"
					>
				-->
					<form action="lager_artikel.php?ArtikelId=<?=$lArtikelId?>&AuftragsNr=<?=@$_GET['AuftragsNr']?>&PosNr=<?=@$_GET['PosNr']?>&AuftragsKennung=<?=$AuftragsKennung?>&Ref=<?=@$_GET['Ref']?>&group=<?=@$_GET['group']?>" 
						  method="post" 
					>
						<input type="hidden" id="action" name="action" value="saveMg">
						<input type="hidden" id="ArtikelId" name="ArtikelId" value="<?=$lArtikelId?>">
						<table width="100%" border="0" width="100%">
							<tr>
								<td width="50px">Bestand:</td>
								<td width="50px" style="color:yellow;" align="right"><span style="padding-right:19px;<?if($LG_Info['Bestand']<0)echo 'color:red;'?>"><?=number_format($LG_Info['Bestand'],0,',','.')?></span></td>
								<td width="100%">&nbsp;</td>
							</tr>
							<tr><td>Reserviert:</td>		<td style="color:yellow;" align="right"><span style="padding-right:19px;<?if($LG_Info['Reserviert']<0)echo 'color:red;'?>"><?=number_format($LG_Info['Reserviert'],0,',','.')?></span></td><td>&nbsp;</td></tr>
							<tr><td><b>Verfügbar:</b></td>	<td style="color:yellow;" align="right"><span style="padding-right:19px;<?if($LG_Info['Verfuegbar']<0)echo 'color:red;'?>"><b><?=number_format($LG_Info['Verfuegbar'],0,',','.')?></b></span></td>
								<td style="<?=$style['Mindestbestand']?>">
								<?
								if(@$LG_Info['Mindestbestand']){
									echo $lStatus_Min.'Mindestbestand '.$LG_Info['Mindestbestand'].'&nbsp;'.$strLinfo."\n";
								}
								?>
								</td>
							</tr>
							<tr>
								<td>Zu-/Abgang:</td><td align="right"><input class="lager-input" type="number" id="iMenge" name="iMenge" min="-100000" max="100000"></td>
								<td><input style="width:100%;" class="lager-input-comment" type="text" id="iKommentar" name="iKommentar"></td>
							</tr>
						</table>
						<?=$sendErr?>
						<div style="margin:20px 0 20px 0;text-align:center;border:0px solid green;">
							<!--<button type="submit">Speichern</button>-->
							<input type="submit" value="Speichern" class="confirm" />
							</div>
					</form>
					<div style="position:absolute;bottom:10px;">
						<input type="button" class="button_back" onclick="window.location.href='<?=@$_GET['Ref']?>?group=<?=@$_GET['group']?>';" value="Zurück" />
					</div>
				</div>
			</td>
			<td width="30%" valign="top" style="font-size:8px;">
				<div style="padding:0px;border:1px solid white;">
					<table border="0">
						<tr><td align="right">Absatz <?=(date('Y') - 1)?>:</td><td style="color:yellow;"><?=number_format($Absatz['Absatz_Anno'],0,',','.')?></td></tr>
						<tr><td align="right">Aufträge:</td><td style="color:yellow;"><?=number_format($Absatz['count'],0,',','.')?></td></tr>
						<tr><td align="right">&Oslash; Menge/Auftrag:</td><td style="color:yellow;"><?=($Absatz['count']) ? number_format($Absatz['Absatz_Anno'] / $Absatz['count'],0,',','.') : 0;?></td></tr>
					</table>
				</div>
				<div style="margin-top:8px;padding:0px;border:0px solid white;"><?=$FF_Str?></div>
				<?if($Art_Data['Beschreibung']) { ?>
				<div style="margin-top:8px;padding:2px;border:1px solid white;"><?=nl2br(utf8_encode($Art_Data['Beschreibung']))?></div>
				<?}?>
			</td>
		</tr>
	</table>
	<div class="lager-footer"></div>
	<?
	if(@$_GET['AuftragsNr'] && @$_GET['PosNr']) {
		$pos = $Lx_Orders->GetOrderPosition($_GET['AuftragsNr'], $_GET['PosNr'], $AuftragsKennung);
		$StrLs = ($AuftragsKennung == 2) ? '(Lieferschein erstellt)' : '';
		?>
		<div class="lager-header"style="margin-top:8px;">
			<span class="lager-column-header"><h3>Auftragsposition <?=$StrLs?></h3></span>
		</div>
		
		<form 
			action="lager_artikel.php?ArtikelId=<?=$lArtikelId?>&AuftragsNr=<?=$_GET['AuftragsNr']?>&PosNr=<?=$_GET['PosNr']?>&AuftragsKennung=<?=$AuftragsKennung?>" 
			method="post">
			<input type="hidden" name="action" value="savePos">
			<input type="hidden" name="AuftragsNr" value="<?=$pos['AuftragsNr']?>">
			<input type="hidden" name="PosNr" value="<?=$pos['PosNr']?>">
			<input type="hidden" name="lArtikelReservierungID" value="<?=$pos['lArtikelReservierungID']?>">
			<div class="table-positions">
				<div class="table-row-artikelpos" style="padding:10px;">
					<!--<div class="artikel-pos-nr"><?=$pos['PosNr']?></div>-->
					<div class="artikel-pos-artikelnr">[<?=$pos['ArtikelNr']?>]</div>
					<div class="artikel-pos-bez"><?=utf8_encode($pos['Artikel_Bezeichnung'])?></div>
					<?
					if($AuftragsKennung == 1) {
						?>
						<div class="artikel-pos-menge"><input class="lager-input" id="Artikel_Menge" name="Artikel_Menge" type="number" value="<?=$pos['Artikel_Menge']?>"></div>
						<div class="artikel-pos-menge">
							<!--<button style="margin-left:10px;font-size:8px;" type="submit">Speichern</button>-->
							<input style="margin-left:10px;" type="submit" value="Speichern" class="confirm" />
						</div>
						<?
					} else {
						?>
						<div class="artikel-pos-menge"><input class="lager-input" id="Artikel_Menge" name="Artikel_Menge" type="number" value="<?=$pos['Artikel_Menge']?>" disabled></div>
						<div class="artikel-pos-menge"><button style="margin-left:10px;font-size:8px;" type="submit" disabled>Speichern</button></div>
						<?
					}
					?>
				</div>
				<?=@$Stat_Update_Menge?>
			</div>
			<div class="lager-footer"></div>
		</form>
		<?
	}
	?>
	
	<?
	if($Art_Data['bStatus_stueckliste'])
	{
		?>
		<div class="lager-header" style="margin-top:8px;">
			<span class="lager-column-header"><h3>Stückliste</h3></span>
		</div>
		<div class="table-positions">
		<?
		
		$bom = $Lx_Artikel->Get_Bom($Art_Data['ArtikelNr']);
//echo '<pre>';var_export($bom);echo "\n";
		foreach($bom as $bom_val)
		{
//echo $bom_val['UnterartikelNr']."\n";
			$UnterArtikelId = $Lx_Artikel->Get_ArtikelId_By_ArtikelNr($bom_val['UnterartikelNr']);
//echo "UnterArtikelId:".$UnterArtikelId."\n";
			$bom_Data = $Lx_Artikel->Get_Artikel_Info($UnterArtikelId);
//echo '<pre>';var_export($bom_Data);echo "\n";
			?>
			<div class="lagerjournal-row" style="line-height:16px;">
				<div class="lager-artikelnr" style="width:55px;text-align:left;"><?=$bom_Data['ArtikelNr']?></div>
				<div class="lager-bez" style=""><?=utf8_encode($bom_Data['Bezeichnung']).' '.$strLinfo?></div>
			</div>
			<?
		}
		?>
		</div>
		<div class="lager-footer"></div>
		<?
	}
	?>
	
	<div class="lager-header" style="margin-top:8px;">
		<span class="lager-column-header"><h3>Lagerjournal</h3></span>
	</div>

	<div class="table-positions">
	<!--
		<div class="lagerjournal-row">
			<div class="lager-time"><b>Zeit</b></div>
			<div class="lager-bez"><b>Beschreibung</b></div>
			<div class="lager-menge"><b>Menge</b></div>
			<div class="lager-user"><b>Benutzer</b></div>
		</div>
	-->
		<?	
	foreach($LagerJournal as &$pos) {
		$Zeit = new DateTimeImmutable($pos['tsAktion']);
		
		if($pos['szAuftragNr'] != "")
			$szAuftragNr = ":&nbsp;&ltLS_".$pos['szAuftragNr']."&gt"; 
		else 
			$szAuftragNr = "";
		
		$Lpos_style = '';
		if($pos['dftMenge'] < 0){
			$Lpos_style = 'color:red;';
		}elseif($pos['dftMenge'] > 0) {
			$Lpos_style = 'color:green;';
		}
		
		if($pos['dftMenge'] == 0) $dftMenge = ""; else $dftMenge = number_format($pos['dftMenge'],0,',','.');
		?>
		<div class="lagerjournal-row" style="line-height:11px;">
			<div class="lager-time"><?=$Zeit->format('d.m.y-H:i')?></div>
			<div class="lager-bez" style="<?=$Lpos_style?>"><?=htmlspecialchars($pos['szBeschreibung']) . $szAuftragNr?></div>
			<div class="lager-menge">
			<span style="<?=$Lpos_style?>"><?=$dftMenge?></span></div>
			<div class="lager-user"><?=$pos['szUser']?></div>
		</div>
		<?
	}
	?>
	</div>
	<div class="lager-footer"></div>
</div>

</td>

<td valign="top" style="font-size:8px;">
	<?
	/****
	*Zeichnungen
	****/
	// inkscape -z -f Variante_1.pdf -l Variante_1.svg
	$handle = @fopen("artdocu/CAD/".$Freifelder['Zeichnung'].".svg", "r");
	
	if($handle) {
		?>
		<div class="lager-header" style="margin-top:40px;"><span class="lager-column-header"><h3>Zeichnung</h3></span></div>
		<div style="border:0px solid lime;background-color:#111111;">
		<?
		$Out = '<svg x="0" y="0" width="400px" viewBox="-10 -10 700 900" style="-webkit-filter:invert(100%);filter:invert(100%);">'."\n";
		
		// ToDo: Suche nach Str "Stütze", "Feder", "AD" und ersetzte|ergänze durch Maße?
		while(($line = fgets($handle)) !== false) {
			if(!str_contains($line, '<?xml')) {
				$line = str_replace('(Schacht)',	'(<tspan style="fill:#0000ff;">'.@$Freifelder['Maße']['Schacht'].'</tspan>)',$line);
				$line = str_replace('(Stütze)', 	'(<tspan style="fill:#0000ff;">'.@$Freifelder['Maße']['Stütze'].'</tspan>)',$line);
				$line = str_replace('(AD)', 		'(<tspan style="fill:#0000ff;">'.@$Freifelder['Maße']['AD'].'</tspan>)',$line);
				$line = str_replace('(Feder)', 		'(<tspan style="fill:#0000ff;">'.@$Freifelder['Maße']['Feder'].'</tspan>)',$line);
				$line = str_replace('(Stab)', 		'(<tspan style="fill:#0000ff;">'.@$Freifelder['Maße']['Stab'].'</tspan>)',$line);
				$Out .= $line;
			}
		}
		fclose($handle);
	
		/****
		*Maßtabelle
		****/
		// $Out .= '<text x="50" y="50">TEST</text>'; //works
		
		$Out .= '</svg>'."\n";
		echo $Out;
		?>
		</div>
		<div class="lager-footer" style="margin-bottom:40px;"></div>
		<?
	}
	?>
</td>
</tr>
</table>
</body>
</html>