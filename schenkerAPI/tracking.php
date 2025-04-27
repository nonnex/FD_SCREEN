<?php
include '../inc/db/db_fd.php';
require('api/fd_schenker.php');

$SST_Nr = (isset($_GET['SST_Nr'])) ? $_GET['SST_Nr'] : die('GET_[SST_Nr] fehlt');
$output_format = (isset($_GET['output'])) ? $_GET['output'] : null;

$Schenker_Tracking = new Schenker_Tracking(); // Default=PROD|DEV
$Response = $Schenker_Tracking->getPublicShipmentDetails($SST_Nr);

if(!isset($Response->out->Shipment->ShipmentInfo)) die ('Keine Trackingiformationen für '.$SST_Nr.' gefunden');

//include('response_tracking_multi.php'); //DEBUG
//include('response_tracking.php'); //DEBUG

$Partner 					= $Response->out->Shipment->ShipmentInfo->ShipmentBasicInfo->Partner;
$StatusEventList 			= $Response->out->Shipment->ShipmentInfo->ShipmentBasicInfo->StatusEventList;
$DateAndTimes 				= $Response->out->Shipment->ShipmentInfo->ShipmentBasicInfo->DateAndTimes;
$ConsignmentMeasurements 	= $Response->out->Shipment->ShipmentInfo->ShipmentBasicInfo->ConsignmentMeasurements;

$EventList  = (is_array($StatusEventList->StatusEvent)) ? $StatusEventList->StatusEvent : $StatusEventList;

$Events = array();
foreach($EventList as $Event)
{
	if(isset($Event->StatusDescription)) {
		$Events[] = array
		(	
			'Datum' 	=> (new DateTime($Event->Date))->format('d.m.Y').' '.(new DateTime($Event->Time))->format('H:i'),
			'Ort'		=> $Event->OccurredAt->LocationName,
			'Ereignis'	=> $Event->StatusDescription->_,
		);
	}
}


// (new DateTime($DateAndTimes[1]->Date))->format('d.m.Y')
$data = array
(
	'Abgangsort' 		=> $Partner[0]->Address->AddressDetails->ISOCountry->_.'-'.$Partner[0]->Address->AddressDetails->Physical->PostalCode.' '.$Partner[0]->Address->AddressDetails->Physical->City->_,
	'Zustellungsort' 	=> $Partner[1]->Address->AddressDetails->ISOCountry->_.'-'.$Partner[1]->Address->AddressDetails->Physical->PostalCode.' '.$Partner[1]->Address->AddressDetails->Physical->City->_,
	'Gebucht'			=> (new DateTime( (isset($DateAndTimes->Date)) ? $DateAndTimes->Date : $DateAndTimes[0]->Date))->format('d.m.Y'),
	'Vorraussichtlich' 	=> (is_array($DateAndTimes)) ? (new DateTime($DateAndTimes[1]->Date))->format('d.m.Y') : '',
	'Gewicht'			=> $ConsignmentMeasurements[0]->MeasureValue->_.' '.$ConsignmentMeasurements[0]->MeasureValue->unit,
	'Volumen'			=> $ConsignmentMeasurements[1]->MeasureValue->_.' '.$ConsignmentMeasurements[1]->MeasureValue->unit,
	'Collis'			=> $ConsignmentMeasurements[2]->MeasureValue->_,
	'Lademeter'			=> $ConsignmentMeasurements[3]->MeasureValue->_.' '.$ConsignmentMeasurements[3]->MeasureValue->unit,
	'LastEvent'			=> $Response->out->Shipment->ShipmentInfo->ShipmentBasicInfo->EventDateTime.' '.$Response->out->Shipment->ShipmentInfo->ShipmentBasicInfo->LastEvent,
	'Events'			=> $Events,
);

//var_export($data);
if($output_format == 'JSON') 
{
	echo json_encode($data['Events']);
	die();
} 
else 
{
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Auftragsübersicht</title>
	<link href="https://fonts.googleapis.com/css?family=Inconsolata" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
	<link rel="stylesheet" href="../css/style.css">
	<style>
		html {
			margin: 0;
			padding: 0;
		}

		body {
			background:white;
			color: #222222;
			overflow:hidden;
		}

		.group{
		  padding:8px;
		}
		
		.s_title {
			color: #222222;
			font-weight:normal;
		}
		
		.s_data {
		  color: #222222;
		  font-weight:bold;
		}
	</style>
</head>
<body style="overflow:hidden;">

<div class="lager-container" style="padding:10px;">
	<div class="lager-header"><span class="lager-column-header"><h3>Sendungsdetails <?=$SST_Nr?></h3></span></div>
	<div class="table-positions">
		<div class="lagerjournal-row">
			<table border="0" width="100%">
				<tr>
					<td width="50%">
						<div class="group"> 
							<span class="s_title">Abgangsort</span><br>
							<span class="s_data"><?=$data['Abgangsort']?></span><br>
						</div>
						<div class="group"> 
							<span class="s_title">Gebucht</span><br>
							<span class="s_data"><?=$data['Gebucht']?></span><br>
						</div>
					</td>
					<td width="50%">
						<div class="group">
							<span class="s_title">Zustellungsort</span><br>
							<span class="s_data"><?=$data['Zustellungsort']?></span><br>
						</div>
						<div class="group"> 
							<span class="s_title">Voraussichtliche Ankunft</span><br>
							<span class="s_data"><?=$data['Vorraussichtlich']?></span><br>
						</div>
					</td>
				</tr>
			</table>
			<hr>
			<table border="0" width="100%">
				<tr>
					<td align="center">
						<div class="group">
							<span class="s_title">Collis</span><br>
							<span class="s_data"><?=$data['Collis']?></span>
						</div>
					</td>
					<td align="center">
						<div class="group">
							<span class="s_title">Gesamtgewicht</span><br>
							<span class="s_data"><?=$data['Gewicht']?></span>
						</div>
					</td>
					<td align="center">
						<div class="group">
							<span class="s_title">Gesamtvolumen</span><br>
							<span class="s_data"><?=$data['Volumen']?></span>
						</div>
					</td>
					<td align="center">
						<div class="group">
							<span class="s_title">Lademeter</span><br>
							<span class="s_data"><?=$data['Lademeter']?></span>
						</div>
					</td>
				</tr>
			</table>
			
		</div>
	</div>
	<div class="lager-footer" style="margin-bottom:16px;"></div>
	
	<div class="lager-header"><span class="lager-column-header"><h3>Sendungsstatus Historie</h3></span></div>
	<div class="table-positions">
		<div class="lagerjournal-row">
			<div class="group">
				<table>
					<tr>
						<td width="100px"><div class="s_data">Datum</span></td>
						<td width="100px"><div class="s_data">Ort</span></td>
						<td width="100px"><div class="s_data">Ereignis</span></td>
					</tr>
					<?
					foreach($data['Events'] as $val)
					{
						echo '<tr>';
						echo '<td>'.$val['Datum'].'</td>';
						echo '<td>'.$val['Ort'].'</td>';
						echo '<td>'.$val['Ereignis'].'</td>';
						echo '</tr>';
					}
					?>
				</table>
			</div>
		</div>
	</div>
	<div class="lager-footer" style="margin-bottom:16px;"></div>
</div>

</body>
</html>

<? } ?>