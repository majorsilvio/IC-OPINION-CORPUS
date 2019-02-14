<?php 

require_once 'controle.php';


function progress_bar($done, $total, $info="", $width=50) {
	$perc = round(($done * 100) / $total);
	$bar = round(($width * $perc) / 100);
	return sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
}
for ($i=0; $i < sizeof($file); $i++) { 
	getComentsPages($file[$i]);
	echo progress_bar($i,sizeof($file),'ICSORM');
}
