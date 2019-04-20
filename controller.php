<?php

/**
 * @created by Silvio Paiva
 */
class Controller
{

	private $links;
	private $rootPath = "pages";
	
	function __construct($links_file = "links.csv")
	{

		if (is_file($links_file)) {
			$this->links = file($links_file,FILE_IGNORE_NEW_LINES);	
			if (!is_dir($this->rootPath)) {
				mkdir($this->rootPath);
			}
		}else{
			echo "link file does not exist in the specified path.".PHP_EOL;
		}
		
	}

	public function getLinks(int $link = NULL){
		
		if ($link === NULL) {

			return $this->links;

		}else{

			return $this->links[$link];

		}
	}


	function getName(int $link ,$re = "/\/(.*[0-9]*)-.*\/$/"){

		preg_match($re, $this->links[$link], $name);
		
		return $name[1];
	}

	function getCode(int $link ,$re = "/([0-9]*)\/$/" ){

		preg_match($re, $this->links[$link], $code);
		
		return $code[1];
	}

	function getPath(int $link){

		$path = $this->rootPath ."/".$this->getCode($link)."/";

		return $path;
	}

	function createPath(int $link){
		$path = $this->getPath($link);
		if (!is_dir($path)) {
			mkdir($path);
		}
	}

	function getPage(int $link){
		
		$path = $this->getPath($link);
		$arq_name = $this->getName($link).".html";
		$page_path = $path.$arq_name;
		
		if (!is_file($page_path) || !filesize($page_path) > 0) {
		// cria pastas para organizar arquivos de acordo com as paginas pegas
			$this->createPath($link);
		//faz o request do html do link pego
			$contents = file_get_contents("https://".$this->getLinks($link));
		//salva o html do pego no request na pasta criada a cima
			$arquivo= fopen($page_path, 'a+');
			fwrite($arquivo, $contents);
			return $arq_name.PHP_EOL;
		}else{
			return;
		}

	}

	function getAllPages(){

		for ($i=0; $i < sizeof($this->links); $i++) { 

			echo $this->getPage($i);

		}
	}

	function getComentsPages(int $link){

		$path = $this->getPath($link);
		$page_name = $this->getName($link);
		$code = $this->getCode($link);
		$arq_path = $path.$page_name;
		echo $page_name.PHP_EOL;
		for ($i=1; ; $i++) { 
			$this->createPath($link);

			if (file_exists($path."/last_page-$i")) {
				break;
			}
			$jsonname = function($p) use($arq_path) { return $arq_path."-".$p.".json"; };

			if (is_file($jsonname($i)) && filesize($jsonname($i)) > 0) {
				continue;
			}

			$doc=file_get_contents('https://filmow.com/async/comments/?content_type=22&object_pk='.$code.'&user=all&order_by=-created&page='.$i);

			$json = json_decode($doc, true);

			$ajax= fopen($jsonname($json['pagination']['current_page']), 'a+');
			fwrite($ajax, $doc);

			echo "		".$jsonname($i).PHP_EOL;
			if ($json['pagination']['has_next'] == false) {
				touch($path."last_page-$i");
				break;
			}

		}

	}

	function getAllComentsPages(){
		for ($i=0; $i < sizeof($this->links); $i++) { 
			$this->getComentsPages($i);
		}
	}

	function getComents($re = '/<p>(.*?)<.p>/m'){


		$root = dir($this->rootPath);

		if (!is_dir("extractions")) {
			mkdir("extractions");
		}

		while ($path = $root->read()) {
			if ($path <= 2) {
				continue;
			}

			$diretorio = dir($this->rootPath."/".$path);
			while($arquivo = $diretorio->read()){	
				if (strlen($arquivo) <= 3) {
					continue;
				}	
				$page = $this->rootPath."/".$path."/".$arquivo;

				if (is_file($page) && filesize($page) > 0) {

					$contents= file_get_contents($page);
					preg_match_all($re, $contents, $matches, PREG_SET_ORDER, 0);
					for ($j=0; $j < sizeof($matches); $j++) { 
						$string = strip_tags($matches[$j][1]);
						$string=str_replace("Comentário contando partes do filme. Mostrar.", "", $string);
						$file=fopen('extractions/coments.csv', 'a+');
						fwrite($file, $string.PHP_EOL);
					}
					echo $page."\n";
				}

			}

		}

	}

	function getSentences($re = "/.*?[\S]{3,}[\.?!]{1,}(?=[\s]{0,})/"){
		$coments = file('./extractions/coments.csv', FILE_IGNORE_NEW_LINES);

		for ($i=0; $i < sizeof($coments); $i++) { 
			preg_match_all($re, $coments[$i], $phrase);
			for ($j=0; $j < sizeof($phrase[0]); $j++) {
				$pattern = "/Esse recado foi MODERADO\.|Motivo: Infração dos Termos de Uso\.|\.{1,}$/";
				$phrase[0][$j] = preg_replace($pattern, '', $phrase[0][$j]);

				$number_of_words = explode(' ',$phrase[0][$j]);
				$number_of_words  = sizeof($number_of_words);
				
				if ($number_of_words >= 3 ) {
					$phrase[0][$j] = mb_strtolower(trim($phrase[0][$j]));
					$file = fopen('extractions/sentences.txt', 'a+');
					fwrite($file, $phrase[0][$j]."\n");
				}
				echo $i."\n";
			}
		}
	}


}