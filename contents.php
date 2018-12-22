<?php 
// Arquivo com todos os liniks de filmes no site
$file=file('links.csv');

//criar pagina raiz 
if (!is_dir("paginas")) {
	mkdir('paginas');	
}
$mult = $argv[1] ?? 1;
$resto = $argv[2] ?? 0;
for ($i=0; $i < sizeof($file); $i++) { 
	if ($i % $mult != $resto) {
		continue;
	}
//apenas um contador para ver o progresso 
	echo "$i-{$file[$i]}".PHP_EOL;

	//retira a quebra de linha do item pego do arquivo de links
	$file[$i]=str_replace("\n", "", $file[$i]);

	//pega o nome do filme contido no link
	$re = '/\/(.*[0-9]*)\/$/';
	preg_match($re, $file[$i], $matches);

	//pegar codigo do filme contido no link
	$re = '/[0-9]*$/';
	preg_match($re, $matches[1], $numb);

	// cria pastas para organizar arquivos de acordo com as paginas pegas
	if (!is_dir("paginas/".$numb[0]."/")) {
		mkdir("paginas/".$numb[0]."/");
	}

	$filename = "paginas/".$numb[0]."/".$matches[1].".html";
	if (!is_file($filename) || !filesize($filename) > 0) {
		//faz o request do html do link pego
		$contents = file_get_contents("https://".$file[$i]);

		//salva o html do pego no request na pasta criada a cima
		$arquivo= fopen($filename, 'a+');
		fwrite($arquivo, $contents);
	}

	//laço para pegar todos os arquivos de comentarios (json) da pagina
	for ($j=1; ; $j++) { 
		if (file_exists("paginas/".$numb[0]."/last_page-$j")) {
			break;
		}
		$jsonname = function($p) use($numb, $matches) { return "paginas/".$numb[0]."/".$matches[1]."-".$p.".json"; };

		if (is_file($jsonname($j)) && filesize($jsonname($j)) > 0) {
			continue;
		}
		echo "p$j, ";

		$doc=file_get_contents('https://filmow.com/async/comments/?content_type=22&object_pk='.$numb[0].'&user=all&order_by=-created&page='.$j);

		$json = json_decode($doc, true);

		$ajax= fopen($jsonname($json['pagination']['current_page']), 'a+');
		fwrite($ajax, $doc);
		
		if ($json['pagination']['has_next'] == false) {
			touch("paginas/".$numb[0]."/last_page-$j");
			break;
		}
	}
	echo PHP_EOL;
}

