<?php
use RRule\RRule;

include("rrule/RRuleInterface.php");
include("rrule/RRuleTrait.php");
include("rrule/RfcParser.php");
include("rrule/RRule.php");

class Lx_Events
{
	private $Events_Data;
	
	function __construct() {
		$this->Load_Events();
	}
	
	private function Load_Events()
	{
		$events_json_url = ($_SERVER['HTTP_HOST'] == '127.0.0.1') ? 'http://127.0.0.1/lex/calendar/api/load.php' : 'http://192.168.175.80:85//calendar/api/load.php' ;
		$this->Events_Data = json_decode(file_get_contents($events_json_url), true);
	}

	public function Get_Events($start = '', $end = '')
	{
		if(!is_array($this->Events_Data)) { return; }
		
		$datetime = new DateTime();
		$heute 	= $datetime->format('Y-m-d');
		
		$datetime = new DateTime('tomorrow');
		//$datetime->modify('+1 day');
		$datetime->modify('+1440 minutes');
		$morgen = $datetime->format('Y-m-d');
		
		$ret_data = array();
		
		foreach($this->Events_Data as $event)
		{
			// Event has RRule
			if(isset($event['rrule']))
			{			
				$rrule = new RRule($event['rrule']);
				
				foreach ($rrule as $occurrence) {
					$rruleDate = $occurrence->format('Y-m-d');
					if($rruleDate >= $heute && $rruleDate <= $morgen)
					{
						$ret_data[] = $event;
					}
				}
				//echo $rrule->humanReadable(),"\n";
			}
			
			// Normal Events
			if($event['start'] >= $heute && $event['start'] <= $morgen)
			{
				$ret_data[] = $event;
			}
		}
		
		array_multisort(array_map('strtotime',array_column($ret_data,'start')),SORT_ASC,$ret_data);
		
		return $ret_data;
	}
	
	public function Print_Events($events) 
	{
		if(!is_array($events)) { return; }
		if(count($events))
		{
			echo '<div style="margin-top:8px;">';
			echo '<br>Hinweise für Heute und die nächsten Tage:<br>';
			foreach($events as $event) 
			{	
			?>
				<div style="margin-top:8px;">
					<a href="calendar/index.php">
						<div style="
							padding:5px;
							line-height:12px;
							color:<?=@$event['title']?>;
							border-color:<?=@$event['borderColor']?>;
							border-radius:2px;
							background-color:<?=@$event['backgroundColor']?>;
							white-space: nowrap;
						">
							<div class="fc-event-main-frame">
								<div class="fc-event-title-container">
									<div><?=(new DateTime($event['start']))->format('d.m.Y')?> - <?=$event['title']?></div>
								</div>
							</div>
						</div>
					</a>
			<?
			}
			echo '</div>';
		}
	}
}