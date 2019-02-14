<?php 
$file=file('links.csv');


function getPages(){
	global $file;
	for ($i=0; $i < sizeof($file); $i++) { 
		if (!is_dir("paginas")) {
			mkdir('paginas');	
		}

	//pegar codigo do filme contido no link
		$code = getCode($file[$i]);

	// cria pastas para organizar arquivos de acordo com as paginas pegas
		if (!is_dir("paginas/".$code."/")) {
			mkdir("paginas/".$code."/");
		}

		$path = Path().getName().".html";
			//faz o request do html do link pego
		getPage($file[$i],$path);

	}
}

function createdPath($string){
	$path = "";
	$string = explode("/", $string);
	// var_dump($string);
	for ($i=0; $i < sizeof($string); $i++) { 
		$path = $path.$string[$i]."/";
		// echo $path;
		if (!is_dir($path)) {
			mkdir($path);
		}
	}
}
function getCode($link){
	$re = '/([0-9]*)\/$/';
	preg_match($re, $link, $code);
	return $code[1];
}
function getName($link){
	$re = '/\/(.*[0-9]*)-.*\/$/';
	preg_match($re, $link, $matches);
	return $matches[1];
}
function Path($link){
	$path = "paginas/".getCode($link)."/";
	return $path;
}

function getPage($link , $path){

	$link=str_replace("\n", "", $link);

	if (!is_file($path) || !filesize($path) > 0) {

		$contents = file_get_contents("https://".$link);
		//salva o html do pego no request na pasta criada a cima
		$arquivo= fopen($path, 'a+');
		fwrite($arquivo, $contents);
	}
}

function getComentsPages($file){
	// global $file;
	if (!is_dir("paginas")) {
		mkdir('paginas');	
	} 
		$code = getCode($file);
		$movieName = getName($file);
		for ($i=1; ; $i++) { 
			if (!is_dir("paginas/".$code."/")) {
				mkdir("paginas/".$code."/");
			}
			if (file_exists("paginas/".$code."/last_page-$i")) {
				break;
			}
			$jsonname = function($p) use($code, $movieName) { return "paginas/".$code."/".$movieName."-".$p.".json"; };

			if (is_file($jsonname($i)) && filesize($jsonname($i)) > 0) {
				continue;
			}
			// echo "p$i, ";

			$doc=file_get_contents('https://filmow.com/async/comments/?content_type=22&object_pk='.$code.'&user=all&order_by=-created&page='.$i);

			$json = json_decode($doc, true);

			$ajax= fopen($jsonname($json['pagination']['current_page']), 'a+');
			fwrite($ajax, $doc);

			if ($json['pagination']['has_next'] == false) {
				touch("paginas/".$code."/"."last_page-$i");
				break;
			}
		}
	}


function getComents($comentsPage){
	$re = '/<p>(.*?)<.p>/m';
	for ($i=1; ; $i++) { 
		$page = $comentsPage."-".$i.".json";
		if (is_file($page)) {
			$contents= file_get_contents($page);
			preg_match_all($re, $contents, $matches, PREG_SET_ORDER, 0);
			for ($j=0; $j < sizeof($matches); $j++) { 
				$string = strip_tags($matches[$j][1]);
				$string=str_replace("Comentário contando partes do filme. Mostrar.", "", $string)."\n";
				$file=fopen('comentarios.csv', 'a+');
				fwrite($file, $string);
			}
			// return $matches;
			echo $i;
		}else{
			break;
		}
	}
}

function getSentences(){
	$coments = file('comentarios.csv');
	$re = "/.*?\./";

	for ($i=0; $i < sizeof($coments); $i++) { 
		// $explode=explode('.', $coments[$i]);
		preg_match_all($re, $coments[$i], $explode);
		for ($j=0; $j < sizeof($explode[0]); $j++) { 
			if (strlen($explode[0][$j]) > 1) {
				$file = fopen('teste.csv', 'a+');
				fwrite($file, $explode[0][$j]."\n\n");
			}
		}
		return $explode[0];
	}
}