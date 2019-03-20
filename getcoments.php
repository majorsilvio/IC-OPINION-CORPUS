<?php
require_once 'controle.php';

if (!file_exists('continue')) {
	touch('continue');
}
$startfrom = sizeof(file('continue'));

for ($i=$startfrom; $i < sizeof($file); $i++) { 
	$path = path($file[$i]);
	getComents($path);
	$continue = fopen('continue', 'a');
	fwrite($continue, $i."\n");
	echo "\n\n".$i."\n\n";
	if (is_file('last')) {
	unlink('last');
	}
}