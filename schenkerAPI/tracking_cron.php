<?php
include '../inc/db/db_fd.php';
include '../inc/db/db_lx.php';
include '../inc/lx_orders.php';
include '../inc/lx_lager.php';
require('api/fd_schenker.php');

$Lx_Orders 			= new Lx_Orders();
$Schenker_Tracking 	= new Schenker_Tracking(); // Default=PROD|DEV
$LxData_LS 			= $Lx_Orders->GetAllOpenOrdersFromLX(2);

/*
function Check_Delivery_Status($SST_Nr)
{
	global $Schenker_Tracking;
	
	$Events = array();
	
	$Response = $Schenker_Tracking->getPublicShipmentDetails($SST_Nr);
	if(!isset($Response->out->Shipment->ShipmentInfo)) die ('Keine Trackingiformationen für '.$SST_Nr.' gefunden')."\n";
	
	$StatusEventList 	= $Response->out->Shipment->ShipmentInfo->ShipmentBasicInfo->StatusEventList;
	$EventList 			= (is_array($StatusEventList->StatusEvent)) ? $StatusEventList->StatusEvent : $StatusEventList;
	
	foreach($EventList as $Event)
	{
		if(isset($Event->StatusDescription)) 
		{
			$Events[] = array
			(
				'Datum' 	=> (new DateTime($Event->Date))->format('d.m.Y').' '.(new DateTime($Event->Time))->format('H:i'),
				'Ort'		=> $Event->OccurredAt->LocationName,
				'Ereignis'	=> $Event->StatusDescription->_,
			);
		}
	}
	return $Events;
}
*/
//echo '<pre>';
//echo "\n";
//var_export($LxData_LS);

foreach($LxData_LS as $val)
{
	if(trim($val['szUserdefined3']) && stristr($val['szUserdefined2'], 'Schenker'))
	{
		$Data = array(
			'SST' 		=> trim($val['szUserdefined3']),
			'AuftragId' => $val['AuftragId'],
		);
		
echo $val['szUserdefined2'].' - '.trim($val['szUserdefined3']).' ('.$val['KundenMatchcode'].'): ';
		$Status = $Schenker_Tracking->Put_Tracking_Db($Data);
echo $Status;
		
		$Tags = $Lx_Orders->GetOrderTags($val['AuftragId']);
		
		if($Status && $Status != 'Booked' && $Status != 'Delivered' && $Tags[0]['lTagId'] != 1)
		{
echo " -> Setting Order from '".$Tags[0]['szName']."(".$Tags[0]['lTagId'].")' to 'Versendet'";
			$Lx_Orders->SetOrderTags($val['AuftragId'], 4); // Set Order_Tag to 'Fakturieren'
			$Lx_Orders->SetDeliveryTime($val['AuftragId'], date('d.m.Y H:i'));
		}
echo "\n";

		if($Status == 'Delivered' && $Tags[0]['lTagId'] != 6)
		{
echo " -> Setting Order from '".$Tags[0]['szName']."(".$Tags[0]['lTagId'].")' to 'Fakturieren'";
			$Lx_Orders->SetOrderTags($val['AuftragId'], 5); // Set Order_Tag to 'Fakturieren'
			$Lx_Orders->SetDeliveryTime($val['AuftragId'], date('d.m.Y H:i'));
		}
echo "\n";
		sleep(8);
	}
}

?>