<?php
include("../config.php");

$data = [];

$result = $db->rows("SELECT * FROM ".CALENDAR_TABLE." ORDER BY id");

/***********************/
/* Manuelle Eintragungen
/* FD_DB
/***********************/
foreach($result as $row) 
{
	$allDay = ($row->end_event) ? true : false;
    
	$data[] = [
        'id'              	=> $row->id,
        'title'           	=> $row->title,
        'start'           	=> $row->start_event,
        'end'             	=> $row->end_event,
        'backgroundColor' 	=> $row->color,
        'textColor'       	=> $row->text_color,
		'allDay' 			=> $allDay
    ];
}

/***********************/
/* RRULES
/* https://github.com/jakubroztocil/rrule, https://jakubroztocil.github.io/rrule/
/***********************/  

/*
new RRule([
	'FREQ' => 'WEEKLY',
	'INTERVAL' => 1,
	'DTSTART' => '2023-02-03',
	'COUNT' => 30,
	'WKST' => 'FR'
]);
new RRule('RRULE:FREQ=DAILY;UNTIL=19971224T000000Z;WKST=SU;BYDAY=MO,WE,FR;BYMONTH=1');
"DTSTART:20230203T112700Z\nRRULE:FREQ=WEEKLY;COUNT=30;INTERVAL=1;WKST=FR"
*/

$data[] = [
	'id'              	=> 9998,
	'title'           	=> 'KAFFEE MASCHINE (Reinigen)',
	'rrule'				=> "DTSTART:20221201T123600Z\nRRULE:FREQ=MONTHLY;INTERVAL=1;COUNT=60;BYDAY=1FR",
	'start'           	=> '',
	//'end'             	=> '',
	'backgroundColor' 	=> 'grey',
	'borderColor'		=> 'grey',
	'textColor'       	=> 'white',
	'allDay' 			=> true,
	'constraint' 		=> true,
	'editable' 			=> false,
];

$data[] = [
	'id'              	=> 9999,
	'title'           	=> 'AIM_1 Schmierung #1 bis #4 (Siehe Wartungsblatt)',
	'rrule'				=> "DTSTART:20221201T123600Z\nRRULE:FREQ=MONTHLY;INTERVAL=1;COUNT=60;BYDAY=1MO",
	'start'           	=> '',
	//'end'             	=> '',
	'backgroundColor' 	=> 'grey',
	'borderColor'		=> 'grey',
	'textColor'       	=> 'white',
	'allDay' 			=> true,
	'constraint' 		=> true,
	'editable' 			=> false,
];


/***********************/
/* Feiertage
/* JSON https://feiertage-api.de/api/?jahr=2023&nur_land=BY
/***********************/
$Feiertage_API = "https://feiertage-api.de/api/?jahr=".date('Y')."&nur_land=BY";
$Feiertage = json_decode(file_get_contents($Feiertage_API), true);
$c = count($data);
foreach($Feiertage as $k => $v)
{
	$data[] = [
        'id' 		=> $c++,
        'title'		=> $k,
        'start'		=> $v['datum'],
        'end'		=> '',
		'display' 	=> 'background'
    ];
}

/***********************/
/* Restmüll Rechtmehring
/* CSV https://www.lra-mue.de/buergerservice/themenfelder/abfallwirtschaft/entsorgungskalender-2021.html
************************/
$row = 0;
if(($handle = fopen("../data/".TRASH_CSV.".csv", "r")) !== FALSE) 
{
    while(($line = fgetcsv($handle, 1000, ",")) !== FALSE) 
	{
		$row++;
		if ($row == 1) { continue; } // Skip first line
		if(strcmp(utf8_encode($line[3]), 'Restmüll Container') == 0) { continue;}
		//if(strcmp(utf8_encode($line[3]), 'Problemabfallsammlung') == 0) { continue;}
		
		$fDate = explode(" ", $line[4]); // Strip Wochentag
		$myDateTime = DateTime::createFromFormat('d.m.Y', $fDate[1]);

		$fColors['backgroundColor'] = '';
		$fColors['borderColor'] = '';
		$fColors['textColor'] = '';
		
		if(strcmp(utf8_encode($line[3]), 'Restmüll') == 0)
		{
			$fColors = array(
				'backgroundColor' => 'black',
				'borderColor' => 'black',
				'textColor' => 'white'
			);
		}
		elseif(strcmp(utf8_encode($line[3]), 'Papiertonne') == 0)
		{
			$fColors = array(
				'backgroundColor' => '#5DADE2',
				'borderColor' => '#5DADE2',
				'textColor' => 'white'
			);
		}
		elseif(strcmp(utf8_encode($line[3]), 'Gelber Sack') == 0)
		{
			$fColors = array(
				'backgroundColor' => '#F9E79F',
				'borderColor' => '#F9E79F',
				'textColor' => 'black'
			);
		}
		
		$line_2 = ($line[2]) ? ' ('.$line[2].')' : '';
		
		$data[] = [
			'id' 				=> $c++,
			'title' 			=> utf8_encode($line[3]) . $line_2,
			'start'				=> $myDateTime->format('Y-m-d'), // convert to fullcalender Date/Time format
			'end'				=> '',
			'allDay' 			=> true,
			'constraint' 		=> true,
			'editable' 			=> false,
			'backgroundColor' 	=> $fColors['backgroundColor'],
			'borderColor' 		=> $fColors['borderColor'],
			'textColor' 		=> $fColors['textColor']
		];
    }
    fclose($handle);
}

//var_export($data);

echo json_encode($data);

//echo '</pre>';