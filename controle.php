<?php 
$file=file('links.csv');

function getName($link){
	$re = '/\/(.*[0-9]*)-.*\/$/';
	preg_match($re, $link, $matches);
	return $matches[1];
}

function getCode($link){
	$re = '/([0-9]*)\/$/';
	preg_match($re, $link, $code);
	return $code[1];
}

function Path($link){
	$path = "paginas/".getCode($link)."/";
	return $path;
}

function createdPath($string){
	$path = "";
	$string = explode("/", $string);
	for ($i=0; $i < sizeof($string); $i++) { 
		$path = $path.$string[$i]."/";
		if (!is_dir($path)) {
			mkdir($path);
		}
	}
}

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


function getComents($path){
	$re = '/<p>(.*?)<.p>/m';
	$diretorio = dir($path);
	if (is_file('last')) {
		$continue = file('last');
		$last = $continue[0];
	}

	while($arquivo = $diretorio -> read()){		
		$page = $path.$arquivo;
		if (isset($last) && $page != $last) {
			continue;
		}
		fwrite(fopen('last', 'w'), $page);

		if (is_file($page) && filesize($page) > 0) {
			$contents= file_get_contents($page);
			preg_match_all($re, $contents, $matches, PREG_SET_ORDER, 0);
			for ($j=0; $j < sizeof($matches); $j++) { 
				$string = strip_tags($matches[$j][1]);
				$string=str_replace("Comentário contando partes do filme. Mostrar.", "", $string)."\n";
				$file=fopen('comentarios.csv', 'a+');
				fwrite($file, $string);
			}
			echo $page."\n";		
		}

	}

}

function getSentences(){
	$coments = file('./sentenças/comentarios.csv');
	$re = "/.*?[\S]{3,}[\.?!]{1,}(?=[\s]{0,})/";

	for ($i=0; $i < sizeof($coments); $i++) { 
		preg_match_all($re, $coments[$i], $phrase);
		for ($j=0; $j < sizeof($phrase[0]); $j++) {
			$pattern = "/Esse recado foi MODERADO\.|Motivo: Infração dos Termos de Uso\.|&.{2,4};|\.{1,}$/";
			$phrase[0][$j] = preg_replace($pattern, '', trim($phrase[0][$j])); 
			$number_of_words = explode(' ',$phrase[0][$j]);
			$number_of_words  = sizeof($number_of_words);
			if ($number_of_words >= 3 ) {
				$phrase[0][$j] = mb_strtolower($phrase[0][$j]);
				$file = fopen('sentences.txt', 'a+');
				fwrite($file, $phrase[0][$j]."\n");
			}
			echo $i."\n";
		}
	}
}
