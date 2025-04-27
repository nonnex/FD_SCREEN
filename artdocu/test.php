<?php
$ArtikelNr = @$_GET['ArtikelNr'];

$Data = array (
	'ArtikelNr' => $ArtikelNr,
	'Kurzbez' 	=> 'LAF Drahtabstandhalter D18',
	'Gewicht' 	=> '250g',
	'Werkstoff'	=> 'C9D',
	'Coating'	=> 'ZaAl',
);

$Out = 
<<<SVG
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
SVG;

$Out .= 
<<<SVG
<svg class="my-hardcoded-svg" width="595.276pt" height="841.89pt" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
<style>
@import url("https://fonts.googleapis.com/css?family=Chakra+Petch:400,400i,700,700i");

foreignobject{
	background-color:#EEEEEE;
	/*background-color:lime;*/
}

div {
	font-family:Chakra Petch;
	font-size:9px;
}
span {
	font-family:Chakra Petch;
	font-size:9px;
	line-height: 1.0;
}
.txtbig	{
	font-size:16px;
}

.dTable {
	display:table;
	border:0px solid black;
}
.dRow {
	display:table-row;
	border:0px solid black;
	
}
.dRow-hl {
	display:table-row;
	background-color: yellow;
	border:0px solid black;
	
}
.dCell {
	display:table-cell;
	padding-left:2px;
	padding-right:2px;
	border:1px solid black;
}
</style>

SVG;

$handle = fopen("Blank_L.svg", "r");
if($handle) {
	while(($line = fgets($handle)) !== false) {
		if(!str_contains($line, '<?xml')) {
			//$Out .= $line."\n";
		}
	}
	fclose($handle);
}

$Out .= '
<foreignobject x="454" y="905" width="295" height="30"><span>FERRODOM GmbH<br/>Am Unterfeld 10<br/>83526 Rechtmehring</span></foreignobject>
<foreignobject x="454" y="953" width="295" height="65"><span class="txtbig">'.$Data['Kurzbez'].'</span></foreignobject>
<foreignobject x="454" y="1031" width="245" height="34"><span class="txtbig">'.$Data['ArtikelNr'].'</span></foreignobject>
<foreignobject x="324" y="1072" width="90" height="10"><span>'.$Data['Gewicht'].'</span></foreignobject>
<foreignobject x="286" y="1033" width="160" height="32"><span>'.$Data['Werkstoff'].'</span></foreignobject>
<foreignobject x="206" y="890" width="170" height="48"><span>'.$Data['Coating'].'</span></foreignobject>
';

$Out .= '
<foreignobject x="0" y="0" width="295" height="30">
	<span>FERRODOM GmbH<br/>Am Unterfeld 10<br/>83526 Rechtmehring</span>
</foreignobject>
'."\n";

	/****
	*Zeichnungen
	****/
	// inkscape -z -f Variante_1.pdf -l Variante_1.svg
	$handle = fopen("CAD/AH_Standard.svg", "r");
	if($handle) {
		$Out .= '<svg x="80" y="40" width="670">'."\n";
		while(($line = fgets($handle)) !== false) {
			if(!str_contains($line, '<?xml')) {
				$Out .= $line;
			}
		}
		fclose($handle);
		$Out .= '</svg>'."\n";
	}
	
	
	/****
	*Maßtabelle
	****/
	$handle = fopen("table_1.csv", "r");
	$stp = 14;
	$Y = 885 - $stp;
	$height = 0;
	
	while(($data = fgetcsv($handle,null,';')) !== FALSE) {
		if($data[0]) {
			$row[] = $data;
			$Y = $Y - $stp;
			$height = $height + $stp;
		}
	}
	$Out .= '<foreignobject x="80" y="'.$Y.'" width="670" height="'.$height.'" scale="0.5">'."\n";
	$Out .= '<div class="dTable">'."\n";
	foreach($row as $col) {
		
		$highlight_row = $Data['ArtikelNr'];
			
		$row_class = ($col[0] == $highlight_row) ? 'dRow-hl' : 'dRow' ;
			
		$Out .= '<div class="'.$row_class.'">'."\n";
		foreach($col as $cell) {
			$Out .= '<div class="dCell">'.utf8_encode($cell).'</div>'."\n";
		}
		$Out .= '</div>'."\n";
	}
	$Out .= '</div>'."\n";
	$Out .= '</foreignobject>'."\n";
	

$Out .= '</svg>';

header('Content-type: image/svg+xml');
echo $Out;

?>