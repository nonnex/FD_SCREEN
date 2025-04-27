<?php
include 'inc/db/db_lx.php';
include 'inc/db/db_fd.php';
include 'inc/lx_artikel.php';
include 'inc/lx_lager.php';

function menu($arr) {
	echo '<ul>'."\n";
	foreach ($arr as $val) {
		if (!empty($val['nodes'])) {
			echo "<li>";
			menu($val['nodes']);
			echo "</li>\n";
		} else {
			echo '<li style="height:30px;"><a href="?group='.$val['id'].'">' . $val['name'] . "</a><br></li>\n";
		}
	}
	echo "</ul>\n";
}

$Lx_Artikel = new Lx_Artikel();
$Lx_Lager = new Lx_Lager();

$ArtikelData = $Lx_Artikel->Get_Artikel_All(@$_GET['group']);
$WarenGrpTree = $Lx_Artikel->Get_WarengruppenTree(1); // 2

$LagerJournal = $Lx_Lager->Get_LagerJounal();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Auftragsübersicht</title>
  <link href="https://fonts.googleapis.com/css?family=Inconsolata" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="lager-container" style="margin-top:40px;">
<table border="0" width="100%">
<tr>

<td valign="top">
	<div style="margin:10px 0px 20px 0px;text-align:center;">
		<form action="index.php"><input type="submit" class="button_back" value="Zurück"></form>
	</div>
	<div style="margin-top:20px;font-size:12px;">
	<hr>
	<a href="lager.php">Alle Lagerartikel</a>
	<hr>
	<br>
		<?menu($WarenGrpTree);?>
	</div>
	<hr>
</td>

<td valign="top">
	<table border="0" width="100%">
	<tr>
	<td><div id="treeview"></div></td>
	<td>

	<div class="lager-header"><span class="lager-column-header"><h3>Lagerbestände</h3></span></div>

	<div class="table-positions">
		<div class="lagerjournal-row">
			<div class="lager-status"><b>S</b></div>
			<div class="lager-artikelnr" style="text-align:left;width:50px;"><b>Art-Nr.</b></div>
			<div class="lager-bez"><b>Bez.</b></div>
			<div class="lager-menge" style="color:#BBBBBB;width:100px;"><b>[<?=date('Y') - 1?>|Ø|#]</b></div>
			<div class="lager-menge"><b>Bestand</b></div>
			<div class="lager-menge"><b>Res.</b></div>
			<div class="lager-menge"><b>Verf.</b></div>
			<div class="lager-menge"><b>Mind.</b></div>
		</div>
		<?
		foreach($ArtikelData as &$Artikel) {
			$Lager = $Lx_Lager->Get_LagerInfo_By_Id($Artikel['SheetNr']);		
			$Absatz = $Lx_Artikel->Get_Artikel_Absatz($Artikel['SheetNr'], date('Y') - 1);
			
			$style = array();
			$strLinfo = '';
			$lStatus_Min = '';
			
			if($Lager['Mindestbestand'] > 0 && ($Lager['Mindestbestand'] - $Lager['Verfuegbar']) > 0) {
				$lStatus_Min .= '<img src="img/UI/minot.svg" width="9px" height="9px" style="transform:translateY(22%);" />';
				$style['Mindestbestand'] = 'color:red;';
				$strLinfo .= '<span style="'.$style['Mindestbestand'].'">('.$Lager['Mindestbestand'] - $Lager['Verfuegbar'].' auffüllen)</span>';
			}
			
			if($Lager['Verfuegbar'] < 0) {
				$lStatus_Min .= '<img src="img/UI/reserv.svg" width="10px" height="10px" style="position:relative; top:50%; transform:translateY(25%);" />';
				$style['Verfuegbar'] = 'color:orange;';
			}
			
			if($Absatz['Absatz_Anno']) {
				$Str_Absatz = '['.number_format($Absatz['Absatz_Anno'],0,',','.').'|'.number_format($Absatz['Absatz_Monat'],0,',','.').'|'.$Absatz['count'].']';
			} else {
				$Str_Absatz = '&nbsp;';
			}
			
			?>
			<div class="lagerjournal-row" style="line-height:16px;" onclick="location.href='lager_artikel.php?ArtikelId=<?=$Artikel['SheetNr']?>&group=<?=@$_GET['group']?>&Ref=lager.php';">
				<div class="lager-status"><?=$lStatus_Min?></div>
				<div class="lager-artikelnr" style="width:55px;text-align:left;"><?=$Artikel['ArtikelNr']?></div>
				<div class="lager-bez" style=""><?=utf8_encode($Artikel['Bezeichnung']).' '.$strLinfo?></div>
				<div class="lager-menge" style="color:#BBBBBB;width:100px;"><?=$Str_Absatz?></div>
				<div class="lager-menge" style="<?=@$style['Bestand']?>font-size:7px;"><?=$Lager['Bestand'] > 0 ? number_format($Lager['Bestand'],0,',','.') : ""?></div>
				<div class="lager-menge" style="<?=@$style['Reserviert']?>color:#888888;font-size:7px;"><?=$Lager['Reserviert'] > 0 ? number_format($Lager['Reserviert'],0,',','.') : ""?></div>
				<div class="lager-menge" style="<?=@$style['Verfuegbar']?>"><b><?=$Lager['Verfuegbar'] > 0 ? number_format($Lager['Verfuegbar'],0,',','.') : ""?></b></div>
				<div class="lager-menge" style="<?=@$style['Mindestbestand']?>color:#888888;font-size:7px;"><?=$Lager['Mindestbestand'] > 0 ? "(".number_format($Lager['Mindestbestand'],0,',','.').")" : ""?></div>
			</div>
			<?
		}
		?>
	</div>
	<div class="lager-footer" style="margin-bottom:16px;"></div>
<? 
if(!@$_GET['group']) { ?>
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
			<div class="lager-time" style="width:55px;text-align:left;"><?=$Zeit->format('d.m.y-H:i')?></div>
			<div class="lager-artikelnr" style="width:55px;text-align:left;<?=$Lpos_style?>"><?=$pos['szArtikelNr']?></div>
			<div class="lager-bez" style="<?=$Lpos_style?>"><?=htmlspecialchars($pos['szBeschreibung']) . $szAuftragNr?></div>
			<div class="lager-menge">
			<span style="<?=$Lpos_style?>"><?=$dftMenge?></span></div>
			<div class="lager-user"><?=$pos['szUser']?></div>
		</div>
		<?
	}
	?>
	</div>
<? } ?>
</tr>
</table>
</div>

</body>
</html>