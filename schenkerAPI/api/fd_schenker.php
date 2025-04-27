<?php
class Schenker
{
	public  $AccessKey 	= '';
	public  $client 	= '';
	public  $wsdl 		= '';
	public  $Mode 		= '';
	
	function __construct($Mode = 'PROD') 
	{		
		$this->AccessKey 	= ($this->Mode == 'DEV') ? '50ed3413-b9c2-4188-a70d-40ce4900f95a' : 'dcbd9c19-0ca2-4e81-b1f4-f91ae6284994';
		$this->client 		= new SoapClient($this->wsdl);
	}
}


class Schenker_Tracking extends Schenker
{
	private $DB_FD = '';
	
	function __construct($Mode = 'PROD') 
	{	
		$this->Mode = $Mode;
		$this->wsdl = ($this->Mode == 'DEV') ? 'https://eschenker-fat.dbschenker.com/webservice/trackingWebServiceV3?WSDL' : 'https://eschenker.dbschenker.com/webservice/trackingWebServiceV3?WSDL';
		$this->DB_FD = new DB_FD();
		parent::__construct();
	}
	
	public function getPublicShipmentDetails($SST_Nr)
	{
		$parameters = array(
			'AccessKey' => $this->AccessKey,
			'in' => array(
				'referenceType' => 'ff', 
				'referenceNumber' => $SST_Nr
			)
		);	
		return $this->client->getPublicShipmentDetails($parameters);
	}
	
	public function Get_Delivery_Status_Last($Events)
	{
		foreach($Events as $val)
		{
			switch($val['Ereignis'])
			{
				case 'Booked':
				case 'Collected':
				case 'Out for Delivery':
				case 'Delivered':
					$data = $val['Ereignis'];
			}
		}
		return $data;
	}

	public function Get_Tracking_Db($SST)
	{
		$query 	= "SELECT * FROM fd_schenker_tracking WHERE SST = '".$SST."'";
		$res 	= $this->DB_FD->query($query);
		$data 	= $this->DB_FD->fetchArray($res);
		return ($data) ? $data : null;
	}

	function Check_Delivery_Status($SST_Nr)
	{	
		$Events = array();
		
		$Response = $this->getPublicShipmentDetails($SST_Nr);
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

	/*
	Needed for Tracking Cronjob
	*/
	function Put_Tracking_Db($Data) 
	{
		$Status = $this->Get_Delivery_Status_Last($this->Check_Delivery_Status($Data['SST']));
		//$this->Del_Tracking_Db();
		if(!$this->Get_Tracking_Db($Data['SST']))
			$query = "INSERT INTO fd_schenker_tracking (SST, AuftragId, Status) VALUES ('".$Data['SST']."', '".$Data['AuftragId']."', '".$Status."')";
		else 
			$query = "UPDATE fd_schenker_tracking SET Status = '".$Status."' WHERE SST = '".$Data['SST']."'";
		
		$this->DB_FD->query($query);
		
		return $Status;
	}

	function Del_Tracking_Db() 
	{
		$query = "TRUNCATE TABLE `lx_fd`.`fd_schenker_tracking`";
		echo $query."\n";
		$this->DB_FD->query($query);
	}
	
} // End Class

class Schenker_Booking extends Schenker
{
	function __construct($Mode = 'PROD') 
	{
		if($Mode == 'PROD') 
		{
			$this->wsdl = 'https://eschenker.dbschenker.com/webservice/bookingWebServiceV1_1?WSDL';
		}
		elseif($Mode == 'DEV') 
		{
			$this->wsdl = 'https://eschenker-fat.dbschenker.com/webservice/bookingWebServiceV1_1?WSDL';
		}
		parent::__construct();
	}
	
	public function getBookingRequestLand()
	{
		$parameters = array(
			'in' => array(
				'applicationArea' => array(
					'AccessKey' => $this->AccessKey,
					'userId' => 'DCOLUCCI',
				),
				'bookingLand'
			),
		);	
		
		return $this->client->getBookingRequestLand($parameters);
	}
} // End Class
?>