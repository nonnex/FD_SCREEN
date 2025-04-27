<?php
include 'inc/db/db_lx.php';
include 'inc/db/db_fd.php';
include "inc/lx_orders.php";
include "inc/lx_lager.php";

if(!$_GET['AuftragsNr']) die('Keine AuftragId');
$AuftragsNr = $_GET['AuftragsNr'];

$LX_Orders = new Lx_Orders();

$OrderData = $LX_Orders->GetOrderInfo($AuftragsNr);

$Liefertermin = new DateTimeImmutable($OrderData['Liefertermin']);
$Erfassungsdatum = new DateTimeImmutable($OrderData['Datum_Erfassung']);
			
//echo '<pre>';var_export($OrderData);echo '</pre>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Versand</title>
  <link href="https://fonts.googleapis.com/css?family=Inconsolata" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dragula/3.7.3/dragula.min.css"/>
  <link rel="stylesheet" href="./css/style.css"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
</head>
<body>
<script>
// Prevent resubmit on "F5"
if(window.history.replaceState){
	window.history.replaceState(null,null,window.location.href);
}
</script>

<div class="lager-artikel-container" style="margin-top:100px;">
	
	<div class="lager-header">
		<span class="lager-column-header"><h3>Auftrag <<?=$OrderData['AuftragsNr']?>> als versedet markieren</h3></span>
	</div>

	<div style="padding:10px;background:#222222;">
		<li class="drag-item" id="5155">
			<div style="height:5px;background-color:green"></div>
			<div class="order-container">
				<div class="table-orderinfo">
					<div class="table-row-orderinfo">
						<div class="table-cell-kunde"><img src="img/clients/<?=$OrderData['KundenNr']?>.png" height="20px"></div>
						<div class="table-cell-liefertermin"><img style="padding-right:5px;padding-top:5px;" height="18px" src="img/UI/delivery.png"><?=$Liefertermin->modify('-3 day')->format('d.m.y')?></div>
					</div>
					<div class="table-row-orderinfo">
						<div class="table-cell" style="text-align:left;font-size:12px;">AB-Nr.:<?=$OrderData['AuftragsNr']?> vom <?=$Erfassungsdatum->format('d.m.y')?></div>
						<div class="table-cell" style="text-align:right;font-size:12px;">Bestell-Nr.: <?=$OrderData['BestellNr']?></div>
					</div>
				</div>
			</div>
			
			<div class="table-positions" style="padding:0px 4px 0px 4px;">
			<?
			foreach($OrderData['Positionen'] as &$pos) {
				echo '<div class="table-row-artikelpos" ArtikelId="'.$pos['ArtikelId'].'">
					<div class="artikel-pos-artikelnr">'.$pos['ArtikelNr'].'</div>
					<div class="artikel-pos-bez">'.$pos['Artikel_Bezeichnung'].'</div>
					<div class="artikel-pos-menge">'.number_format($pos['Artikel_Menge'],0,',','.').'</div>
				</div>';
			}
			?>
			</div>
		</li>
		
		<div style="margin:20px 0 20px 0; text-align:center;border:0px solid green;"><button type="submit">Speichern</button></div>
	</div>
	<div style="margin:30px 0px 0px 0px;"><form action="index.php"><input type="submit" value="Zurück"></form></div>
</div>
		
</body>
</html>