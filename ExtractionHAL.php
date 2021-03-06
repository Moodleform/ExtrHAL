<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>ExtrHAL : outil d’extraction des publications HAL d’une unité, d'une équipe de recherche ou d'un auteur</title>
  <meta name="Description" content="ExtrHAL : outil d’extraction des publications HAL d’une unité, d'une équipe de recherche ou d'un auteur">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="icon" type="type/ico" href="favicon.ico">
  <script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.1/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>
  <script type='text/x-mathjax-config'>
    MathJax.Hub.Config({tex2jax: {inlineMath: [['$','$'], ['$$','$$']]}});
  </script>
  <link rel="stylesheet" href="./ExtractionHAL.css">
  <script src="./lib/jscolor-2.0.4/jscolor.js"></script>
</head>

<?php
//Liste auteurs externes à Rennes 1 > autosoumission d'un formulaire avec le champ nécessaire pour retrouver le fichier
if (isset($_GET['extur1']) && $_GET['extur1'] != '' && isset($_GET["import"]) && $_GET["import"] == "ok") {
  $uniq = $_GET['extur1'];
  echo('<form name="troli" action="ExtractionHAL.php" method="post">');
  echo('<input type="hidden" name="extur1" value="'.$uniq.'">');
  echo('</form>');
  echo('<script type="text/javascript">');
  echo('document.troli.submit();');
  echo('</script>');
}

//Institut général
$institut = "";// -> univ-rennes1/ par exemple, mais est-ce vraiment nécessaire ?

function suppression($dossier, $age) {
  $repertoire = opendir($dossier);
    while(false !== ($fichier = readdir($repertoire)))
    {
      $chemin = $dossier."/".$fichier;
      $infos = pathinfo($chemin);
      $age_fichier = time() - filemtime($chemin);
      if($fichier != "." AND $fichier != ".." AND !is_dir($fichier) AND $age_fichier > $age)
      {
      unlink($chemin);
      //echo $chemin." - ".date ("F d Y H:i:s.", filemtime($chemin))."<br>";
      }
    }
  closedir($repertoire);
}

function normalize($st) {
    $st = strtr($st,' ()"-!?[]{}:,;./*+$^=\'\\','                       ');
    return preg_replace('/\s+/', '', $st);
}

function utf8_fopen_read($fileName) {
    $fc = file_get_contents($fileName);
    $handle=fopen("php://memory", "rw");
    fwrite($handle, $fc);
    fseek($handle, 0);
    return $handle;
}

function cleanup_title($titre) {
  // présence de " et combien
  $nb = mb_substr_count ($titre, '"', 'UTF-8');
  if ($nb%2 == 0) {
    return $titre;  // nombre pair (ou 0) rien à faire
  }
  // on ajoute le " à la fin
  return $titre . '"';
}

function nettoy1($quoiAvt) {
  $quoiApr = str_replace(array(". : ",",, ",", , ","..","?.","?,","<br>.","--"), array(" : ",", ",", ",".","?","?","<br>","-"), $quoiAvt);
  return($quoiApr);
}

function mb_ucwords($str) {
  $str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
  return ($str);
}

function prenomCompInit($prenom) {
  $prenom = str_replace("  ", " ",$prenom);
  if (strpos(trim($prenom),"-") !== false) {//Le prénom comporte un tiret
    $postiret = strpos(trim($prenom),"-");
    $prenomg = trim(mb_substr($prenom,0,($postiret-1),'UTF-8'));
    $prenomd = trim(mb_substr($prenom,($postiret+1),strlen($prenom),'UTF-8'));
    $autg = mb_substr($prenomg,0,1,'UTF-8');
    $autd = mb_substr($prenomd,0,1,'UTF-8');
    $prenom = mb_ucwords($autg).".-".mb_ucwords($autd).".";
  }else{
    if (strpos(trim($prenom)," ") !== false) {//plusieurs prénoms
      $posespace = strpos(trim($prenom)," ");
      $tabprenom = explode(" ", trim($prenom));
      $p = 0;
      $prenom = "";
      while (isset($tabprenom[$p])) {
        if ($p == 0) {
          $prenom .= mb_ucwords(mb_substr($tabprenom[$p], 0, 1, 'UTF-8')).".";
        }else{
          $prenom .= " ".mb_ucwords(mb_substr($tabprenom[$p], 0, 1, 'UTF-8')).".";
        }
        $p++;
      }
    }else{
      $prenom = mb_ucwords(mb_substr($prenom, 0, 1, 'UTF-8')).".";
    }
  }
  return $prenom;
}

function prenomCompEntier($prenom) {
  $prenom = trim($prenom);
  if (strpos($prenom,"-") !== false) {//Le prénom comporte un tiret
    $postiret = strpos($prenom,"-");
    $autg = substr($prenom,0,$postiret);
    $autd = substr($prenom,($postiret+1),strlen($prenom));
    $prenom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    $prenom = mb_ucwords($prenom);
  }
  return $prenom;
}

function nomCompEntier($nom) {
  $nom = trim(mb_strtolower($nom,'UTF-8'));
  if (strpos($nom,"-") !== false) {//Le nom comporte un tiret
    $postiret = strpos($nom,"-");
    $autg = substr($nom,0,$postiret);
    $autd = substr($nom,($postiret+1),strlen($nom));
    $nom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    $nom = mb_ucwords($nom);
  }
  return $nom;
}

function mise_en_evidence($phrase, $string, $deb, $fin) {
  $non_letter_chars = '/[^\pL]/iu';
  $words = preg_split($non_letter_chars, $phrase);

  $search_words = array();
  foreach ($words as $word) {
    if (strlen($word) > 2 && !preg_match($non_letter_chars, $word)) {
      $search_words[] = $word;
    }
  }

  $search_words = array_unique($search_words);

  $patterns = array(
    /* à répéter pour chaque caractère accentué possible */
    '/(ae|æ)/iu' => '(ae|æ)',
    '/(oe|œ)/iu' => '(oe|œ)',
    '/[aàáâãäå]/iu' => '[aàáâãäå]',
    '/[cç]/iu' => '[cç]',
    '/[eèéêëeeeee]/iu' => '[eèéêëeeeee]',
    '/[iìíîïiiiii]/iu' => '[iìíîïiiiii]',
    '/[nñ]/iu' => '[nñ]',
    '/[oòóôõö]/iu' => '[oòóôõö]',
    '/[sš]/iu' => '[sš]',
    '/[uùúûü]/iu' => '[uùúûü]',
    '/[yýÿ]/iu' => '[yýÿ]',
    '/[zž]/iu' => '[zž]',
  );

  foreach ($search_words as $word) {
    $search = preg_quote($word);

    $search = preg_replace(array_keys($patterns), $patterns, $search);

    $string = preg_replace('/\b' . $search . '(e?s)?\b/iu', $deb.'$0'.$fin, $string);
    return $string;
  }
}

//Suppresion des accents
function wd_remove_accents($str, $charset='utf-8')
{
    $str = htmlentities($str, ENT_NOQUOTES, $charset);

    $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

    return $str;
}

//Initialisation des variables
$idhal = "";
$evhal = "";
$depotforce = "";
$depotdeb = "";
$depotfin = "";
$typidh = "";
$typcro = "";
$prefeq = "";
$sortArray = array();
$rtfArray = array();
$bibArray = array();
$gr = "";
$listedoi = "";
$listetitre = "";
$arriv = "";
$depar = "";

if (isset($_POST["soumis"])) {
  $team = strtoupper(htmlspecialchars($_POST["team"]));
  $idhal = htmlspecialchars($_POST["idhal"]);
  if (isset($idhal) && $idhal != "") {$team = $idhal;}
	//export Bibtex
	$Fnm2 = "./HAL/extractionHAL_".$team.".bib";
	$inF2 = fopen($Fnm2,"w+");
	fseek($inF2, 0);
	$chaine2 = "\xEF\xBB\xBF";
	fwrite($inF2,$chaine2);
	fclose($inF2);
	//export CSV
	$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
	$inF = fopen($Fnm1,"w+");
	fseek($inF, 0);
	$chaine = "\xEF\xBB\xBF";
	fwrite($inF,$chaine);
	//export en RTF
	$Fnm = "./HAL/extractionHAL_".$team.".rtf";
	require_once ("./lib/phprtflite-1.2.0/lib/PHPRtfLite.php");
	PHPRtfLite::registerAutoloader();
	$rtfic = new PHPRtfLite();
	$sect = $rtfic->addSection();
	$font = new PHPRtfLite_Font(9, 'Trebuchet', '#000000', '#FFFFFF');
	$fontlien = new PHPRtfLite_Font(9, 'Trebuchet', '#0000FF', '#FFFFFF');
	$fonth3 = new PHPRtfLite_Font(12, 'Trebuchet', '#000000', '#FFFFFF');
	$fonth2 = new PHPRtfLite_Font(14, 'Trebuchet', '#000000', '#FFFFFF');
	$parFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_JUSTIFY);

	//sauvegarde URL
	$root = 'http';
	if ( isset ($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")	{
		$root.= "s";
	}
	$urlsauv = $root."://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
  $urlsauv .= "?team=".$team;
  $listaut = strtoupper(htmlspecialchars($_POST["listaut"]));
  if ($listaut == "") {$listaut = $team;}
  $urlsauv .= "&listaut=".$listaut;
  $urlsauv .= "&idhal=".$idhal;
  $evhal = htmlspecialchars($_POST["evhal"]);
  $urlsauv .= "&evhal=".$evhal;

  if (isset($_POST['publis'])) {
    $choix_publis = "-";
    $liste_publis = "~";
    $publis_array = $_POST['publis'];
    if (!empty($publis_array)) {
      foreach($publis_array as $selectValue){
        $choix_publis .= $selectValue."-";
        $liste_publis .= $selectValue."~";
      }
    }
    $urlsauv .= "&publis=".$liste_publis;
  }

  if (isset($_POST['comm'])) {
    $choix_comm = "-";
    $liste_comm = "~";
    $comm_array = $_POST['comm'];
    if (!empty($comm_array)) {
      foreach($comm_array as $selectValue){
        $choix_comm .= $selectValue."-";
        $liste_comm .= $selectValue."~";
      }
    }
    $urlsauv .= "&comm=".$liste_comm;
  }

  if (isset($_POST['ouvr'])) {
    $choix_ouvr = "-";
    $liste_ouvr = "~";
    $ouvr_array = $_POST['ouvr'];
    if (!empty($ouvr_array)) {
      foreach($ouvr_array as $selectValue){
        $choix_ouvr .= $selectValue."-";
        $liste_ouvr .= $selectValue."~";
      }
    }
    $urlsauv .= "&ouvr=".$liste_ouvr;
  }

  if (isset($_POST['autr'])) {
    $choix_autr = "-";
    $liste_autr = "~";
    $autr_array = $_POST['autr'];
    if (!empty($autr_array)) {
      foreach($autr_array as $selectValue){
        $choix_autr .= $selectValue."-";
        $liste_autr .= $selectValue."~";
      }
    }
    $urlsauv .= "&autr=".$liste_autr;
  }

	//Création des listes des auteurs appartenant à la collection spécifiée pour la liste
  if (isset($_POST['extur1']) && $_POST['extur1'] != '') {//Liste d'auteurs extérieurs à Rennes 1
    $uniq = $_POST['extur1'];
    include "./pvt/ExtractionHAL-auteurs-extur1-".$uniq.".php";
    $urlsauv .= "&extur1=".$uniq;
  }else{
    include "./pvt/ExtractionHAL-auteurs.php";
  }
  $listenominit = "~";
  $listenomcomp1 = "~";
  $listenomcomp2 = "~";
	$arriv = "~";
	$depar = "~";
  foreach($AUTEURS_LISTE AS $i => $valeur) {
    if ($AUTEURS_LISTE[$i]['collhal'] == $listaut || $AUTEURS_LISTE[$i]['colleqhal'] == $listaut) {
      $listenomcomp1 .= nomCompEntier($AUTEURS_LISTE[$i]['nom'])." ".prenomCompEntier($AUTEURS_LISTE[$i]['prenom'])."~";
      $listenomcomp2 .= prenomCompEntier($AUTEURS_LISTE[$i]['prenom'])." ".nomCompEntier($AUTEURS_LISTE[$i]['nom'])."~";
      //si prénom composé et juste les ititiales
      $prenom = prenomCompInit($AUTEURS_LISTE[$i]['prenom']);
      $listenominit .= nomCompEntier($AUTEURS_LISTE[$i]['nom'])." ".$prenom.".~";
			if (isset($AUTEURS_LISTE[$i]['arriv']) && $AUTEURS_LISTE[$i]['arriv'] != "" && $AUTEURS_LISTE[$i]['arriv'] != "x") {
				$arriv .= $AUTEURS_LISTE[$i]['arriv']."~";
			}else{
				$arriv .= "1900~";
			}
			if (isset($AUTEURS_LISTE[$i]['depar']) && $AUTEURS_LISTE[$i]['depar'] != "" && $AUTEURS_LISTE[$i]['depar'] != "x") {
				$depar .= $AUTEURS_LISTE[$i]['depar']."~";
			}else{
        $moisactuel = date('n', time());
        if ($moisactuel >= 10) {$idepar = date('Y', time())+1;}else{$idepar = date('Y', time());}
        $depar .= $idepar."~";
			}
    }
  }
  //echo $depar;
  //Extraction sur un IdHAL > auteur à mettre en évidence
  if (isset($evhal) && $evhal != "") {
    $list = explode(" ", $evhal);
    $listenomcomp1 = "~".str_replace("_", " ", nomCompEntier($list[1]))." ".str_replace("_", " ", prenomCompEntier($list[0]))."~";
    $listenomcomp2 = "~".str_replace("_", " ", prenomCompEntier($list[0]))." ".str_replace("_", " ", nomCompEntier($list[1]))."~";
    //si prénom composé et juste les ititiales
    $prenom = prenomCompInit($list[0]);
    $listenominit = "~".str_replace("_", " ", nomCompEntier($list[1]))." ".$prenom.".~";
    $arriv = "~1900~";
    $moisactuel = date('n', time());
    if ($moisactuel >= 10) {$idepar = date('Y', time())+1;}else{$idepar = date('Y', time());}
    $depar = "~".$idepar."~";
  }
	if (isset($_POST['anneedeb'])) {$anneedeb = $_POST['anneedeb'];}else{$anneedeb = date('Y', time());$anneefin = date('Y', time());}
  if (isset($_POST['anneefin'])) {$anneefin = $_POST['anneefin'];}else{if (isset($_POST['anneedeb'])) {$anneefin = $_POST['anneedeb'];}else{$anneefin = $anneedeb;}}
  // vérification sur ordre des années si différentes
  if ($anneefin < $anneedeb) {$anneetemp = $anneedeb; $anneedeb = $anneefin; $anneefin = $anneetemp;}
  $urlsauv .= "&anneedeb=".$anneedeb;
  $urlsauv .= "&anneefin=".$anneefin;

  if (isset($_POST['depotdeb'])) {$depotdeb = $_POST['depotdeb'];}
  if (isset($_POST['depotfin'])) {$depotfin = $_POST['depotfin'];}
  // si depotdeb et depotfin non définis, on force depotdeb au 01/01/anneedeb et depotfin au 31/12/anneefin
  if ($depotdeb == '' && $depotfin == '') {
    $depotforce = "oui";
    //$depotdeb = date('d/m/Y', mktime(0, 0, 0, 1, 1, $anneedeb));
    //$depotfin = date('d/m/Y', mktime(0, 0, 0, 12, 31, $anneefin));
  }
  // si depotdeb défini mais pas depotfin, on force depotfin à aujourd'hui
  if ($depotdeb != '' && $depotfin == '') {$depotfin = date('d/m/Y', time());}
  // si depotfin défini mais pas depotdeb, on force depotdeb au 1er janvier de l'année de depotfin
  if ($depotdeb == '' && $depotfin != '') {
    $tabdepotfin = explode('/', $depotfin);
    $depotdeb = date('d/m/Y', mktime(0, 0, 0, 1, 1, $tabdepotfin[2]));
  }
  // si depotdeb est postérieur à depotfin, on inverse les deux
  if ($depotdeb != '' && $depotfin != '') {
    $tabdepotdeb = explode('/', $depotdeb);
    $tabdepotfin = explode('/', $depotfin);
    $timedeb = mktime(0, 0, 0, $tabdepotdeb[1], $tabdepotdeb[0], $tabdepotdeb[2]);
    $timefin = mktime(0, 0, 0, $tabdepotfin[1], $tabdepotfin[0], $tabdepotfin[2]);
    if ($timefin < $timedeb) {$depottemp = $depotdeb; $depotdeb = $depotfin; $depotfin = $depottemp;}
  }
  $urlsauv .= "&depotdeb=".$depotdeb;
  $urlsauv .= "&depotfin=".$depotfin;

	$typnum = $_POST["typnum"];
	$urlsauv .= "&typnum=".$typnum;
	$typaut = $_POST["typaut"];
	$urlsauv .= "&typaut=".$typaut;
	$typnom = $_POST["typnom"];
	$urlsauv .= "&typnom=".$typnom;
	$typcol = $_POST["typcol"];
	$urlsauv .= "&typcol=".$typcol;
	$typlim = $_POST["typlim"];
	$urlsauv .= "&typlim=".$typlim;
  $limaff = $_POST["limaff"];
	$urlsauv .= "&limaff=".$limaff;
	$typtit = ',';
	$listit = '~';
  for ($i=0;$i<count($_POST['typtit']);$i++) {
    $typtit .= $_POST['typtit'][$i].',';
    $listit .= $_POST['typtit'][$i].'~';
  }
  $urlsauv .= "&typtit=".$listit;
	$typann = $_POST["typann"];
	$urlsauv .= "&typann=".$typann;
	$typchr = $_POST["typchr"];
	$urlsauv .= "&typchr=".$typchr;
	$typtri = $_POST["typtri"];
	$urlsauv .= "&typtri=".$typtri;
	$typfor = $_POST["typfor"];
	$urlsauv .= "&typfor=".$typfor;
	$typdoi = $_POST["typdoi"];
	$urlsauv .= "&typdoi=".$typdoi;
	$typurl = $_POST["typurl"];
	$urlsauv .= "&typurl=".$typurl;
	$surdoi = $_POST["surdoi"];
	$urlsauv .= "&surdoi=".$surdoi;
	$sursou = $_POST["sursou"];
	$urlsauv .= "&sursou=".$sursou;
	$typidh = $_POST["typidh"];
	$urlsauv .= "&typidh=".$typidh;
	$racine = $_POST["racine"];
	$urlsauv .= "&racine=".$racine;
	$typreva = $_POST["typreva"];
	$urlsauv .= "&typreva=".$typreva;
  $typrevh = $_POST["typrevh"];
	$urlsauv .= "&typrevh=".$typrevh;
  $dscp = $_POST["dscp"];
	$urlsauv .= "&dscp=".$dscp;
	$typrevc = $_POST["typrevc"];
	$urlsauv .= "&typrevc=".$typrevc;
	$typif = $_POST["typif"];
	$urlsauv .= "&typif=".$typif;
	$typavsa = $_POST["typavsa"];
	$urlsauv .= "&typavsa=".$typavsa;
	$delim = $_POST["delim"];
	switch($delim) {
    case ";":
      $urlsauv .= "&delim=pvir";
      break;
    case "£":
      $urlsauv .= "&delim=poun";
      break;
    case "§":
      $urlsauv .= "&delim=para";
      break;
  }
  if (isset($_POST['typcro'])) {
    $typcro = $_POST["typcro"];
    $urlsauv .= "&typcro=".$typcro;
  }
  if (isset($_POST['typeqp'])) {
    $typeqp = $_POST["typeqp"];
    $urlsauv .= "&typeqp=".$typeqp;
  }
  if (isset($_POST['prefeq'])) {
    $prefeq = $_POST["prefeq"];
    $urlsauv .= "&prefeq=".$prefeq;
  }
  if (isset($_POST['nbeqp'])) {
    $nbeqp = $_POST["nbeqp"];
    $urlsauv .= "&nbeqp=".$nbeqp;
  }

  $nomeqp[0] = $team;
  $typeqp = $_POST["typeqp"];
  if (isset($typeqp) && $typeqp == "oui") {//Numérotation/codification par équipe
    $nbeqp = $_POST['nbeqp'];
    $gr = "¤".$team."¤";
    for($i = 1; $i <= $nbeqp; $i++) {
      //$gr = "¤GR¤GR1¤GR2¤GR3¤GR4¤GR5¤GR6¤GR7¤GR8¤GR9¤";
      $gr .= strtoupper($_POST['eqp'.$i])."¤";
      $nomeqp[$i] = strtoupper($_POST['eqp'.$i]);
      $urlsauv .= "&eqp".$i."=".$nomeqp[$i];
    }
  }

  $stpdf = $_POST['stpdf'];
  $urlsauv .= "&stpdf=".$stpdf;

  $spa = $_POST['spa'];
  $urlsauv .= "&spa=".$spa;

  $nmo = $_POST['nmo'];
  $urlsauv .= "&nmo=".$nmo;

  $gp1 = $_POST['gp1'];
  $urlsauv .= "&gp1=".$gp1;

  $gp2 = $_POST['gp2'];
  $urlsauv .= "&gp2=".$gp2;

  $gp3 = $_POST['gp3'];
  $urlsauv .= "&gp3=".$gp3;

  $gp4 = $_POST['gp4'];
  $urlsauv .= "&gp4=".$gp4;

  $gp5 = $_POST['gp5'];
  $urlsauv .= "&gp5=".$gp5;

  $gp6 = $_POST['gp6'];
  $urlsauv .= "&gp6=".$gp6;

  $gp7 = $_POST['gp7'];
  $urlsauv .= "&gp7=".$gp7;

  $sep1 = $_POST['sep1'];
  $urlsauv .= "&sep1=".$sep1;

  $sep2 = $_POST['sep2'];
  $urlsauv .= "&sep2=".$sep2;

  $sep3 = $_POST['sep3'];
  $urlsauv .= "&sep3=".$sep3;

  $sep4 = $_POST['sep4'];
  $urlsauv .= "&sep4=".$sep4;

  $sep5 = $_POST['sep5'];
  $urlsauv .= "&sep5=".$sep5;

  $sep6 = $_POST['sep6'];
  $urlsauv .= "&sep6=".$sep6;

  $sep7 = $_POST['sep7'];
  $urlsauv .= "&sep7=".$sep7;

  $choix_mp1 = "~";
  $choix_mp2 = "~";
  $choix_mp3 = "~";
  $choix_mp4 = "~";
  $choix_mp5 = "~";
  $choix_mp6 = "~";
  $choix_mp7 = "~";
  $mp1_array = $_POST['mp1'];
  if (!empty($mp1_array)) {
    foreach($mp1_array as $selectValue){
      $choix_mp1 .= $selectValue."~";
    }
  }
  $urlsauv .= "&mp1=".$choix_mp1;

  $mp2_array = $_POST['mp2'];
  if (!empty($mp2_array)) {
    foreach($mp2_array as $selectValue){
      $choix_mp2 .= $selectValue."~";
    }
  }
  $urlsauv .= "&mp2=".$choix_mp2;

  $mp3_array = $_POST['mp3'];
  if (!empty($mp3_array)) {
    foreach($mp3_array as $selectValue){
      $choix_mp3 .= $selectValue."~";
    }
  }
  $urlsauv .= "&mp3=".$choix_mp3;

  $mp4_array = $_POST['mp4'];
  if (!empty($mp4_array)) {
    foreach($mp4_array as $selectValue){
      $choix_mp4 .= $selectValue."~";
    }
  }
  $urlsauv .= "&mp4=".$choix_mp4;

  $mp5_array = $_POST['mp5'];
  if (!empty($mp5_array)) {
    foreach($mp5_array as $selectValue){
      $choix_mp5 .= $selectValue."~";
    }
  }
  $urlsauv .= "&mp5=".$choix_mp5;

  $mp6_array = $_POST['mp6'];
  if (!empty($mp6_array)) {
    foreach($mp6_array as $selectValue){
      $choix_mp6 .= $selectValue."~";
    }
  }
  $urlsauv .= "&mp6=".$choix_mp6;

  $mp7_array = $_POST['mp7'];
  if (!empty($mp7_array)) {
    foreach($mp7_array as $selectValue){
      $choix_mp7 .= $selectValue."~";
    }
  }
  $urlsauv .= "&mp7=".$choix_mp7;

  $choix_cg1 = "#".$_POST['cg1'];
  $urlsauv .= "&cg1=".$_POST['cg1'];

  $choix_cg2 = "#".$_POST['cg2'];
  $urlsauv .= "&cg2=".$_POST['cg2'];

  $choix_cg3 = "#".$_POST['cg3'];
  $urlsauv .= "&cg3=".$_POST['cg3'];

  $choix_cg4 = "#".$_POST['cg4'];
  $urlsauv .= "&cg4=".$_POST['cg4'];

  $choix_cg5 = "#".$_POST['cg5'];
  $urlsauv .= "&cg5=".$_POST['cg5'];

  $choix_cg6 = "#".$_POST['cg6'];
  $urlsauv .= "&cg6=".$_POST['cg6'];

  $choix_cg7 = "#".$_POST['cg7'];
  $urlsauv .= "&cg7=".$_POST['cg7'];
}

if (isset($_GET["team"])) {
  $team = strtoupper(htmlspecialchars($_GET["team"]));
  $idhal = $_GET["idhal"];
  $quand0 = time();
	$quand = substr($quand0, 0, 9);
  if (isset($idhal) && $idhal != "") {$team = $idhal;}
	//export Bibtex
	$Fnm2 = "./HAL/extractionHAL_".$team."_".$quand.".bib";
	//$Fnm2 = "./HAL/extractionHAL_".$team.".bib";
	$inF2 = fopen($Fnm2,"w+");
	fseek($inF2, 0);
	$chaine2 = "\xEF\xBB\xBF";
	fwrite($inF2,$chaine2);
	fclose($inF2);
	//export CSV
	$Fnm1 = "./HAL/extractionHAL_".$team."_".$quand.".csv";
	//$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
	$inF1 = fopen($Fnm1,"w");
	fseek($inF1, 0);
	$chaine1 = "\xEF\xBB\xBF";
	fwrite($inF1,$chaine1);
	//export en RTF
	$Fnm = "./HAL/extractionHAL_".$team."_".$quand.".rtf";
	require_once ("./lib/phprtflite-1.2.0/lib/PHPRtfLite.php");
	PHPRtfLite::registerAutoloader();
	$rtfic = new PHPRtfLite();
	$sect = $rtfic->addSection();
	$font = new PHPRtfLite_Font(9, 'Trebuchet', '#000000', '#FFFFFF');
	$fontlien = new PHPRtfLite_Font(9, 'Trebuchet', '#0000FF', '#FFFFFF');
	$fonth3 = new PHPRtfLite_Font(12, 'Trebuchet', '#000000', '#FFFFFF');
	$fonth2 = new PHPRtfLite_Font(14, 'Trebuchet', '#000000', '#FFFFFF');
	$parFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_JUSTIFY);

	//sauvegarde URL
	$root = 'http';
	if ( isset ($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")	{
		$root.= "s";
	}
	$urlsauv = $root."://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	$urlsauv .= "?team=".$team;
  $listaut = strtoupper($_GET["listaut"]);
  if ($listaut == "") {$listaut = $team;}
  $urlsauv .= "&listaut=".$listaut;
  $urlsauv .= "&idhal=".$idhal;
  $evhal = $_GET["evhal"];
  $urlsauv .= "&evhal=".$evhal;
  if (isset($_GET['publis'])) {//Articles de revue
    $publis = $_GET["publis"];
    $urlsauv .= "&publis=".$publis;
    $tabpublis = explode("~", $publis);
    $i = 0;
    $choix_publis = "-";
    while (isset($tabpublis[$i])) {
      $choix_publis .= $tabpublis[$i]."-";
      $i++;
    }
  }
  if (isset($_GET['comm'])) {//Communications / conférences
    $comm = $_GET["comm"];
    $urlsauv .= "&comm=".$comm;
    $tabcomm = explode("~", $comm);
    $i = 0;
    $choix_comm = "-";
    while (isset($tabcomm[$i])) {
      $choix_comm .= $tabcomm[$i]."-";
      $i++;
    }
  }
  if (isset($_GET['ouvr'])) {//Ouvrages
    $ouvr = $_GET["ouvr"];
    $urlsauv .= "&ouvr=".$ouvr;
    $tabouvr = explode("~", $ouvr);
    $i = 0;
    $choix_ouvr = "-";
    while (isset($tabouvr[$i])) {
      $choix_ouvr .= $tabouvr[$i]."-";
      $i++;
    }
  }
  if (isset($_GET['autr'])) {//Autres
    $autr = $_GET["autr"];
    $urlsauv .= "&autr=".$autr;
    $tabautr = explode("~", $autr);
    $i = 0;
    $choix_autr = "-";
    while (isset($tabautr[$i])) {
      $choix_autr .= $tabautr[$i]."-";
      $i++;
    }
  }

	//Création des listes des auteurs appartenant à la collection spécifiée pour la liste
  if (isset($_GET['extur1']) && $_GET['extur1'] != '') {//Liste d'auteurs extérieurs à Rennes 1
    $uniq = $_GET['extur1'];
    include "./pvt/ExtractionHAL-auteurs-extur1-".$uniq.".php";
    $urlsauv .= "&extur1=".$uniq;
  }else{
    include "./pvt/ExtractionHAL-auteurs.php";
  }
  $listenominit = "~";
  $listenomcomp1 = "~";
  $listenomcomp2 = "~";
  foreach($AUTEURS_LISTE AS $i => $valeur) {
    if ($AUTEURS_LISTE[$i]['collhal'] == $listaut || $AUTEURS_LISTE[$i]['colleqhal'] == $listaut) {
      $listenomcomp1 .= nomCompEntier($AUTEURS_LISTE[$i]['nom'])." ".prenomCompEntier($AUTEURS_LISTE[$i]['prenom'])."~";
      $listenomcomp2 .= prenomCompEntier($AUTEURS_LISTE[$i]['prenom'])." ".nomCompEntier($AUTEURS_LISTE[$i]['nom'])."~";
      //si prénom composé et juste les ititiales
      $prenom = prenomCompInit($AUTEURS_LISTE[$i]['prenom']);
      $listenominit .= nomCompEntier($AUTEURS_LISTE[$i]['nom'])." ".$prenom.".~";
			if (isset($AUTEURS_LISTE[$i]['arriv']) && $AUTEURS_LISTE[$i]['arriv'] != "" && $AUTEURS_LISTE[$i]['arriv'] != "x") {
				$arriv .= $AUTEURS_LISTE[$i]['arriv']."~";
			}else{
				$arriv .= "1900~";
			}
			if (isset($AUTEURS_LISTE[$i]['depar']) && $AUTEURS_LISTE[$i]['depar'] != "" && $AUTEURS_LISTE[$i]['depar'] != "x") {
				$depar .= $AUTEURS_LISTE[$i]['depar']."~";
			}else{
        $moisactuel = date('n', time());
        if ($moisactuel >= 10) {$idepar = date('Y', time())+1;}else{$idepar = date('Y', time());}
        $depar .= $idepar."~";
			}
    }
  }
  //Extraction sur un IdHAL > auteur à mettre en évidence
  if (isset($evhal) && $evhal != "") {
    $list = explode(" ", $evhal);
    $listenomcomp1 = "~".nomCompEntier($list[1])." ".prenomCompEntier($list[0])."~";
    $listenomcomp2 = "~".prenomCompEntier($list[0])." ".nomCompEntier($list[1])."~";
    //si prénom composé et juste les ititiales
    $prenom = prenomCompInit($list[0]);
    $listenominit = "~".nomCompEntier($list[1])." ".$prenom.".~";
    $arriv = "~1900~";
    $moisactuel = date('n', time());
    if ($moisactuel >= 10) {$idepar = date('Y', time())+1;}else{$idepar = date('Y', time());}
    $depar = "~".$idepar."~";
  }

	if (isset($_GET['anneedeb'])) {$anneedeb = $_GET['anneedeb'];}else{$anneedeb = date('Y', time());$anneefin = date('Y', time());}
  if (isset($_GET['anneefin'])) {$anneefin = $_GET['anneefin'];}else{if (isset($_GET['anneedeb'])) {$anneefin = $_GET['anneedeb'];}else{$anneefin = $anneedeb;}}
  // vérification sur ordre des années si différentes
  if ($anneefin < $anneedeb) {$anneetemp = $anneedeb; $anneedeb = $anneefin; $anneefin = $anneetemp;}
	$urlsauv .= "&anneedeb=".$anneedeb;
  $urlsauv .= "&anneefin=".$anneefin;

  if (isset($_GET['depotdeb'])) {$depotdeb = $_GET['depotdeb'];}
  if (isset($_GET['depotfin'])) {$depotfin = $_GET['depotfin'];}
  // si depotdeb et depotfin non définis, on force depotdeb au 01/01/anneedeb et depotfin au 31/12/anneefin
  if ($depotdeb == '' && $depotfin == '') {
    $depotforce = "oui";
    //$depotdeb = date('d/m/Y', mktime(0, 0, 0, 1, 1, $anneedeb));
    //$depotfin = date('d/m/Y', mktime(0, 0, 0, 12, 31, $anneefin));
  }
  // si depotdeb défini mais pas depotfin, on force depotfin à aujourd'hui
  if ($depotdeb != '' && $depotfin == '') {$depotfin = date('d/m/Y', time());}
  // si depotfin défini mais pas depotdeb, on force depotdeb au 1er janvier de l'année de depotfin
  if ($depotdeb == '' && $depotfin != '') {
    $tabdepotfin = explode('/', $depotfin);
    $depotdeb = date('d/m/Y', mktime(0, 0, 0, 1, 1, $tabdepotfin[2]));
  }
  // si depotdeb est postérieur à depotfin, on inverse les deux
  if ($depotfin < $depotdeb) {$depottemp = $depotdeb; $depotdeb = $depotfin; $depotfin = $depottemp;}

  $urlsauv .= "&depotdeb=".$depotdeb;
  $urlsauv .= "&depotfin=".$depotfin;

  $typnum = $_GET["typnum"];
	$urlsauv .= "&typnum=".$typnum;
  $typaut = $_GET["typaut"];
	$urlsauv .= "&typaut=".$typaut;
  $typnom = $_GET["typnom"];
	$urlsauv .= "&typnom=".$typnom;
  $typcol = $_GET["typcol"];
	$urlsauv .= "&typcol=".$typcol;
  $typlim = $_GET["typlim"];
	$urlsauv .= "&typlim=".$typlim;
  $limaff = $_GET["limaff"];
	$urlsauv .= "&limaff=".$limaff;
  $typtit = $_GET["typtit"];
	$urlsauv .= "&typtit=".$typtit;
  $typann = $_GET["typann"];
	$urlsauv .= "&typann=".$typann;
  $typchr = $_GET["typchr"];
	$urlsauv .= "&typchr=".$typchr;
	$typtri = $_GET["typtri"];
	$urlsauv .= "&typtri=".$typtri;
  $typfor = $_GET["typfor"];
	$urlsauv .= "&typfor=".$typfor;
  $typdoi = $_GET["typdoi"];
	$urlsauv .= "&typdoi=".$typdoi;
	$typurl = $_GET["typurl"];
	$urlsauv .= "&typurl=".$typurl;
	$surdoi = $_GET["surdoi"];
	$urlsauv .= "&surdoi=".$surdoi;
	$sursou = $_GET["sursou"];
	$urlsauv .= "&sursou=".$sursou;
  $typidh = $_GET["typidh"];
	$urlsauv .= "&typidh=".$typidh;
	$racine = $_GET["racine"];
	$urlsauv .= "&racine=".$racine;
  $typreva = $_GET["typreva"];
	$urlsauv .= "&typreva=".$typreva;
  $typrevh = $_GET["typrevh"];
	$urlsauv .= "&typrevh=".$typrevh;
  $dscp = $_GET["dscp"];
	$urlsauv .= "&dscp=".$dscp;
  $typrevc = $_GET["typrevc"];
	$urlsauv .= "&typrevc=".$typrevc;
	$typif = $_GET["typif"];
	$urlsauv .= "&typif=".$typif;
  $typavsa = $_GET["typavsa"];
	$urlsauv .= "&typavsa=".$typavsa;
  $delim = $_GET["delim"];
  switch($delim) {
    case "pvir":
      $delim = ";";
			$urlsauv .= "&delim=pvir";
      break;
    case "poun":
      $delim = "£";
			$urlsauv .= "&delim=poun";
      break;
    case "para":
      $delim = "§";
			$urlsauv .= "&delim=para";
      break;
  }
  $nomeqp[0] = $team;
  if (isset($_GET['typcro'])) {
    $typcro = $_GET["typcro"];
    $urlsauv .= "&typcro=".$typcro;
  }
  if (isset($_GET['typeqp'])) {
    $typeqp = $_GET["typeqp"];
    $urlsauv .= "&typeqp=".$typeqp;
  }
  if (isset($_GET['prefeq'])) {
    $prefeq = $_GET["prefeq"];
    $urlsauv .= "&prefeq=".$prefeq;
  }
  if (isset($_GET['nbeqp'])) {
    $nbeqp = $_GET["nbeqp"];
    $urlsauv .= "&nbeqp=".$nbeqp;
  }
  if (isset($typeqp) && $typeqp == "oui") {//Numérotation/codification par équipe
    $gr = "¤".$team."¤";
    for($i = 1; $i <= $nbeqp; $i++) {
      $gr .= $_GET['eqp'.$i]."¤";
      $nomeqp[$i] = $_GET['eqp'.$i];
			$urlsauv .= "&eqp".$i."=".$nomeqp[$i];
    }
  }

  $stpdf = $_GET['stpdf'];
  $urlsauv .= "&stpdf=".$stpdf;

  $spa = $_GET['spa'];
  $urlsauv .= "&spa=".$spa;

  $nmo = $_GET['nmo'];
  $urlsauv .= "&nmo=".$nmo;

  $gp1 = $_GET['gp1'];
  $urlsauv .= "&gp1=".$gp1;

  $gp2 = $_GET['gp2'];
  $urlsauv .= "&gp2=".$gp2;

  $gp3 = $_GET['gp3'];
  $urlsauv .= "&gp3=".$gp3;

  $gp4 = $_GET['gp4'];
  $urlsauv .= "&gp4=".$gp4;

  $gp5 = $_GET['gp5'];
  $urlsauv .= "&gp5=".$gp5;

  $gp6 = $_GET['gp6'];
  $urlsauv .= "&gp6=".$gp6;

  $gp7 = $_GET['gp7'];
  $urlsauv .= "&gp7=".$gp7;

  $sep1 = $_GET['sep1'];
  $urlsauv .= "&sep1=".$sep1;

  $sep2 = $_GET['sep2'];
  $urlsauv .= "&sep2=".$sep2;

  $sep3 = $_GET['sep3'];
  $urlsauv .= "&sep3=".$sep3;

  $sep4 = $_GET['sep4'];
  $urlsauv .= "&sep4=".$sep4;

  $sep5 = $_GET['sep5'];
  $urlsauv .= "&sep5=".$sep5;

  $sep6 = $_GET['sep6'];
  $urlsauv .= "&sep6=".$sep6;

  $sep7 = $_GET['sep7'];
  $urlsauv .= "&sep7=".$sep7;

  $choix_mp1 = $_GET["mp1"];//Mise en page 1
	$urlsauv .= "&mp1=".$choix_mp1;
  $tabmp1 = explode("~", $choix_mp1);
  $i = 0;
  $choix_mp1 = "~";
  while (isset($tabmp1[$i])) {
    $choix_mp1 .= $tabmp1[$i]."~";
    $i++;
  }

  $choix_mp2 = $_GET["mp2"];//Mise en page 2
	$urlsauv .= "&mp2=".$choix_mp2;
  $tabmp2 = explode("~", $choix_mp2);
  $i = 0;
  $choix_mp2 = "~";
  while (isset($tabmp2[$i])) {
    $choix_mp2 .= $tabmp2[$i]."~";
    $i++;
  }

  $choix_mp3 = $_GET["mp3"];//Mise en page 3
	$urlsauv .= "&mp3=".$choix_mp3;
  $tabmp3 = explode("~", $choix_mp3);
  $i = 0;
  $choix_mp3 = "~";
  while (isset($tabmp3[$i])) {
    $choix_mp3 .= $tabmp3[$i]."~";
    $i++;
  }

  $choix_mp4 = $_GET["mp4"];//Mise en page 4
	$urlsauv .= "&mp4=".$choix_mp4;
  $tabmp4 = explode("~", $choix_mp4);
  $i = 0;
  $choix_mp4 = "~";
  while (isset($tabmp4[$i])) {
    $choix_mp4 .= $tabmp4[$i]."~";
    $i++;
  }

  $choix_mp5 = $_GET["mp5"];//Mise en page 5
	$urlsauv .= "&mp5=".$choix_mp5;
  $tabmp5 = explode("~", $choix_mp5);
  $i = 0;
  $choix_mp5 = "~";
  while (isset($tabmp5[$i])) {
    $choix_mp5 .= $tabmp5[$i]."~";
    $i++;
  }

  $choix_mp6 = $_GET["mp6"];//Mise en page 6
	$urlsauv .= "&mp6=".$choix_mp6;
  $tabmp6 = explode("~", $choix_mp6);
  $i = 0;
  $choix_mp6 = "~";
  while (isset($tabmp6[$i])) {
    $choix_mp6 .= $tabmp6[$i]."~";
    $i++;
  }

  $choix_mp7 = $_GET["mp7"];//Mise en page 7
	$urlsauv .= "&mp7=".$choix_mp7;
  $tabmp7 = explode("~", $choix_mp7);
  $i = 0;
  $choix_mp7 = "~";
  while (isset($tabmp7[$i])) {
    $choix_mp7 .= $tabmp7[$i]."~";
    $i++;
  }

  $choix_cg1 = "#".$_GET['cg1'];
  $urlsauv .= "&cg1=".$_GET['cg1'];

  $choix_cg2 = "#".$_GET['cg2'];
  $urlsauv .= "&cg2=".$_GET['cg2'];

  $choix_cg3 = "#".$_GET['cg3'];
  $urlsauv .= "&cg3=".$_GET['cg3'];

  $choix_cg4 = "#".$_GET['cg4'];
  $urlsauv .= "&cg4=".$_GET['cg4'];

  $choix_cg5 = "#".$_GET['cg5'];
  $urlsauv .= "&cg5=".$_GET['cg5'];

  $choix_cg6 = "#".$_GET['cg6'];
  $urlsauv .= "&cg6=".$_GET['cg6'];

  $choix_cg7 = "#".$_GET['cg7'];
  $urlsauv .= "&cg7=".$_GET['cg7'];
}
?>

<body style="font-family:calibri,verdana">

<noscript>
<div align='center' id='noscript'><font color='red'><b>ATTENTION !!! JavaScript est désactivé ou non pris en charge par votre navigateur : cette procédure ne fonctionnera pas correctement.</b></font><br>
<b>Pour modifier cette option, voir <a target='_blank' href='http://www.libellules.ch/browser_javascript_activ.php'>ce lien</a>.</b></div><br>
</noscript>

<table width="100%">
<tr>
<td style="text-align: left;"><img alt="ExtrHAL" title="ExtrHAL" width="250px" src="./img/logo_Extrhal.png"></td>
<td style="text-align: right;"><img alt="Université de Rennes 1" title="Université de Rennes 1" width="150px" src="./img/logo_UR1_gris_petit.jpg"></td>
</tr>
</table>
<hr style="color: #467666;">

<p>ExtrHAL permet d’afficher et d’exporter en RTF,CSV et/ou Bibtex des listes de publications HAL d’une unité, d'une équipe de recherche ou d'un auteur,
à partir d’un script PHP créé par <a target="_blank" href="http://igm.univ-mlv.fr/~gambette/ExtractionHAL/ExtractionHAL.php?collection=UPEC-UPEM">
Philippe Gambette</a>, repris et modifié par <a target="_blank" href="https://ecobio.univ-rennes1.fr/personnel.php?qui=Olivier_Troccaz">Olivier Troccaz</a> (ECOBIO - OSUR) pour l’Université de Rennes 1.
Si vous souhaitez utiliser et adapter ExtrHAL pour une autre institution, consultez
<a target="_blank" href="https://wiki.ccsd.cnrs.fr/wikis/hal/index.php/Outils_et_services_d%C3%A9velopp%C3%A9s_localement_pour_am%C3%A9liorer_ou_faciliter_l%27utilisation_de_HAL#Extraction_et_mise_en_forme_des_publications">le wiki du CCSD</a>.</p>

<form method="POST" accept-charset="utf-8" name="extrhal" action="ExtractionHAL.php#sommaire">
<?php
$uniq = "";
if (isset($_GET['extur1']) && $_GET['extur1'] != '') {$uniq = $_GET['extur1'];}
if (isset($_POST['extur1']) && $_POST['extur1'] != '') {$uniq = $_POST['extur1'];}
if ($uniq != '') {
  echo('Vous utilisez votre propre fichier de liste d\'auteurs à mettre en évidence');
  echo('<input type="hidden" value="'.$uniq.'" name="extur1">');
}else{
  echo('Extérieurs à Rennes 1, vous avez la possibilité de mettre en évidence les auteurs de votre collection en <a href="ExtractionHAL-liste-auteurs-extur1.php">prétéléchargeant un fichier CSV ou TXT</a> réalisé selon <a href="https://halur1.univ-rennes1.fr/modele.csv">ce modèle</a>.');
}
?>
<p><b>Code collection HAL</b> <a class=info onclick='return false' href="#">(qu’est-ce que c’est ?)<span>Code visible dans l’URL d’une collection.
Exemple : IPR-MOL est le code de la collection http://hal.archives-ouvertes.fr/<b>IPR-PMOL</b> de l’équipe Physique moléculaire
de l’unité IPR UMR CNRS 6251</span></a> :
<?php
if (isset($team) && $team != "") {$team1 = $team; $team2 = $team;}else{$team1 = "Entrez le code de votre collection"; $team2 = "";}
if (!isset($listaut)) {$listaut = "";}
if (isset($idhal) && $idhal != "") {$team1 = ""; $listaut = "";}
?>
<input type="text" name="team" value="<?php echo $team1;?>" size="40" onClick="this.value='<?php echo $team2;?>';"><br>
<p>Code collection HAL pour la liste des auteurs à mettre en évidence <a class=info onclick='return false' href="#">(exemple)<span>Indiquez ici
le code collection de votre labo ou de votre équipe, selon que vous souhaitez mettre en évidence le nom des auteurs du labo ou de l'équipe.
</span></a> :
<input type="text" name="listaut" value="<?php echo $listaut;?>" size="40"><br>
<h2><b><u>ou</u></b></h2>
<p><b>Identifiant alphabétique auteur HAL</b> <i>(IdHAL > olivier-troccaz, par exemple)</i> :
<input type="text" name="idhal" value="<?php echo $idhal;?>" size="30">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="https://hal.archives-ouvertes.fr/page/mon-idhal">Créer mon IdHAL</a>
<br><br>
Auteur correspondant à l'IdHAL à mettre en évidence <i>(Remplacez les espaces par des _ > Jean-Luc Le_Breton, par exemple)</i> :
<input type="text" name="evhal" value="<?php echo $evhal;?>" size="30"></p>
<br>
<?php
if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {$ta = "selected";}else{$ta = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ACL-") !== false) {$acl = "selected";}else{$acl = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ASCL-") !== false) {$ascl = "selected";}else{$ascl = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ARI-") !== false) {$ari = "selected";}else{$ari = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ARN-") !== false) {$arn = "selected";}else{$arn = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRI-") !== false) {$aclri = "selected";}else{$aclri = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRN-") !== false) {$aclrn = "selected";}else{$aclrn = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRI-") !== false) {$asclri = "selected";}else{$asclri = "";}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRN-") !== false) {$asclrn = "selected";}else{$asclrn = "";}
if (isset($choix_publis) && strpos($choix_publis, "-AV-") !== false) {$av = "selected";}else{$av = "";}
?>
<div style='width:99%;float: left;'>
<font color="#cccccc"><i>Cliquez sur les titres des menus pour afficher les choix et options</i></font>
<div style='width:100%;float: left;background-color:#d9face;border:1px solid #dddddd;padding: 3px;border-radius: 3px;margin-bottom: 10px;'>
<span style='color:#333333;' class='accordeon'><b>Choix des listes de publications à afficher :</b></span>
<div class="panel">
<i>(sélection/désélection multiple en maintenant la touche 'Ctrl' (PC) ou 'Pomme' (Mac) enfoncée)</i>:
<table>
<tr><td valign="top">Articles de revue :</td>
<td><select size="10" name="publis[]" multiple>
<option value="TA" <?php echo $ta;?>>Tous les articles (sauf vulgarisation)</option>
<option value="ACL" <?php echo $acl;?>>Articles de revues à comité de lecture</option>
<option value="ASCL" <?php echo $ascl;?>>Articles de revues sans comité de lecture</option>
<option value="ARI" <?php echo $ari;?>>Articles de revues internationales</option>
<option value="ARN" <?php echo $arn;?>>Articles de revues nationales</option>
<option value="ACLRI" <?php echo $aclri;?>>Articles de revues internationales à comité de lecture</option>
<option value="ACLRN" <?php echo $aclrn;?>>Articles de revues nationales à comité de lecture</option>
<option value="ASCLRI" <?php echo $asclri;?>>Articles de revues internationales sans comité de lecture</option>
<option value="ASCLRN" <?php echo $asclrn;?>>Articles de revues nationales sans comité de lecture</option>
<option value="AV" <?php echo $av;?>>Articles de vulgarisation</option>
</select></td></tr></table><br>
<br>
<?php
if (isset($choix_comm) && strpos($choix_comm, "-TC-") !== false) {$tc = "selected";}else{$tc = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CA-") !== false) {$ca = "selected";}else{$ca = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CSA-") !== false) {$csa = "selected";}else{$csa = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CI-") !== false) {$ci = "selected";}else{$ci = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CN-") !== false) {$cn = "selected";}else{$cn = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CAI-") !== false) {$cai = "selected";}else{$cai = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CSAI-") !== false) {$csai = "selected";}else{$csai = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CAN-") !== false) {$can = "selected";}else{$can = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CSAN-") !== false) {$csan = "selected";}else{$csan = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CINVASANI-") !== false) {$cinvasani = "selected";}else{$cinvasani = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CINVA-") !== false) {$cinva = "selected";}else{$cinva = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CINVSA-") !== false) {$cinvsa = "selected";}else{$cinvsa = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVA-") !== false) {$cnoninva = "selected";}else{$cnoninva = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVSA-") !== false) {$cnoninvsa = "selected";}else{$cnoninvsa = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CINVI-") !== false) {$cinvi = "selected";}else{$cinvi = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVI-") !== false) {$cnoninvi = "selected";}else{$cnoninvi = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CINVN-") !== false) {$cinvn = "selected";}else{$cinvn = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVN-") !== false) {$cnoninvn = "selected";}else{$cnoninvn = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CPASANI-") !== false) {$cpasani = "selected";}else{$cpasani = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CPA-") !== false) {$cpa = "selected";}else{$cpa = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CPSA-") !== false) {$cpsa = "selected";}else{$cpsa = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CPI-") !== false) {$cpi = "selected";}else{$cpi = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CPN-") !== false) {$cpn = "selected";}else{$cpn = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CGP-") !== false) {$cgp = "selected";}else{$cgp = "";}
?>
<table>
<tr><td valign="top">Communications / conférences :</td>
<td><select size="24" name="comm[]" multiple>
<option value="TC" <?php echo $tc;?>>Toutes les communications (sauf grand public)</option>
<option value="CA" <?php echo $ca;?>>Communications avec actes</option>
<option value="CSA" <?php echo $csa;?>>Communications sans actes</option>
<option value="CI" <?php echo $ci;?>>Communications internationales</option>
<option value="CN" <?php echo $cn;?>>Communications nationales</option>
<option value="CAI" <?php echo $cai;?>>Communications avec actes internationales</option>
<option value="CSAI" <?php echo $csai;?>>Communications sans actes internationales</option>
<option value="CAN" <?php echo $can;?>>Communications avec actes nationales</option>
<option value="CSAN" <?php echo $csan;?>>Communications sans actes nationales</option>
<option value="CINVASANI" <?php echo $cinvasani;?>>Communications invitées avec ou sans actes, nationales ou internationales</option>
<option value="CINVA" <?php echo $cinva;?>>Communications invitées avec actes</option>
<option value="CINVSA" <?php echo $cinvsa;?>>Communications invitées sans actes</option>
<option value="CNONINVA" <?php echo $cnoninva;?>>Communications non invitées avec actes</option>
<option value="CNONINVSA" <?php echo $cnoninvsa;?>>Communications non invitées sans actes</option>
<option value="CINVI" <?php echo $cinvi;?>>Communications invitées internationales</option>
<option value="CNONINVI" <?php echo $cnoninvi;?>>Communications non invitées internationales</option>
<option value="CINVN" <?php echo $cinvn;?>>Communications invitées nationales</option>
<option value="CNONINVN" <?php echo $cnoninvn;?>>Communications non invitées nationales</option>
<option value="CPASANI" <?php echo $cpa;?>>Communications par affiches (posters) avec ou sans actes, nationales ou internationales</option>
<option value="CPA" <?php echo $cpa;?>>Communications par affiches (posters) avec actes</option>
<option value="CPSA" <?php echo $cpsa;?>>Communications par affiches (posters) sans actes</option>
<option value="CPI" <?php echo $cpi;?>>Communications par affiches internationales</option>
<option value="CPN" <?php echo $cpn;?>>Communications par affiches nationales</option>
<option value="CGP" <?php echo $cgp;?>>Conférences grand public</option>
</select></td></tr></table><br>
<br>
<?php
if (isset($choix_ouvr) && strpos($choix_ouvr, "-TO-") !== false) {$to = "selected";}else{$to = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPI-") !== false) {$ospi = "selected";}else{$ospi = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPN-") !== false) {$ospn = "selected";}else{$ospn = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COS-") !== false) {$cos = "selected";}else{$cos = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSI-") !== false) {$cosi = "selected";}else{$cosi = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSN-") !== false) {$cosn = "selected";}else{$cosn = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOS-") !== false) {$dos = "selected";}else{$dos = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSI-") !== false) {$dosi = "selected";}else{$dosi = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSN-") !== false) {$dosn = "selected";}else{$dosn = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCO-") !== false) {$oco = "selected";}else{$oco = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCOI-") !== false) {$ocoi = "selected";}else{$ocoi = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCON-") !== false) {$ocon = "selected";}else{$ocon = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODO-") !== false) {$odo = "selected";}else{$odo = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODOI-") !== false) {$odoi = "selected";}else{$odoi = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODON-") !== false) {$odon = "selected";}else{$odon = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDO-") !== false) {$ocdo = "selected";}else{$ocdo = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDOI-") !== false) {$ocdoi = "selected";}else{$ocdoi = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDON-") !== false) {$ocdon = "selected";}else{$ocdon = "";}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCV-") !== false) {$ocv = "selected";}else{$ocv = "";}
?>
<table>
<tr><td valign="top">Ouvrages :</td>
<td><select size="19" name="ouvr[]" multiple>
<option value="TO" <?php echo $to;?>>Tous les ouvrages (sauf vulgarisation)</option>
<option value="OSPI" <?php echo $ospi;?>>Ouvrages scientifiques de portée internationale</option>
<option value="OSPN" <?php echo $ospn;?>>Ouvrages scientifiques de portée nationale</option>
<option value="COS" <?php echo $cos;?>>Chapitres d’ouvrages scientifiques</option>
<option value="COSI" <?php echo $cosi;?>>Chapitres d’ouvrages scientifiques de portée internationale</option>
<option value="COSN" <?php echo $cosn;?>>Chapitres d’ouvrages scientifiques de portée nationale</option>
<option value="DOS" <?php echo $dos;?>>Directions d’ouvrages scientifiques</option>
<option value="DOSI" <?php echo $dosi;?>>Directions d’ouvrages scientifiques de portée internationale</option>
<option value="DOSN" <?php echo $dosn;?>>Directions d’ouvrages scientifiques de portée nationale</option>
<option value="OCO" <?php echo $oco;?>>Ouvrages ou chapitres d’ouvrages</option>
<option value="OCOI" <?php echo $ocoi;?>>Ouvrages ou chapitres d’ouvrages de portée internationale</option>
<option value="OCON" <?php echo $ocon;?>>Ouvrages ou chapitres d’ouvrages de portée nationale</option>
<option value="ODO" <?php echo $odo;?>>Ouvrages ou directions d’ouvrages</option>
<option value="ODOI" <?php echo $odoi;?>>Ouvrages ou directions d’ouvrages de portée internationale</option>
<option value="ODON" <?php echo $odon;?>>Ouvrages ou directions d’ouvrages de portée nationale</option>
<option value="OCDO" <?php echo $ocdo;?>>Ouvrages ou chapitres ou directions d’ouvrages</option>
<option value="OCDOI" <?php echo $ocdoi;?>>Ouvrages ou chapitres ou directions d’ouvrages de portée internationale</option>
<option value="OCDON" <?php echo $ocdon;?>>Ouvrages ou chapitres ou directions d’ouvrages de portée nationale</option>
<option value="OCV" <?php echo $ocv;?>>Ouvrages ou chapitres de vulgarisation</option>
</select></td></tr></table><br>
<br>
<?php
if (isset($choix_autr) && strpos($choix_autr, "-BRE-") !== false) {$bre = "selected";}else{$bre = "";}
if (isset($choix_autr) && strpos($choix_autr, "-RAP-") !== false) {$rap = "selected";}else{$rap = "";}
if (isset($choix_autr) && strpos($choix_autr, "-THE-") !== false) {$the = "selected";}else{$the = "";}
if (isset($choix_autr) && strpos($choix_autr, "-HDR-") !== false) {$hdr = "selected";}else{$hdr = "";}
if (isset($choix_autr) && strpos($choix_autr, "-PWM-") !== false) {$pwm = "selected";}else{$pwm = "";}
if (isset($choix_autr) && strpos($choix_autr, "-AP-") !== false) {$ap = "selected";}else{$ap = "";}
?>
<table>
<tr><td valign="top">Autres productions scientifiques :</td>
<td><select size="6" name="autr[]" multiple>
<option value="BRE" <?php echo $bre;?>>Brevets</option>
<option value="RAP" <?php echo $rap;?>>Rapports</option>
<option value="THE" <?php echo $the;?>>Thèses</option>
<option value="HDR" <?php echo $hdr;?>>HDR</option>
<option value="PWM" <?php echo $pwm;?>>Preprints, working papers, manuscrits non publiés</option>
<option value="AP" <?php echo $ap;?>>Autres publications</option>
</select></td></tr></table><br>
<br>
<table>
<tr><td valign="top">Période :</td>
<td>
Depuis
<select name="anneedeb">
<?php
$moisactuel = date('n', time());
if ($moisactuel >= 10) {$i = date('Y', time())+1;}else{$i = date('Y', time());}
while ($i >= date('Y', time()) - 20) {
  if(isset($anneedeb) && $anneedeb == $i) {$txt = "selected";}else{$txt = "";}
  echo('<option value='.$i.' '.$txt.'>'.$i.'</option>');
  $i--;
}
?>
</select>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Jusqu'à
<select name="anneefin">
<?php
$moisactuel = date('n', time());
if ($moisactuel >= 10) {$i = date('Y', time())+1;}else{$i = date('Y', time());}
while ($i >= date('Y', time()) - 20) {
  if(isset($anneefin) && $anneefin == $i) {$txt = "selected";}else{$txt = "";}
  echo('<option value='.$i.' '.$txt.'>'.$i.'</option>');
  $i--;
}

if ($depotforce == "oui") {
  $depotdebval = "";
  $depotfinval = "";
}else{
  $depotdebval = $depotdeb;
  $depotfinval = $depotfin;
}
?>
</select></td></tr>
<tr><td>Date de dépôt :</td>
<td>
Du <input type="text" name="depotdeb" value="<?php echo $depotdebval;?>" class="calendrier">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Jusqu'au
<input type="text" name="depotfin" value="<?php echo $depotfinval;?>" class="calendrier">
</td></tr></table><br>
<br>
<?php
if (isset($typnum) && $typnum == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typnum) && $typnum == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
</div>
</div>
<div style='width:100%;float: left;background-color:#afeafc;border:1px solid #dddddd;padding: 3px;border-radius: 3px;margin-bottom: 10px;'>
<span style='color:#333333;' class='accordeon'><b>Options d'affichage et d'export</b> :</span>
<div class="panel" id="panel2">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Numérotation :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typnum" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typnum" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($typaut) && $typaut == "soul") {$soul = "checked";}else{$soul = "";}
if (isset($typaut) && $typaut == "gras") {$gras = "checked";}else{$gras = "";}
if (isset($typaut) && $typaut == "aucun" || !isset($team)) {$auc = "checked";}else{$auc = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Auteurs (tous) :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typaut" value="soul" <?php echo $soul;?>>soulignés
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typaut" value="gras" <?php echo $gras;?>>gras
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typaut" value="aucun" <?php echo $auc;?>>aucun
<br>
<?php
if (isset($typnom) && $typnom == "nominit" || !isset($team)) {$nominit = "checked";}else{$nominit = "";}
if (isset($typnom) && $typnom == "nomcomp1" || isset($stpdf) && $stpdf == "chi") {$nomcomp1 = "checked";}else{$nomcomp1 = "";}
if (isset($typnom) && $typnom == "nomcomp2") {$nomcomp2 = "checked";}else{$nomcomp2 = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Auteurs (tous) :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typnom" value="nominit" <?php echo $nominit;?>>Nom, initiale(s) du(des) prénom(s)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typnom" value="nomcomp1" <?php echo $nomcomp1;?>>Nom Prénom(s)
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typnom" value="nomcomp2" <?php echo $nomcomp2;?>>Prénom(s) Nom
<br>
<?php
if (isset($typcol) && $typcol == "soul" || !isset($team)) {$soul = "checked";}else{$soul = "";}
if (isset($typcol) && $typcol == "gras") {$gras = "checked";}else{$gras = "";}
if (isset($typcol) && $typcol == "aucun") {$auc = "checked";}else{$auc = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Auteurs (de la collection) ou auteur IdHAL:
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typcol" value="soul" <?php echo $soul;?>>soulignés
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typcol" value="gras" <?php echo $gras;?>>gras
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typcol" value="aucun" <?php echo $auc;?>>aucun
<br>
<?php
if (isset($typlim) && $typlim == "non" || !isset($team)) {$limn = "checked";}else{$limn = "";}
if (isset($typlim) && $typlim == "oui") {$limo = "checked";}else{$limo = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Limiter l’affichage aux
<select name="limaff">
<?php
if(isset($limaff) && $limaff == 1 || isset($stpdf) && $stpdf == "mla") {$txt = "selected";}else{$txt = "";}
echo('<option value=1 '.$txt.'>1</option>');
if((isset($limaff) && $limaff == 5) || !isset($team)) {$txt = "selected";}else{$txt = "";}
echo('<option value=5 '.$txt.'>5</option>');
if(isset($limaff) && $limaff == 10) {$txt = "selected";}else{$txt = "";}
echo('<option value=10 '.$txt.'>10</option>');
if(isset($limaff) && $limaff == 15) {$txt = "selected";}else{$txt = "";}
echo('<option value=15 '.$txt.'>15</option>');
if(isset($limaff) && $limaff == 20) {$txt = "selected";}else{$txt = "";}
echo('<option value=20 '.$txt.'>20</option>');
?>
</select>
 premier(s) auteur(s) (« et al. ») :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typlim" value="non" <?php echo $limn;?>>non
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typlim" value="oui" <?php echo $limo;?>>oui
<br>
<?php
$guil = "";
$gras = "";
$ital = "";
$reto = "";
$aucun = "";
if ((isset($typtit) && strpos($typtit,"aucun") >= 1) || !isset($team)) {
  $aucun = "checked";
  $typtit = ",aucun";
}else{
  $typtit = str_replace("aucun,","",$typtit);
  if (strpos($typtit,"guil") >= 1) {$guil = "checked";}
  if (strpos($typtit,"gras") >= 1) {$gras = "checked";}
  if (strpos($typtit,"ital") >= 1) {$ital = "checked";}
  if (strpos($typtit,"reto") >= 1) {$reto = "checked";}
}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Titres (articles, ouvrages, chapitres, etc.) <i>('aucun' est prioritaire et doit donc être décoché pour activer une ou plusieurs des autres formes)</i> :<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="checkbox" name="typtit[]" value="guil" <?php echo $guil;?>>entre guillemets
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="checkbox" name="typtit[]" value="gras" <?php echo $gras;?>>en gras
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="checkbox" name="typtit[]" value="ital" <?php echo $ital;?>>en italique
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="checkbox" name="typtit[]" value="reto" <?php echo $reto;?>>suivi d'un RC
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="checkbox" name="typtit[]" value="aucun" <?php echo $aucun;?>>aucun
<br>
<?php
if (isset($typann) && $typann == "apres" || !isset($team)) {$apres = "checked";}else{$apres = "";}
if (isset($typann) && $typann == "avant") {$avant = "checked";}else{$avant = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Année :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typann" value="apres" <?php echo $apres;?>>après les auteurs
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typann" value="avant" <?php echo $avant;?>>avant le numéro de volume
<br>
<?php
if (isset($typtri) && $typtri == "premierauteur" || !isset($team)) {$premierauteur= "checked";}else{$premierauteur = "";}
if (isset($typtri) && $typtri == "journal") {$journal = "checked";}else{$journal = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Classer par :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typtri" value="premierauteur" <?php echo $premierauteur;?>>année puis nom du premier auteur
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typtri" value="journal" <?php echo $journal;?>>année puis journal
<br>
<?php
if (isset($typchr) && $typchr == "decr" || !isset($team)) {$decr= "checked";}else{$decr = "";}
if (isset($typchr) && $typchr == "croi") {$croi = "checked";}else{$croi = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Années :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typchr" value="decr" <?php echo $decr;?>>décroissantes
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typchr" value="croi" <?php echo $croi;?>>croissantes
<br>
<?php
if (isset($typfor) && $typfor == "typ1") {$typ1 = "checked";}else{$typ1 = "";}
if (isset($typfor) && $typfor == "typ2" || !isset($team )) {$typ2 = "checked";}else{$typ2 = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Format métadonnées (articles de revues) :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typfor" value="typ1" <?php echo $typ1;?>>vol 5, n°2, pp. 320
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typfor" value="typ2" <?php echo $typ2;?>>5(2):320
<br>
<?php
if (isset($typurl) && $typurl == "vis" || !isset($team)) {$vis = "checked";}else{$vis = "";}
if (isset($typurl) && $typurl == "inv") {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Lien URL :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typurl" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typurl" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($typdoi) && $typdoi == "vis" || !isset($team)) {$vis = "checked";}else{$vis = "";}
if (isset($typdoi) && $typdoi == "inv") {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Lien DOI :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typdoi" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typdoi" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($surdoi) && $surdoi == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
if (isset($surdoi) && $surdoi == "vis") {$vis = "checked";}else{$vis = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Afficher les doublons par surlignage :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="surdoi" value="vis" <?php echo $vis;?>>oui
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="surdoi" value="inv" <?php echo $inv;?>>non
<br>
<?php
if (isset($sursou) && $sursou == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
if (isset($sursou) && $sursou == "vis") {$vis = "checked";}else{$vis = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Afficher les absences d'affiliation par surlignage :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="sursou" value="vis" <?php echo $vis;?>>oui
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="sursou" value="inv" <?php echo $inv;?>>non
<br>
<?php
if (isset($typidh) && $typidh == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typidh) && $typidh == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Identifiant HAL :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typidh" value="vis" onClick="affich_form2();" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typidh" value="inv" onClick="cacher_form2();"<?php echo $inv;?>>invisible
<div id="detrac">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;URL racine HAL :
<?php
if (!isset($racine)) {$racine = "https://hal-univ-rennes1.archives-ouvertes.fr/";}
?>
<select size="1" name="racine">
<?php if ($racine == "http://archivesic.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://archivesic.ccsd.cnrs.fr/">http://archivesic.ccsd.cnrs.fr/</option>
<?php if ($racine == "http://artxiker.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://artxiker.ccsd.cnrs.fr/">http://artxiker.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://cel.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://cel.archives-ouvertes.fr/">https://cel.archives-ouvertes.fr/</option>
<?php if ($racine == "http://dumas.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://dumas.ccsd.cnrs.fr/">http://dumas.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://edutice.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://edutice.archives-ouvertes.fr/">https://edutice.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal.archives-ouvertes.fr/">https://hal.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal.cirad.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.cirad.fr/">http://hal.cirad.fr/</option>
<?php if ($racine == "http://hal.grenoble-em.com/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.grenoble-em.com""">"http://hal.grenoble-em.com</option>
<?php if ($racine == "http://hal.in2p3.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.in2p3.fr/">http://hal.in2p3.fr/</option>
<?php if ($racine == "https://hal.inria.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal.inria.fr/">https://hal.inria.fr/</option>
<?php if ($racine == "http://hal.ird.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.ird.fr/">http://hal.ird.fr/</option>
<?php if ($racine == "http://hal.univ-brest.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.univ-brest.fr/">http://hal.univ-brest.fr/</option>
<?php if ($racine == "http://hal.univ-grenoble-alpes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.univ-grenoble-alpes.fr/">http://hal.univ-grenoble-alpes.fr/</option>
<?php if ($racine == "http://hal.univ-lille3.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.univ-lille3.fr/">http://hal.univ-lille3.fr/</option>
<?php if ($racine == "http://hal.univ-nantes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.univ-nantes.fr/">http://hal.univ-nantes.fr/</option>
<?php if ($racine == "http://hal.univ-reunion.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.univ-reunion.fr/">http://hal.univ-reunion.fr/</option>
<?php if ($racine == "http://hal.univ-smb.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.univ-smb.fr/">http://hal.univ-smb.fr/</option>
<?php if ($racine == "http://hal.upmc.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal.upmc.fr/">http://hal.upmc.fr/</option>
<?php if ($racine == "https://hal-agrocampus-ouest.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-agrocampus-ouest.archives-ouvertes.fr/">https://hal-agrocampus-ouest.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-agroparistech.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-agroparistech.archives-ouvertes.fr/">https://hal-agroparistech.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-amu.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-amu.archives-ouvertes.fr/">https://hal-amu.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-anses.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-anses.archives-ouvertes.fr/">https://hal-anses.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal-audencia.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal-audencia.archives-ouvertes.fr/">http://hal-audencia.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-auf.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-auf.archives-ouvertes.fr/">https://hal-auf.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal-bioemco.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal-bioemco.ccsd.cnrs.fr/">http://hal-bioemco.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://hal-bnf.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-bnf.archives-ouvertes.fr/">https://hal-bnf.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-brgm.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-brgm.archives-ouvertes.fr/">https://hal-brgm.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-cea.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-cea.archives-ouvertes.fr/">https://hal-cea.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-centralesupelec.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-centralesupelec.archives-ouvertes.fr/">https://hal-centralesupelec.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-clermont-univ.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-clermont-univ.archives-ouvertes.fr/">https://hal-clermont-univ.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-confremo.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-confremo.archives-ouvertes.fr/">https://hal-confremo.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-cstb.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-cstb.archives-ouvertes.fr/">https://hal-cstb.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-descartes.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-descartes.archives-ouvertes.fr/">https://hal-descartes.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ecp.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ecp.archives-ouvertes.fr/">https://hal-ecp.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-em-normandie.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-em-normandie.archives-ouvertes.fr/">https://hal-em-normandie.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal-emse.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal-emse.ccsd.cnrs.fr/">http://hal-emse.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://hal-enac.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-enac.archives-ouvertes.fr/">https://hal-enac.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-enpc.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-enpc.archives-ouvertes.fr/">https://hal-enpc.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ens.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ens.archives-ouvertes.fr/">https://hal-ens.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-enscp.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-enscp.archives-ouvertes.fr/">https://hal-enscp.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ens-lyon.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ens-lyon.archives-ouvertes.fr/">https://hal-ens-lyon.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ensta.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ensta.archives-ouvertes.fr/">https://hal-ensta.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ensta-bretagne.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ensta-bretagne.archives-ouvertes.fr/">https://hal-ensta-bretagne.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ephe.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ephe.archives-ouvertes.fr/">https://hal-ephe.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-esc-rennes.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-esc-rennes.archives-ouvertes.fr/">https://hal-esc-rennes.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-espci.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-espci.archives-ouvertes.fr/">https://hal-espci.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-essec.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-essec.archives-ouvertes.fr/">https://hal-essec.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-genes.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-genes.archives-ouvertes.fr/">https://hal-genes.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-hcl.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-hcl.archives-ouvertes.fr/">https://hal-hcl.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-hec.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-hec.archives-ouvertes.fr/">https://hal-hec.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-hprints.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-hprints.archives-ouvertes.fr/">https://hal-hprints.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-icp.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-icp.archives-ouvertes.fr/">https://hal-icp.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ifp.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ifp.archives-ouvertes.fr/">https://hal-ifp.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-inalco.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-inalco.archives-ouvertes.fr/">https://hal-inalco.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal-ineris.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal-ineris.ccsd.cnrs.fr/">http://hal-ineris.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://hal-inrap.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-inrap.archives-ouvertes.fr/">https://hal-inrap.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-insa-rennes.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-insa-rennes.archives-ouvertes.fr/">https://hal-insa-rennes.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-institut-mines-telecom.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-institut-mines-telecom.archives-ouvertes.fr/">https://hal-institut-mines-telecom.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-insu.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-insu.archives-ouvertes.fr/">https://hal-insu.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-iogs.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-iogs.archives-ouvertes.fr/">https://hal-iogs.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-irsn.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-irsn.archives-ouvertes.fr/">https://hal-irsn.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-lara.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-lara.archives-ouvertes.fr/">https://hal-lara.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal-lirmm.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal-lirmm.ccsd.cnrs.fr/">http://hal-lirmm.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://hal-meteofrance.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-meteofrance.archives-ouvertes.fr/">https://hal-meteofrance.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-mines-albi.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-mines-albi.archives-ouvertes.fr/">https://hal-mines-albi.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-mines-nantes.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-mines-nantes.archives-ouvertes.fr/">https://hal-mines-nantes.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-mines-paristech.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-mines-paristech.archives-ouvertes.fr/">https://hal-mines-paristech.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-mnhn.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-mnhn.archives-ouvertes.fr/">https://hal-mnhn.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-neoma-bs.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-neoma-bs.archives-ouvertes.fr/">https://hal-neoma-bs.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-normandie-univ.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-normandie-univ.archives-ouvertes.fr/">https://hal-normandie-univ.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal-obspm.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal-obspm.ccsd.cnrs.fr/">http://hal-obspm.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://hal-onera.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-onera.archives-ouvertes.fr/">https://hal-onera.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-paris1.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-paris1.archives-ouvertes.fr/">https://hal-paris1.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-pasteur.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-pasteur.archives-ouvertes.fr/">https://hal-pasteur.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-pjse.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-pjse.archives-ouvertes.fr/">https://hal-pjse.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-polytechnique.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-polytechnique.archives-ouvertes.fr/">https://hal-polytechnique.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-pse.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-pse.archives-ouvertes.fr/">https://hal-pse.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-rbs.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-rbs.archives-ouvertes.fr/">https://hal-rbs.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-riip.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-riip.archives-ouvertes.fr/">https://hal-riip.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-sciencespo.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-sciencespo.archives-ouvertes.fr/">https://hal-sciencespo.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-sde.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-sde.archives-ouvertes.fr/">https://hal-sde.archives-ouvertes.fr/</option>
<?php if ($racine == "http://hal-sfo.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://hal-sfo.ccsd.cnrs.fr/">http://hal-sfo.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://halshs.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://halshs.archives-ouvertes.fr/">https://halshs.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ssa.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ssa.archives-ouvertes.fr/">https://hal-ssa.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-supelec.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-supelec.archives-ouvertes.fr/">https://hal-supelec.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-uag.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-uag.archives-ouvertes.fr/">https://hal-uag.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-ujm.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-ujm.archives-ouvertes.fr/">https://hal-ujm.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-unice.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-unice.archives-ouvertes.fr/">https://hal-unice.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-unilim.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-unilim.archives-ouvertes.fr/">https://hal-unilim.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-artois.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-artois.archives-ouvertes.fr/">https://hal-univ-artois.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-avignon.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-avignon.archives-ouvertes.fr/">https://hal-univ-avignon.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-bourgogne.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-bourgogne.archives-ouvertes.fr/">https://hal-univ-bourgogne.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-corse.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-corse.archives-ouvertes.fr/">https://hal-univ-corse.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-diderot.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-diderot.archives-ouvertes.fr/">https://hal-univ-diderot.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-fcomte.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-fcomte.archives-ouvertes.fr/">https://hal-univ-fcomte.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-lorraine.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-lorraine.archives-ouvertes.fr/">https://hal-univ-lorraine.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-lyon3.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-lyon3.archives-ouvertes.fr/">https://hal-univ-lyon3.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-orleans.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-orleans.archives-ouvertes.fr/">https://hal-univ-orleans.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-paris13.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-paris13.archives-ouvertes.fr/">https://hal-univ-paris13.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-paris3.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-paris3.archives-ouvertes.fr/">https://hal-univ-paris3.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-paris8.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-paris8.archives-ouvertes.fr/">https://hal-univ-paris8.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-paris-dauphine.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-paris-dauphine.archives-ouvertes.fr/">https://hal-univ-paris-dauphine.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-perp.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-perp.archives-ouvertes.fr/">https://hal-univ-perp.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-rennes1.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-rennes1.archives-ouvertes.fr/">https://hal-univ-rennes1.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-tln.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-tln.archives-ouvertes.fr/">https://hal-univ-tln.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-tlse2.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-tlse2.archives-ouvertes.fr/">https://hal-univ-tlse2.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-tlse3.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-tlse3.archives-ouvertes.fr/">https://hal-univ-tlse3.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-tours.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-tours.archives-ouvertes.fr/">https://hal-univ-tours.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-univ-ubs.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-univ-ubs.archives-ouvertes.fr/">https://hal-univ-ubs.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-upec-upem.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-upec-upem.archives-ouvertes.fr/">https://hal-upec-upem.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-usj.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-usj.archives-ouvertes.fr/">https://hal-usj.archives-ouvertes.fr/</option>
<?php if ($racine == "https://hal-uvsq.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://hal-uvsq.archives-ouvertes.fr/">https://hal-uvsq.archives-ouvertes.fr/</option>
<?php if ($racine == "http://jeannicod.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://jeannicod.ccsd.cnrs.fr/">http://jeannicod.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://medihal.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://medihal.archives-ouvertes.fr/">https://medihal.archives-ouvertes.fr/</option>
<?php if ($racine == "http://memsic.ccsd.cnrs.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://memsic.ccsd.cnrs.fr/">http://memsic.ccsd.cnrs.fr/</option>
<?php if ($racine == "https://pastel.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://pastel.archives-ouvertes.fr/">https://pastel.archives-ouvertes.fr/</option>
<?php if ($racine == "https://tel.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://tel.archives-ouvertes.fr/">https://tel.archives-ouvertes.fr/</option>
<?php if ($racine == "https://telearn.archives-ouvertes.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="https://telearn.archives-ouvertes.fr/">https://telearn.archives-ouvertes.fr/</option>
<?php if ($racine == "http://www.hal.inserm.fr/") {$txt = "selected";}else{$txt = "";}?>
<option <?php echo $txt;?> value="http://www.hal.inserm.fr/">http://www.hal.inserm.fr/</option>
</select>
</div>
<br>
<?php
if (isset($typreva) && $typreva == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typreva) && $typreva == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Rang revues HCERES (Economie-Gestion) :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typreva" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typreva" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($typrevh) && $typrevh == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typrevh) && $typrevh == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Rang revues HCERES (Toutes disciplines) :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typrevh" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typrevh" value="inv" <?php echo $inv;?>>invisible
<?php
if (isset($dscp) && $dscp == "gau") {$gau = "selected";}else{$gau = "";}
if (isset($dscp) && $dscp == "sp") {$sp = "selected";}else{$sp = "";}
if (isset($dscp) && $dscp == "tsr") {$tsr = "selected";}else{$tsr = "";}
if (isset($dscp) && $dscp == "hps") {$hps = "selected";}else{$hps = "";}
if (isset($dscp) && $dscp == "d") {$d = "selected";}else{$d = "";}
if (isset($dscp) && $dscp == "ae") {$ae = "selected";}else{$ae = "";}
if (isset($dscp) && $dscp == "sd") {$sd = "selected";}else{$sd = "";}
if (isset($dscp) && $dscp == "a") {$a = "selected";}else{$a = "";}
if (isset($dscp) && $dscp == "p") {$p = "selected";}else{$p = "";}
if (isset($dscp) && $dscp == "se") {$se = "selected";}else{$se = "";}
if (isset($dscp) && $dscp == "sic") {$sic = "selected";}else{$sic = "";}
if (isset($dscp) && $dscp == "staps") {$staps = "selected";}else{$staps = "";}
if (isset($dscp) && $dscp == "g") {$g = "selected";}else{$g = "";}
if (isset($dscp) && $dscp == "e") {$e = "selected";}else{$e = "";}
if (isset($dscp) && $dscp == "arc") {$arc = "selected";}else{$arc = "";}
if (isset($dscp) && $dscp == "h") {$h = "selected";}else{$h = "";}
if (isset($dscp) && $dscp == "pee") {$pee = "selected";}else{$pee = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Discipline :
<select name="dscp">
<option value='gau' <?php echo $gau;?>>Géographie, Aménagement, Urbanisme (2013)</option>
<option value='sp' <?php echo $sp;?>>Science Politique (2011)</option>
<option value='tsr' <?php echo $tsr;?>>Théologie et Sciences religieuses (2012)</option>
<option value='hps' <?php echo $hps;?>>Histoire et Philosophie des Sciences (2012)</option>
<option value='d' <?php echo $d;?>>Droit (2010)</option>
<option value='ae' <?php echo $ae;?>>Anthropologie, Ethnologie (2012)</option>
<option value='sd' <?php echo $sd;?>>Sociologie, Démographie (2013)</option>
<option value='a' <?php echo $a;?>>Arts (2014)</option>
<option value='p' <?php echo $p;?>>Philosophie (2013)</option>
<option value='se' <?php echo $se;?>>Sciences de l'Education (2014)</option>
<option value='sic' <?php echo $sic;?>>SIC (2013)</option>
<option value='staps' <?php echo $staps;?>>STAPS (2012)</option>
<option value='g' <?php echo $g;?>>Gestion (2016)</option>
<option value='e' <?php echo $e;?>>Economie (2015)</option>
<option value='arc' <?php echo $arc;?>>Histoire, Histoire de l'Art, Archéologie (2009)</option>
<option value='h' <?php echo $h;?>>Histoire (2012)</option>
<option value='pee' <?php echo $pee;?>>Psychologie – Ethologie – Ergonomie (2011)</option>
</select>
<br>
<?php
if (isset($typrevc) && $typrevc == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typrevc) && $typrevc == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Rang revues CNRS (Economie-Gestion) :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typrevc" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typrevc" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($typif) && $typif == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typif) && $typif == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; IF des revues <i>(il peut être nécessaire de lancer <a target="_blank" href="./ExtractionHAL-IF.php">la procédure d'extraction</a> à partir de votre liste CSV réalisée selon ce <a href="./modele-JCR.csv">modèle</a>)</i> :<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typif" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typif" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($prefeq) && $prefeq == "oui") {$prefo = "checked";}else{$prefo = "";}
if (isset($prefeq) && $prefeq == "non" || !isset($team)) {$prefn = "checked";}else{$prefn = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Afficher le préfixe AERES :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="prefeq" value="oui" <?php echo $prefo;?>>oui
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="prefeq" value="non" <?php echo $prefn;?>>non
<br>
<?php
if (isset($typavsa) && $typavsa == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typavsa) && $typavsa == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Information <i>(acte)/(sans acte)</i> pour les communications et posters :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typavsa" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typavsa" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($delim) && $delim == ",") {$virg = "selected";}else{$virg = "";}
if (isset($delim) && $delim == ";") {$pvir = "selected";}else{$pvir = "";}
if (isset($delim) && $delim == "£") {$poun = "selected";}else{$poun = "";}
if (isset($delim) && $delim == "§") {$para = "selected";}else{$para = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Délimiteur export CSV :
<select name="delim">
<option value=';' <?php echo $pvir;?>>Point-virgule</option>
<option value='£' <?php echo $poun;?>>Symbole pound (£)</option>
<option value='§' <?php echo $para;?>>Symbole paragraphe (§)</option>
</select>
<br><br>
<?php
if (isset($typeqp) && $typeqp == "oui") {$eqpo = "checked";}else{$eqpo = "";}
if (isset($typeqp) && $typeqp == "non" || !isset($team)) {$eqpn = "checked";}else{$eqpn = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Numérotation/codification par équipe :
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typeqp" value="oui" onClick="affich_form();" <?php echo $eqpo;?>>oui
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typeqp" value="non" onClick="cacher_form();" <?php echo $eqpn;?>>non
<div id="deteqp">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Nombre d'équipes :
<?php
if (!isset($nbeqp)) {$nbeqp = "";}
?>
<input type="text" name="nbeqp" id="nbeqpid" size="1" value="<?php echo $nbeqp;?>">
</div>
<div id="eqp">
<?php
if (isset($typcro) && $typcro == "non" || !isset($team)) {$cron = "checked";}else{$cron = "";}
if (isset($typcro) && $typcro == "oui") {$croo = "checked";}else{$croo = "";}
if (isset($typeqp) && $typeqp == "oui") {//Numérotation/codification par équipe
  if (isset($_POST["soumis"])) {
    for($i = 1; $i <= $nbeqp; $i++) {
      echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Nom HAL équipe '.$i.' : <input type="text" name="eqp'.$i.'" value = "'.strtoupper($_POST['eqp'.$i]).'" size="30"><br>');
    }
    echo('<br>');
    echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Limiter l\'affichage seulement aux publications croisées :');
    echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    echo('<input type="radio" name="typcro" value="non" '.$cron.'>non');
    echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    echo('<input type="radio" name="typcro" value="oui" '.$croo.'>oui');
  }
  if (isset($_GET["team"])) {
    for($i = 1; $i <= $nbeqp; $i++) {
      echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Nom HAL équipe '.$i.' : <input type="text" name="eqp'.$i.'" value = "'.$_GET['eqp'.$i].'" size="30"><br>');
    }
    echo('<br>');
    echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Limiter l\'affichage seulement aux publications croisées :');
    echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    echo('<input type="radio" name="typcro" value="non" '.$cron.'>non');
    echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    echo('<input type="radio" name="typcro" value="oui" '.$croo.'>oui');
  }
}
?>
</div><br>
</div>
</div>
<br><br>
<div style='width:100%;float: left;background-color:#fcecc9;border:1px solid #dddddd;padding: 3px;border-radius: 3px;margin-bottom: 10px;'>
<span style='color:#333333;' class='accordeon'><b>Options et styles de citations :</b></span>
<div class="panel">
<i>(sélection/désélection multiple pour la mise en page en maintenant la touche 'Ctrl' (PC) ou 'Pomme' (Mac) enfoncée)</i><br>
<b>Important, loi du tout ou rien :</b> si aucune option ci-dessous n'est choisie, ce sont les règles équivalentes ci-dessus qui seront appliquées. A l'inverse, si une option ci-dessous est choisie, il faut alors <u>obligatoirement</u> faire un choix pour toutes les autres possibilités et ce seront ces règles qui seront appliquées. Le style 'Majuscules' sera prioritaire au style 'Minuscules' si les deux sont sélectionnés.<br><br>
<?php
if (isset($stpdf) && $stpdf == "- -" || !isset($team)) {$st0 = "selected ";}else{$st0 = "";}
if (isset($stpdf) && $stpdf == "acs") {$st1 = "selected ";}else{$st1 = "";}
if (isset($stpdf) && $stpdf == "apa") {$st2 = "selected ";}else{$st2 = "";}
if (isset($stpdf) && $stpdf == "chi") {$st3 = "selected "; $typnom = "nomcomp1"; $nomcomp1 = "checked";}else{$st3 = "";}
if (isset($stpdf) && $stpdf == "har") {$st4 = "selected ";}else{$st4 = "";}
if (isset($stpdf) && $stpdf == "iee") {$st5 = "selected ";}else{$st5 = "";}
if (isset($stpdf) && $stpdf == "nlm") {$st6 = "selected ";}else{$st6 = "";}
if (isset($stpdf) && $stpdf == "nat") {$st7 = "selected ";}else{$st7 = "";}
if (isset($stpdf) && $stpdf == "mla") {$st8 = "selected "; $typlim = "oui"; $limaff = 1; $typnom = "nomcomp1"; $nomcomp1 = "checked";}else{$st8 = "";}
if (isset($stpdf) && $stpdf == "van") {$st9 = "selected ";}else{$st9 = "";}
if (isset($stpdf) && $stpdf == "zo1") {$st10 = "selected ";}else{$st10 = "";}
?>
<u>Styles prédéfinis :</u> <i>(l'adéquation avec le style demandé dépend des éléments qui ont été renseignés dans HAL)</i><br>
<select id="stpdf" size="1" name="stpdf" onChange="appst();mise_en_ordre('1');mise_en_ordre('2');majapercu();">
<option <?php echo $st0;?>value="- -">- -</option>
<option <?php echo $st1;?>value="acs">ACS - American Chemical Society</option>
<option <?php echo $st2;?>value="apa">APA - American Psychological Association, 6th ed.</option>
<option <?php echo $st3;?>value="chi">Chicago</option>
<option <?php echo $st4;?>value="har">Harvard</option>
<option <?php echo $st5;?>value="iee">IEEE</option>
<option <?php echo $st6;?>value="nlm">National Library of Medicine (NLM)</option>
<option <?php echo $st7;?>value="nat">Nature</option>
<option <?php echo $st8;?>value="mla">Modern Language Association (MLA), 8th ed.</option>
<option <?php echo $st9;?>value="van">Vancouver</option>
<option <?php echo $st10;?>value="zo1">Zotero1</option>
</select>
<br><br>
<u>Styles personnalisés :</u><br>
<center><table width="80%" style="font-size: 80%">
  <tr>
    <td colspan="15">
      Séparateur interne au groupe d'auteurs :
      <?php
      if (isset($spa) && $spa == "- -" || !isset($team)) {$spa_0 = "selected ";}else{$spa_0 = "";}
      if (isset($spa) && $spa == "vir") {$spa_1 = "selected ";}else{$spa_1 = "";}
      if (isset($spa) && $spa == "pvi") {$spa_2 = "selected ";}else{$spa_2 = "";}
      if (isset($spa) && $spa == "esp") {$spa_3 = "selected ";}else{$spa_3 = "";}
      if (isset($spa) && $spa == "tir") {$spa_4 = "selected ";}else{$spa_4 = "";}
      ?>
      <select id="spa" size="1" name="spa" onChange="mise_en_ordre('1'); majapercu();" style="font-size: 80%">
      <option <?php echo $spa_0;?>value="- -">- -</option>
      <option <?php echo $spa_1;?>value="vir">virg. + esp.</option>
      <option <?php echo $spa_2;?>value="pvi">p.virg. + esp.</option>
      <option <?php echo $spa_3;?>value="esp">espace</option>
      <option <?php echo $spa_4;?>value="tir">esp. + tiret + esp.</option>
      </select>
    </td>
  </tr>
  <tr>
    <td>Numérot.</td>
    <td>Groupe 1</td>
    <td>Sép. 1</td>
    <td>Groupe 2</td>
    <td>Sép. 2</td>
    <td>Groupe 3</td>
    <td>Sép. 3</td>
    <td>Groupe 4</td>
    <td>Sép. 4</td>
    <td>Groupe 5</td>
    <td>Sép. 5</td>
    <td>Groupe 6</td>
    <td>Sép. 6</td>
    <td>Groupe 7</td>
    <td>Sép. 7</td>
  </tr>
  <tr>
<?php
if (isset($nmo) && $nmo == "- -" || !isset($team)) {$nmo_0 = "selected ";}else{$nmo_0 = "";}
if (isset($nmo) && $nmo == "auc") {$nmo_1 = "selected ";}else{$nmo_1 = "";}
if (isset($nmo) && $nmo == "sim") {$nmo_2 = "selected ";}else{$nmo_2 = "";}
if (isset($nmo) && $nmo == "par") {$nmo_3 = "selected ";}else{$nmo_3 = "";}
if (isset($nmo) && $nmo == "cro") {$nmo_4 = "selected ";}else{$nmo_4 = "";}
?>
    <td width="100px">
      <select id="nmo" size="1" name="nmo" onChange="mise_en_ordre('1'); majapercu();" style="font-size: 80%">
      <option <?php echo $nmo_0;?>value="- -">- -</option>
      <option <?php echo $nmo_1;?>value="auc">Aucune</option>
      <option <?php echo $nmo_2;?>value="sim">x. + esp.</option>
      <option <?php echo $nmo_3;?>value="par">(x) + esp.</option>
      <option <?php echo $nmo_4;?>value="cro">[x] + esp.</option>
      </select>
    </td>
<?php
if (isset($gp1) && $gp1 == "- -" || !isset($team)) {$gp1_0 = "selected ";}else{$gp1_0 = "";}
if (isset($gp1) && $gp1 == "auteurs") {$gp1_1 = "selected ";}else{$gp1_1 = "";}
if (isset($gp1) && $gp1 == "année") {$gp1_2 = "selected ";}else{$gp1_2 = "";}
if (isset($gp1) && $gp1 == "titre") {$gp1_3 = "selected ";}else{$gp1_3 = "";}
if (isset($gp1) && $gp1 == "revue") {$gp1_4 = "selected ";}else{$gp1_4 = "";}
if (isset($gp1) && $gp1 == "volume") {$gp1_5 = "selected ";}else{$gp1_5 = "";}
if (isset($gp1) && $gp1 == "numéro") {$gp1_6 = "selected ";}else{$gp1_6 = "";}
if (isset($gp1) && $gp1 == "pages") {$gp1_7 = "selected ";}else{$gp1_7 = "";}
?>
    <td width="100px">
      <select id="gp1" size="1" name="gp1" onChange="mise_en_ordre('1'); majapercu();" style="font-size: 80%">
      <option <?php echo $gp1_0;?>value="- -">- -</option>
      <option <?php echo $gp1_1;?>value="auteurs">Auteurs</option>
      <option <?php echo $gp1_2;?>value="année">Année</option>
      <option <?php echo $gp1_3;?>value="titre">Titre</option>
      <option <?php echo $gp1_4;?>value="revue">Revue</option>
      <option <?php echo $gp1_5;?>value="volume">Volume</option>
      <option <?php echo $gp1_6;?>value="numéro">Numéro</option>
      <option <?php echo $gp1_7;?>value="pages">Pages</option>
      </select>
    </td>
<?php
if (isset($sep1) && $sep1 == "- -" || !isset($team)) {$sep1_0 = "selected ";}else{$sep1_0 = "";}
if (isset($sep1) && $sep1 == " ") {$sep1_1 = "selected ";}else{$sep1_1 = "";}
if (isset($sep1) && $sep1 == ", ") {$sep1_2 = "selected ";}else{$sep1_2 = "";}
if (isset($sep1) && $sep1 == ". ") {$sep1_3 = "selected ";}else{$sep1_3 = "";}
if (isset($sep1) && $sep1 == "; ") {$sep1_4 = "selected ";}else{$sep1_4 = "";}
if (isset($sep1) && $sep1 == " - ") {$sep1_5 = "selected ";}else{$sep1_5 = "";}
if (isset($sep1) && $sep1 == "auc") {$sep1_6 = "selected ";}else{$sep1_6 = "";}
if (isset($sep1) && $sep1 == ": ") {$sep1_7 = "selected ";}else{$sep1_7 = "";}
?>
    <td width="100px">
      <select id="sep1" size="1" name="sep1" onChange="majapercu();" style="font-size: 80%">
      <option <?php echo $sep1_0;?>value="- -" >- -</option>
      <option <?php echo $sep1_1;?>value=" ">_</option>
      <option <?php echo $sep1_2;?>value=", ">,_</option>
      <option <?php echo $sep1_3;?>value=". ">._</option>
      <option <?php echo $sep1_4;?>value="; ">;_</option>
      <option <?php echo $sep1_5;?>value=" - ">_-_</option>
      <option <?php echo $sep1_6;?>value="">auc</option>
      <option <?php echo $sep1_7;?>value=": ">:_</option>
      </select>
    </td>
<?php
if (isset($gp2) && $gp2 == "- -" || !isset($team)) {$gp2_0 = "selected ";}else{$gp2_0 = "";}
if (isset($gp2) && $gp2 == "auteurs") {$gp2_1 = "selected ";}else{$gp2_1 = "";}
if (isset($gp2) && $gp2 == "année") {$gp2_2 = "selected ";}else{$gp2_2 = "";}
if (isset($gp2) && $gp2 == "titre") {$gp2_3 = "selected ";}else{$gp2_3 = "";}
if (isset($gp2) && $gp2 == "revue") {$gp2_4 = "selected ";}else{$gp2_4 = "";}
if (isset($gp2) && $gp2 == "volume") {$gp2_5 = "selected ";}else{$gp2_5 = "";}
if (isset($gp2) && $gp2 == "numéro") {$gp2_6 = "selected ";}else{$gp2_6 = "";}
if (isset($gp2) && $gp2 == "pages") {$gp2_7 = "selected ";}else{$gp2_7 = "";}
?>
    <td width="100px">
      <select id="gp2" size="1" name="gp2" onChange="mise_en_ordre('2'); majapercu();" style="font-size: 80%">
      <option <?php echo $gp2_0;?>value="- -">- -</option>
      <option <?php echo $gp2_1;?>value="auteurs">Auteurs</option>
      <option <?php echo $gp2_2;?>value="année">Année</option>
      <option <?php echo $gp2_3;?>value="titre">Titre</option>
      <option <?php echo $gp2_4;?>value="revue">Revue</option>
      <option <?php echo $gp2_5;?>value="volume">Volume</option>
      <option <?php echo $gp2_6;?>value="numéro">Numéro</option>
      <option <?php echo $gp2_7;?>value="pages">Pages</option>
      </select>
    </td>
<?php
if (isset($sep2) && $sep2 == "- -" || !isset($team)) {$sep2_0 = "selected ";}else{$sep2_0 = "";}
if (isset($sep2) && $sep2 == " ") {$sep2_1 = "selected ";}else{$sep2_1 = "";}
if (isset($sep2) && $sep2 == ", ") {$sep2_2 = "selected ";}else{$sep2_2 = "";}
if (isset($sep2) && $sep2 == ". ") {$sep2_3 = "selected ";}else{$sep2_3 = "";}
if (isset($sep2) && $sep2 == "; ") {$sep2_4 = "selected ";}else{$sep2_4 = "";}
if (isset($sep2) && $sep2 == " - ") {$sep2_5 = "selected ";}else{$sep2_5 = "";}
if (isset($sep2) && $sep2 == "auc") {$sep2_6 = "selected ";}else{$sep2_6 = "";}
if (isset($sep2) && $sep2 == ": ") {$sep2_7 = "selected ";}else{$sep2_7 = "";}
?>
    <td width="100px">
      <select id="sep2" size="1" name="sep2" onChange="majapercu();" style="font-size: 80%">
      <option <?php echo $sep2_0;?>value="- -">- -</option>
      <option <?php echo $sep2_1;?>value=" ">_</option>
      <option <?php echo $sep2_2;?>value=", ">,_</option>
      <option <?php echo $sep2_3;?>value=". ">._</option>
      <option <?php echo $sep2_4;?>value="; ">;_</option>
      <option <?php echo $sep2_5;?>value=" - ">_-_</option>
      <option <?php echo $sep2_6;?>value="">auc</option>
      <option <?php echo $sep2_7;?>value=": ">:_</option>
      </select>
    </td>
<?php
if (isset($gp3) && $gp3 == "- -" || !isset($team)) {$gp3_0 = "selected ";}else{$gp3_0 = "";}
if (isset($gp3) && $gp3 == "auteurs") {$gp3_1 = "selected ";}else{$gp3_1 = "";}
if (isset($gp3) && $gp3 == "année") {$gp3_2 = "selected ";}else{$gp3_2 = "";}
if (isset($gp3) && $gp3 == "titre") {$gp3_3 = "selected ";}else{$gp3_3 = "";}
if (isset($gp3) && $gp3 == "revue") {$gp3_4 = "selected ";}else{$gp3_4 = "";}
if (isset($gp3) && $gp3 == "volume") {$gp3_5 = "selected ";}else{$gp3_5 = "";}
if (isset($gp3) && $gp3 == "numéro") {$gp3_6 = "selected ";}else{$gp3_6 = "";}
if (isset($gp3) && $gp3 == "pages") {$gp3_7 = "selected ";}else{$gp3_7 = "";}
?>
    <td width="100px">
      <select id="gp3" size="1" name="gp3" onChange="mise_en_ordre('3'); majapercu();" style="font-size: 80%">
      <option <?php echo $gp3_0;?>value="- -">- -</option>
      <option <?php echo $gp3_1;?>value="auteurs">Auteurs</option>
      <option <?php echo $gp3_2;?>value="année">Année</option>
      <option <?php echo $gp3_3;?>value="titre">Titre</option>
      <option <?php echo $gp3_4;?>value="revue">Revue</option>
      <option <?php echo $gp3_5;?>value="volume">Volume</option>
      <option <?php echo $gp3_6;?>value="numéro">Numéro</option>
      <option <?php echo $gp3_7;?>value="pages">Pages</option>
      </select>
    </td>
<?php
if (isset($sep3) && $sep3 == "- -" || !isset($team)) {$sep3_0 = "selected ";}else{$sep3_0 = "";}
if (isset($sep3) && $sep3 == " ") {$sep3_1 = "selected ";}else{$sep3_1 = "";}
if (isset($sep3) && $sep3 == ", ") {$sep3_2 = "selected ";}else{$sep3_2 = "";}
if (isset($sep3) && $sep3 == ". ") {$sep3_3 = "selected ";}else{$sep3_3 = "";}
if (isset($sep3) && $sep3 == "; ") {$sep3_4 = "selected ";}else{$sep3_4 = "";}
if (isset($sep3) && $sep3 == " - ") {$sep3_5 = "selected ";}else{$sep3_5 = "";}
if (isset($sep3) && $sep3 == "auc") {$sep3_6 = "selected ";}else{$sep3_6 = "";}
if (isset($sep3) && $sep3 == ": ") {$sep3_7 = "selected ";}else{$sep3_7 = "";}
?>
    <td width="100px">
      <select id="sep3" size="1" name="sep3" onChange="majapercu();" style="font-size: 80%">
      <option <?php echo $sep3_0;?>value="- -">- -</option>
      <option <?php echo $sep3_1;?>value=" ">_</option>
      <option <?php echo $sep3_2;?>value=", ">,_</option>
      <option <?php echo $sep3_3;?>value=". ">._</option>
      <option <?php echo $sep3_4;?>value="; ">;_</option>
      <option <?php echo $sep3_5;?>value=" - ">_-_</option>
      <option <?php echo $sep3_6;?>value="">auc</option>
      <option <?php echo $sep3_7;?>value=": ">:_</option>
      </select>
    </td>
<?php
if (isset($gp4) && $gp4 == "- -" || !isset($team)) {$gp4_0 = "selected ";}else{$gp4_0 = "";}
if (isset($gp4) && $gp4 == "auteurs") {$gp4_1 = "selected ";}else{$gp4_1 = "";}
if (isset($gp4) && $gp4 == "année") {$gp4_2 = "selected ";}else{$gp4_2 = "";}
if (isset($gp4) && $gp4 == "titre") {$gp4_3 = "selected ";}else{$gp4_3 = "";}
if (isset($gp4) && $gp4 == "revue") {$gp4_4 = "selected ";}else{$gp4_4 = "";}
if (isset($gp4) && $gp4 == "volume") {$gp4_5 = "selected ";}else{$gp4_5 = "";}
if (isset($gp4) && $gp4 == "numéro") {$gp4_6 = "selected ";}else{$gp4_6 = "";}
if (isset($gp4) && $gp4 == "pages") {$gp4_7 = "selected ";}else{$gp4_7 = "";}
?>
    <td width="100px">
      <select id="gp4" size="1" name="gp4" onChange="mise_en_ordre('4'); majapercu();" style="font-size: 80%">
      <option <?php echo $gp4_0;?>value="- -">- -</option>
      <option <?php echo $gp4_1;?>value="auteurs">Auteurs</option>
      <option <?php echo $gp4_2;?>value="année">Année</option>
      <option <?php echo $gp4_3;?>value="titre">Titre</option>
      <option <?php echo $gp4_4;?>value="revue">Revue</option>
      <option <?php echo $gp4_5;?>value="volume">Volume</option>
      <option <?php echo $gp4_6;?>value="numéro">Numéro</option>
      <option <?php echo $gp4_7;?>value="pages">Pages</option>
      </select>
    </td>
<?php
if (isset($sep4) && $sep4 == "- -" || !isset($team)) {$sep4_0 = "selected ";}else{$sep4_0 = "";}
if (isset($sep4) && $sep4 == " ") {$sep4_1 = "selected ";}else{$sep4_1 = "";}
if (isset($sep4) && $sep4 == ", ") {$sep4_2 = "selected ";}else{$sep4_2 = "";}
if (isset($sep4) && $sep4 == ". ") {$sep4_3 = "selected ";}else{$sep4_3 = "";}
if (isset($sep4) && $sep4 == "; ") {$sep4_4 = "selected ";}else{$sep4_4 = "";}
if (isset($sep4) && $sep4 == " - ") {$sep4_5 = "selected ";}else{$sep4_5 = "";}
if (isset($sep4) && $sep4 == "auc") {$sep4_6 = "selected ";}else{$sep4_6 = "";}
if (isset($sep4) && $sep4 == ": ") {$sep4_7 = "selected ";}else{$sep4_7 = "";}
?>
    <td width="100px">
      <select id="sep4" size="1" name="sep4" onChange="majapercu();" style="font-size: 80%">
      <option <?php echo $sep4_0;?>value="- -">- -</option>
      <option <?php echo $sep4_1;?>value=" ">_</option>
      <option <?php echo $sep4_2;?>value=", ">,_</option>
      <option <?php echo $sep4_3;?>value=". ">._</option>
      <option <?php echo $sep4_4;?>value="; ">;_</option>
      <option <?php echo $sep4_5;?>value=" - ">_-_</option>
      <option <?php echo $sep4_6;?>value="">auc</option>
      <option <?php echo $sep4_7;?>value=": ">:_</option>
      </select>
    </td>
<?php
if (isset($gp5) && $gp5 == "- -" || !isset($team)) {$gp5_0 = "selected ";}else{$gp5_0 = "";}
if (isset($gp5) && $gp5 == "auteurs") {$gp5_1 = "selected ";}else{$gp5_1 = "";}
if (isset($gp5) && $gp5 == "année") {$gp5_2 = "selected ";}else{$gp5_2 = "";}
if (isset($gp5) && $gp5 == "titre") {$gp5_3 = "selected ";}else{$gp5_3 = "";}
if (isset($gp5) && $gp5 == "revue") {$gp5_4 = "selected ";}else{$gp5_4 = "";}
if (isset($gp5) && $gp5 == "volume") {$gp5_5 = "selected ";}else{$gp5_5 = "";}
if (isset($gp5) && $gp5 == "numéro") {$gp5_6 = "selected ";}else{$gp5_6 = "";}
if (isset($gp5) && $gp5 == "pages") {$gp5_7 = "selected ";}else{$gp5_7 = "";}
?>
    <td width="100px">
      <select id="gp5" size="1" name="gp5" onChange="mise_en_ordre('5'); majapercu();" style="font-size: 80%">
      <option <?php echo $gp5_0;?>value="- -">- -</option>
      <option <?php echo $gp5_1;?>value="auteurs">Auteurs</option>
      <option <?php echo $gp5_2;?>value="année">Année</option>
      <option <?php echo $gp5_3;?>value="titre">Titre</option>
      <option <?php echo $gp5_4;?>value="revue">Revue</option>
      <option <?php echo $gp5_5;?>value="volume">Volume</option>
      <option <?php echo $gp5_6;?>value="numéro">Numéro</option>
      <option <?php echo $gp5_7;?>value="pages">Pages</option>
      </select>
    </td>
    <?php
    if (isset($sep5) && $sep5 == "- -" || !isset($team)) {$sep5_0 = "selected ";}else{$sep5_0 = "";}
    if (isset($sep5) && $sep5 == " ") {$sep5_1 = "selected ";}else{$sep5_1 = "";}
    if (isset($sep5) && $sep5 == ", ") {$sep5_2 = "selected ";}else{$sep5_2 = "";}
    if (isset($sep5) && $sep5 == ". ") {$sep5_3 = "selected ";}else{$sep5_3 = "";}
    if (isset($sep5) && $sep5 == "; ") {$sep5_4 = "selected ";}else{$sep5_4 = "";}
    if (isset($sep5) && $sep5 == " - ") {$sep5_5 = "selected ";}else{$sep5_5 = "";}
    if (isset($sep5) && $sep5 == "auc") {$sep5_6 = "selected ";}else{$sep5_6 = "";}
    if (isset($sep5) && $sep5 == ": ") {$sep5_7 = "selected ";}else{$sep5_7 = "";}
    ?>
    <td>
      <select id="sep5" size="1" name="sep5"  onChange="majapercu();" style="font-size: 80%">
      <option <?php echo $sep5_0;?>value="- -">- -</option>
      <option <?php echo $sep5_1;?>value=" ">_</option>
      <option <?php echo $sep5_2;?>value=", ">,_</option>
      <option <?php echo $sep5_3;?>value=". ">._</option>
      <option <?php echo $sep5_4;?>value="; ">;_</option>
      <option <?php echo $sep5_5;?>value=" - ">_-_</option>
      <option <?php echo $sep5_6;?>value="">auc</option>
      <option <?php echo $sep5_7;?>value=": ">:_</option>
      </select>
    </td>
    <?php
    if (isset($gp6) && $gp6 == "- -" || !isset($team)) {$gp6_0 = "selected ";}else{$gp6_0 = "";}
    if (isset($gp6) && $gp6 == "auteurs") {$gp6_1 = "selected ";}else{$gp6_1 = "";}
    if (isset($gp6) && $gp6 == "année") {$gp6_2 = "selected ";}else{$gp6_2 = "";}
    if (isset($gp6) && $gp6 == "titre") {$gp6_3 = "selected ";}else{$gp6_3 = "";}
    if (isset($gp6) && $gp6 == "revue") {$gp6_4 = "selected ";}else{$gp6_4 = "";}
    if (isset($gp6) && $gp6 == "volume") {$gp6_5 = "selected ";}else{$gp6_5 = "";}
    if (isset($gp6) && $gp6 == "numéro") {$gp6_6 = "selected ";}else{$gp6_6 = "";}
    if (isset($gp6) && $gp6 == "pages") {$gp6_7 = "selected ";}else{$gp6_7 = "";}
    ?>
        <td width="100px">
          <select id="gp6" size="1" name="gp6" onChange="mise_en_ordre('6'); majapercu();" style="font-size: 80%">
          <option <?php echo $gp6_0;?>value="- -">- -</option>
          <option <?php echo $gp6_1;?>value="auteurs">Auteurs</option>
          <option <?php echo $gp6_2;?>value="année">Année</option>
          <option <?php echo $gp6_3;?>value="titre">Titre</option>
          <option <?php echo $gp6_4;?>value="revue">Revue</option>
          <option <?php echo $gp6_5;?>value="volume">Volume</option>
          <option <?php echo $gp6_6;?>value="numéro">Numéro</option>
          <option <?php echo $gp6_7;?>value="pages">Pages</option>
          </select>
        </td>
    <?php
    if (isset($sep6) && $sep6 == "- -" || !isset($team)) {$sep6_0 = "selected ";}else{$sep6_0 = "";}
    if (isset($sep6) && $sep6 == " ") {$sep6_1 = "selected ";}else{$sep6_1 = "";}
    if (isset($sep6) && $sep6 == ", ") {$sep6_2 = "selected ";}else{$sep6_2 = "";}
    if (isset($sep6) && $sep6 == ". ") {$sep6_3 = "selected ";}else{$sep6_3 = "";}
    if (isset($sep6) && $sep6 == "; ") {$sep6_4 = "selected ";}else{$sep6_4 = "";}
    if (isset($sep6) && $sep6 == " - ") {$sep6_5 = "selected ";}else{$sep6_5 = "";}
    if (isset($sep6) && $sep6 == "auc") {$sep6_6 = "selected ";}else{$sep6_6 = "";}
    if (isset($sep6) && $sep6 == ": ") {$sep6_7 = "selected ";}else{$sep6_7 = "";}
    ?>
        <td>
          <select id="sep6" size="1" name="sep6"  onChange="majapercu();" style="font-size: 80%">
          <option <?php echo $sep6_0;?>value="- -">- -</option>
          <option <?php echo $sep6_1;?>value=" ">_</option>
          <option <?php echo $sep6_2;?>value=", ">,_</option>
          <option <?php echo $sep6_3;?>value=". ">._</option>
          <option <?php echo $sep6_4;?>value="; ">;_</option>
          <option <?php echo $sep6_5;?>value=" - ">_-_</option>
          <option <?php echo $sep6_6;?>value="">auc</option>
          <option <?php echo $sep6_7;?>value=": ">:_</option>
          </select>
        </td>
    <?php
    if (isset($gp7) && $gp7 == "- -" || !isset($team)) {$gp7_0 = "selected ";}else{$gp7_0 = "";}
    if (isset($gp7) && $gp7 == "auteurs") {$gp7_1 = "selected ";}else{$gp7_1 = "";}
    if (isset($gp7) && $gp7 == "année") {$gp7_2 = "selected ";}else{$gp7_2 = "";}
    if (isset($gp7) && $gp7 == "titre") {$gp7_3 = "selected ";}else{$gp7_3 = "";}
    if (isset($gp7) && $gp7 == "revue") {$gp7_4 = "selected ";}else{$gp7_4 = "";}
    if (isset($gp7) && $gp7 == "volume") {$gp7_5 = "selected ";}else{$gp7_5 = "";}
    if (isset($gp7) && $gp7 == "numéro") {$gp7_6 = "selected ";}else{$gp7_6 = "";}
    if (isset($gp7) && $gp7 == "pages") {$gp7_7 = "selected ";}else{$gp7_7 = "";}
    ?>
        <td width="100px">
          <select id="gp7" size="1" name="gp7" onChange="mise_en_ordre('7'); majapercu();" style="font-size: 80%">
          <option <?php echo $gp7_0;?>value="- -">- -</option>
          <option <?php echo $gp7_1;?>value="auteurs">Auteurs</option>
          <option <?php echo $gp7_2;?>value="année">Année</option>
          <option <?php echo $gp7_3;?>value="titre">Titre</option>
          <option <?php echo $gp7_4;?>value="revue">Revue</option>
          <option <?php echo $gp7_5;?>value="volume">Volume</option>
          <option <?php echo $gp7_6;?>value="numéro">Numéro</option>
          <option <?php echo $gp7_7;?>value="pages">Pages</option>
          </select>
        </td>
    <?php
    if (isset($sep7) && $sep7 == "- -" || !isset($team)) {$sep7_0 = "selected ";}else{$sep7_0 = "";}
    if (isset($sep7) && $sep7 == " ") {$sep7_1 = "selected ";}else{$sep7_1 = "";}
    if (isset($sep7) && $sep7 == ", ") {$sep7_2 = "selected ";}else{$sep7_2 = "";}
    if (isset($sep7) && $sep7 == ". ") {$sep7_3 = "selected ";}else{$sep7_3 = "";}
    if (isset($sep7) && $sep7 == "; ") {$sep7_4 = "selected ";}else{$sep7_4 = "";}
    if (isset($sep7) && $sep7 == " - ") {$sep7_5 = "selected ";}else{$sep7_5 = "";}
    if (isset($sep7) && $sep7 == "auc") {$sep7_6 = "selected ";}else{$sep7_6 = "";}
    if (isset($sep7) && $sep7 == ": ") {$sep7_7 = "selected ";}else{$sep7_7 = "";}
    ?>
        <td>
          <select id="sep7" size="1" name="sep7"  onChange="majapercu();" style="font-size: 80%">
          <option <?php echo $sep7_0;?>value="- -">- -</option>
          <option <?php echo $sep7_1;?>value=" ">_</option>
          <option <?php echo $sep7_2;?>value=", ">,_</option>
          <option <?php echo $sep7_3;?>value=". ">._</option>
          <option <?php echo $sep7_4;?>value="; ">;_</option>
          <option <?php echo $sep7_5;?>value=" - ">_-_</option>
          <option <?php echo $sep7_6;?>value="">auc</option>
          <option <?php echo $sep7_7;?>value=": ">:_</option>
          </select>
        </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
<?php
if (isset($choix_mp1) && strpos($choix_mp1, "~- -~") !== false || !isset($team)) {$mp1v = "selected ";}else{$mp1v = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~norm~") !== false) {$mp1n = "selected ";}else{$mp1n = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~gras~") !== false) {$mp1g = "selected ";}else{$mp1g = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~soul~") !== false) {$mp1s = "selected ";}else{$mp1s = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~ital~") !== false) {$mp1i = "selected ";}else{$mp1i = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~epar~") !== false) {$mp1e = "selected ";}else{$mp1e = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~ecro~") !== false) {$mp1c = "selected ";}else{$mp1c = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~egui~") !== false) {$mp1u = "selected ";}else{$mp1u = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~emin~") !== false) {$mp1m = "selected ";}else{$mp1m = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~emaj~") !== false) {$mp1a = "selected ";}else{$mp1a = "";}
if (isset($choix_mp1) && strpos($choix_mp1, "~effa~") !== false) {$mp1f = "selected ";}else{$mp1f = "";}
?>
    <td>
      <select id="mp1" size="11" name="mp1[]" multiple style="font-size: 80%">
      <option <?php echo $mp1v;?>value="- -" onClick="majapercu();">- -</option>
      <option <?php echo $mp1n;?>value="norm" onClick="majapercu();">Normal</option>
      <option <?php echo $mp1g;?>value="gras" onClick="majapercu();">Gras</option>
      <option <?php echo $mp1s;?>value="soul" onClick="majapercu();">Souligné</option>
      <option <?php echo $mp1i;?>value="ital" onClick="majapercu();">Italique</option>
      <option <?php echo $mp1e;?>value="epar" onClick="majapercu();">Entre ( )</option>
      <option <?php echo $mp1c;?>value="ecro" onClick="majapercu();">Entre [ ]</option>
      <option <?php echo $mp1u;?>value="egui" onClick="majapercu();">Entre " "</option>
      <option <?php echo $mp1m;?>value="emin" onClick="majapercu();">Minuscules</option>
      <option <?php echo $mp1a;?>value="emaj" onClick="majapercu();">Majuscules</option>
      <option <?php echo $mp1f;?>value="effa" onClick="majapercu();">Effacé</option>
      </select>
    </td>
    <td>&nbsp;</td>
<?php
if (isset($choix_mp2) && strpos($choix_mp2, "~- -~") !== false || !isset($team)) {$mp2v = "selected ";}else{$mp2v = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~norm~") !== false) {$mp2n = "selected ";}else{$mp2n = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~gras~") !== false) {$mp2g = "selected ";}else{$mp2g = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~soul~") !== false) {$mp2s = "selected ";}else{$mp2s = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~ital~") !== false) {$mp2i = "selected ";}else{$mp2i = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~epar~") !== false) {$mp2e = "selected ";}else{$mp2e = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~ecro~") !== false) {$mp2c = "selected ";}else{$mp2c = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~egui~") !== false) {$mp2u = "selected ";}else{$mp2u = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~emin~") !== false) {$mp2m = "selected ";}else{$mp2m = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~emaj~") !== false) {$mp2a = "selected ";}else{$mp2a = "";}
if (isset($choix_mp2) && strpos($choix_mp2, "~effa~") !== false) {$mp2f = "selected ";}else{$mp2f = "";}
?>
    <td>
      <select id="mp2" size="11" name="mp2[]" multiple style="font-size: 80%">
      <option <?php echo $mp2v;?>value="- -" onClick="majapercu();">- -</option>
      <option <?php echo $mp2n;?>value="norm" onClick="majapercu();">Normal</option>
      <option <?php echo $mp2g;?>value="gras" onClick="majapercu();">Gras</option>
      <option <?php echo $mp2s;?>value="soul" onClick="majapercu();">Souligné</option>
      <option <?php echo $mp2i;?>value="ital" onClick="majapercu();">Italique</option>
      <option <?php echo $mp2e;?>value="epar" onClick="majapercu();">Entre ( )</option>
      <option <?php echo $mp2c;?>value="ecro" onClick="majapercu();">Entre [ ]</option>
      <option <?php echo $mp2u;?>value="egui" onClick="majapercu();">Entre " "</option>
      <option <?php echo $mp2m;?>value="emin" onClick="majapercu();">Minuscules</option>
      <option <?php echo $mp2a;?>value="emaj" onClick="majapercu();">Majuscules</option>
      <option <?php echo $mp2f;?>value="effa" onClick="majapercu();">Effacé</option>
      </select>
    </td>
    <td>&nbsp;</td>
<?php
if (isset($choix_mp3) && strpos($choix_mp3, "~- -~") !== false || !isset($team)) {$mp3v = "selected ";}else{$mp3v = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~norm~") !== false) {$mp3n = "selected ";}else{$mp3n = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~gras~") !== false) {$mp3g = "selected ";}else{$mp3g = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~soul~") !== false) {$mp3s = "selected ";}else{$mp3s = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~ital~") !== false) {$mp3i = "selected ";}else{$mp3i = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~epar~") !== false) {$mp3e = "selected ";}else{$mp3e = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~ecro~") !== false) {$mp3c = "selected ";}else{$mp3c = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~egui~") !== false) {$mp3u = "selected ";}else{$mp3u = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~emin~") !== false) {$mp3m = "selected ";}else{$mp3m = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~emaj~") !== false) {$mp3a = "selected ";}else{$mp3a = "";}
if (isset($choix_mp3) && strpos($choix_mp3, "~effa~") !== false) {$mp3f = "selected ";}else{$mp3f = "";}
?>
    <td>
      <select id="mp3" size="11" name="mp3[]" multiple style="font-size: 80%">
      <option <?php echo $mp3v;?>value="- -" onClick="majapercu();">- -</option>
      <option <?php echo $mp3n;?>value="norm" onClick="majapercu();">Normal</option>
      <option <?php echo $mp3g;?>value="gras" onClick="majapercu();">Gras</option>
      <option <?php echo $mp3s;?>value="soul" onClick="majapercu();">Souligné</option>
      <option <?php echo $mp3i;?>value="ital" onClick="majapercu();">Italique</option>
      <option <?php echo $mp3e;?>value="epar" onClick="majapercu();">Entre ( )</option>
      <option <?php echo $mp3c;?>value="ecro" onClick="majapercu();">Entre [ ]</option>
      <option <?php echo $mp3u;?>value="egui" onClick="majapercu();">Entre " "</option>
      <option <?php echo $mp3m;?>value="emin" onClick="majapercu();">Minuscules</option>
      <option <?php echo $mp3a;?>value="emaj" onClick="majapercu();">Majuscules</option>
      <option <?php echo $mp3f;?>value="effa" onClick="majapercu();">Effacé</option>
      </select>
    </td>
    <td>&nbsp;</td>
<?php
if (isset($choix_mp4) && strpos($choix_mp4, "~- -~") !== false || !isset($team)) {$mp4v = "selected ";}else{$mp4v = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~norm~") !== false) {$mp4n = "selected ";}else{$mp4n = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~gras~") !== false) {$mp4g = "selected ";}else{$mp4g = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~soul~") !== false) {$mp4s = "selected ";}else{$mp4s = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~ital~") !== false) {$mp4i = "selected ";}else{$mp4i = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~epar~") !== false) {$mp4e = "selected ";}else{$mp4e = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~ecro~") !== false) {$mp4c = "selected ";}else{$mp4c = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~egui~") !== false) {$mp4u = "selected ";}else{$mp4u = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~emin~") !== false) {$mp4m = "selected ";}else{$mp4m = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~emaj~") !== false) {$mp4a = "selected ";}else{$mp4a = "";}
if (isset($choix_mp4) && strpos($choix_mp4, "~effa~") !== false) {$mp4f = "selected ";}else{$mp4f = "";}
?>
    <td>
      <select id="mp4" size="11" name="mp4[]" multiple style="font-size: 80%">
      <option <?php echo $mp4v;?>value="- -" onClick="majapercu();">- -</option>
      <option <?php echo $mp4n;?>value="norm" onClick="majapercu();">Normal</option>
      <option <?php echo $mp4g;?>value="gras" onClick="majapercu();">Gras</option>
      <option <?php echo $mp4s;?>value="soul" onClick="majapercu();">Souligné</option>
      <option <?php echo $mp4i;?>value="ital" onClick="majapercu();">Italique</option>
      <option <?php echo $mp4e;?>value="epar" onClick="majapercu();">Entre ( )</option>
      <option <?php echo $mp4c;?>value="ecro" onClick="majapercu();">Entre [ ]</option>
      <option <?php echo $mp4u;?>value="egui" onClick="majapercu();">Entre " "</option>
      <option <?php echo $mp4m;?>value="emin" onClick="majapercu();">Minuscules</option>
      <option <?php echo $mp4a;?>value="emaj" onClick="majapercu();">Majuscules</option>
      <option <?php echo $mp4f;?>value="effa" onClick="majapercu();">Effacé</option>
      </select>
    </td>
    <td>&nbsp;</td>
<?php
if (isset($choix_mp5) && strpos($choix_mp5, "~- -~") !== false || !isset($team)) {$mp5v = "selected ";}else{$mp5v = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~norm~") !== false) {$mp5n = "selected ";}else{$mp5n = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~gras~") !== false) {$mp5g = "selected ";}else{$mp5g = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~soul~") !== false) {$mp5s = "selected ";}else{$mp5s = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~ital~") !== false) {$mp5i = "selected ";}else{$mp5i = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~epar~") !== false) {$mp5e = "selected ";}else{$mp5e = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~ecro~") !== false) {$mp5c = "selected ";}else{$mp5c = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~egui~") !== false) {$mp5u = "selected ";}else{$mp5u = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~emin~") !== false) {$mp5m = "selected ";}else{$mp5m = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~emaj~") !== false) {$mp5a = "selected ";}else{$mp5a = "";}
if (isset($choix_mp5) && strpos($choix_mp5, "~effa~") !== false) {$mp5f = "selected ";}else{$mp5f = "";}
?>
    <td>
      <select id="mp5" size="11" name="mp5[]" multiple style="font-size: 80%">
      <option <?php echo $mp5v;?>value="- -" onClick="majapercu();">- -</option>
      <option <?php echo $mp5n;?>value="norm" onClick="majapercu();">Normal</option>
      <option <?php echo $mp5g;?>value="gras" onClick="majapercu();">Gras</option>
      <option <?php echo $mp5s;?>value="soul" onClick="majapercu();">Souligné</option>
      <option <?php echo $mp5i;?>value="ital" onClick="majapercu();">Italique</option>
      <option <?php echo $mp5e;?>value="epar" onClick="majapercu();">Entre ( )</option>
      <option <?php echo $mp5c;?>value="ecro" onClick="majapercu();">Entre [ ]</option>
      <option <?php echo $mp5u;?>value="egui" onClick="majapercu();">Entre " "</option>
      <option <?php echo $mp5m;?>value="emin" onClick="majapercu();">Minuscules</option>
      <option <?php echo $mp5a;?>value="emaj" onClick="majapercu();">Majuscules</option>
      <option <?php echo $mp5f;?>value="effa" onClick="majapercu();">Effacé</option>
      </select>
    </td>
    <td>&nbsp;</td>
<?php
if (isset($choix_mp6) && strpos($choix_mp6, "~- -~") !== false || !isset($team)) {$mp6v = "selected ";}else{$mp6v = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~norm~") !== false) {$mp6n = "selected ";}else{$mp6n = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~gras~") !== false) {$mp6g = "selected ";}else{$mp6g = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~soul~") !== false) {$mp6s = "selected ";}else{$mp6s = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~ital~") !== false) {$mp6i = "selected ";}else{$mp6i = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~epar~") !== false) {$mp6e = "selected ";}else{$mp6e = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~ecro~") !== false) {$mp6c = "selected ";}else{$mp6c = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~egui~") !== false) {$mp6u = "selected ";}else{$mp6u = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~emin~") !== false) {$mp6m = "selected ";}else{$mp6m = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~emaj~") !== false) {$mp6a = "selected ";}else{$mp6a = "";}
if (isset($choix_mp6) && strpos($choix_mp6, "~effa~") !== false) {$mp6f = "selected ";}else{$mp6f = "";}
?>
    <td>
      <select id="mp6" size="11" name="mp6[]" multiple style="font-size: 80%">
      <option <?php echo $mp6v;?>value="- -" onClick="majapercu();">- -</option>
      <option <?php echo $mp6n;?>value="norm" onClick="majapercu();">Normal</option>
      <option <?php echo $mp6g;?>value="gras" onClick="majapercu();">Gras</option>
      <option <?php echo $mp6s;?>value="soul" onClick="majapercu();">Souligné</option>
      <option <?php echo $mp6i;?>value="ital" onClick="majapercu();">Italique</option>
      <option <?php echo $mp6e;?>value="epar" onClick="majapercu();">Entre ( )</option>
      <option <?php echo $mp6c;?>value="ecro" onClick="majapercu();">Entre [ ]</option>
      <option <?php echo $mp6u;?>value="egui" onClick="majapercu();">Entre " "</option>
      <option <?php echo $mp6m;?>value="emin" onClick="majapercu();">Minuscules</option>
      <option <?php echo $mp6a;?>value="emaj" onClick="majapercu();">Majuscules</option>
      <option <?php echo $mp6f;?>value="effa" onClick="majapercu();">Effacé</option>
      </select>
    </td>
    <td>&nbsp;</td>
<?php
if (isset($choix_mp7) && strpos($choix_mp7, "~- -~") !== false || !isset($team)) {$mp7v = "selected ";}else{$mp7v = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~norm~") !== false) {$mp7n = "selected ";}else{$mp7n = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~gras~") !== false) {$mp7g = "selected ";}else{$mp7g = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~soul~") !== false) {$mp7s = "selected ";}else{$mp7s = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~ital~") !== false) {$mp7i = "selected ";}else{$mp7i = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~epar~") !== false) {$mp7e = "selected ";}else{$mp7e = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~ecro~") !== false) {$mp7c = "selected ";}else{$mp7c = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~egui~") !== false) {$mp7u = "selected ";}else{$mp7u = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~emin~") !== false) {$mp7m = "selected ";}else{$mp7m = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~emaj~") !== false) {$mp7a = "selected ";}else{$mp7a = "";}
if (isset($choix_mp7) && strpos($choix_mp7, "~effa~") !== false) {$mp7f = "selected ";}else{$mp7f = "";}
?>
    <td>
      <select id="mp7" size="11" name="mp7[]" multiple style="font-size: 80%">
      <option <?php echo $mp7v;?>value="- -" onClick="majapercu();">- -</option>
      <option <?php echo $mp7n;?>value="norm" onClick="majapercu();">Normal</option>
      <option <?php echo $mp7g;?>value="gras" onClick="majapercu();">Gras</option>
      <option <?php echo $mp7s;?>value="soul" onClick="majapercu();">Souligné</option>
      <option <?php echo $mp7i;?>value="ital" onClick="majapercu();">Italique</option>
      <option <?php echo $mp7e;?>value="epar" onClick="majapercu();">Entre ( )</option>
      <option <?php echo $mp7c;?>value="ecro" onClick="majapercu();">Entre [ ]</option>
      <option <?php echo $mp7u;?>value="egui" onClick="majapercu();">Entre " "</option>
      <option <?php echo $mp7m;?>value="emin" onClick="majapercu();">Minuscules</option>
      <option <?php echo $mp7a;?>value="emaj" onClick="majapercu();">Majuscules</option>
      <option <?php echo $mp7f;?>value="effa" onClick="majapercu();">Effacé</option>
      </select>
    </td>
    <td>&nbsp;</td>
  </tr>
<?php
$cg1v = "000000";
$cg2v = "000000";
$cg3v = "000000";
$cg4v = "000000";
$cg5v = "000000";
$cg6v = "000000";
$cg7v = "000000";
if (isset($choix_cg1)) {$cg1v = $choix_cg1;}
if (isset($choix_cg2)) {$cg2v = $choix_cg2;}
if (isset($choix_cg3)) {$cg3v = $choix_cg3;}
if (isset($choix_cg4)) {$cg4v = $choix_cg4;}
if (isset($choix_cg5)) {$cg5v = $choix_cg5;}
if (isset($choix_cg6)) {$cg6v = $choix_cg6;}
if (isset($choix_cg7)) {$cg7v = $choix_cg7;}
?>
  <tr>
   <td>&nbsp;</td>
   <td><input type="text" id="cg1" name="cg1" size="10" class="jscolor {closable:true,closeText:'Fermer'}" value="<?php echo $cg1v;?>" onChange="majapercu();" style="font-size: 80%"></td>
   <td>&nbsp;</td>
   <td><input type="text" id="cg2" name="cg2" size="10" class="jscolor {closable:true,closeText:'Fermer'}" value="<?php echo $cg2v;?>" onChange="majapercu();" style="font-size: 80%"></td>
   <td>&nbsp;</td>
   <td><input type="text" id="cg3" name="cg3" size="10" class="jscolor {closable:true,closeText:'Fermer'}" value="<?php echo $cg3v;?>" onChange="majapercu();" style="font-size: 80%"></td>
   <td>&nbsp;</td>
   <td><input type="text" id="cg4" name="cg4" size="10" class="jscolor {closable:true,closeText:'Fermer'}" value="<?php echo $cg4v;?>" onChange="majapercu();" style="font-size: 80%"></td>
   <td>&nbsp;</td>
   <td><input type="text" id="cg5" name="cg5" size="10" class="jscolor {closable:true,closeText:'Fermer'}" value="<?php echo $cg5v;?>" onChange="majapercu();" style="font-size: 80%"></td>
   <td>&nbsp;</td>
   <td><input type="text" id="cg6" name="cg6" size="10" class="jscolor {closable:true,closeText:'Fermer'}" value="<?php echo $cg6v;?>" onChange="majapercu();" style="font-size: 80%"></td>
   <td>&nbsp;</td>
   <td><input type="text" id="cg7" name="cg7" size="10" class="jscolor {closable:true,closeText:'Fermer'}" value="<?php echo $cg7v;?>" onChange="majapercu();" style="font-size: 80%"></td>
   <td>&nbsp;</td>
  </tr>
</table></center><br><br>
<u>Aperçu :</u><br>
<?php
  //Définition des variables initiales
  //26 (14), 1911–1915 DOI: 10.1016/j.cub.2016.05.047.
  $txtAut = "Hisakata, R.; Nishida, S.; Johnston, A.";
  $txtAutChi = "Hisakata, Rumi, Shin 'ya Nishida, and Alan Johnston";
  $txtAutMla = "Hisakata, Rumi, et al.";
  $txtTit = "An Adaptable Metric Shapes Perceptual Space";
  $txtAnn = "2016";
  $txtRev = "Current Biology";
  $txtVol = "26";
  $txtNum = "14";
  $txtPag = "1911-1915";
?>
<div id="apercu">
<!--<span id="listAut"><?php echo($txtAut);?></span>
<span id="listTit"><?php echo($txtTit);?></span>
<span id="listAnn"><?php echo($txtAnn);?></span>
<span id="listRev"><?php echo($txtRev);?></span>
<span id="listVol"><?php echo($txtVol);?></span>
<span id="listNum"><?php echo($txtNum);?></span>
<span id="listPag"><?php echo($txtPag);?></span>-->
</div>
<br><br>
La suite sera constituée des éléments habituels s'ils ont été demandés : DOI, Pubmed, etc.
<br><br>
</div></div></div>
<br><br>
<input type="submit" value="Valider" name="soumis">
</form>

<br>
<?php
//Quelques liens pour les utilitaires
if (isset($_POST["soumis"]) || isset($_GET["team"])) {
  //URL de sauvegarde raccourcie via Bitly
  $bitly = "aucun";
  if (strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {include_once('bitly_local.php');$bitly = "ok";}
  if (strpos($_SERVER['HTTP_HOST'], 'ecobio') !== false) {include_once('bitly_ecobio.php');$bitly = "ok";}
  if (strpos($_SERVER['HTTP_HOST'], 'halur1') !== false) {include_once('bitly_halur1.php');$bitly = "ok";}
  if ($bitly == "aucun") {include_once('bitly_extrhal.php');$bitly = "ok";}
  $results = bitly_v3_shorten($urlsauv, 'a77347d33877d34446fa9a61d17bdcfafd70a087', 'bit.ly');
  //var_dump($results);
  $urlbitly = $results["url"];

  if (isset($idhal) && $idhal != "") {$team = $idhal;}
  echo("<center><b><a target='_blank' href='./HAL/extractionHAL_".$team.".rtf'>Exporter les données affichées en RTF</a></b>, <b><a target='_blank' href='./HAL/extractionHAL_".$team.".csv'>en CSV</a> ou <b><a target='_blank' href='./HAL/extractionHAL_".$team.".bib'>en Bibtex</a></b>");
  echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
  echo("<a href='ExtractionHAL.php'>Réinitialiser tous les paramètres</a>");
  echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
  echo("URL raccourcie directe : <a href=".$urlbitly.">".$urlbitly."</a></b></center>");
  echo("<br><br>");
}
?>

<h2><a name="sommaire"></a>Sommaire</h2>
<ul>
<?php
if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {echo('<li><a href="#TA">Tous les articles</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ACL-") !== false) {echo('<li><a href="#ACL">Articles de revues à comité de lecture</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ASCL-") !== false) {echo('<li><a href="#ASCL">Articles de revues sans comité de lecture</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ARI-") !== false) {echo('<li><a href="#ARI">Articles de revues internationales</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ARN-") !== false) {echo('<li><a href="#ARN">Articles de revues nationales</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRI-") !== false) {echo('<li><a href="#ACLRI">Articles de revues internationales à comité de lecture</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRN-") !== false) {echo('<li><a href="#ACLRN">Articles de revues nationales à comité de lecture</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRI-") !== false) {echo('<li><a href="#ASCLRI">Articles de revues internationales sans comité de lecture</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRN-") !== false) {echo('<li><a href="#ASCLRN">Articles de revues nationales sans comité de lecture</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-AV-") !== false) {echo('<li><a href="#AV">Articles de vulgarisation</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-TC-") !== false) {echo('<li><a href="#TC">Toutes les communications</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CA-") !== false) {echo('<li><a href="#CA">Communications avec actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CSA-") !== false) {echo('<li><a href="#CSA">Communications sans actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CI-") !== false) {echo('<li><a href="#CI">Communications internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CN-") !== false) {echo('<li><a href="#CN">Communications nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CAI-") !== false) {echo('<li><a href="#CAI">Communications avec actes internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CSAI-") !== false) {echo('<li><a href="#CAI">Communications sans actes internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CAN-") !== false) {echo('<li><a href="#CSAN">Communications avec actes nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CSAN-") !== false) {echo('<li><a href="#CSAN">Communications sans actes nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CINVASANI-") !== false) {echo('<li><a href="#CINVASANI">Communications invitées avec ou sans actes, nationales ou internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CINVA-") !== false) {echo('<li><a href="#CINVA">Communications invitées avec actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CINVSA-") !== false) {echo('<li><a href="#CINVSA">Communications invitées sans actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVA-") !== false) {echo('<li><a href="#CNONINVA">Communications non invitées avec actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVSA-") !== false) {echo('<li><a href="#CNONINVSA">Communications non invitées sans actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CINVI-") !== false) {echo('<li><a href="#CINVI">Communications invitées internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVI-") !== false) {echo('<li><a href="#CNONINVI">Communications non invitées internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CINVN-") !== false) {echo('<li><a href="#CINVN">Communications invitées nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVN-") !== false) {echo('<li><a href="#CNONINVN">Communications non invitées nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CPASANI-") !== false) {echo('<li><a href="#CPASANI">Communications par affiches (posters) avec ou sans actes, nationales ou internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CPA-") !== false) {echo('<li><a href="#CPA">Communications par affiches (posters) avec actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CPSA-") !== false) {echo('<li><a href="#CPSA">Communications par affiches (posters) sans actes</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CPI-") !== false) {echo('<li><a href="#CPI">Communications par affiches internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CPN-") !== false) {echo('<li><a href="#CPN">Communications par affiches nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CG-") !== false) {echo('<li><a href="#CG">Conférences grand public</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-TO-") !== false) {echo('<li><a href="#TO">Tous les ouvrages</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPI-") !== false) {echo('<li><a href="#OSPI">Ouvrages scientifiques de portée internationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPN-") !== false) {echo('<li><a href="#OSPN">Ouvrages scientifiques de portée nationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COS-") !== false) {echo('<li><a href="#COS">Chapitres d’ouvrages scientifiques</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSI-") !== false) {echo('<li><a href="#COSI">Chapitres d’ouvrages scientifiques de portée internationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSN-") !== false) {echo('<li><a href="#COSN">Chapitres d’ouvrages scientifiques de portée nationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOS-") !== false) {echo('<li><a href="#DOS">Directions d’ouvrages scientifiques</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSI-") !== false) {echo('<li><a href="#DOSI">Directions d’ouvrages scientifiques de portée internationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSN-") !== false) {echo('<li><a href="#DOSN">Directions d’ouvrages scientifiques de portée nationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCO-") !== false) {echo('<li><a href="#OCO">Ouvrages ou chapitres d’ouvrages</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCOI-") !== false) {echo('<li><a href="#OCOI">Ouvrages ou chapitres d’ouvrages de portée internationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCON-") !== false) {echo('<li><a href="#OCON">Ouvrages ou chapitres d’ouvrages de portée nationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODO-") !== false) {echo('<li><a href="#ODO">Ouvrages ou directions d’ouvrages</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODOI-") !== false) {echo('<li><a href="#ODOI">Ouvrages ou directions d’ouvrages de portée internationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODON-") !== false) {echo('<li><a href="#ODON">Ouvrages ou directions d’ouvrages de portée nationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDO-") !== false) {echo('<li><a href="#OCDO">Ouvrages ou chapitres ou directions d’ouvrages</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDOI-") !== false) {echo('<li><a href="#OCDOI">Ouvrages ou chapitres ou directions d’ouvrages de portée internationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDON-") !== false) {echo('<li><a href="#OCDON">Ouvrages ou chapitres ou directions d’ouvrages de portée internationale</a></li>');}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCV-") !== false) {echo('<li><a href="#OCV">Ouvrages ou chapitres de vulgarisation</a></li>');}
if (isset($choix_autr) && strpos($choix_autr, "-BRE-") !== false) {echo('<li><a href="#BRE">Brevets</a></li>');}
if (isset($choix_autr) && strpos($choix_autr, "-RAP-") !== false) {echo('<li><a href="#RAP">Rapports</a></li>');}
if (isset($choix_autr) && strpos($choix_autr, "-THE-") !== false) {echo('<li><a href="#THE">Thèses</a></li>');}
if (isset($choix_autr) && strpos($choix_autr, "-HDR-") !== false) {echo('<li><a href="#HDR">HDR</a></li>');}
if (isset($choix_autr) && strpos($choix_autr, "-PWM-") !== false) {echo('<li><a href="#PWM">Preprints, working papers, manuscrits non publiés</a></li>');}
if (isset($choix_autr) && strpos($choix_autr, "-AP-") !== false) {echo('<li><a href="#AP">Autres publications</a></li>');}
echo('<li><a href="#BILAN">Bilan quantitatif</a></li>');
?>
</ul>



<?php

/*
    ExtractionHAL - 2014-11-06
    Copyright (C) 2014 Guillaume Blin & Philippe Gambette (HAL_UPEMLV@univ-mlv.fr)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

//Compilation des critères de recherche
$specificRequestCode = '';

//Période de recherche
if (isset($anneedeb) && isset($anneefin)) {
  $specificRequestCode .= '%20AND%20producedDateY_i:["'.$anneedeb.'"%20TO%20"'.$anneefin.'"]';
}

//Date de dépôt
if (isset($depotdeb) && $depotdeb != "" && isset($depotfin) && $depotfin != "") {
  //Conversion des dates au format HAL ISO 8601 jj/mm/aaaa > aaaa-mm-jjT00:00:00Z
  $tabdepotdeb = explode('/', $depotdeb);
  $depotdebiso = $tabdepotdeb[2].'-'.$tabdepotdeb[1].'-'.$tabdepotdeb[0].'T00:00:00Z';
  $tabdepotfin = explode('/', $depotfin);
  $depotfiniso = $tabdepotfin[2].'-'.$tabdepotfin[1].'-'.$tabdepotfin[0].'T00:00:00Z';
  //champ:[valeurDébut TO valeurFin]
  $specificRequestCode .= '%20AND%20submittedDate_tdate:['.$depotdebiso.'%20TO%20'.$depotfiniso.']';
}

//collCode_s sert aussi bien pour une collection que pour un idhal
function getReferences($infoArray,$resArray,$sortArray,$docType,$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7){
	 static $listedoi = "";
   include "ExtractionHAL-rang-AERES-SHS.php";
   include "ExtractionHAL-rang-CNRS.php";
   include "ExtractionHAL-revues-AERES-HCERES.php";
   $docType_s=$docType;
   if (isset($idhal) && $idhal != "") {$atester = "authIdHal_s";}else{$atester = "collCode_s";}
   $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=0");
   //echo "http://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=0";
	 if ($docType_s=="COMM+POST"){
      $anneeConf = str_replace("%20AND%20popularLevel_s:0%20AND%20producedDateY_i:", "", $specificRequestCode);
      $specificRequestCode .= '%20AND%20conferenceStartDateY_i:'.$anneeConf;
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"COMM\"%20OR%20docType_s:\"POSTER\")".$specificRequestCode."&rows=0");
   }
	 if ($docType_s=="OUV+COUV"){
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\")".$specificRequestCode."&rows=0");
   }
   if ($docType_s=="OUV+DOUV"){
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=0");
   }
   if ($docType_s=="OUV+COUV+DOUV"){
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=0");
   }
   if ($docType_s=="UNDEF"){
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20docType_s:\"UNDEFINED\"".$specificRequestCode."&rows=0");
   }
   if ($docType_s!="OUV+COUV" && $docType_s!="OUV+DOUV" && $docType_s!="OUV+COUV+DOUV" && $docType_s!="UNDEF" && $docType_s!="COMM+POST"){
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=0");
    }
   $contents = utf8_encode($contents);
   $results = json_decode($contents);
   $numFound=$results->response->numFound;

   //Extracted fields depend on type of reference:
   $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,title_s,files_s,label_s,seeAlso_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   if ($docType_s=="ART"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,journalTitle_s,journalIssn_s,volume_s,issue_s,page_s,producedDateY_i,proceedings_s,files_s,label_s,doiId_s,publisherLink_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="COMM"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,comment_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,conferenceStartDateD_i,conferenceStartDateM_i,conferenceStartDateY_i,conferenceEndDateD_i,conferenceEndDateM_i,conferenceEndDateY_i,collCode_s,source_s,bookTitle_s,volume_s,issue_s,page_s,doiId_s,publisherLink_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="POSTER"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s,conferenceEndDateY_i,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s,source_s,volume_s,page_s";
   }
   if ($docType_s=="OTHER" or $docType_s=="OTHERREPORT"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,comment_s,files_s,label_s,description_s,seeAlso_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="REPORT"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,comment_s,files_s,label_s,description_s,seeAlso_s,halId_s,pubmedId_s,arxivId_s,reportType_s,number_s,authorityInstitution_s,page_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="THESE"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,producedDateY_i,director_s,authorityInstitution_s,defenseDateY_i,nntId_id,nntId_s,seeAlso_s,halId_s,pubmedId_s,arxivId_s,reportType_s,number_s,authorityInstitution_s,page_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="HDR"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,producedDateY_i,director_s,authorityInstitution_s,defenseDateY_i,nntId_id,nntId_s,seeAlso_s,halId_s,pubmedId_s,arxivId_s,reportType_s,number_s,authorityInstitution_s,page_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="OUV" or $docType_s=="DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,bookCollection_s,isbn_s,page_s,doiId_s,publisherLink_s,seeAlso_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="COUV" or $docType_s=="DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,bookCollection_s,isbn_s,page_s,doiId_s,publisherLink_s,seeAlso_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   if ($docType_s=="PATENT"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,bookCollection_s,number_s,producedDateY_i,producedDateY_i,seeAlso_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
   }
   //Cas particulierS pour combinaisons
   if ($docType_s=="COMM+POST"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,comment_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,conferenceStartDateD_i,conferenceStartDateM_i,conferenceStartDateY_i,conferenceEndDateD_i,conferenceEndDateM_i,conferenceEndDateY_i,collCode_s,source_s,bookTitle_s,volume_s,issue_s,page_s,doiId_s,publisherLink_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"COMM\"%20OR%20docType_s:\"POSTER\")".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
	 if ($docType_s=="OUV+COUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,bookCollection_s,isbn_s,page_s,doiId_s,publisherLink_s,seeAlso_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\")".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s=="OUV+DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,bookCollection_s,isbn_s,page_s,doiId_s,publisherLink_s,seeAlso_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s=="OUV+COUV+DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,bookCollection_s,isbn_s,page_s,doiId_s,publisherLink_s,seeAlso_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s=="UNDEF"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authMiddleName_s,authIdHalFullName_fs,authAlphaLastNameFirstNameId_fs,title_s,subTitle_s,version_i,journalTitle_s,volume_s,issue_s,page_s,producedDateY_i,proceedings_s,files_s,label_s,doiId_s,publisherLink_s,halId_s,pubmedId_s,arxivId_s,seeAlso_s,localReference_s,collCode_s,popularLevel_s,peerReviewing_s,invitedCommunication_s,proceedings_s,audience_s,language_s,label_bibtex,docType_s";
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20docType_s:\"UNDEFINED\"".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s!="OUV+COUV" && $docType_s!="OUV+DOUV" && $docType_s!="OUV+COUV+DOUV" && $docType_s!="UNDEF" && $docType_s!="COMM+POST"){
      $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
      //$contents = utf8_encode($contents);
    }
   //echo "http://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc";
   ini_set('memory_limit', '256M');
   $results = json_decode($contents);
   //var_dump($results);
   $iRA = 0;
   $nmoOrd = 0;
   foreach($results->response->docs as $entry){
      $img="";
      $chaine1 = "";
      $chaine2 = "";
      $listTit = "";
      if(isset($entry->files_s)){
         $img="<a href=\"".$entry->files_s[0]."\"><img
         src=\"http://haltools-new.inria.fr/images/Haltools_pdf.png\"/></a>";
      }
      $img.=" <a href=\"http://api.archives-ouvertes.fr/search/".$institut."?q=docid:".$entry->docid."&wt=bibtex\"><img
      src=\"http://haltools-new.inria.fr/images/Haltools_bibtex3.png\"/></a>";

      $entryInfo0 = "";//Début avec auteurs + titre + année + revue
      $entryInfo = "";//Suite avec doi + pubmed + ...

      //Adding collCode_s for specific case GR
      $listColl = "~";
      if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {
        foreach($entry->collCode_s as $coll){
        if (strpos($listColl, "~".$coll."~") === false) {
          $listColl .= "~".$coll."~";
            for($i = 1; $i <= $nbeqp; $i++) {
              if (isset($_POST["soumis"])) {
                if ($coll == strtoupper($_POST['eqp'.$i])) {
                  $entryInfo0 .= "GR".$i." - ¤ - ";
                  $eqpgr = strtoupper($_POST['eqp'.$i]);
                  break;
                }
              }
              if (isset($_GET["team"])) {
                if ($coll == $_GET['eqp'.$i]) {
                  $entryInfo0 .= "GR".$i." - ¤ - ";
                  $eqpgr = $_GET['eqp'.$i];
                  break;
                }
              }
            }
          }
        }
        $chaine1 .= "Collection";
        $chaine2 .= $entryInfo0;
        $resArray[$iRA]["GR"] = $entryInfo0;
      }

      //Le champ 'producedDateY_i' n'est pas obligatoire pour les communications et posters > on testera alors avec conferenceEndDateY_i
      if ($docType_s != "COMM" || $docType_s != "POSTER" || $docType_s != "COMM+POST") {
        $dateprod = $entry->producedDateY_i;
      }else{
        if (isset($entry->producedDateY_i)) {
          $dateprod = $entry->producedDateY_i;
        }else{
          $dateprod = $entry->conferenceEndDateY_i;
        }
      }

      //Adding authors:
      $initial = 1;
      $i = 0;
      $affil = "aucune";
      foreach($entry->authLastName_s as $nom){
        //$nom = ucwords(mb_strtolower($nom, 'UTF-8'));
        $nom = nomCompEntier($nom);
        $prenom = ucfirst(mb_strtolower($entry->authFirstName_s[$i], 'UTF-8'));
        $prenomPlus = "";
        //if (isset($entry->authMiddleName_s[$i])) {
        if (isset($entry->authMiddleName_s[0])) {
          foreach($entry->authMiddleName_s as $mid){
            $midTab = explode(" ", $mid);
            if (stripos($entry->authIdHalFullName_fs[$i], $midTab[0]) !== false) {//Pour vérifier qu'authMiddleName s'applique bien à cet auteur
              $prenomPlus = ucwords(mb_strtolower($mid, 'UTF-8'));//Champ HAL prévu pour complément prénom
              //echo 'toto : '.$nom.' '.$prenom.' '.$prenomPlus.'<br>';
            }
          }
        }
        //Si, Nom, initiale du prénom
        if ($typnom == "nominit") {
          //si prénom composé et initiales
          $prenom = prenomCompInit($prenom);
          if ($prenomPlus != "") {
            $prenomPlus = prenomCompInit($prenomPlus);
            $prenomPlus = str_replace(array(".", "-", "'", " "), array("trolipoint", "trolitiret", "troliapos", "troliesp") , $prenomPlus);
          }
          $prenom2 = str_replace(array(".", "-", "'", " "), array("trolipoint", "trolitiret", "troliapos", "troliesp") , $prenom);
          $nom2 = str_replace(array(".", "-", "'", " "), array("trolipoint", "trolitiret", "troliapos", "troliesp") , $nom);
          if ($initial == 1){
            $initial = 0;
            $authors = "";
          }else{
            $authors .= ", ";
          }
          if (strpos(wd_remove_accents($listenominit), wd_remove_accents($nom." ".$prenom)) === false) {
            $deb = "";
            $fin = "";
          }else{
            //On vérifie que l'auteur est bien dans la collection pour l'année de la publication
            $deb = "";
            $fin = "";
            $pos = strpos(wd_remove_accents($listenominit), wd_remove_accents($nom." ".$prenom));
            $pos = substr_count(substr($listenominit, 0, $pos), '~');
            $crit = 0;
            for ($k = 1; $k <= $pos; $k++) {
              $crit = strpos($arriv, '~', $crit+1);
              //echo 'toto : '.strlen($arriv).' - '.$crit.'<br>';
              //echo 'toto : '.$arriv.'<br>';
              //echo 'toto : '.$depar.'<br>';
            }
            $datearriv = substr($arriv, $crit-4, 4);
            $datedepar = substr($depar, $crit-4, 4);
            //echo 'titi : '.$dateprod <= $datedepar;
            if ($dateprod >= $datearriv && $dateprod <= $datedepar) {
              $affil = "ok";
              if ($typcol == "soul") {$deb = "<u>";$fin = "</u>";}
              if ($typcol == "gras") {$deb = "<b>";$fin = "</b>";}
              if ($typcol == "aucun") {$deb = "";$fin = "";}
            }
          }
          if ($prenomPlus != "") {
            $authors .= $nom2."troliesp".$prenom2."troliesp".$prenomPlus;
            $authors = mise_en_evidence(wd_remove_accents($nom2."troliesp".$prenom2."troliesp".$prenomPlus), $authors, $deb, $fin);
          }else{
            $authors .= $nom2."troliesp".$prenom2;
            $authors = mise_en_evidence(wd_remove_accents($nom2."troliesp".$prenom2), $authors, $deb, $fin);
          }
          $authors = str_replace(array("troliesp", "trolipoint", "trolitiret", "troliapos"), array(" ", ".", "-", "'") , $authors);
        }else{//Si nom/prénom complets
          if ($typnom == "nomcomp1") {//Nom Prénom
            if ($initial == 1){
              $initial = 0;
              $authors = "";
            }else{
              $authors .= ", ";
            }
            $prenom = prenomCompEntier($prenom);
            $prenom2 = str_replace(array(".", "-", "'"), array("trolipoint", "trolitiret", "troliapos") , $prenom);
            $nom2 = str_replace(array(".", "-", "'"), array("trolipoint", "trolitiret", "troliapos") , $nom);
            if (strpos(wd_remove_accents($listenomcomp1), wd_remove_accents($nom." ".$prenom)) === false) {
              $deb = "";$fin = "";
            }else{
              //On vérifie que l'auteur est bien dans la collection pour l'année de la publication
              $deb = "";
              $fin = "";
              $pos = strpos(wd_remove_accents($listenomcomp1), wd_remove_accents($nom." ".$prenom));
              $pos = substr_count(substr($listenomcomp1, 0, $pos), '~');
              $crit = 0;
              for ($k = 1; $k <= $pos; $k++) {
                $crit = strpos($arriv, '~', $crit+1);
              }
              $datearriv = substr($arriv, $crit-4, 4);
              $datedepar = substr($depar, $crit-4, 4);
              if ($dateprod >= $datearriv && $dateprod <= $datedepar) {
                $affil = "ok";
                if ($typcol == "soul") {$deb = "<u>";$fin = "</u>";}
                if ($typcol == "gras") {$deb = "<b>";$fin = "</b>";}
                if ($typcol == "aucun") {$deb = "";$fin = "";}
              }
            }
            if ($prenomPlus != "") {
              $authors .= $nom2."troliesp".$prenom2."troliesp".$prenomPlus;
              $authors = mise_en_evidence(wd_remove_accents($nom2."troliesp".$prenom2."troliesp".$prenomPlus), $authors, $deb, $fin);
            }else{
              $authors .= $nom2."troliesp".$prenom2;
              $authors = mise_en_evidence(wd_remove_accents($nom2."troliesp".$prenom2), $authors, $deb, $fin);
            }
            $authors = str_replace(array("troliesp", "trolipoint", "trolitiret", "troliapos"), array(" ", ".", "-", "'") , $authors);
          }else{//Prénom Nom
            if ($initial == 1){
              $initial = 0;
              $authors = "";
            }else{
              $authors .= ", ";
            }
            $prenom = prenomCompEntier($prenom);
            $prenom2 = str_replace(array(".", "-", "'"), array("trolipoint", "trolitiret", "troliapos") , $prenom);
            $nom2 = str_replace(array(".", "-", "'"), array("trolipoint", "trolitiret", "troliapos") , $nom);
            if (strpos(wd_remove_accents($listenomcomp2), wd_remove_accents($prenom." ".$nom)) === false) {
              $deb = "";$fin = "";
            }else{
              //On vérifie que l'auteur est bien dans la collection pour l'année de la publication
              $pos = strpos(wd_remove_accents($listenomcomp2), wd_remove_accents($prenom." ".$nom));
              $pos = substr_count(substr($listenomcomp2, 0, $pos), '~');
              $crit = 0;
              for ($k = 1; $k <= $pos; $k++) {
                $crit = strpos($arriv, '~', $crit+1);
              }
              $datearriv = substr($arriv, $crit-4, 4);
              $datedepar = substr($depar, $crit-4, 4);
              if ($dateprod >= $datearriv && $dateprod <= $datedepar) {
                $affil = "ok";
                if ($typcol == "soul") {$deb = "<u>";$fin = "</u>";}
                if ($typcol == "gras") {$deb = "<b>";$fin = "</b>";}
                if ($typcol == "aucun") {$deb = "";$fin = "";}
              }
            }
            if ($prenomPlus != "") {
              $authors .= $nom2."troliesp".$prenom2."troliesp".$prenomPlus;
              $authors = mise_en_evidence(wd_remove_accents($nom2."troliesp".$prenom2."troliesp".$prenomPlus), $authors, $deb, $fin);
            }else{
              $authors .= $nom2."troliesp".$prenom2;
              $authors = mise_en_evidence(wd_remove_accents($nom2."troliesp".$prenom2), $authors, $deb, $fin);
            }
            $authors = str_replace(array("troliesp", "trolipoint", "trolitiret", "troliapos"), array(" ", ".", "-", "'") , $authors);
          }
        }
        $i++;
      }
      //Limiting to 1, 5, 10, 15 or 20 authors + et al.
      if (isset($typlim) && $typlim == "oui") {
        $cpt = 1;
        $pospv = 0;
        $lim_aut_ok = 1;
        $limvirg = $limaff;
        while ($cpt <= $limvirg) {
          if (strpos($authors, ",", $pospv+1) !== false) {
            $pospv = strpos($authors, ",", $pospv+1);
            $cpt ++;
          }else{
            $lim_aut_ok = 0;
            break;
          }
        }
        $extract = $authors;
        if ($lim_aut_ok != 0) {
          //$extract = mb_substr($authors, 0, $pospv, 'UTF-8');
          $extract = substr($authors, 0, $pospv);
          if (isset($stpdf) && $stpdf == "mla") {//Cas spécifique du style MLA prédéfini
            $extract .= ", et al.";
          }else{
            $extract .= " <i> et al.</i>";
          }
        }else{
          if ($typnom != "nominit") {
            $extract .= ".";
          }
        }
      }else{
        $extract = $authors;
      }
      $extractpur = $extract;
      if ($typaut == "soul") {$extract = "<u>".$extract."</u>";}
      if ($typaut == "gras") {$extract = "<b>".$extract."</b>";}
      if (isset($spa) && $spa == "pvi") {$extract = str_replace(", ", "; ", $extract);}
      if (isset($spa) && $spa == "esp") {$extract = str_replace(", ", " ", $extract);}
      if (isset($spa) && $spa == "tir") {$extract = str_replace(", ", " - ", $extract);}

      $deb3 = "";
      $fin3 = "";
      //Y-a-t-il absence d'affiliation, et, si oui, faut-il l'afficher ?
      if ($sursou == "vis" && $affil == "aucune") {
        $deb3 = "<span style='background:#FF0000'>";
        $fin3 = "</span>";
      }

      $entryInfo0 .= $deb3.$extract.$fin3;
      $resArray[$iRA]["authors"] = $extract;
      if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {
        $chaine1 .= $delim."Auteurs";
        $chaine2 .= $delim.strip_tags($extractpur);
      }else{
        $chaine1 .= "Auteurs";
        $chaine2 .= strip_tags($extractpur);
      }

      //Adding producedDateY_i:
      $chaine1 .= $delim."Année";
      $resArray[$iRA]["annee"] = $dateprod;
      if ($typann == "apres") {//Année après les auteurs
        if ($docType_s=="ART" || $docType_s=="UNDEF" || $docType_s=="COMM" || $docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" or $docType_s=="OUV+COUV" or $docType_s=="OUV+DOUV" or $docType_s=="OUV+COUV+DOUV" or $docType_s=="OTHER" or $docType_s=="OTHERREPORT" or $docType_s=="REPORT" or $docType_s=="COMM+POST"){
           $entryInfo0 .= " (".$dateprod.")";
           $chaine2 .= $delim.$dateprod;
        }else{
          $chaine2 .= $delim;
        }
      }else{
        $entryInfo0 .= ", ";
        $chaine2 .= $delim;
      }

      //HDR - adding defenseDateY_i
      $chaine1 .= $delim."Année de soutenance";
      if ($docType_s=="HDR" && isset($entry->defenseDateY_i)){
        $entryInfo0 .= " (".$entry->defenseDateY_i.")";
        $resArray[$iRA]["annee"] = " (".$entry->defenseDateY_i.")";
        $chaine2 .= $delim.$entry->defenseDateY_i;
      }else{
        $chaine2 .= $delim;
      }

      //Adding title:
      $chaine1 .= $delim."Titre";
      if ($typann == "apres") {$point = ".";}else{$point = "";}
      $deb = "&nbsp;";
      $fin = "";
      if (strpos($typtit,"guil") >= 1) {$deb .= "«&nbsp;";$fin .= "&nbsp;»";}
      if (strpos($typtit,"gras") >= 1) {$deb .= "<b>";$fin .= "</b>";}
      if (strpos($typtit,"ital") >= 1) {$deb .= "<i>";$fin .= "</i>";}
      if (strpos($typtit,"reto") >= 1) {$fin .= "<br>";}
      $titrePlus = $entry->title_s[0];
      if (isset($entry->subTitle_s[0])) {//existence d'un sous-titre
        $titrePlus .= " : ".$entry->subTitle_s[0];
      }
      $titre = nettoy1(cleanup_title($titrePlus));
      $deb2 = "";
      $fin2 = "";

      //Est-ce un doublon et, si oui, faut-il l'afficher?
      if (stripos($listetitre, $titre) === false) {//non
        $listetitre .= "¤".$titre;
      }else{
        if ($surdoi == "vis") {
          $deb2 = "<span style='background:#00FF00'><b>";
          $fin2 = "</b></span>";
        }
      }
      
      $entryInfo0 .= $point.$deb.$deb2.$titre.$fin2.$fin;
      $resArray[$iRA]["titre"] = $titre;
      $chaine2 .= $delim.$titre;

      //Adding journalTitle_s:
      $chaine1 .= $delim."Titre journal";
      if (isset($entry->journalTitle_s)) {
        $resArray[$iRA]["revue"] = $entry->journalTitle_s;
        if ($docType_s == "ART"){
          $entryInfo0 .= ". <i>".$entry->journalTitle_s."</i>";
          $chaine2 .= $delim.$entry->journalTitle_s;
          $JT = $entry->journalTitle_s;//for IF
        }else{
          $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding $dateprod (=producedDateY_i ou conferenceEndDateY_i)
      $chaine1 .= $delim."Année";
      if ($typann == "avant") {//Année avant le numéro de volume
        if ($docType_s=="ART" || $docType_s=="UNDEF"){
          if (strpos($typtit,"reto") >= 1) {
            $entryInfo0 .= $dateprod.",";
          }else{
            $entryInfo0 .= ", ".$dateprod.",";
          }
          $chaine2 .= $delim.$dateprod;
        }else{
          $chaine2 .= $delim;
        }
        if ($docType_s == "COMM" || $docType_s == "COMM+POST"){
          if (strpos($typtit,"reto") >= 1) {
            $entryInfo0 .= $dateprod.",";
          }else{
            $entryInfo0 .= ", ".$dateprod.",";
          }
          $chaine2 .= $delim.$dateprod;
        }else{
          $chaine2 .= $delim;
        }
      }else{
        if ($docType_s != "THESE" && $docType_s != "HDR") {
          if (strpos($typtit,"reto") >= 1) {
          }else{
            $entryInfo0 .= ", ";
          }
          $chaine2 .= $delim;
        }else{
          $entryInfo0 .= ". ";
          $chaine2 .= $delim;
        }
      }

      $hasVolumeOrNumber=0;
      $toAppear=0;

      //Adding volume_s:
      $vol = "";
      $chaine1 .= $delim."Volume";
      if ($docType_s=="ART"){
         if(isset($entry->volume_s) && !is_array($entry->volume_s)){
            if($entry->volume_s!="" and $entry->volume_s!=" " and $entry->volume_s!="-" and $entry->volume_s!="()"){
               if(toAppear($entry->volume_s)){
                  $toAppear=1;
               } else {
                  $resArray[$iRA]["volume"] = $entry->volume_s;
                  if ($typfor == "typ2") {
                    $entryInfo .= " ".$entry->volume_s;
                    $chaine2 .= $delim.$entry->volume_s;
                    $hasVolumeOrNumber=1;
                  }else{
                    $vol = $entry->volume_s;
                    $hasVolumeOrNumber=1;
                    $chaine2 .= $delim;
                  }
               }
            }else{
              $chaine2 .= $delim;
            }
         }else{
           $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding issue_s:
      $iss = "";
      $chaine1 .= $delim."Issue";
      //if ($docType_s=="ART" OR $docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV" OR $docType_s=="COMM+POST"){
      if ($docType_s=="ART" OR $docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV"){
         if(isset($entry->issue_s[0]) && !is_array($entry->issue_s[0])){
            if($entry->issue_s[0]!="" and $entry->issue_s[0]!=" " and $entry->issue_s[0]!="-" and $entry->issue_s[0]!="()"){
               if(toAppear($entry->issue_s[0])){
                  $toAppear=1;
               }else{
                  $resArray[$iRA]["issue"] = $entry->issue_s[0];
                  if ($typfor == "typ2") {
                    $entryInfo .= "(".$entry->issue_s[0].")";
                    $chaine2 .= $delim.$entry->issue_s[0];
                    $hasVolumeOrNumber=1;
                  }else{
                    $iss = $entry->issue_s[0];
                    $hasVolumeOrNumber=1;
                    $chaine2 .= $delim;
                  }
               }
            }else{
              $chaine2 .= $delim;
            }
         }else{
           $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding scientificEditor_s:
      $chaine1 .= $delim."Editeur scientifique";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV"){
         if (isset($entry->scientificEditor_s)) {
           if(count($entry->scientificEditor_s)>0){
              $initial = 1;
              foreach($entry->scientificEditor_s as $editor){
                 if ($initial==1){
                    $entryInfo .= ", <i>in</i> ".$editor;
                    $resArray[$iRA]["editor"] = $editor;
                    $chaine2 .= $delim.$editor;
                    $initial=0;
                 } else {
                    //$entryInfo .= ", <i>in</i> ".$editor;
                    $entryInfo .= ", ".$editor;
                    $resArray[$iRA]["editor"] .= "~ ".$editor;
                    $chaine2 .= $delim.$editor;
                 }
              }
           }else{
            $chaine2 .= $delim;
           }
         }else{
           $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding bookTitle_s:
      $chaine1 .= $delim."Titre ouvrage";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV"){
        if (isset($entry->bookTitle_s)) {
          $entryInfo .= ", <i>".$entry->bookTitle_s."</i>";
          $resArray[$iRA]["bookTitle"] = $entry->bookTitle_s;
          $chaine2 .= $delim.$entry->bookTitle_s;
        }else{
          $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }

      //Adding bookCollection_s:
      $chaine1 .= $delim."Titre du volume";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV"){
        if (isset($entry->bookCollection_s)) {
          $entryInfo .= ". ".$entry->bookCollection_s;
          $resArray[$iRA]["bookCollection"] = $entry->bookCollection_s;
          $chaine2 .= $delim.$entry->bookCollection_s;
        }else{
          $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }

      //Adding publisher_s:
      $chaine1 .= $delim."Editeur revue";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV"){
         if(!$entry->publisher_s[0]==""){
            $entryInfo .= ", ".$entry->publisher_s[0];
            $resArray[$iRA]["publisher"] = $entry->publisher_s[0];
            $chaine2 .= $delim.$entry->publisher_s[0];
         }else{
          $chaine2 .= $delim.$entry->publisher_s[0];
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding page_s:
      $chaine1 .= $delim."Volume, Issue, Pages";
      if ($docType_s=="ART" or $docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV"){
         if (isset($entry->page_s)) {
           $page = $entry->page_s;
           $patterns = array();
           $patterns[0] = '/--/';
           $patterns[1] = '/Pages:/';
           $patterns[2] = '/–/';
           $patterns[3] = '/ - /';
           $replacements = array();
           $replacements[0] = '-';
           $replacements[1] = '';
           $replacements[2] = '-';
           $replacements[3] = '-';

           $page = preg_replace($patterns, $replacements, $page);
           if(substr($page,0,1)==" "){
              $page=substr($page,-(strlen($page)-1));
           }
           if(toAppear($page)){
              $toAppear=1;
           }
           if($toAppear==1){
              $entryInfo .= ", to appear";
              $chaine2 .= $delim."to appear";
           } else {
              if(!($page=="?" or $page=="-" or $page=="" or $page==" " or $page=="–")){
                $resArray[$iRA]["page"] = $page;
                if ($typfor == "typ2") {
                 if($hasVolumeOrNumber==1){
                    $entryInfo .= ":".$page;
                    //$resArray[$iRA]["page"] = ":".$page;
                    $chaine2 .= $delim.$page;
                 }else{
                    $entryInfo .= ", ".$page;
                    //$resArray[$iRA]["page"] = ", ".$page;
                    $chaine2 .= $delim.$page;
                 }
                }else{
                    if ($vol != "") {$entryInfo .= " vol ".$vol;$chaine2 .= $delim." vol ".$vol;}else{$chaine2 .= $delim;}
                    if ($iss != "") {$entryInfo .= ", n°".$iss;$chaine2 .= " ,n° ".$iss;}
                    if ($page != "") {
                      if (is_numeric(substr($page,0,1))) {
                        $entryInfo .= ", pp. ".$page;
                        //$resArray[$iRA]["page"] = ", pp. ".$page;
                        $chaine2 .= ", pp. ".$page;
                      }else{
                        $entryInfo .= $page;
                        //$resArray[$iRA]["page"] = $page;
                        $chaine2 .= $page;
                      }
                    }
                }
              }else{
                $chaine2 .= $delim;
              }
           }
         }else{
          if ($docType_s=="ART") {$entryInfo .= ' in press';}
          $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding isbn_s:
      $chaine1 .= $delim."ISBN";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" OR $docType_s=="OUV+COUV" OR $docType_s=="OUV+DOUV" OR $docType_s=="OUV+COUV+DOUV"){
         if (isset($entry->isbn_s)) {
           $entryInfo .= ", ".$entry->isbn_s.".";
           $resArray[$iRA]["isbn"] = $entry->isbn_s;
           $chaine2 .= $delim.$entry->isbn_s;
         }else{
          $chaine2 .= $delim;
         }
      }else{
         $chaine2 .= $delim;
      }

      //Adding conferenceTitle_s:
      $chaine1 .= $delim."Titre conférence";
      if ($docType_s=="COMM" || $docType_s=="POSTER" || $docType_s == "COMM+POST"){
         $resArray[$iRA]["conferenceTitle"] = $entry->conferenceTitle_s;
         if (strpos($typtit,"reto") >= 1) {
           $entryInfo .= " ".$entry->conferenceTitle_s;
         }else{
           $entryInfo .= ", ".$entry->conferenceTitle_s;
         }
         $chaine2 .= $delim.$entry->conferenceTitle_s;
      }else{
         $chaine2 .= $delim;
      }

      //Adding comment:
      $chaine1 .= $delim."Commentaire";
      if (($docType_s=="COMM" and $specificRequestCode=="%20AND%20invitedCommunication_s:1") or ($docType_s=="OTHER") or ($docType_s=="OTHERREPORT") || $docType_s == "COMM+POST"){
         if (isset($entry->comment_s) && $entry->comment_s!="" and $entry->comment_s!=" " and $entry->comment_s!="-" and $entry->comment_s!="?"){
           $entryInfo .= ", ".$entry->comment_s;
           $resArray[$iRA]["commentaire"] = $entry->comment_s;
           $chaine2 .= $delim.$entry->comment_s;
         }else{
           $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding congress dates
      $chaine1 .= $delim."Date congrès";
      $mois = Array('','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre');
      if ($docType_s=="COMM" || $docType_s=="POSTER" || $docType_s == "COMM+POST"){
        if (isset($entry->conferenceStartDateY_i) && isset($entry->conferenceEndDateY_i) && $entry->conferenceStartDateY_i != "" && $entry->conferenceStartDateY_i == $entry->conferenceEndDateY_i) {//même année
          if (isset($entry->conferenceStartDateM_i) && isset($entry->conferenceEndDateM_i) && $entry->conferenceStartDateM_i != "" && $entry->conferenceStartDateM_i == $entry->conferenceEndDateM_i) {//même mois
            if (isset($entry->conferenceStartDateD_i) && isset($entry->conferenceEndDateD_i) && $entry->conferenceStartDateD_i != "" && $entry->conferenceStartDateD_i == $entry->conferenceEndDateD_i) {//même jour
              $entryInfo .= ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              $chaine2 .= $delim.$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
            }else{//jours différents
              if (isset($entry->conferenceStartDateD_i) && $entry->conferenceStartDateD_i != "") {
                $entryInfo .= ", ".$entry->conferenceStartDateD_i;
                $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i;
                $chaine2 .= $delim.$entry->conferenceStartDateD_i;
              }
              if (isset($entry->conferenceEndDateD_i) && $entry->conferenceEndDateD_i != "" && $entry->conferenceEndDateM_i != "" && $entry->conferenceEndDateY_i != "") {
                $entryInfo .= "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
                $resArray[$iRA]["congressDates"] = "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
                $chaine2 .= "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              }
            }
          }else{//mois différents
            if (isset($entry->conferenceStartDateD_i) && $entry->conferenceStartDateD_i != "" && $entry->conferenceStartDateM_i != "") {
              $entryInfo .= ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i];
              $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i];
              $chaine2 .= $delim.$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i];
            }
            if (isset($entry->conferenceEndDateD_i) && $entry->conferenceEndDateD_i != "" && $entry->conferenceEndDateM_i != "" && $entry->conferenceEndDateY_i != "") {
              $entryInfo .= "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              $resArray[$iRA]["congressDates"] = "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              $chaine2 .= "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
            }
          }
        }else{//années différentes
          if (isset($entry->conferenceStartDateD_i) && $entry->conferenceStartDateD_i != "" && $entry->conferenceStartDateM_i != "" && $entry->conferenceStartDateY_i != "") {
            $entryInfo .= ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i]." ".$entry->conferenceStartDateY_i;
            $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i]." ".$entry->conferenceStartDateY_i;
            $chaine2 .= $delim.$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i]." ".$entry->conferenceStartDateY_i;
          }
          if (isset($entry->conferenceEndDateD_i) && $entry->conferenceEndDateD_i != "" && $entry->conferenceEndDateM_i != "" && $entry->conferenceEndDateY_i != "") {
            $entryInfo .= " - ".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
            $resArray[$iRA]["congressDates"] = " - ".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
            $chaine2 .= " - ".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
          }
        }
        //si aucune date renseignée
        if (isset($entry->conferenceStartDateY_i) && $entry->conferenceStartDateY_i == "" && $entry->conferenceStartDateM_i == "" && $entry->conferenceStartDateD_i == "" && $entry->conferenceEndDateY_i == "" && $entry->conferenceEndDateM_i == "" && $entry->conferenceEndDateD_i == "") {
          $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }

      //Adding city_s:
      $chaine1 .= $delim."Ville";
      if ($docType_s=="COMM" || $docType_s=="POSTER" || $docType_s == "COMM+POST"){
         if(isset($entry->city_s)){
            $entryInfo .= ", ".$entry->city_s;
            $resArray[$iRA]["city"] = $entry->city_s;
            $chaine2 .= $delim.$entry->city_s;
         }else{
        $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }

      //Adding country_s:
      $chaine1 .= $delim."Pays";
      if ($docType_s=="COMM" || $docType_s=="POSTER" || $docType_s == "COMM+POST"){
         if($entry->country_s!=""){
           $entryInfo .= " (".$countries[$entry->country_s].").";
           $resArray[$iRA]["countries"] = $countries[$entry->country_s];
           $chaine2 .= $delim.$countries[$entry->country_s];
         }else{
           $entryInfo .= ".";
           $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Cas où certaines communications sont recensées sous formes d'abstracts dans des revues
      if ($docType_s == "COMM" || $docType_s == "COMM+POST") {
        //Adding source_s:
        $chaine1 .= $delim."Source";
        if(isset($entry->source_s) && $entry->source_s != ""){
          $entryInfo .= " <i>".$entry->source_s."</i>,";
          $resArray[$iRA]["source"] = $entry->source_s;
          $chaine2 .= $delim.$entry->source_s;
        }else{
          if(isset($entry->bookTitle_s) && $entry->bookTitle_s != "") {
            $entryInfo .= " <i>".$entry->bookTitle_s."</i>,";
            $resArray[$iRA]["source"] = $entry->bookTitle_s;
            $chaine2 .= $delim.$entry->bookTitle_s;
          }else{
            $chaine2 .= $delim;
          }
        }
        //Adding volume_s:
        $vol = 0;
        $chaine1 .= $delim."Volume";
        if(isset($entry->volume_s) && $entry->volume_s != ""){
          $vol = 1;
          $entryInfo .= " ".$entry->volume_s;
          $chaine2 .= $delim.$entry->volume_s;
        }else{
          $chaine2 .= $delim;
        }
        //Adding issue_s:
				$iss = 0;
        $chaine1 .= $delim."Numéro";
        if(isset($entry->issue_s) && $entry->issue_s != ""){
				 $iss = 1;
         $entryInfo .= "(".$entry->issue_s[0].")";
         $chaine2 .= $delim.$entry->issue_s[0];
        }else{
         $chaine2 .= $delim;
        }
        //Adding page_s:
        $chaine1 .= $delim."Pagination";
        if(isset($entry->page_s) && $entry->page_s != ""){
				 if ($vol == 1 && $iss == 1) {
				  $entryInfo .= ":";
				 }else{
					$entryInfo .= " ";
				 }
         $entryInfo .= $entry->page_s;
         $chaine2 .= $delim.$entry->page_s;
        }else{
         $entryInfo .= " in press";
         $chaine2 .= $delim;
        }
        $entryInfo .= ".";
      }

      //Adding conferenceStartDate_s:
      //if ($docType_s=="COMM" || $docType_s=="POSTER" || $docType_s == "COMM+POST"){
         //$entryInfo .= ", ".$entry->conferenceStartDate_s;
      //}

      //Ajout de l'identifiant et des actes pour les posters avec actes
      if ($docType_s == "POSTER") {
        //Adding source_s:
        $chaine1 .= $delim."Source";
        if($entry->source_s != ""){
         $entryInfo .= " <i>".$entry->source_s."</i>,";
         $chaine2 .= $delim.$entry->source_s;
        }
        $chaine2 .= $delim;
        //Adding volume_s:
        $chaine1 .= $delim."Volume";
        if($entry->volume_s != ""){
         $entryInfo .= " <i>".$entry->volume_s."</i>,";
         $chaine2 .= $delim.$entry->volume_s;
        }
        $chaine2 .= $delim;
        //Adding page_s:
        $chaine1 .= $delim."Page/identifiant";
        if($entry->page_s != ""){
         $entryInfo .= " <i>pp.".$entry->page_s."</i>,";
         $chaine2 .= $delim.$entry->page_s;
        }
        $chaine2 .= $delim;
      }



      //Adding (avec acte)/(sans acte) pour les communications et posters
      if ($docType_s == "COMM" || $docType_s == "POSTER" || $docType_s == "COMM+POST") {
        if (isset($typavsa) && $typavsa == "vis") {
          $chaine1 .= $delim."Info avsa";
          if ($entry->proceedings_s == "0") {
            $entryInfo .= " <i>(sans acte)</i>";
            $resArray[$iRA]["avsa"] = " <i>(sans acte)</i>";
            $chaine2 .= $delim."(sans acte)";
          }else{
            $entryInfo .= " <i>(avec acte)</i>";
            $resArray[$iRA]["avsa"] = " <i>(avec acte)</i>";
            $chaine2 .= $delim."(avec acte)";
          }
        }
      }

      //Adding patent number:
      $chaine1 .= $delim."Patent n°";
      if ($docType_s=="PATENT"){
        $entryInfo .= " Patent n°".$entry->number_s[0];
        $chaine2 .= $delim.$entry->number_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Adding $dateprod (=producedDateY_i ou conferenceEndDateY_i) :
      $chaine1 .= $delim."Date de publication";
      if ($docType_s=="PATENT"){
        $entryInfo .= " (".$dateprod.")";
        $chaine2 .= $delim.$dateprod;
      }else{
        $chaine2 .= $delim;
      }

      //Adding reportType_s:
      $chaine1 .= $delim."Type de rapport";
      if ($docType_s=="REPORT" && isset($entry->reportType_s)) {
        if ($entry->reportType_s == 6) {$reportType = "Rapport de recherche";}
        if ($entry->reportType_s == 2) {$reportType = "Contrat";}
        if ($entry->reportType_s == 5) {$reportType = "Stage";}
        if ($entry->reportType_s == 3) {$reportType = "Interne";}
        if ($entry->reportType_s == 1) {$reportType = "Travail universitaire";}
        if ($entry->reportType_s == 4) {$reportType = "Rapport technique";}
        if ($entry->reportType_s == 0) {$reportType = "Rapport de recherche";}
        $entryInfo .= ". ".$reportType;
        $resArray[$iRA]["reportType"] = $reportType;
        $chaine2 .= $delim.$reportType;
      }else{
        $chaine2 .= $delim;
      }

      //Adding number_s:
      $chaine1 .= $delim."N°";
      if ($docType_s=="REPORT" && isset($entry->number_s)) {
         $entryInfo .= ", N°".$entry->number_s[0];
         $resArray[$iRA]["reportNumber"] = ", N°".$entry->number_s[0];
         $chaine2 .= $delim.$entry->number_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Adding authorityInstitution_s:
      $chaine1 .= $delim."Organisme de délivrance";
      if ($docType_s=="REPORT" && isset($entry->authorityInstitution)) {
         $entryInfo .= ". ".$entry->authorityInstitution;
         $resArray[$iRA]["authorityInstitution"] = $entry->authorityInstitution;
         $chaine2 .= $delim.$entry->authorityInstitution;
      }else{
        $chaine2 .= $delim;
      }

      //Adding page_s for report:
      $chaine1 .= $delim."Pages";
      if ($docType_s=="REPORT") {
        if (isset($entry->page_s)) {
           $entryInfo .= ". ".$entry->page_s;
           $chaine2 .= $delim.$entry->page_s;
           if (strpos($entry->page_s, "p") === false) {$entryInfo .= "p.";}
        }else{
          $entryInfo .= ", in press";
          $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }

      //Adding $dateprod (=producedDateY_i ou conferenceEndDateY_i) :
      $chaine1 .= $delim."Date de publication";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" or $docType_s=="OUV+COUV" or $docType_s=="OUV+DOUV" or $docType_s=="OUV+COUV+DOUV" or $docType_s=="OTHER" or ($docType_s=="OTHERREPORT") or ($docType_s=="REPORT")){
         if ($typann == "avant") {
          $entryInfo .= ", ".$dateprod.".";
          $chaine2 .= $delim.$dateprod;
         }else{
          $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Thesis - adding director_s
      $chaine1 .= $delim."Directeur de thèse";
      if ($docType_s=="THESE" && isset($entry->director_s)){
        $entryInfo .= "Dir : ".$entry->director_s[0].".";
        $resArray[$iRA]["thesisDirector"] = "Dir : ".$entry->director_s[0].".";
        $chaine2 .= $delim.$entry->director_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Thesis - adding authorityInstitution_s
      $chaine1 .= $delim."Université de soutenance";
      if ($docType_s=="THESE" && isset($entry->authorityInstitution_s)){
        $entryInfo .= " ".$entry->authorityInstitution_s[0];
        $resArray[$iRA]["authorityInstitution"] = $entry->authorityInstitution_s[0];
        $chaine2 .= $delim.$entry->authorityInstitution_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Thesis - adding defenseDateY_i
      $chaine1 .= $delim."Année de soutenance";
      if ($docType_s=="THESE" && isset($entry->defenseDateY_i)){
        $entryInfo .= ", ".$entry->defenseDateY_i;
        $resArray[$iRA]["defenseDate"] = $entry->defenseDateY_i;
        $chaine2 .= $delim.$entry->defenseDateY_i;
      }else{
        $chaine2 .= $delim;
      }

      //HDR - adding authorityInstitution_s
      $chaine1 .= $delim."Organisme de délivrance";
      if ($docType_s=="HDR" && isset($entry->authorityInstitution_s)){
        $entryInfo .= "HDR, ".$entry->authorityInstitution_s[0];
        $chaine2 .= $delim.$entry->authorityInstitution_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Corrections diverses
      $entryInfo = nettoy1($entryInfo);
      $entryInfo0 = nettoy1($entryInfo0);
      //$entryInfo = str_replace(array(". : ",",, ",", , ","..","?.","?,","<br>.","--"), array(" : ",", ",", ",".","?","?","<br>","-"), $entryInfo);
      //$entryInfo0 = str_replace(array(". : ",",, ",", , ","..","?.","?,","<br>.","--"), array(" : ",", ",", ",".","?","?","<br>","-"), $entryInfo0);

      //Ordre à respecter
      $ord = "non";
      if ($nmo != "- -" && $gp1 != "- -" && $gp2 != "- -" && $gp3 != "- -" && $gp4 != "- -" && $gp5 != "- -" && $gp6 != "- -" && $gp7 != "- -" && $sep1 != "- -" && $sep2 != "- -" && $sep3 != "- -" && $sep4 != "- -" && $sep5 != "- -" && $sep6 != "- -" && $sep7 != "- -") {
        //echo 'toto : '.$gp1.' '.$gp2.' '.$gp3.' '.$gp4.' ';
        $ord = "oui";
        //Mise en page des groupes
        $i = 1;
        while ($i <= 7) {//$i = Nombre de groupes
          if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~gras~") !== false) {${"deb" .$i . "_1"} = "<b>";${"fin" . $i ."_1"} = "</b>";}else{${"deb" .$i . "_1"} = "";${"fin" .$i . "_1"} = "";}
          if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~soul~") !== false) {${"deb" .$i . "_2"} = "<u>";${"fin" . $i ."_2"} = "</u>";}else{${"deb" .$i . "_2"} = "";${"fin" .$i . "_2"} = "";}
          if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~ital~") !== false) {${"deb" .$i . "_3"} = "<i>";${"fin" . $i ."_3"} = "</i>";}else{${"deb" .$i . "_3"} = "";${"fin" .$i . "_3"} = "";}
          if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~epar~") !== false) {${"deb" .$i . "_4"} = "(";${"fin" . $i ."_4"} = ")";}else{${"deb" .$i . "_4"} = "";${"fin" .$i . "_4"} = "";}
          if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~egui~") !== false) {${"deb" .$i . "_5"} = "\"";${"fin" . $i ."_5"} = "\"";}else{${"deb" .$i . "_5"} = "";${"fin" .$i . "_5"} = "";}
          if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~ecro~") !== false) {${"deb" .$i . "_6"} = "[";${"fin" . $i ."_6"} = "]";}else{${"deb" .$i . "_6"} = "";${"fin" .$i . "_6"} = "";}
          ${"debcg" .$i} = "<font color=\"".${"choix_cg" . $i}."\">";
          ${"fincg" . $i} = "</font>";
          $i++;
        }
        $entryOrd = "";
        $rtfOrd = array();
        $rtfSep = array();
        $rtfOrd[0] = $entryOrd;
        $rtfSep[0] = "";
        if (isset($resArray[$iRA]["GR"])) {$entryOrd .= $resArray[$iRA]["GR"];}
        $i = 1;
        while ($i <= 7) {//$i = Nombre de groupes
          $entryOrdi = "".${"deb" .$i . "_1"};
          $entryOrdi .= "".${"deb" .$i . "_2"};
          $entryOrdi .= "".${"deb" .$i . "_3"};
          $entryOrdi .= "".${"deb" .$i . "_4"};
          $entryOrdi .= "".${"deb" .$i . "_5"};
          $entryOrdi .= "".${"deb" .$i . "_6"};
          //$entryOrdi .= "".${"debcg" .$i};
          //$entryOrdf = "".${"fincg" .$i};
          $entryOrdf = "".${"fin" .$i . "_6"};
          $entryOrdf .= "".${"fin" .$i . "_5"};
          $entryOrdf .= "".${"fin" .$i . "_4"};
          $entryOrdf .= "".${"fin" .$i . "_3"};
          $entryOrdf .= "".${"fin" .$i . "_2"};
          $entryOrdf .= "".${"fin" .$i . "_1"};
          switch (${"gp" . $i}) {
            case "auteurs":
              if (isset($resArray[$iRA]["authors"])) {
                $entryAut = $resArray[$iRA]["authors"];
              }else{
                $entryAut = "";
              }
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emin~") !== false) {$entryAut = mb_strtolower($resArray[$iRA]["authors"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emaj~") !== false) {$entryAut = mb_strtoupper($resArray[$iRA]["authors"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~effa~") !== false) {$entryAut = "";}
              $rtfOrd[$i] = $entryOrdi.$entryAut.$entryOrdf;
              $entryOrd .= $entryOrdi.${"debcg" .$i};
              $entryOrd .= $entryAut;
              $entryOrd .= ${"fincg" .$i}.$entryOrdf;
              break;
            case "revue":
              if (isset($resArray[$iRA]["revue"])) {
                $entryRev = $resArray[$iRA]["revue"];
              }else{
                $entryRev = "";
              }
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emin~") !== false) {$entryRev = mb_strtolower($resArray[$iRA]["revue"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emaj~") !== false) {$entryRev = mb_strtoupper($resArray[$iRA]["revue"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~effa~") !== false) {$entryRev = "";}
              $rtfOrd[$i] = $entryOrdi.$entryRev.$entryOrdf;
              $entryOrd .= $entryOrdi.${"debcg" .$i};
              $entryOrd .= $entryRev;
              $entryOrd .= ${"fincg" .$i}.$entryOrdf;
              break;
            case "titre":
              if (isset($resArray[$iRA]["titre"])) {
                $entryTit = $resArray[$iRA]["titre"];
              }else{
                $entryTit = "";
              }
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emin~") !== false) {$entryTit = mb_strtolower($resArray[$iRA]["titre"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emaj~") !== false) {$entryTit = mb_strtoupper($resArray[$iRA]["titre"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~effa~") !== false) {$entryTit = "";}
              $rtfOrd[$i] = $entryOrdi.$entryTit.$entryOrdf;
              $entryOrd .= $entryOrdi.${"debcg" .$i};
              $entryOrd .= $entryTit;
              $entryOrd .= ${"fincg" .$i}.$entryOrdf;
              break;
            case "année":
              if (isset($resArray[$iRA]["annee"])) {
                $entryAnn = $resArray[$iRA]["annee"];
              }else{
                $entryAnn = "";
              }
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emin~") !== false) {$entryAnn = mb_strtolower($resArray[$iRA]["annee"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emaj~") !== false) {$entryAnn = mb_strtoupper($resArray[$iRA]["annee"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~effa~") !== false) {$entryAnn = "";}
              $rtfOrd[$i] = $entryOrdi.$entryAnn.$entryOrdf;
              $entryOrd .= $entryOrdi.${"debcg" .$i};
              $entryOrd .= $entryAnn;
              $entryOrd .= ${"fincg" .$i}.$entryOrdf;
              break;
            case "volume":
              if (isset($resArray[$iRA]["volume"])) {
                if ($_POST["stpdf"] == "zo1") {
                  $entryVol = "vol.".$resArray[$iRA]["volume"];
                }else{
                  $entryVol = $resArray[$iRA]["volume"];
                }
              }else{
                $entryVol = "";
              }
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emin~") !== false) {$entryVol = mb_strtolower($resArray[$iRA]["volume"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emaj~") !== false) {$entryVol = mb_strtoupper($resArray[$iRA]["volume"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~effa~") !== false) {$entryVol = "";}
              $rtfOrd[$i] = $entryOrdi.$entryVol.$entryOrdf;
              $entryOrd .= $entryOrdi.${"debcg" .$i};
              $entryOrd .= $entryVol;
              $entryOrd .= ${"fincg" .$i}.$entryOrdf;
              break;
            case "numéro":
              if (isset($resArray[$iRA]["issue"])) {
                if ($_POST["stpdf"] == "zo1") {
                  $entryNum = "n°".$resArray[$iRA]["issue"];
                }else{
                  $entryNum = $resArray[$iRA]["issue"];
                }
              }else{
                $entryNum = "";
              }
              if ($entryNum != "") {
                if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emin~") !== false) {$entryNum = mb_strtolower($resArray[$iRA]["issue"],'UTF-8');}
                if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emaj~") !== false) {$entryNum = mb_strtoupper($resArray[$iRA]["issue"],'UTF-8');}
                if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~effa~") !== false) {$entryNum = "";}
                $rtfOrd[$i] = $entryOrdi.$entryNum.$entryOrdf;
                $entryOrd .= $entryOrdi.${"debcg" .$i};
                $entryOrd .= $entryNum;
                $entryOrd .= ${"fincg" .$i}.$entryOrdf;
              }else{
                $rtfOrd[$i] = $entryOrdi.$entryNum.$entryOrdf;
              }
              break;
            case "pages":
              if (isset($resArray[$iRA]["page"])) {
                $entryPag = $resArray[$iRA]["page"];
              }else{
                $entryPag = "";
              }
              if ($entryPag == "") {$entryPag = "sans pagination";}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emin~") !== false) {$entryPag = mb_strtolower($resArray[$iRA]["page"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~emaj~") !== false) {$entryPag = mb_strtoupper($resArray[$iRA]["page"],'UTF-8');}
              if (isset(${"choix_mp" . $i}) && strpos(${"choix_mp" . $i}, "~effa~") !== false) {$entryPag = "";}
              $rtfOrd[$i] = $entryOrdi.$entryPag.$entryOrdf;
              $entryOrd .= $entryOrdi.${"debcg" .$i};
              $entryOrd .= $entryPag;
              $entryOrd .= ${"fincg" .$i}.$entryOrdf;
              break;
          }
          switch (${"sep" . $i}) {
            case " ":
              $entryOrd .= " ";
              $rtfSep[$i] = " ";
              break;
            case ", ":
              $entryOrd .= ", ";
              $rtfSep[$i] = ", ";
              break;
            case ". ":
              $entryOrd .= ". ";
              $rtfSep[$i] = ". ";
              break;
            case "; ":
              $entryOrd .= "; ";
              $rtfSep[$i] = "; ";
              break;
            case " - ":
              $entryOrd .= " - ";
              $rtfSep[$i] = " - ";
              break;
            case "":
              $entryOrd .= "";
              $rtfSep[$i] = "";
              break;
            case ": ":
              $entryOrd .= ": ";
              $rtfSep[$i] = ": ";
              break;
            //cas spéciaux
            case ", vol. ":
              $entryOrd .= ", vol. ";
              $rtfSep[$i] = ", vol. ";
              break;
            case ", Vol. ":
              $entryOrd .= ", Vol. ";
              $rtfSep[$i] = ", Vol. ";
              break;
            case ", no. ":
              $entryOrd .= ", no. ";
              $rtfSep[$i] = ", no. ";
              break;
            case ", No.":
              $entryOrd .= " , No.";
              $rtfSep[$i] = " , No.";
              break;
            case ", pp. ":
              $entryOrd .= ", pp. ";
              $rtfSep[$i] = ", pp. ";
              break;
            case ":":
              $entryOrd .= ":";
              $rtfSep[$i] = ":";
              break;
            case ";":
              $entryOrd .= ";";
              $rtfSep[$i] = ";";
              break;
          }
          $i++;
        }
        $rtfOrd[$i] = $entryInfo;
        //$entryInfo = $entryOrd . $entryInfo;
        $entryInfo = $entryOrd;
        $i = 0;
        $rtfInfo = "~|~";
        while($i < count($rtfSep)) {
          $rtfInfo .= $rtfOrd[$i]."~|~".$rtfSep[$i]."~|~";
          $i++;
        }
        $rtfInfo .= $rtfOrd[$i]."~|~";
      }else{
        //Concaténation
        $entryInfo = $entryInfo0 . $entryInfo;
        $rtfInfo = $entryInfo;
        $rtfInfo = str_replace("  ", " ", $rtfInfo);
      }
      //echo $entryOrd.'<br>';
      //echo $entryInfo.'<br>';
      //echo $rtfInfo.'<br>';
      //var_dump($rtfSep);

      //Thesis - adding nntId_s
      $rtfnnt = "";
      $chaine1 .= $delim."NNT";
      if ($docType_s=="THESE" && isset($entry->nntId_s)){
        $entryInfo .= ". NNT: <a target='_blank' href='http://www.theses.fr/".$entry->nntId_s."'>".$entry->nntId_s."</a>";
        $rtfnnt = $entry->nntId_s;
        $chaine2 .= $delim.$entry->nntId_s;
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding URL
      $rtfurl = "";
      $chaine1 .= $delim."URL";
      if (isset($entry->publisherLink_s[0]) && $typurl == "vis") {
        $entryInfo .= ". url: <a target='_blank' href='".$entry->publisherLink_s[0]."'>".$entry->publisherLink_s[0]."</a>";
        $entryInfo = str_replace(array(" . url", " , "), array(". url", ", "), $entryInfo);
        $rtfurl = $entry->publisherLink_s[0];
        $chaine2 .= $delim.$entry->publisherLink_s[0];
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding DOI
      $rtfdoi = "";
      $chaine1 .= $delim."DOI";
      if (isset($entry->doiId_s) && $typdoi == "vis") {
        //Est-ce un doublon et, si oui, faut-il l'afficher?
        $deb = "";
        $fin = "";
        if (stripos($listedoi, $entry->doiId_s) === false) {//non
          $listedoi .= "~".$entry->doiId_s;
        }else{
          if ($surdoi == "vis") {
            $deb = "<span style='background:#00FF00'><b>";
            $fin = "</b></span>";
          }
        }
        $entryInfo .= ". doi: <a target='_blank' href='https://doi.org/".$entry->doiId_s."'>".$deb."https://doi.org/".$entry->doiId_s.$fin."</a>";
        $entryInfo = str_replace(array(" . doi", " , "), array(". doi", ", "), $entryInfo);
        $rtfdoi = $entry->doiId_s;
        $chaine2 .= $delim.$entry->doiId_s;
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding Pubmed ID
      $rtfpubmed = "";
      $chaine1 .= $delim."Pubmed";
      if (isset($entry->pubmedId_s)) {
        $entryInfo .= ". Pubmed: <a target='_blank' href='http://www.ncbi.nlm.nih.gov/pubmed/".$entry->pubmedId_s."'>".$entry->pubmedId_s."</a>";
        $rtfpubmed = $entry->pubmedId_s;
        $chaine2 .= $delim.$entry->pubmedId_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding localReference_s
      $rtflocref = "";
      $chaine1 .= $delim."Référence";
      if ($docType_s=="UNDEF" && isset($entry->localReference_s)) {
        $entryInfo .= ". Référence: ".$entry->localReference_s[0];
        $rtflocref = $entry->localReference_s[0];
        $chaine2 .= $delim.$entry->localReference_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Adding ArXiv ID
      $rtfarxiv = "";
      $chaine1 .= $delim."ArXiv";
      if (isset($entry->arxivId_s) && $typidh != "vis") {
        $entryInfo .= ". ArXiv: <a target='_blank' href='http://arxiv.org/abs/".$entry->arxivId_s."'>".$entry->arxivId_s."</a>";
        $rtfarxiv = $entry->arxivId_s;
        $chaine2 .= $delim.$entry->arxivId_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding description_s
      $rtfdescrip = "";
      $chaine1 .= $delim."Description";
      if ($docType_s=="OTHER" && isset($entry->description_s)) {
        $entryInfo .= ". ".ucfirst($entry->description_s);
        $rtfdescrip = $entry->description_s;
        $chaine2 .= $delim.$entry->description_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding seeAlso_s
      $rtfalso = "";
      $chaine1 .= $delim."Voir aussi";
      if (($docType_s=="PATENT" || $docType_s=="REPORT" || $docType_s=="UNDEF" || $docType_s=="OTHER") && isset($entry->seeAlso_s)) {
        $entryInfo .= ". URL: <a target='_blank' href='".$entry->seeAlso_s[0]."'>".$entry->seeAlso_s[0]."</a>";
        $rtfalso = $entry->seeAlso_s[0];
        $chaine2 .= $delim.$entry->seeAlso_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Adding référence HAL
      $rtfrefhal = "";
      $chaine1 .= $delim."Réf. HAL";
      if (isset($entry->halId_s) && $typidh == "vis") {
        $entryInfo .= ". Réf. HAL: <a target='_blank' href='".$racine.$entry->halId_s."'>".$entry->halId_s."</a>";
        $rtfrefhal = $entry->halId_s;
        $chaine2 .= $delim.$entry->halId_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding rang HCERES (Economie-Gestion)
      $rtfaeres = "";
      $chaine1 .= $delim."Rang HCERES (Economie-Gestion)";
      if ($docType_s=="ART" && isset($entry->journalIssn_s) && $typreva == "vis") {
        foreach($AERES_SHS AS $i => $valeur) {
          if (($AERES_SHS[$i]['issn'] == $entry->journalIssn_s) && ($AERES_SHS[$i]['rang'] != "")) {
            $entryInfo .= ". Rang HCERES: ".$AERES_SHS[$i]['rang'];
            $rtfaeres = $AERES_SHS[$i]['rang'];
            $chaine2 .= $delim.$AERES_SHS[$i]['rang'];
            break;
          }
        }
        if ($rtfaeres == "") {$chaine2 .= $delim;}
      }else{
        $chaine2 .= $delim;
      }

      //Adding rang HCERES (Toutes disciplines)
      $rtfhceres = "";
      $chaine1 .= $delim."Rang HCERES (Toutes disciplines)";
      if ($docType_s=="ART" && isset($entry->journalIssn_s) && $typrevh == "vis") {
        foreach($AERES_HCERES AS $i => $valeur) {
          if (($AERES_HCERES[$i]['issn'] == $entry->journalIssn_s) && ($AERES_HCERES[$i][$dscp] != "0")) {
            $entryInfo .= ". Revue classée HCERES";
            $rtfhceres = ". Revue classée HCERES";
            $chaine2 .= $delim."oui";
            break;
          }
        }
        if ($rtfhceres == "") {$chaine2 .= $delim;}
      }else{
        $chaine2 .= $delim;
      }

      //Adding rang CNRS
      $rtfcnrs = "";
      $chaine1 .= $delim."Rang CNRS";
      if ($docType_s=="ART" && $typrevc == "vis") {
        foreach($CNRS AS $i => $valeur) {
          if (($CNRS[$i]['titre'] == $entry->journalTitle_s) && ($CNRS[$i]['rang'] != "")) {
            $entryInfo .= ". Rang CNRS: ".$CNRS[$i]['rang'];
            $rtfcnrs = $CNRS[$i]['rang'];
            $chaine2 .= $delim.$CNRS[$i]['rang'];
            break;
          }
        }
        if ($rtfcnrs == "") {$chaine2 .= $delim;}
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding Impact Factor
      $rtfif = "";
      if (isset($typif) && $typif == "vis") {
        if ($JT != "") {
          $IF = "";
          if (file_exists("./pvt/JCR.php")) {
            include "./pvt/JCR.php";
            foreach($JCR_LISTE AS $i => $valeur) {
              if (normalize(strtoupper(str_replace('&', 'and', $JCR_LISTE[$i]["Full Journal Title"]))) == normalize(strtoupper(str_replace('&', 'and', $JT)))) {$IF = $JCR_LISTE[$i]["Journal Impact Factor"];}
            }
          }else{
            $IF = "<i>unkwown</i>";
          }
          $chaine1 .= $delim."IF";
          $resArray[$iRA]["IF"] = $IF;
          if ($IF != ""){
            $entryInfo .= ". IF=".$IF;
            $rtfif = $IF;
            $chaine2 .= $delim.$IF;
          }else{
            $chaine2 .= $delim;
          }
        }
      }

      //Corrections diverses
      $entryInfo =str_replace("..", ".", $entryInfo);
      $entryInfo =str_replace(", .", ".", $entryInfo);
      $entryInfo =str_replace(",,", ",", $entryInfo);
      $entryInfo =str_replace(", , ", ", ", $entryInfo);
      $entryInfo =str_replace(" : ", ": ", $entryInfo);
      $entryInfo =str_replace(", No.,", ",", $entryInfo);
      $entryInfo =str_replace(", no.,", ",", $entryInfo);
      $rtfInfo =str_replace("..", ".", $rtfInfo);
      $rtfInfo =str_replace(",,", ",", $rtfInfo);
      $rtfInfo =str_replace(", .", ".", $rtfInfo);
      $rtfInfo =str_replace("~|~, ~|~~|~, ~|~", "~|~, ~|~", $rtfInfo);

      if (!isset($entry->page_s)) {
        $entryInfo = str_replace(array(",  in press", " in press.", " in press", "; in press"), "", $entryInfo);
        $rtfInfo = str_replace(array(",  in press", " in press.", " in press", "; in press"), "", $rtfInfo);
      }

      //Adding the reference to the array
      array_push($infoArray,$entryInfo);
      //if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {
        //créer un tableau avec GR1,2,3... + (10000 - année) + premier auteur + année et faire un tri ensuite dessus ?
        //if($typchr == "decr") {//ordre chronologique décroissant
         //array_push($sortArray,substr(10000-($dateprod),0,5)."-".$eqpgr."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$dateprod);
        //}else{
          //array_push($sortArray,substr($dateprod,0,5)."-".$eqpgr."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$dateprod);
        //}
      //}else{
        if($typtri == "premierauteur") {
          if($typchr == "decr") {//ordre chronologique décroissant
            array_push($sortArray,substr(10000-($dateprod),0,5)."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$dateprod);
          }else{
            array_push($sortArray,substr($dateprod,0,5)."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$dateprod);
          }
        }else{
          if($typchr == "decr") {//ordre chronologique décroissant
            array_push($sortArray,substr(10000-($dateprod),0,5)."-".$entry->journalTitle_s."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$dateprod);
          }else{
            array_push($sortArray,substr($dateprod,0,5)."-".$entry->journalTitle_s."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$dateprod);
          }
        }
      //}
      //array_push($sortArray,$dateprod);

      //Récupération du préfixe AERES pour affichage éventuel
      $affprefeq = "";
      if (isset($entry->popularLevel_s)) {
        if ($entry->popularLevel_s == 1) {$affprefeq = "PV";}
        if ($entry->popularLevel_s == 0) {
          if ($docType_s == "ART") {
            if ($entry->peerReviewing_s == 0) {
              $affprefeq = "ASCL";
            }else{
              $affprefeq = "ACL";
            }
          }
          if ($docType_s == "PATENT") {$affprefeq = "BRE";}
          if ($docType_s == "COMM") {
            if ($entry->invitedCommunication_s == 1) {$affprefeq = "C-INV";}
            if ($entry->proceedings_s == 1) {
              if ($entry->audience_s == 2) {
                $affprefeq = "C-ACTI";
              }else{
                $affprefeq = "C-ACTN";
              }
            }
            if ($entry->proceedings_s == 0) {$affprefeq = "C-COM";}
          }
          if ($docType_s == "POSTER") {$affprefeq = "C-AFF";}
          if ($docType_s == "DOUV") {$affprefeq = "DO";}
          if ($docType_s == "OUV" || $docType_s == "COUV") {$affprefeq = "OS";}
          //$affprefeq = "Toto";
        }
        if ($affprefeq == "") {$affprefeq = "AP";}
      }

      array_push($rtfArray,$rtfInfo."^".$rtfdoi."^".$rtfpubmed."^".$rtflocref."^".$rtfarxiv."^".$rtfdescrip."^".$rtfalso."^".$rtfrefhal."^".$rtfaeres."^".$rtfcnrs."^".$chaine1."^".$chaine2."^".$rtfnnt."^".$affprefeq."^".$racine."^".$rtfhceres."^".$rtfif."^".$rtfurl);
      //bibtex
      $bibLab = "";
      //if (isset($entry->label_bibtex)) {$bibLab = $entry->label_bibtex;}
      $type = $entry->docType_s;
      switch($type)
        {
          case "ART":
            $typebib = "article";
            break;
          case "COMM":
            $typebib = "inproceedings";
            break;
          case "COUV":
            $typebib = "inbook";
            break;
          case "THESE":
            $typebib = "phdthesis";
            break;
          case "UNDEFINED":
            $typebib = "unpublished";
            break;
          case "OTHER":
            $typebib = "misc";
            break;
          case "OUV":
            $typebib = "book";
            break;
          case "DOUV":
            $typebib = "proceedings";
            break;
          case "MEM":
            $typebib = "masterthesis";
            break;
          case "POSTER":
            $typebib = "poster";
            break;
          case "HDR":
            $typebib = "phdthesis";
            break;
          case "PATENT":
            $typebib = "patent";
            break;
          case "PRESCONF":
            $typebib = "presconf";
            break;
        }
      $bibLab .= chr(13).chr(10)."@".$typebib."{";
      $auteurs = $entry->authLastName_s[0];
      $bibLab .= mb_strtolower(str_replace(" ", "_", $auteurs), 'UTF-8');
      $titre = explode(" ", $entry->title_s[0]);
      $bibLab .= "_".mb_strtolower(str_replace(array("(", ")", ","), "_", $titre[0]), 'UTF-8');
      //add a constant to differenciate same initial identifier
      if (isset($auteurs) && isset($titre))
      {
        $tit = mb_strtolower(str_replace(" ", "_", $auteurs), 'UTF-8')."_".mb_strtolower(str_replace(array("(", ")", ","), "_", $titre[0]), 'UTF-8');
        if (strpos($listTit, "¤".$tit."¤") !== false)
        {
          $cst++;
          $chaine .= $cst;
        }
        $listTit .= $tit."¤";
      }
      if (isset($entry->producedDateY_i)) {$bibLab .= "_".mb_strtolower($entry->producedDateY_i, 'UTF-8');}
      if (isset($entry->title_s[0])) {$bibLab .= ",".chr(13).chr(10)."	title = {".$entry->title_s[0];}
      if (isset($entry->subTitle_s[0])) {$bibLab .= " : ".$entry->subTitle_s[0];}
      if (isset($entry->title_s[0])) {$bibLab .= "}";}
      if (isset($entry->volume_s)) {$bibLab .= ",".chr(13).chr(10)."	volume = {".$entry->volume_s."}";}
      if (isset($entry->journalIssn_s)) {$bibLab .= ",".chr(13).chr(10)."	issn = {".$entry->journalIssn_s."}";}
      if (isset($entry->publisherLink_s[0])) {$bibLab .= ",".chr(13).chr(10)."	url = {".$entry->publisherLink_s[0]."}";}
      if (isset($entry->doiId_s)) {$bibLab .= ",".chr(13).chr(10)."	doi = {".$entry->doiId_s."}";}
      if (isset($entry->abstract_s)) {$bibLab .= ",".chr(13).chr(10)."	abstract = {".str_replace(array("{", "}"), "_", $entry->abstract_s)."}";}
      if (isset($entry->journalTitle_s)) {$bibLab .= ",".chr(13).chr(10)."	journal = {".$entry->journalTitle_s."}";}
      if (isset($authors)) {
        $auteurs = $authors;
        //add a comma after the name
        $i = 0;
        $autvirg = "";
        $esp = explode(", ", $auteurs);
        while ($i < count($esp))
        {
          $autesp = strrchr(trim($esp[$i]), " ");
          if ($i != count($esp) - 1)
          {
            $autvirg .= str_replace($autesp, ",".$autesp.",", $esp[$i]);
          }else{
            $autvirg .= str_replace($autesp, ",".$autesp, $esp[$i]);
          }
          $i++;
        }
        $auteurs = str_replace(".,", ". and ", $autvirg);
        $bibLab .= ",".chr(13).chr(10)."	author = {".$auteurs."}";
      }
      if (isset($entry->uri_s)) {$bibLab .= ",".chr(13).chr(10)."	url = {".$entry->uri_s."}";}
      if (isset($entry->page_s)) {$bibLab .= ",".chr(13).chr(10)."	pages = {".$entry->page_s."}";}
      if (isset($entry->funding_s)) {$bibLab .= ",".chr(13).chr(10)."	x-funding = {".$entry->funding_s."}";}
      if (isset($entry->pubmedId_s)) {$bibLab .= ",".chr(13).chr(10)."	pmid = {".$entry->pubmedId_s."}";}
      if (isset($entry->publisher_s)) {$bibLab .= ",".chr(13).chr(10)."	publisher = {".$entry->publisher_s[0]."}";}
      if (isset($entry->producedDateY_i)) {$bibLab .= ",".chr(13).chr(10)."	year = {".$entry->producedDateY_i."}";}
      if (isset($entry->keyword_s)) {$bibLab .= ",".chr(13).chr(10)."	keywords = {".$entry->keyword_s."}";}
      if (isset($entry->halId_s)) {$bibLab .= ",".chr(13).chr(10)."	HAL_id = {".$entry->halId_s."}";}
      if (isset($entry->peerReviewing_s)) {$bibLab .= ",".chr(13).chr(10)."	peer_reviewing = {".$entry->peerReviewing_s."}";}
      if (isset($entry->audience_s)) {$bibLab .= ",".chr(13).chr(10)."	audience = {".$entry->audience_s."}";}
      if (isset($entry->proceedings_s)) {$bibLab .= ",".chr(13).chr(10)."	proceedings = {".$entry->proceedings_s."}";}
      if (isset($entry->invitedCommunication_s)) {$bibLab .= ",".chr(13).chr(10)."	invited_communication = {".$entry->invitedCommunication_s."}";}
      if (isset($entry->version_i)) {$bibLab .= ",".chr(13).chr(10)."	HAL_version = {v".$entry->version_i."}";}
      //$bibPR = "";
      //if (isset($entry->peerReviewing_s)) {$bibPR = $entry->peerReviewing_s;}
      //$bibAud = "";
      //if (isset($entry->audience_s)) {$bibAud = $entry->audience_s;}
      //$bibPro = "";
      //if (isset($entry->proceedings_s)) {$bibPro = $entry->proceedings_s;}
      //$bibInv = "";
      //if (isset($entry->invitedCommunication_s)) {$bibInv = $entry->invitedCommunication_s;}
      //array_push($bibArray,$bibLab."¤".$bibPR."¤".$bibAud."¤".$bibPro."¤".$bibInv);
      $bibLab .= ",".chr(13).chr(10)."}";
      array_push($bibArray,$bibLab);
   $iRA++;
   }
   $result=array();
   array_push($result,$infoArray);
   array_push($result,$sortArray);
   array_push($result,$rtfArray);
   array_push($result,$bibArray);
   array_push($result,$resArray);
   //var_dump($rtfArray);
   return $result;
}

function toAppear($string){
   $toAppear=0;
   if (strtolower($string)=="accepted" or strtolower($string)=="accepté" or strtolower($string)=="to appear" or strtolower($string)=="accepted manuscript"){
      $toAppear=1;
   }
   return $toAppear;
}

function mpcg($sect, $groupe, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7) {//fonction pour ordonner les groupes et attribuer les couleurs au RTF
   $tabgp = explode("~|~", $groupe);
   //var_dump($tabgp);
   $font = new PHPRtfLite_Font(9, 'Trebuchet', '#000000', '#FFFFFF');
   $fontgp1 = new PHPRtfLite_Font(9, 'Trebuchet', $choix_cg1, '#FFFFFF');
   $fontgp2 = new PHPRtfLite_Font(9, 'Trebuchet', $choix_cg2, '#FFFFFF');
   $fontgp3 = new PHPRtfLite_Font(9, 'Trebuchet', $choix_cg3, '#FFFFFF');
   $fontgp4 = new PHPRtfLite_Font(9, 'Trebuchet', $choix_cg4, '#FFFFFF');
   $fontgp5 = new PHPRtfLite_Font(9, 'Trebuchet', $choix_cg5, '#FFFFFF');
   $fontgp6 = new PHPRtfLite_Font(9, 'Trebuchet', $choix_cg6, '#FFFFFF');
   $fontgp7 = new PHPRtfLite_Font(9, 'Trebuchet', $choix_cg7, '#FFFFFF');
   if (isset($tabgp[3])) {$sect->writeText($tabgp[3], $fontgp1);}//1er groupe
   if (isset($tabgp[4])) {$sect->writeText($tabgp[4], $font);}//1er séparateur
   if (isset($tabgp[5])) {$sect->writeText($tabgp[5], $fontgp2);}//2ème groupe
   if (isset($tabgp[6])) {$sect->writeText($tabgp[6], $font);}//2ème séparateur
   if (isset($tabgp[7])) {$sect->writeText($tabgp[7], $fontgp3);}//3ème groupe
   if (isset($tabgp[8])) {$sect->writeText($tabgp[8], $font);}//3ème séparateur
   if (isset($tabgp[9])) {$sect->writeText($tabgp[9], $fontgp4);}//4ème groupe
   if (isset($tabgp[10])) {$sect->writeText($tabgp[10], $font);}//4ème séparateur
   if (isset($tabgp[11])) {$sect->writeText($tabgp[11], $fontgp5);}//5ème groupe
   if (isset($tabgp[12])) {$sect->writeText($tabgp[12], $font);}//5ème séparateur
   if (isset($tabgp[13])) {$sect->writeText($tabgp[13], $fontgp6);}//6ème groupe
   if (isset($tabgp[14])) {$sect->writeText($tabgp[14], $font);}//6ème séparateur
   if (isset($tabgp[15])) {$sect->writeText($tabgp[15], $fontgp7);}//7ème groupe
   if (isset($tabgp[16])) {$sect->writeText($tabgp[16], $font);}//7ème séparateur
   if (isset($tabgp[17])) {$sect->writeText($tabgp[17], $font);}//suite
}

function displayRefList($docType_s,$collCode_s,$specificRequestCode,$countries,$refType,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7){
   $infoArray = array();
   $sortArray = array();
   $rtfArray = array();
   $bibArray = array();
   $resArray = array();

   if ($docType_s=="COMPOSTER"){
      //Request on a union of HAL types
      //COMM ACTI
      $result = getReferences($infoArray,$resArray,$sortArray,"COMM",$collCode_s,"%20AND%20proceedings_s:1%20AND%20audience_s:2".$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
      //$result = getReferences($infoArray,$resArray,$sortArray,"COMM",$collCode_s,$specificRequestCode,$countries);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
      $bibArray = $result[3];
      $resArray = $result[4];
      //COMM ACTN
      $result = getReferences($infoArray,$resArray,$sortArray,"COMM",$collCode_s,"%20AND%20proceedings_s:1%20AND%20audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0".$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
      //$result = getReferences($infoArray,$resArray,$sortArray,"COMM",$collCode_s,$specificRequestCode,$countries);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
      $bibArray = $result[3];
      $resArray = $result[4];
      //COMM COM
      $specificRequestCode = '%20AND%20proceedings_s:0';
      $result = getReferences($infoArray,$resArray,$sortArray,"COMM",$collCode_s,"%20AND%20proceedings_s:0".$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
      //$result = getReferences($infoArray,$resArray,$sortArray,"COMM",$collCode_s,$specificRequestCode,$countries);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
      $bibArray = $result[3];
      $resArray = $result[4];
      //COMM POSTER
      $result = getReferences($infoArray,$resArray,$sortArray,"POSTER",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
      $bibArray = $result[3];
      $resArray = $result[4];
   } else {
      if ($docType_s=="VULG"){
      //Request on a union of HAL types
         $result = getReferences($infoArray,$resArray,$sortArray,"COUV",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
         $infoArray = $result[0];
         $sortArray = $result[1];
         $rtfArray = $result[2];
         $bibArray = $result[3];
         $resArray = $result[4];
         $result = getReferences($infoArray,$resArray,$sortArray,"OUV",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
         $infoArray = $result[0];
         $sortArray = $result[1];
         $rtfArray = $result[2];
         $bibArray = $result[3];
         $resArray = $result[4];
      } else {
         if ($docType_s=="OTHER"){
         //Request on a union of HAL types
            $result = getReferences($infoArray,$resArray,$sortArray,"OTHER",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
            $infoArray = $result[0];
            $sortArray = $result[1];
            $rtfArray = $result[2];
            $bibArray = $result[3];
            $resArray = $result[4];
            $result = getReferences($infoArray,$resArray,$sortArray,"OTHERREPORT",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
            $infoArray = $result[0];
            $sortArray = $result[1];
            $rtfArray = $result[2];
            $bibArray = $result[3];
            $resArray = $result[4];
         } else {
            //Request on a simple HAL type
            $result = getReferences($infoArray,$resArray,$sortArray,$docType_s,$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
            $infoArray = $result[0];
            $sortArray = $result[1];
            $rtfArray = $result[2];
            $bibArray = $result[3];
            $resArray = $result[4];
            //var_dump($result[1]);
         }
      }
   }

   array_multisort($sortArray, $infoArray, $rtfArray, $resArray);

   $currentYear="99999";
   $i = 0;
   $ind = 0;
   static $indgr = array();
   static $crogr = array();
   static $drefl = array();
   for ($j = 1; $j <= $nbeqp; $j++) {
     if (isset($drefl[0]) && $drefl[0] == "") {
       $indgr[$j] = 1;
       $crogr[$j] = 0;
     }else{
       $indgr[$j] = 1;
       $crogr[$j] = "";
     }
   }

   $yearNumbers = array();

   foreach($infoArray as $entryInfo){
     if($typcro == "oui") {//afficher seulement les publications croisées
       $aff = "non";//critère d'affichage (ou non) des résultats
     }else{
       $aff = "oui";
     }
      if (strcmp($currentYear,substr($sortArray[$i],-4))==0){ // Même année
         $rtf = explode("^", $rtfArray[$i]);
         if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {//GR
           $rtfval = $rtf[0];
           $rtfcha = $rtf[11];
           for ($j = 1; $j <= $nbeqp; $j++) {
             if (strpos($entryInfo,"GR".$j." - ¤ -") !== false) {
               $entryInfo = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $entryInfo);
               $rtfval = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $rtfval);
               $rtfcha = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j], $rtfcha);
               if (strpos($entryInfo, " - GR") !== false) {//publication croisée
                 $crogr[$j] += 1;
                 $aff = "oui";
               }
               if ($aff == "oui") {$indgr[$j] += 1;}
             }
           }
         }
         for ($j = 1; $j <= $nbeqp; $j++) {
           $entryInfo = str_replace("GR".$j, $nomeqp[$j], $entryInfo);
           $rtfval = str_replace("GR".$j, $nomeqp[$j], $rtfval);
           $rtfcha = str_replace("GR".$j, $nomeqp[$j], $rtfcha);
         }
         if ($aff == "oui") {
           if ($typnum == "vis") {
             $ind += 1;
             echo "<p>".$ind.". ";
             if ($prefeq == "oui") {echo $rtf[13]." - ";}//Affichage préfixe AERES
             echo $entryInfo."</p>";
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){//GR
               if ($prefeq == "oui") {//Ecriture préfixe AERES
                 $sect->writeText($ind.". ".$rtf[13]." - ", $font);
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }else{
                 $sect->writeText($ind.". ", $font);
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }
             }else{
               $sect->writeText($ind.". ", $font);
               if (strpos($rtf[0], "~|~") !== false) {//Ordre à respecter
                 mpcg($sect, $rtf[0], $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
               }else{
                 $sect->writeText($rtf[0], $font);
               }
             }
           }else{
             echo "<p>";
             if (isset($nmo) && $nmo == "sim") {$ind += 1; echo $ind.". "; $sect->writeText($ind.". ", $font);}
             if (isset($nmo) && $nmo == "par") {$ind += 1; echo "(".$ind.") "; $sect->writeText("(".$ind.") ", $font);}
             if (isset($nmo) && $nmo == "cro") {$ind += 1; echo "[".$ind."] "; $sect->writeText("[".$ind."] ", $font);}
             if ($prefeq == "oui") {echo $rtf[13]." - ";}
             echo $entryInfo."</p>";
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){//GR
               if ($prefeq == "oui") {//Ecriture préfixe AERES
                 $sect->writeText($rtf[13]." - ", $font);
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }else{
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }
             }else{
               if (strpos($rtf[0], "~|~") !== false) {//Ordre à respecter
                 mpcg($sect, $rtf[0], $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
               }else{
                 $sect->writeText($rtf[0], $font);
               }
             }
           }
           if ($rtf[17] != "") {
              $sect->writeText(". url: ", $font);
              $sect->writeHyperLink($rtf[17], "<u>".$rtf[17]."</u>", $fontlien);
           }
           if ($rtf[1] != "") {
              $sect->writeText(". doi: ", $font);
              $sect->writeHyperLink("https://doi.org/".$rtf[1], "<u>https://doi.org/".$rtf[1]."</u>", $fontlien);
           }
           if ($rtf[12] != "") {
              $sect->writeText(". NNT: ", $font);
              $sect->writeHyperLink("http://www.theses.fr/".$rtf[12], "<u>".$rtf[12]."</u>", $fontlien);
           }
           if ($rtf[2] != "") {
              $sect->writeText(". Pubmed: ", $font);
              $sect->writeHyperLink("http://www.ncbi.nlm.nih.gov/pubmed/".$rtf[2], "<u>".$rtf[2]."</u>", $fontlien);
           }
           if ($rtf[3] != "") {
              $sect->writeText(". Référence: ".$rtf[3], $font);
           }
           if ($rtf[4] != "") {
              $sect->writeText(". ArXiv: ", $font);
              $sect->writeHyperLink("http://arxiv.org/abs/".$rtf[4], "<u>".$rtf[4]."</u>", $fontlien);
           }
           if ($rtf[5] != "") {
              $sect->writeText(". ".ucfirst($rtf[5]), $font);
           }
           if ($rtf[6] != "") {
              $sect->writeText(". URL: ", $font);
              $sect->writeHyperLink($rtf[5], "<u>".$rtf[6]."</u>", $fontlien);
           }
           if ($rtf[7] != "") {
              $sect->writeText(". Réf. HAL: ", $font);
              $sect->writeHyperLink($rtf[14].$rtf[7], "<u>".$rtf[7]."</u>", $fontlien);
           }
           if ($rtf[8] != "") {
              $sect->writeText(". Rang HCERES: ".$rtf[8], $font);
           }
           if ($rtf[15] != "") {
              $sect->writeText($rtf[15], $font);
           }
           if ($rtf[9] != "") {
              $sect->writeText(". Rang CNRS: ".$rtf[9], $font);
           }
           if (isset($typif)) {
             if ($rtf[16] != "") {
                $sect->writeText(". IF=".$rtf[16], $font);
             }
           }
           $sect->writeText("<br><br>", $font);
           $yearNumbers[substr($sortArray[$i],-4)]+=1;
           //export CSV
           if ($i == 0) {
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {//GR
               $chaine = $rtf[10].chr(13).chr(10).$rtfcha.chr(13).chr(10);
             }else{
               $chaine = $rtf[10].chr(13).chr(10).$rtf[10].chr(13).chr(10);
             }
           }else{
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {//GR
               $chaine = $rtfcha.chr(13).chr(10);
             }else{
               $chaine = $rtf[11].chr(13).chr(10);
             }
           }
           if (isset($idhal) && $idhal != "") {$team = $idhal;}
           //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
           $inF1 = fopen($Fnm1,"a+");
           //fseek($inF1, 0);
           fwrite($inF1,$chaine);
         }
       }else{ //Année différente
         $rtf = explode("^", $rtfArray[$i]);
         echo "<h3>".substr($sortArray[$i],-4)."</h3>";
         $currentYear=substr($sortArray[$i],-4);
         $yearNumbers[$currentYear] = 0;
         $sect->writeText("<b>".substr($sortArray[$i],-4)."</b><br><br>", $fonth3);
         if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {//GR
           $rtfval = $rtf[0];
           $rtfcha = $rtf[11];
           for ($j = 1; $j <= $nbeqp; $j++) {
             if (strpos($entryInfo,"GR".$j." - ¤ -") !== false) {
               $entryInfo = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $entryInfo);
               $rtfval = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $rtfval);
               $rtfcha = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j], $rtfcha);
               if (strpos($entryInfo, " - GR") !== false) {//publication croisée
                 $crogr[$j] += 1;
                 $aff = "oui";
               }
               if ($aff == "oui") {$indgr[$j] += 1;}
             }
           }
         }
         for ($j = 1; $j <= $nbeqp; $j++) {
           $entryInfo = str_replace("GR".$j, $nomeqp[$j], $entryInfo);
           $rtfval = str_replace("GR".$j, $nomeqp[$j], $rtfval);
           $rtfcha = str_replace("GR".$j, $nomeqp[$j], $rtfcha);
         }
         if ($aff == "oui") {
           $yearNumbers[substr($sortArray[$i],-4)]=1;
           if ($typnum == "vis") {
             $ind += 1;
             echo "<p>".$ind.". ";
             if ($prefeq == "oui") {echo $rtf[13]." - ";}//Affichage préfixe AERES
             echo $entryInfo."</p>";
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){//GR
               if ($prefeq == "oui") {//Ecriture préfixe AERES
                 $sect->writeText($ind.". ".$rtf[13]." - ", $font);
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }else{
                 $sect->writeText($ind.". ", $font);
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }
             }else{
               $sect->writeText($ind.". ", $font);
               if (strpos($rtf[0], "~|~") !== false) {//Ordre à respecter
                 mpcg($sect, $rtf[0], $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
               }else{
                 $sect->writeText($rtf[0], $font);
               }
             }
           }else{
             echo "<p>";
             if (isset($nmo) && $nmo == "sim") {$ind += 1; echo $ind.". "; $sect->writeText($ind.". ", $font);}
             if (isset($nmo) && $nmo == "par") {$ind += 1; echo "(".$ind.") "; $sect->writeText("(".$ind.") ", $font);}
             if (isset($nmo) && $nmo == "cro") {$ind += 1; echo "[".$ind."] "; $sect->writeText("[".$ind."] ", $font);}
             if ($prefeq == "oui") {echo $rtf[13]." - ";}
             echo $entryInfo."</p>";
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){//GR
               if ($prefeq == "oui") {//Ecriture préfixe AERES
                 $sect->writeText($rtf[13]." - ", $font);
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }else{
                 if (strpos($rtfval, "~|~") !== false) {//Ordre à respecter
                   mpcg($sect, $rtfval, $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
                 }else{
                   $sect->writeText($rtfval, $font);
                 }
               }
             }else{
               if (strpos($rtf[0], "~|~") !== false) {//Ordre à respecter
                 mpcg($sect, $rtf[0], $choix_cg1, $choix_cg2, $choix_cg3, $choix_cg4, $choix_cg5, $choix_cg6, $choix_cg7);
               }else{
                 $sect->writeText($rtf[0], $font);
               }
             }
           }
           if ($rtf[17] != "") {
              $sect->writeText(". url: ", $font);
              $sect->writeHyperLink($rtf[17], "<u>".$rtf[17]."</u>", $fontlien);
           }
           if ($rtf[1] != "") {
              $sect->writeText(". doi: ", $font);
              $sect->writeHyperLink("https://doi.org/".$rtf[1], "<u>https://doi.org/".$rtf[1]."</u>", $fontlien);
           }
           if ($rtf[12] != "") {
              $sect->writeText(". NNT: ", $font);
              $sect->writeHyperLink("http://www.theses.fr/".$rtf[12], "<u>".$rtf[12]."</u>", $fontlien);
           }
           if ($rtf[2] != "") {
              $sect->writeText(". Pubmed: ", $font);
              $sect->writeHyperLink("http://www.ncbi.nlm.nih.gov/pubmed/".$rtf[2], "<u>".$rtf[2]."</u>", $fontlien);
           }
           if ($rtf[3] != "") {
              $sect->writeText(". Référence: ".$rtf[3], $font);
           }
           if ($rtf[4] != "") {
              $sect->writeText(". ArXiv: ", $font);
              $sect->writeHyperLink("http://arxiv.org/abs/".$rtf[4], "<u>".$rtf[4]."</u>", $fontlien);
           }
           if ($rtf[5] != "") {
              $sect->writeText(". ".ucfirst($rtf[5]), $font);
           }
           if ($rtf[6] != "") {
              $sect->writeText(". URL: ", $font);
              $sect->writeHyperLink($rtf[5], "<u>".$rtf[6]."</u>", $fontlien);
           }
           if ($rtf[7] != "") {
              $sect->writeText(". Réf. HAL: ", $font);
              $sect->writeHyperLink($rtf[14].$rtf[7], "<u>".$rtf[7]."</u>", $fontlien);
           }
           if ($rtf[8] != "") {
              $sect->writeText(". Rang HCERES: ".$rtf[8], $font);
           }
           if ($rtf[15] != "") {
              $sect->writeText($rtf[15], $font);
           }
           if ($rtf[9] != "") {
              $sect->writeText(". Rang CNRS: ".$rtf[9], $font);
           }
           if (isset($typif)) {
             if ($rtf[16] != "") {
                $sect->writeText(". IF=".$rtf[16], $font);
             }
           }
           $sect->writeText("<br><br>", $font);
           //export CSV
           if ($i == 0) {
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){
               $chaine = $rtf[10].chr(13).chr(10).$rtfcha.chr(13).chr(10);
             }else{
               $chaine = $rtf[10].chr(13).chr(10).$rtf[11].chr(13).chr(10);
             }
           }else{
             if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){
               $chaine = $rtfcha.chr(13).chr(10);
             }else{
               $chaine = $rtf[11].chr(13).chr(10);
             }
           }
           if (isset($idhal) && $idhal != "") {$team = $idhal;}
           //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
           $inF1 = fopen($Fnm1,"a+");
           //fseek($inF1, 0);
           fwrite($inF1,$chaine);
         }
      }
      //export bibtex
      $bib = explode("¤", $bibArray[$i]);
      $tex0 = $bib[0];
      //$tex = substr($bib[0], 0, (strlen($bib[0])-2));
      $tex1 = "";
      if (isset($bib[1])) {$tex1 .= "PEER_REVIEWING = {".$bib[1]."},\r\n";}
      if (isset($bib[2])) {$tex1 .= "  AUDIENCE = {".$bib[2]."},\r\n";}
      if (isset($bib[3])) {$tex1 .= "  PROCEEDINGS = {".$bib[3]."},\r\n";}
      if (isset($bib[4])) {$tex1 .= "  INVITED_COMMUNICATION = {".$bib[4]."},\r\n";}
      //$tex .= "}\r\n";
      $tex = str_replace("HAL_VERSION", $tex1."  HAL_VERSION", $tex0);
      //$Fnm2 = "./HAL/extractionHAL_".$team.".bib";
      $inF2 = fopen($Fnm2,"a+");
      fseek($inF2, 0);
      fwrite($inF2,$tex."\r\n");
      fclose($inF2);
      $i++;
   }
   if (isset($idhal) && $idhal != "") {$team = $idhal;}
   //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
   $inF1 = fopen($Fnm1,"a+");
   //fseek($inF, 0);
   fwrite($inF1,chr(13).chr(10));
   $drefl[0] = $yearNumbers;//le nombre de publications
   $drefl[1] = $crogr;//le nombre de publications croisées
   //return $yearNumbers;
   //var_dump($crogr);
   return $drefl;
}
?>

<?php
//List of country codes
$countries = array(
"af" => "Afghanistan",
"za" => "Afrique du Sud",
"al" => "Albanie",
"dz" => "Algérie",
"de" => "Allemagne",
"ad" => "Andorre",
"ao" => "Angola",
"ai" => "Anguilla",
"aq" => "Antarctique",
"ag" => "Antigua-et-Barbuda",
"an" => "Antilles Néerlandaises",
"sa" => "Arabie Saoudite",
"ar" => "Argentine",
"am" => "Arménie",
"aw" => "Aruba",
"au" => "Australie",
"at" => "Autriche",
"az" => "Azerbaïdjan",
"bs" => "Bahamas",
"bh" => "Bahreïn",
"bd" => "Bangladesh",
"bb" => "Barbade",
"be" => "Belgique",
"bz" => "Belize",
"bm" => "Bermudes",
"bt" => "Bhoutan",
"bo" => "Bolivie",
"ba" => "Bosnie-Herzégovine",
"bw" => "Botswana",
"bv" => "Bouvet Island",
"bn" => "Brunei",
"br" => "Brésil",
"bg" => "Bulgarie",
"bf" => "Burkina Faso",
"bi" => "Burundi",
"by" => "Biélorussie",
"bj" => "Bénin",
"kh" => "Cambodge",
"cm" => "Cameroun",
"ca" => "Canada",
"cv" => "Cap Vert",
"cl" => "Chili",
"cn" => "Chine",
"cy" => "Chypre",
"va" => "Cité du Vatican",
"co" => "Colombie",
"km" => "Comores",
"cg" => "Congo, République",
"cd" => "République Démocratique du Congo",
"kp" => "Corée du Nord",
"kr" => "Corée du Sud",
"cr" => "Costa Rica",
"hr" => "Croatie",
"cu" => "Cuba",
"cw" => "Curaçao",
"ci" => "Côte d'Ivoire",
"dk" => "Danemark",
"dj" => "Djibouti",
"dm" => "Dominique",
"eg" => "Égypte",
"ae" => "Émirats Arabes Unis",
"ec" => "Équateur",
"er" => "Érythrée",
"es" => "Espagne",
"ee" => "Estonie",
"us" => "États-Unis",
"et" => "Éthiopie",
"fj" => "Fidji",
"fi" => "Finlande",
"fr" => "France",
"fx" => "France métropolitaine",
"ga" => "Gabon",
"gm" => "Gambie",
"ps" => "Gaza",
"gh" => "Ghana",
"gi" => "Gibraltar",
"gd" => "Grenade",
"gl" => "Groenland",
"gr" => "Grèce",
"gp" => "Guadeloupe",
"gu" => "Guam",
"gt" => "Guatemala",
"gn" => "Guinée",
"gw" => "Guinée Bissau",
"gq" => "Guinée Équatoriale",
"gy" => "Guyana",
"gf" => "Guyane",
"ge" => "Géorgie",
"gs" => "Géorgie du Sud et les îles Sandwich du Sud",
"ht" => "Haïti",
"hn" => "Honduras",
"hk" => "Hong Kong",
"hu" => "Hongrie",
"im" => "Île de Man",
"ky" => "Îles Caïman",
"cx" => "Îles Christmas",
"cc" => "Îles Cocos",
"ck" => "Îles Cook",
"fo" => "Îles Féroé",
"gg" => "Îles Guernesey",
"hm" => "Îles Heardet McDonald",
"fk" => "Îles Malouines",
"mp" => "Îles Mariannes du Nord",
"mh" => "Îles Marshall",
"mu" => "Îles Maurice",
"um" => "Îles mineures éloignées des États-Unis",
"nf" => "Îles Norfolk",
"sb" => "Îles Salomon",
"tc" => "Îles Turques et Caïque",
"vi" => "Îles Vierges des États-Unis",
"vg" => "Îles Vierges du Royaume-Uni",
"in" => "Inde",
"id" => "Indonésie",
"ir" => "Iran",
"iq" => "Iraq",
"ie" => "Irlande",
"is" => "Islande",
"il" => "Israël",
"it" => "Italie",
"jm" => "Jamaïque",
"jp" => "Japon",
"je" => "Jersey",
"jo" => "Jordanie",
"kz" => "Kazakhstan",
"ke" => "Kenya",
"kg" => "Kirghizistan",
"ki" => "Kiribati",
"xk" => "Kosovo",
"kw" => "Koweït",
"la" => "Laos",
"ls" => "Lesotho",
"lv" => "Lettonie",
"lb" => "Liban",
"ly" => "Libye",
"lr" => "Liberia",
"li" => "Liechtenstein",
"lt" => "Lituanie",
"lu" => "Luxembourg",
"mo" => "Macao",
"mk" => "Macédoine",
"mg" => "Madagascar",
"my" => "Malaisie",
"mw" => "Malawi",
"mv" => "Maldives",
"ml" => "Mali",
"mt" => "Malte",
"ma" => "Maroc",
"mq" => "Martinique",
"mr" => "Mauritanie",
"yt" => "Mayotte",
"mx" => "Mexique",
"fm" => "Micronésie",
"md" => "Moldavie",
"mc" => "Monaco",
"mn" => "Mongolie",
"ms" => "Montserrat",
"me" => "Monténégro",
"mz" => "Mozambique",
"mm" => "Birmanie",
"na" => "Namibie",
"nr" => "Nauru",
"ni" => "Nicaragua",
"ne" => "Niger",
"ng" => "Nigeria",
"nu" => "Niue",
"no" => "Norvège",
"nc" => "Nouvelle Calédonie",
"nz" => "Nouvelle Zélande",
"np" => "Népal",
"om" => "Oman",
"ug" => "Ouganda",
"uz" => "Ouzbékistan",
"pk" => "Pakistan",
"pw" => "Palau",
"pa" => "Panama",
"pg" => "Papouasie-Nouvelle-Guinée",
"py" => "Paraguay",
"nl" => "Pays-Bas",
"ph" => "Philippines",
"pn" => "Pitcairn",
"pl" => "Pologne",
"pf" => "Polynésie Française",
"pr" => "Porto Rico",
"pt" => "Portugal",
"pe" => "Pérou",
"qa" => "Qatar",
"ro" => "Roumanie",
"gb" => "Royaume-Uni",
"ru" => "Russie",
"rw" => "Rwanda",
"cf" => "République Centraficaine",
"do" => "République Dominicaine",
"cz" => "République Tchèque",
"re" => "Réunion",
"eh" => "Sahara Occidental",
"bl" => "Saint Barthelemy",
"sh" => "Saint Hélène",
"kn" => "Saint Kitts et Nevis",
"mf" => "Saint Martin",
"sx" => "Saint Martin",
"pm" => "Saint Pierre et Miquelon",
"vc" => "Saint Vincent et les Grenadines",
"lc" => "Sainte Lucie",
"sv" => "Salvador",
"as" => "Samoa Américaines",
"ws" => "Samoa Occidentales",
"sm" => "San Marin",
"st" => "Sao Tomé et Principe",
"rs" => "Serbie",
"sc" => "Seychelles",
"sl" => "Sierra Léone",
"sg" => "Singapour",
"sk" => "Slovaquie",
"si" => "Slovénie",
"so" => "Somalie",
"sd" => "Soudan",
"lk" => "Sri Lanka",
"ss" => "Sud Soudan",
"ch" => "Suisse",
"sr" => "Surinam",
"se" => "Suède",
"sj" => "Svalbard et Jan Mayen",
"sz" => "Swaziland",
"sy" => "Syrie",
"sn" => "Sénégal",
"tj" => "Tadjikistan",
"tw" => "Taïwan",
"tz" => "Tanzanie",
"td" => "Tchad",
"tf" => "Terres Australes et Antarctique Françaises",
"ps" => "Territoires Palestiniens occupés",
"th" => "Thaïlande",
"tl" => "Timor-Leste",
"tg" => "Togo",
"tk" => "Tokelau",
"to" => "Tonga",
"tt" => "Trinité et Tobago",
"tn" => "Tunisie",
"tm" => "Turkménistan",
"tr" => "Turquie",
"tv" => "Tuvalu",
"io" => "Territoire Britannique de l'Océan Indien",
"ua" => "Ukraine",
"uy" => "Uruguay",
"vu" => "Vanuatu",
"ve" => "Venezuela",
"vn" => "Vietnam",
"wf" => "Wallis et Futuna",
"ye" => "Yémen",
"zm" => "Zambie",
"zw" => "Zimbabwe",
"xx" => "inconnu",
"zz" => "inconnu",);

$numbers=array();
//$team sert aussi bien à une collection qu'à un idhal
if (isset($idhal) && $idhal != "") {$team = $idhal;}
if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {
  //$sect->writeText(substr($sortArray[$i],-4)."<br><br>", $font);
  echo "<a name=\"TA\"></a><h2>Tous les articles (sauf vulgarisation) <a href=\"#sommaire\">&#8683;</a></h2>";
  $sect->writeText("<b>Tous les articles (sauf vulgarisation)</b><br><br>", $fonth2);
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Tous les articles (sauf vulgarisation)".chr(13).chr(10));
  list($numbers["TA"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"TA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ACL-") !== false) {
  echo "<a name=\"ACL\"></a><h2>Articles de revues à comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues à comité de lecture".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues à comité de lecture</b><br><br>", $fonth2);
  list($numbers["ACL"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20peerReviewing_s:1".$specificRequestCode,$countries,"ACL",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ASCL-") !== false) {
  echo "<a name=\"ASCL\"></a><h2>Articles de revues sans comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues sans comité de lecture".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues sans comité de lecture</b><br><br>", $fonth2);
  list($numbers["ASCL"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20peerReviewing_s:0".$specificRequestCode,$countries,"ASCL",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ARI-") !== false) {
  echo "<a name=\"ARI\"></a><h2>Articles de revues internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues internationales".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues internationales</b><br><br>", $fonth2);
  list($numbers["ARI"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"ARI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ARN-") !== false) {
  echo "<a name=\"ARN\"></a><h2>Articles de revues nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues nationales".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues nationales</b><br><br>", $fonth2);
  list($numbers["ARN"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20language_s:fr".$specificRequestCode,$countries,"ARN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRI-") !== false) {
  echo "<a name=\"ACLRI\"></a><h2>Articles de revues internationales à comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues internationales à comité de lecture".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues internationales à comité de lecture</b><br><br>", $fonth2);
  list($numbers["ACLRI"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr%20AND%20peerReviewing_s:1".$specificRequestCode,$countries,"ACLRI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRN-") !== false) {
  echo "<a name=\"ACLRN\"></a><h2>Articles de revues nationales à comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues nationales à comité de lecture".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues nationales à comité de lecture</b><br><br>", $fonth2);
  list($numbers["ACLRN"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20language_s:fr%20AND%20peerReviewing_s:1".$specificRequestCode,$countries,"ACLRN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRI-") !== false) {
  echo "<a name=\"ASCLRI\"></a><h2>Articles de revues internationales sans comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues internationales sans comité de lecture".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues internationales sans comité de lecture</b><br><br>", $fonth2);
  list($numbers["ASCLRI"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr%20AND%20peerReviewing_s:0".$specificRequestCode,$countries,"ASCLRI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRN-") !== false) {
  echo "<a name=\"ASCLRN\"></a><h2>Articles de revues nationales sans comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de revues nationales sans comité de lecture".chr(13).chr(10));
  list($numbers["ASCLRN"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20language_s:fr%20AND%20peerReviewing_s:0".$specificRequestCode,$countries,"ASCLRN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
  $sect->writeText("<b>Articles de revues nationales sans comité de lecture</b><br><br>", $fonth2);
}
if (isset($choix_publis) && strpos($choix_publis, "-AV-") !== false) {
  echo "<a name=\"AV\"></a><h2>Articles de vulgarisation <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Articles de vulgarisation".chr(13).chr(10));
  $sect->writeText("<b>Articles de vulgarisation</b><br><br>", $fonth2);
  list($numbers["AV"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:1".$specificRequestCode,$countries,"AV",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-TC-") !== false) {
  echo "<a name=\"TC\"></a><h2>Toutes les communications (sauf grand public) <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Toutes les communications (sauf grand public)".chr(13).chr(10));
  $sect->writeText("<b>Toutes les communications (sauf grand public)</b><br><br>", $fonth2);
  list($numbers["TC"],$crores) = displayRefList("COMM+POST",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"TC",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CA-") !== false) {
  echo "<a name=\"CA\"></a><h2>Communications avec actes <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications avec actes".chr(13).chr(10));
  $sect->writeText("<b>Communications avec actes</b><br><br>", $fonth2);
  list($numbers["CA"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:1".$specificRequestCode,$countries,"CA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CSA-") !== false) {
  echo "<a name=\"CSA\"></a><h2>Communications sans actes <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications sans actes".chr(13).chr(10));
  $sect->writeText("<b>Communications sans actes</b><br><br>", $fonth2);
  list($numbers["CSA"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:0".$specificRequestCode,$countries,"CSA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CI-") !== false) {
  echo "<a name=\"CI\"></a><h2>Communications internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications internationales</b><br><br>", $fonth2);
  list($numbers["CI"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"CI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CN-") !== false) {
  echo "<a name=\"CN\"></a><h2>Communications nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications nationales</b><br><br>", $fonth2);
  list($numbers["CN"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"CN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CAI-") !== false) {
  echo "<a name=\"CAI\"></a><h2>Communications avec actes internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications avec actes internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications avec actes internationales</b><br><br>", $fonth2);
  list($numbers["CAI"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:1%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"CAI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CSAI-") !== false) {
  echo "<a name=\"CSAI\"></a><h2>Communications sans actes internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications sans actes internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications sans actes internationales</b><br><br>", $fonth2);
  list($numbers["CSAI"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"CSAI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CAN-") !== false) {
  echo "<a name=\"CAN\"></a><h2>Communications avec actes nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications avec actes nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications avec actes nationales</b><br><br>", $fonth2);
  list($numbers["CAN"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:1%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"CAN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CSAN-") !== false) {
  echo "<a name=\"CSAN\"></a><h2>Communications sans actes nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications sans actes nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications sans actes nationales</b><br><br>", $fonth2);
  list($numbers["CSAN"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"CSAN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINVASANI-") !== false) {
  echo "<a name=\"CINVA\"></a><h2>Communications invitées avec ou sans actes, nationales ou internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications invitées avec ou sans actes, nationales ou internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées avec ou sans actes, nationales ou internationales</b><br><br>", $fonth2);
  list($numbers["CINVASANI"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:1".$specificRequestCode,$countries,"CINVASANI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINVA-") !== false) {
  echo "<a name=\"CINVA\"></a><h2>Communications invitées avec actes <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications invitées avec actes".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées avec actes</b><br><br>", $fonth2);
  list($numbers["CINVA"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:1%20AND%20proceedings_s:1".$specificRequestCode,$countries,"CINVA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINVSA-") !== false) {
  echo "<a name=\"CINVSA\"></a><h2>Communications invitées sans actes <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications invitées sans actes".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées sans actes</b><br><br>", $fonth2);
  list($numbers["CINVSA"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:1%20AND%20proceedings_s:0".$specificRequestCode,$countries,"CINVSA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVA-") !== false) {
  echo "<a name=\"CNONINVA\"></a><h2>Communications non invitées avec actes<a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications non invitées avec actes".chr(13).chr(10));
  $sect->writeText("<b>Communications non invitées avec actes</b><br><br>", $fonth2);
  list($numbers["CNONINVA"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:0%20AND%20proceedings_s:1".$specificRequestCode,$countries,"CNONINVA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVSA-") !== false) {
  echo "<a name=\"CNONINVSA\"></a><h2>Communications non invitées sans actes<a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications non invitées sans actes".chr(13).chr(10));
  $sect->writeText("<b>Communications non invitées sans actes</b><br><br>", $fonth2);
  list($numbers["CNONINVSA"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:0%20AND%20proceedings_s:0".$specificRequestCode,$countries,"CNONINVSA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINVI-") !== false) {
  echo "<a name=\"CINVI\"></a><h2>Communications invitées internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications invitées internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées internationales</b><br><br>", $fonth2);
  list($numbers["CINVI"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:1%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"CINVI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVI-") !== false) {
  echo "<a name=\"CNONINVI\"></a><h2>Communications non invitées internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications non invitées internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications non invitées internationales</b><br><br>", $fonth2);
  list($numbers["CNONINVI"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"CNONINVI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINVN-") !== false) {
  echo "<a name=\"CINVN\"></a><h2>Communications invitées nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications invitées nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées nationales</b><br><br>", $fonth2);
  list($numbers["CINVN"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:1%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"CINVN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVN-") !== false) {
  echo "<a name=\"CNONINVN\"></a><h2>Communications non invitées nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications non invitées nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications non invitées nationales</b><br><br>", $fonth2);
  list($numbers["CNONINVN"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0%20AND%20invitedCommunication_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"CNONINVN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CPASANI-") !== false) {
  echo "<a name=\"CPASANI\"></a><h2>Communications par affiches (posters) avec ou sans actes, nationales ou internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications par affiches (posters) avec ou sans actes, nationales ou internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches (posters) avec ou sans actes, nationales ou internationales</b><br><br>", $fonth2);
  list($numbers["CPASANI"],$crores) = displayRefList("POSTER",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"CPASANI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CPA-") !== false) {
  echo "<a name=\"CPA\"></a><h2>Communications par affiches (posters) avec actes <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications pas affiches (posters)".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches (posters) avec actes</b><br><br>", $fonth2);
  list($numbers["CPA"],$crores) = displayRefList("POSTER",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:1".$specificRequestCode,$countries,"CPA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CPSA-") !== false) {
  echo "<a name=\"CPSA\"></a><h2>Communications par affiches (posters) sans actes <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications pas affiches (posters)".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches (posters) sans actes</b><br><br>", $fonth2);
  list($numbers["CPSA"],$crores) = displayRefList("POSTER",$team,"%20AND%20popularLevel_s:0%20AND%20proceedings_s:0".$specificRequestCode,$countries,"CPSA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CPI-") !== false) {
  echo "<a name=\"CPI\"></a><h2>Communications par affiches internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications par affiches internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches internationales</b><br><br>", $fonth2);
  list($numbers["CPI"],$crores) = displayRefList("POSTER",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"CPI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CPN-") !== false) {
  echo "<a name=\"CPN\"></a><h2>Communications par affiches nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Communications par affiches nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches nationales</b><br><br>", $fonth2);
  list($numbers["CPN"],$crores) = displayRefList("POSTER",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"CPN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_comm) && strpos($choix_comm, "-CGP-") !== false) {
  echo "<a name=\"CGP\"></a><h2>Conférences grand public <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Conférences grand public".chr(13).chr(10));
  $sect->writeText("<b>Conférences grand public</b><br><br>", $fonth2);
  list($numbers["CGP"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:1".$specificRequestCode,$countries,"CGP",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-TO-") !== false) {
  echo "<a name=\"TO\"></a><h2>Tous les ouvrages (sauf vulgarisation) <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Tous les ouvrages (sauf vulgarisation)".chr(13).chr(10));
  $sect->writeText("<b>Tous les ouvrages (sauf vulgarisation)</b><br><br>", $fonth2);
  list($numbers["TO"],$crores) = displayRefList("OUV",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"TO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPI-") !== false) {
  echo "<a name=\"OSPI\"></a><h2>Ouvrages scientifiques de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages scientifiques de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages scientifiques de portée internationale</b><br><br>", $fonth2);
  list($numbers["OSPI"],$crores) = displayRefList("OUV",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"OSPI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPN-") !== false) {
  echo "<a name=\"OSPN\"></a><h2>Ouvrages scientifiques de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages scientifiques de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages scientifiques de portée nationale</b><br><br>", $fonth2);
  list($numbers["OSPN"],$crores) = displayRefList("OUV",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"OSPN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COS-") !== false) {
  echo "<a name=\"COS\"></a><h2>Chapitres d’ouvrages scientifiques <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Chapitres d'ouvrages scientifiques".chr(13).chr(10));
  $sect->writeText("<b>Chapitres d’ouvrages scientifiques</b><br><br>", $fonth2);
  list($numbers["COS"],$crores) = displayRefList("COUV",$team,""."%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"COS",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSI-") !== false) {
  echo "<a name=\"COSI\"></a><h2>Chapitres d’ouvrages scientifiques de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Chapitres d’ouvrages scientifiques de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Chapitres d’ouvrages scientifiques de portée internationale</b><br><br>", $fonth2);
  list($numbers["COSI"],$crores) = displayRefList("COUV",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"COSI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSN-") !== false) {
  echo "<a name=\"COSN\"></a><h2>Chapitres d’ouvrages scientifiques de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Chapitres d’ouvrages scientifiques de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Chapitres d’ouvrages scientifiques de portée nationale</b><br><br>", $fonth2);
  list($numbers["COSN"],$crores) = displayRefList("COUV",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"COSN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOS-") !== false) {
  echo "<a name=\"DOS\"></a><h2>Directions d’ouvrages scientifiques <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Directions d’ouvrages scientifiques".chr(13).chr(10));
  $sect->writeText("<b>Directions d’ouvrages scientifiques</b><br><br>", $fonth2);
  list($numbers["DOS"],$crores) = displayRefList("DOUV",$team,""."%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"DOS",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSI-") !== false) {
  echo "<a name=\"DOSI\"></a><h2>Directions d’ouvrages scientifiques de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Directions d’ouvrages scientifiques de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Directions d’ouvrages scientifiques de portée internationale</b><br><br>", $fonth2);
  list($numbers["DOSI"],$crores) = displayRefList("DOUV",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"DOSI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);

}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSN-") !== false) {
  echo "<a name=\"DOSN\"></a><h2>Directions d’ouvrages scientifiques de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Directions d’ouvrages scientifiques de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Directions d’ouvrages scientifiques de porté nationale</b><br><br>", $fonth2);
  list($numbers["DOSN"],$crores) = displayRefList("DOUV",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"DOSN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCO-") !== false) {
  echo "<a name=\"OCO\"></a><h2>Ouvrages ou chapitres d’ouvrages <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou chapitres d’ouvrages".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres d’ouvrages</b><br><br>", $fonth2);
  list($numbers["OCO"],$crores) = displayRefList("OUV+COUV",$team,""."%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"OCO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCOI-") !== false) {
  echo "<a name=\"OCOI\"></a><h2>Ouvrages ou chapitres d’ouvrages de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou chapitres d’ouvrages de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres d’ouvrages de portée internationale</b><br><br>", $fonth2);
  list($numbers["OCOI"],$crores) = displayRefList("OUV+COUV",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"OCOI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCON-") !== false) {
  echo "<a name=\"OCON\"></a><h2>Ouvrages ou chapitres d’ouvrages de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou chapitres d’ouvrages de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres d’ouvrages de portée nationale</b><br><br>", $fonth2);
  list($numbers["OCON"],$crores) = displayRefList("OUV+COUV",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"OCON",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODO-") !== false) {
  echo "<a name=\"ODO\"></a><h2>Ouvrages ou directions d’ouvrages <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou directions d’ouvrages".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou directions d’ouvrages</b><br><br>", $fonth2);
  list($numbers["ODO"],$crores) = displayRefList("OUV+DOUV",$team,""."%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"ODO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODOI-") !== false) {
  echo "<a name=\"ODOI\"></a><h2>Ouvrages ou directions d’ouvrages de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou directions d’ouvrages de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou directions d’ouvrages de portée internationale</b><br><br>", $fonth2);
  list($numbers["ODOI"],$crores) = displayRefList("OUV+DOUV",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"ODOI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODON-") !== false) {
  echo "<a name=\"ODON\"></a><h2>Ouvrages ou directions d’ouvrages de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou directions d’ouvrages de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou directions d’ouvrages de portée nationale</b><br><br>", $fonth2);
  list($numbers["ODON"],$crores) = displayRefList("OUV+DOUV",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"ODON",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDO-") !== false) {
  echo "<a name=\"OCDO\"></a><h2>Ouvrages ou chapitres ou directions d’ouvrages <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou chapitres ou directions d’ouvrages".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres ou directions d’ouvrages</b><br><br>", $fonth2);
  list($numbers["OCDO"],$crores) = displayRefList("OUV+COUV+DOUV",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"OCDO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDOI-") !== false) {
  echo "<a name=\"OCDOI\"></a><h2>Ouvrages ou chapitres ou directions d’ouvrages de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou chapitres ou directions d’ouvrages de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres ou directions d’ouvrages de portée internationale</b><br><br>", $fonth2);
  list($numbers["OCDOI"],$crores) = displayRefList("OUV+COUV+DOUV",$team,"%20AND%20popularLevel_s:0%20AND%20audience_s:2%20AND%20NOT%20language_s:fr".$specificRequestCode,$countries,"OCDOI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDON-") !== false) {
  echo "<a name=\"OCDON\"></a><h2>Ouvrages ou chapitres ou directions d’ouvrages de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou chapitres ou directions d’ouvrages de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres ou directions d’ouvrages de portée nationale</b><br><br>", $fonth2);
  list($numbers["OCDON"],$crores) = displayRefList("OUV+COUV+DOUV",$team,"%20AND%20popularLevel_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)%20AND%20language_s:fr".$specificRequestCode,$countries,"OCDON",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCV-") !== false) {
  echo "<a name=\"OCV\"></a><h2>Ouvrages ou chapitres de vulgarisation <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Ouvrages ou chapitres de vulgarisation".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres de vulgarisation</b><br><br>", $fonth2);
  list($numbers["OCV"],$crores) = displayRefList("OUV+COUV",$team,"%20AND%20popularLevel_s:1".$specificRequestCode,$countries,"OCV",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_autr) && strpos($choix_autr, "-BRE-") !== false) {
  echo "<a name=\"BRE\"></a><h2>Brevets <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Brevets".chr(13).chr(10));
  $sect->writeText("<b>Brevets</b><br><br>", $fonth2);
  list($numbers["BRE"],$crores) = displayRefList("PATENT",$team,"".$specificRequestCode,$countries,"BRE",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_autr) && strpos($choix_autr, "-RAP-") !== false) {
  echo "<a name=\"RAP\"></a><h2>Rapports <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Rapports".chr(13).chr(10));
  $sect->writeText("<b>Rapports</b><br><br>", $fonth2);
  list($numbers["RAP"],$crores) = displayRefList("REPORT",$team,"".$specificRequestCode,$countries,"RAP",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_autr) && strpos($choix_autr, "-THE-") !== false) {
  echo "<a name=\"THE\"></a><h2>Thèses <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Thèses".chr(13).chr(10));
  $sect->writeText("<b>Thèses</b><br><br>", $fonth2);
  list($numbers["THE"],$crores) = displayRefList("THESE",$team,"".$specificRequestCode,$countries,"THE",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_autr) && strpos($choix_autr, "-HDR-") !== false) {
  echo "<a name=\"HDR\"></a><h2>HDR <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"HDR".chr(13).chr(10));
  $sect->writeText("<b>HDR</b><br><br>", $fonth2);
  list($numbers["HDR"],$crores) = displayRefList("HDR",$team,"".$specificRequestCode,$countries,"HDR",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_autr) && strpos($choix_autr, "-PWM-") !== false) {
  echo "<a name=\"PWM\"></a><h2>Preprints, working papers, manuscrits non publiés <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Preprints, working papers, manuscrits non publiés".chr(13).chr(10));
  $sect->writeText("<b>Preprints, working papers, manuscrits non publiés</b><br><br>", $fonth2);
  list($numbers["PWM"],$crores) = displayRefList("UNDEF",$team,"".$specificRequestCode,$countries,"PWM",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}
if (isset($choix_autr) && strpos($choix_autr, "-AP-") !== false) {
  echo "<a name=\"AP\"></a><h2>Autres publications <a href=\"#sommaire\">&#8683;</a></h2>";
  //$Fnm1 = "./HAL/extractionHAL_".$team.".csv";
  $inF1 = fopen($Fnm1,"a+");
  //fseek($inF1, 0);
  fwrite($inF1,"Autres publications".chr(13).chr(10));
  $sect->writeText("<b>Autres publications</b><br><br>", $fonth2);
  list($numbers["AP"],$crores) = displayRefList("OTHER",$team,"".$specificRequestCode,$countries,"AP",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$team,$idhal,$typann,$typchr,$typtri,$typfor,$typdoi,$typurl,$surdoi,$sursou,$typidh,$racine,$typreva,$typif,$typrevh,$dscp,$typrevc,$typavsa,$typcro,$listenominit,$listenomcomp1,$listenomcomp2,$arriv,$depar,$sect,$Fnm,$Fnm1,$Fnm2,$delim,$prefeq,$rtfArray,$bibArray,$font,$fontlien,$fonth2,$fonth3,$root,$gr,$nbeqp,$nomeqp,$listedoi,$listetitre,$stpdf,$spa,$nmo,$gp1,$gp2,$gp3,$gp4,$gp5,$gp6,$gp7,$sep1,$sep2,$sep3,$sep4,$sep5,$sep6,$sep7,$choix_mp1,$choix_mp2,$choix_mp3,$choix_mp4,$choix_mp5,$choix_mp6,$choix_mp7,$choix_cg1,$choix_cg2,$choix_cg3,$choix_cg4,$choix_cg5,$choix_cg6,$choix_cg7);
}

echo "<a name=\"BILAN\"></a><h2>Bilan quantitatif <a href=\"#sommaire\">&#8683;</a></h2>";
//Find all years with publications
$availableYears=array();
foreach($numbers as $rType => $yearNumbers){
   foreach($yearNumbers as $year => $nb){
      $availableYears[$year]=1;
   }
}
ksort($availableYears);

if (count($availableYears) != 0) {//Y-a-t-il au moins un résultat ?
  //Display the table of publications by year (column) and by type (line)
  echo "<table>";
  echo "<tr><td></td>";
  foreach($availableYears as $year => $nb){
     echo "<td>".$year."</td>";

  }
  echo "</tr>";
  foreach($numbers as $rType => $yearNumbers){
     echo "<tr><td>".$rType."</td>";

     foreach($availableYears as $year => $nb){
        if(array_key_exists($year,$yearNumbers)){
           echo "<td>".$yearNumbers[$year]."</td>";
        } else {
           echo "<td>0</td>";
        }
     }
     echo "</tr>";
  }
  echo "</table><br><br>";

  //export en RTF
  $sect->writeText("<br><br>", $font);
  $rtfic->save($Fnm);

  if (isset($_GET["team"])) {
    copy ($Fnm, "./HAL/extractionHAL_".$team.".rtf");
    copy ($Fnm1, "./HAL/extractionHAL_".$team.".csv");
    copy ($Fnm2, "./HAL/extractionHAL_".$team.".bib");
  }
  suppression("./HAL", 3600);//Suppression des fichiers du dossier HAL créés il y a plus d'une heure 

  if (isset($_POST["soumis"]) || isset($_GET["team"])) {
    //Création de graphes
    //Librairies pChart
    include("./lib/pChart/class/pData.class.php");
    include("./lib/pChart/class/pDraw.class.php");
    include("./lib/pChart/class/pImage.class.php");

    // Données année par type de publication
    $MyData = new pData();
    //$MyData->addPoints(array(150,220,300,-250,-420,-200,300,200,100),$anneedeb);
    //$MyData->addPoints(array(140,0,340,-300,-320,-300,200,100,50),"Server B");
    foreach($numbers as $rType => $yearNumbers){
      $MyData->addPoints($rType,"Labels");
      foreach($availableYears as $year => $nb){
        if(array_key_exists($year,$yearNumbers)){
           $MyData->addPoints($yearNumbers[$year],$year);
        } else {
           $MyData->addPoints(VOID,$year);
        }
     }
    }
    $MyData->setAxisName(0,"Nombre");
    $MyData->setSerieDescription("Labels","Type de publication");
    $MyData->setAbscissa("Labels");
    $MyData->setAbscissaName("Type de publication");

    /* Create the pChart object */
    $myPicture = new pImage(700,280,$MyData);
    $myPicture->drawGradientArea(0,0,700,280,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
    $myPicture->drawGradientArea(0,0,700,280,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));
    $myPicture->drawRectangle(0,0,699,279,array("R"=>0,"G"=>0,"B"=>0));
    $myPicture->setFontProperties(array("FontName"=>"./lib/pChart/fonts/corbel.ttf","FontSize"=>10));

    /* Turn of Antialiasing */
    $myPicture->Antialias = FALSE;

    /* Draw the scale  */
    $myPicture->setGraphArea(50,50,680,220);
    $myPicture->drawText(350,40,"Type de publication par année",array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));
    $myPicture->drawScale(array("CycleBackground"=>TRUE,"DrawSubTicks"=>TRUE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10,"Mode"=>SCALE_MODE_START0));

    /* Turn on shadow computing */
    $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

    /* Draw the chart */
    $settings = array("Gradient"=>TRUE,"DisplayPos"=>LABEL_POS_INSIDE,"DisplayValues"=>TRUE,"DisplayR"=>255,"DisplayG"=>255,"DisplayB"=>255,"DisplayShadow"=>TRUE,"Surrounding"=>-30,"InnerSurrounding"=>30);
    $myPicture->drawBarChart($settings);

    /* Write the chart legend */
    $myPicture->drawLegend(30,260,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

    /* Do the mirror effect */
    $myPicture->drawAreaMirror(0,220,700,15);

    /* Draw the horizon line */
    //$myPicture->drawLine(1,220,698,220,array("R"=>80,"G"=>80,"B"=>80));

    /* Render the picture (choose the best way) */
    //$myPicture->autoOutput("test.png");
    //$myPicture->stroke();
    $myPicture->render("img/mypic1_".$team.".png");
    echo('<center><img src="img/mypic1_'.$team.'.png"></center><br>');


    // Données type de publication par année
    $MyData = new pData();
    //$MyData->addPoints(array(150,220,300,-250,-420,-200,300,200,100),$anneedeb);
    //$MyData->addPoints(array(140,0,340,-300,-320,-300,200,100,50),"Server B");

    foreach($availableYears as $year => $nb){
      $MyData->addPoints($year,"Labels");
      foreach($numbers as $rType => $yearNumbers){
        if(array_key_exists($year,$yearNumbers)){
           $MyData->addPoints($yearNumbers[$year],$rType);
        } else {
           $MyData->addPoints(VOID,$rType);
        }
      }
    }

    $MyData->setAxisName(0,"Nombre");
    $MyData->setSerieDescription("Labels","Année");
    $MyData->setAbscissa("Labels");
    $MyData->setAbscissaName("Année");

    /* Create the pChart object */
    $myPicture = new pImage(700,280,$MyData);
    $myPicture->drawGradientArea(0,0,700,280,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
    $myPicture->drawGradientArea(0,0,700,280,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));
    $myPicture->drawRectangle(0,0,699,279,array("R"=>0,"G"=>0,"B"=>0));
    $myPicture->setFontProperties(array("FontName"=>"./lib/pChart/fonts/corbel.ttf","FontSize"=>10));

    /* Turn of Antialiasing */
    $myPicture->Antialias = FALSE;

    /* Draw the scale  */
    $myPicture->setGraphArea(50,50,680,220);
    $myPicture->drawText(350,40,"Année par type de publication",array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));
    $myPicture->drawScale(array("CycleBackground"=>TRUE,"DrawSubTicks"=>TRUE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10,"Mode"=>SCALE_MODE_START0));

    /* Turn on shadow computing */
    $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

    /* Draw the chart */
    $settings = array("Gradient"=>TRUE,"DisplayPos"=>LABEL_POS_INSIDE,"DisplayValues"=>TRUE,"DisplayR"=>255,"DisplayG"=>255,"DisplayB"=>255,"DisplayShadow"=>TRUE,"Surrounding"=>-30,"InnerSurrounding"=>30);
    $myPicture->drawBarChart($settings);

    /* Write the chart legend */
    $myPicture->drawLegend(30,260,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

    /* Do the mirror effect */
    $myPicture->drawAreaMirror(0,220,700,15);

    /* Draw the horizon line */
    //$myPicture->drawLine(1,220,698,220,array("R"=>80,"G"=>80,"B"=>80));

    /* Render the picture (choose the best way) */
    //$myPicture->autoOutput("test.png");
    //$myPicture->stroke();
    $myPicture->render("img/mypic2_".$team.".png");
    echo('<center><img src="img/mypic2_'.$team.'.png"></center><br>');

    //Si choix sur tous les articles, camembert avec détails
    if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {
      include("./lib/pChart/class/pPie.class.php");
      $i = 3;
      if (isset($idhal) && $idhal != "") {$atester = "authIdHal_s";}else{$atester = "collCode_s";}
      foreach($availableYears as $year => $nb){
        $MyData = new pData();

        $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$team."%20AND%20docType_s:ART%20AND%20audience_s:2%20AND%20peerReviewing_s:1%20AND%20producedDateY_i:".$year);
        //echo $root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$team."%20AND%20docType_s:ART%20AND%20audience_s:2%20AND%20peerReviewing_s:1%20AND%20producedDateY_i:".$year;
        $results = json_decode($contents);
        $ACLRI=$results->response->numFound;

        $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$team."%20AND%20docType_s:ART%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20peerReviewing_s:1%20AND%20producedDateY_i:".$year);
        $results = json_decode($contents);
        $ACLRN=$results->response->numFound;

        $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$team."%20AND%20docType_s:ART%20AND%20audience_s:2%20AND%20peerReviewing_s:0%20AND%20producedDateY_i:".$year);
        $results = json_decode($contents);
        $ASCLRI=$results->response->numFound;

        $contents = file_get_contents($root."://api.archives-ouvertes.fr/search/".$institut."?q=".$atester.":".$team."%20AND%20docType_s:ART%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20peerReviewing_s:0%20AND%20producedDateY_i:".$year);
        $results = json_decode($contents);
        $ASCLRN=$results->response->numFound;

        $MyData->addPoints(array($ACLRI,$ACLRN,$ASCLRI,$ASCLRN),"Detail");
        $MyData->setSerieDescription("ScoreA","Application A");

        /* Define the absissa serie */
        $MyData->addPoints(array("ACLRI","ACLRN","ASCLRI","ASCLRN"),"Labels");
        $MyData->setAbscissa("Labels");

        /* Create the pChart object */
        $myPicture = new pImage(350,230,$MyData,TRUE);

        /* Draw a solid background */
        $Settings = array("R"=>173, "G"=>152, "B"=>217, "Dash"=>1, "DashR"=>193, "DashG"=>172, "DashB"=>237);
        $myPicture->drawFilledRectangle(0,0,350,230,$Settings);

        /* Draw a gradient overlay */
        //$Settings = array("StartR"=>209, "StartG"=>150, "StartB"=>231, "EndR"=>111, "EndG"=>3, "EndB"=>138, "Alpha"=>50);
        //$myPicture->drawGradientArea(0,0,700,230,DIRECTION_VERTICAL,$Settings);
        //$myPicture->drawGradientArea(0,0,700,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>100));
        $myPicture->drawGradientArea(0,0,350,280,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
        $myPicture->drawGradientArea(0,0,350,280,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));


        /* Add a border to the picture */
        $myPicture->drawRectangle(0,0,349,229,array("R"=>0,"G"=>0,"B"=>0));

        /* Write the picture title */
        $myPicture->setFontProperties(array("FontName"=>"./lib/pChart/fonts/corbel.ttf","FontSize"=>10));
        $myPicture->drawText(175,40,"Détail TA".$year,array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));

        /* Set the default font properties */
        $myPicture->setFontProperties(array("FontName"=>"./lib/pChart/fonts/corbel.ttf","FontSize"=>10,"R"=>80,"G"=>80,"B"=>80));

        /* Create the pPie object */
        $PieChart = new pPie($myPicture,$MyData);

        /* Define the slice color */
        $PieChart->setSliceColor(0,array("R"=>143,"G"=>197,"B"=>0));
        $PieChart->setSliceColor(1,array("R"=>97,"G"=>77,"B"=>63));
        $PieChart->setSliceColor(2,array("R"=>97,"G"=>113,"B"=>63));

        /* Enable shadow computing */
        $myPicture->setShadow(TRUE,array("X"=>3,"Y"=>3,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

        /* Draw a splitted pie chart */
        $PieChart->draw3DPie(175,125,array("WriteValues"=>TRUE,"ValuePosition"=>PIE_VALUE_OUTSIDE,"ValueR"=>0,"ValueG"=>0,"ValueB"=>0,"DataGapAngle"=>10,"DataGapRadius"=>6,"Border"=>TRUE));

        /* Write the legend */
        $myPicture->setFontProperties(array("FontName"=>"./lib/pChart/fonts/corbel.ttf","FontSize"=>10));
        $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>20));

        /* Write the legend box */
        $myPicture->setFontProperties(array("FontName"=>"./lib/pChart/fonts/corbel.ttf","FontSize"=>10,"R"=>0,"G"=>0,"B"=>0));
        $PieChart->drawPieLegend(30,200,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

        $myPicture->render('img/mypic'.$i.'_'.$team.'.png');
        echo('<center><img src="img/mypic'.$i.'_'.$team.'.png"></center><br>');
        $i++;
      }
    }

    foreach($numbers as $rType => $yearNumbers){
      switch($rType) {
        case "TA":
          echo('&nbsp;&nbsp;&nbsp;TA = Tous les articles (sauf vulgarisation)<br>');
          break;
        case "ACL" :
          echo('&nbsp;&nbsp;&nbsp;ACL = Articles de revues à comité de lecture<br>');
          break;
        case "ASCL" :
          echo('&nbsp;&nbsp;&nbsp;ASCL = Articles de revues sans comité de lecture<br>');
          break;
        case "ARI" :
          echo('&nbsp;&nbsp;&nbsp;ARI = Articles de revues internationales<br>');
          break;
        case "ARN" :
          echo('&nbsp;&nbsp;&nbsp;ARN = Articles de revues nationales<br>');
          break;
        case "ACLRI" :
          echo('&nbsp;&nbsp;&nbsp;ACLRI = Articles de revues internationales à comité de lecture<br>');
          break;
        case "ACLRN" :
          echo('&nbsp;&nbsp;&nbsp;ACLRN = Articles de revues nationales à comité de lecture<br>');
          break;
        case "ASCLRI" :
          echo('&nbsp;&nbsp;&nbsp;ASCLRI = Articles de revues internationales sans comité de lecture<br>');
          break;
        case "ASCLRN" :
          echo('&nbsp;&nbsp;&nbsp;ASCLRN = Articles de revues nationales sans comité de lecture<br>');
          break;
        case "AV" :
          echo('&nbsp;&nbsp;&nbsp;AV = Articles de vulgarisation<br>');
          break;
        case "TC" :
          echo('&nbsp;&nbsp;&nbsp;TC = Toutes les communications (sauf grand public)<br>');
          break;
        case "CA" :
          echo('&nbsp;&nbsp;&nbsp;CA = Communications avec actes<br>');
          break;
        case "CSA" :
          echo('&nbsp;&nbsp;&nbsp;CSA = Communications sans actes<br>');
          break;
        case "CI" :
          echo('&nbsp;&nbsp;&nbsp;CI = Communications internationales<br>');
          break;
        case "CN" :
          echo('&nbsp;&nbsp;&nbsp;CN = Communications nationales<br>');
          break;
        case "CAI" :
          echo('&nbsp;&nbsp;&nbsp;CAI = Communications avec actes internationales<br>');
          break;
        case "CSAI" :
          echo('&nbsp;&nbsp;&nbsp;CSAI = Communications sans actes internationales<br>');
          break;
        case "CAN" :
          echo('&nbsp;&nbsp;&nbsp;CAN = Communications avec actes nationales<br>');
          break;
        case "CSAN" :
          echo('&nbsp;&nbsp;&nbsp;CSAN = Communications sans actes nationales<br>');
          break;
        case "CINVA" :
          echo('&nbsp;&nbsp;&nbsp;CINVASANI = Communications invitées avec ou sans actes, nationales ou internationales<br>');
          break;
        case "CINVA" :
          echo('&nbsp;&nbsp;&nbsp;CINVA = Communications invitées avec actes<br>');
          break;
        case "CINVSA" :
          echo('&nbsp;&nbsp;&nbsp;CINVSA = Communications invitées sans actes<br>');
          break;
        case "CNONINVA" :
          echo('&nbsp;&nbsp;&nbsp;CNONINVA = Communications non invitées avec actes<br>');
          break;
        case "CNONINVSA" :
          echo('&nbsp;&nbsp;&nbsp;CNONINVSA = Communications non invitées sans actes<br>');
          break;
        case "CINVI" :
          echo('&nbsp;&nbsp;&nbsp;CINVI = Communications invitées internationales<br>');
          break;
        case "CNONINVI" :
          echo('&nbsp;&nbsp;&nbsp;CNONINVI = Communications non invitées internationales<br>');
          break;
        case "CINVN" :
          echo('&nbsp;&nbsp;&nbsp;CINVN = Communications invitées nationales<br>');
          break;
        case "CNONINVN" :
          echo('&nbsp;&nbsp;&nbsp;CNONINVN = Communications non invitées nationales<br>');
          break;
        case "CPASANI" :
          echo('&nbsp;&nbsp;&nbsp;CPASANI = Communications par affiches (posters) avec ou sans actes, nationales ou internationales<br>');
          break;
        case "CPA" :
          echo('&nbsp;&nbsp;&nbsp;CPA = Communications par affiches (posters) avec actes<br>');
          break;
        case "CPSA" :
          echo('&nbsp;&nbsp;&nbsp;CPSA = Communications par affiches (posters) sans actes<br>');
          break;
        case "CPI" :
          echo('&nbsp;&nbsp;&nbsp;CPI = Communications par affiches internationales<br>');
          break;
        case "CPN" :
          echo('&nbsp;&nbsp;&nbsp;CPN = Communications par affiches nationales<br>');
          break;
        case "CGP" :
          echo('&nbsp;&nbsp;&nbsp;CGP = Conférences grand public<br>');
          break;
        case "TO" :
          echo('&nbsp;&nbsp;&nbsp;TO = Tous les ouvrages (sauf vulgarisation)<br>');
          break;
        case "OSPI" :
          echo('&nbsp;&nbsp;&nbsp;OSPI = Ouvrages scientifiques de portée internationale<br>');
          break;
        case "OSPN" :
          echo('&nbsp;&nbsp;&nbsp;OSPN = Ouvrages scientifiques de portée nationale<br>');
          break;
        case "COS" :
          echo('&nbsp;&nbsp;&nbsp;COS = Chapitres d’ouvrages scientifiques<br>');
          break;
        case "COSI" :
          echo('&nbsp;&nbsp;&nbsp;COSI = Chapitres d’ouvrages scientifiques de portée internationale<br>');
          break;
        case "COSN" :
          echo('&nbsp;&nbsp;&nbsp;COSN = Chapitres d’ouvrages scientifiques de portée nationale<br>');
          break;
        case "DOS" :
          echo('&nbsp;&nbsp;&nbsp;DOS = Directions d’ouvrages scientifiques<br>');
          break;
        case "DOSI" :
          echo('&nbsp;&nbsp;&nbsp;DOSI = Directions d’ouvrages scientifiques de portée internationale<br>');
          break;
        case "DOSN" :
          echo('&nbsp;&nbsp;&nbsp;DOSN = Directions d’ouvrages scientifiques de portée nationale<br>');
          break;
        case "OCO" :
          echo('&nbsp;&nbsp;&nbsp;OCO = Ouvrages ou chapitres d’ouvrages<br>');
          break;
        case "OCOI" :
          echo('&nbsp;&nbsp;&nbsp;OCOI = Ouvrages ou chapitres d’ouvrages de portée internationale<br>');
          break;
        case "OCON" :
          echo('&nbsp;&nbsp;&nbsp;OCON = Ouvrages ou chapitres d’ouvrages de portée nationale<br>');
          break;
        case "ODO" :
          echo('&nbsp;&nbsp;&nbsp;ODO = Ouvrages ou directions d’ouvrages<br>');
          break;
        case "ODOI" :
          echo('&nbsp;&nbsp;&nbsp;ODOI = Ouvrages ou directions d’ouvrages de portée internationale<br>');
          break;
        case "ODON" :
          echo('&nbsp;&nbsp;&nbsp;ODON = Ouvrages ou directions d’ouvrages de portée nationale<br>');
          break;
        case "OCDO" :
          echo('&nbsp;&nbsp;&nbsp;OCDO = Ouvrages ou chapitres ou directions d’ouvrages<br>');
          break;
        case "OCDOI" :
          echo('&nbsp;&nbsp;&nbsp;OCDOI = Ouvrages ou chapitres ou directions d’ouvrages de portée internationale<br>');
          break;
        case "OCDON" :
          echo('&nbsp;&nbsp;&nbsp;OCDON = Ouvrages ou chapitres ou directions d’ouvrages de portée nationale<br>');
          break;
        case "OCV" :
          echo('&nbsp;&nbsp;&nbsp;OCV = Ouvrages ou chapitres de vulgarisation<br>');
          break;
        case "BRE" :
          echo('&nbsp;&nbsp;&nbsp;BRE = Brevets<br>');
          break;
        case "RAP" :
          echo('&nbsp;&nbsp;&nbsp;RAP = Rapports<br>');
          break;
        case "THE" :
          echo('&nbsp;&nbsp;&nbsp;THE = Thèses<br>');
          break;
        case "HDR" :
          echo('&nbsp;&nbsp;&nbsp;HDR = HDR<br>');
          break;
        case "PWM" :
          echo('&nbsp;&nbsp;&nbsp;PWM = Preprints, working papers, manuscrits non publiés<br>');
          break;
        case "AP" :
          echo('&nbsp;&nbsp;&nbsp;AP = Autres publications<br>');
          break;
      }
    }
    if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {
      echo('&nbsp;&nbsp;&nbsp;ACLRI = Articles de revues internationales à comité de lecture<br>');
      echo('&nbsp;&nbsp;&nbsp;ACLRN = Articles de revues nationales à comité de lecture<br>');
      echo('&nbsp;&nbsp;&nbsp;ASCLRI = Articles de revues internationales sans comité de lecture<br>');
      echo('&nbsp;&nbsp;&nbsp;ASCLRN = Articles de revues nationales sans comité de lecture<br>');
    }

    //si GR, graphes dédiés
    if (isset($team) && isset($gr) && (strpos($gr, $team) !== false)) {//GR
      $graphe = "non";
      for($j=1;$j<count($crores);$j++) {
        if($crores[$j] != 0){
          $graphe = "oui";
        }
      }
      if ($graphe == "oui") {
        echo('<br><br>');
        //Nombre de publications croisées par équipe sur la période
        $MyData = new pData();
        $i = 0;
        for($i=0;$i<count($crores);$i++) {
          $j = $i+1;
          $MyData->addPoints($nomeqp[$j],"Labels");
          if($crores[$j] != 0){
            $MyData->addPoints($crores[$j],"Equipe");
          } else {
            $MyData->addPoints(VOID,"Equipe");
          }
        }
        //$MyData->addPoints(array($gr1,$gr2,$gr3,$gr4,$gr5,$gr6,$gr7,$gr8,$gr9),"Equipe");
        //$MyData->addPoints(array("GR1","GR2","GR3","GR4","GR5","GR6","GR7","GR8","GR9"),"Labels");
        $MyData->setAxisName(0,"Nombre");
        $MyData->setSerieDescription("Labels","Nombre de publications croisées");
        $MyData->setAbscissa("Labels");
        $MyData->setAbscissaName("Equipe");

        /* Create the pChart object */
        $myPicture = new pImage(900,280,$MyData);
        $myPicture->drawGradientArea(0,0,900,280,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
        $myPicture->drawGradientArea(0,0,900,280,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));
        $myPicture->drawRectangle(0,0,899,279,array("R"=>0,"G"=>0,"B"=>0));
        $myPicture->setFontProperties(array("FontName"=>"./lib/pChart/fonts/corbel.ttf","FontSize"=>10));

        /* Turn of Antialiasing */
        $myPicture->Antialias = FALSE;

        /* Draw the scale  */
        $myPicture->setGraphArea(50,50,880,220);
        $myPicture->drawText(450,40,"Nombre global de publications croisées par équipe",array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));
        $myPicture->drawScale(array("CycleBackground"=>TRUE,"DrawSubTicks"=>TRUE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10,"Mode"=>SCALE_MODE_START0));

        /* Turn on shadow computing */
        $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

        /* Draw the chart */
        $settings = array("Gradient"=>TRUE,"DisplayPos"=>LABEL_POS_INSIDE,"DisplayValues"=>TRUE,"DisplayR"=>255,"DisplayG"=>255,"DisplayB"=>255,"DisplayShadow"=>TRUE,"Surrounding"=>-30,"InnerSurrounding"=>30);
        $myPicture->drawBarChart($settings);

        /* Write the chart legend */
        //$myPicture->drawLegend(30,260,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

        /* Do the mirror effect */
        $myPicture->drawAreaMirror(0,220,900,15);

        /* Draw the horizon line */
        //$myPicture->drawLine(1,220,898,220,array("R"=>80,"G"=>80,"B"=>80));

        /* Render the picture (choose the best way) */
        $myPicture->render('img/mypic_crogr_'.$team.'.png');
        echo('<center><img src="img/mypic_crogr_'.$team.'.png"></center><br>');
        echo('Ce graphe est généré lors d\'une numérotation/codification par équipe :<br>');
        echo('. Dans le cas d\'une extraction pour une unité, il représente l\'ensemble des publications croisées identifiées pour chaque équipe.<br>');
        echo('. Dans le cas d\'une extraction pour une équipe, il représente le nombre de publications croisées de cette équipe et celui des autres équipes concernées en regard. ');
        echo('Les sommes respectives ne sont pas forcément égales car une même publication croisée peut concerner plus de deux équipes : elle comptera alors pour 1 pour l\'équipe concernée par l\'extraction, ');
        echo('mais également pour 1 pour chacune des autres équipes associées.<br><br>');
        echo('<center><table cellpadding="5" width="80%"><tr><td width="45%" valign="top" style="text-align: justify;"><i>Pour illuster ce dernier cas, l\'exemple ci-contre représente l\'extraction des publications de l\'équipe GR2 dans une unité comportant quatre équipes. GR2 compte ainsi un total de 6 publications croisées: précisément, 3 avec GR1 seule, 1 avec GR3 seule, 1 avec GR1 et GR3, et 1 avec GR1 et GR4, d\'où, globalement, 5 avec GR1, 2 avec GR3 et 1 avec GR4.</i></td><td><img src="HAL_exemple.jpg"></td></tr></table></center><br><br>');
      }
    }
  }
}else{
  echo ('Aucun résultat');
}
?>
<br>
<script type="text/javascript" src="./ExtractionHAL.js"></script>
<script type="text/javascript">
  function affich_form_suite() {
    nbeqpval = document.extrhal.nbeqp.value;
    var eqpaff = '';
    for (i=1; i<=nbeqpval; i++) {
      eqpaff += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Nom HAL équipe '+i+' : <input type="text" name="eqp'+i+'" size="30"><br>';
    }
    eqpaff += '<br>';
    eqpaff += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Limiter l\'affichage seulement aux publications croisées :';
    eqpaff += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    eqpaff += '<input type="radio" name="typcro" value="non" <?php echo $cron;?>>non';
    eqpaff += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    eqpaff += '<input type="radio" name="typcro" value="oui" <?php echo $croo;?>>oui';
    document.getElementById("eqp").innerHTML = eqpaff;
    document.getElementById("panel2").style.maxHeight = document.getElementById("panel2").scrollHeight + "px";
  }

  $("#nbeqpid").keyup(function(event) {affich_form_suite();});

  function majapercu(){
    var selsep = [];
    selsep.push('- -');
    var selgrp = [];
    selgrp.push('- -');
    var idmaxi = 8;
    var idtest = 1;
    var contApercu = "";
    if (document.getElementById("nmo").options[2].selected) {contApercu += "x. ";}
    if (document.getElementById("nmo").options[3].selected) {contApercu += "(x) ";}
    if (document.getElementById("nmo").options[4].selected) {contApercu += "[x] ";}
    while (idtest < idmaxi) {
      var selcnt = document.getElementById("gp"+idtest);
      for (var i = 0; i < selcnt.length; i++) {
        if (selcnt.options[i].selected) selgrp.push(selcnt.options[i].value);
      }
      var selcnt = document.getElementById("sep"+idtest);
      for (var i = 0; i < selcnt.length; i++) {
        if (selcnt.options[i].selected) selsep.push(selcnt.options[i].value);
      }
      idtest++;
    }
    //console.log(selsep);
    var idmaxi = 8;
    var idtest = 1;
    while (idtest < idmaxi) {
      switch(selgrp[idtest]) {
        case "auteurs":
          select = document.getElementById("stpdf");
          choix = select.selectedIndex;
          valeur = document.getElementById("stpdf").options[choix].value;
          if (valeur == "mla") {
            var txtAut = "<?php echo $txtAutMla?>";
            var txtAut2 = "<?php echo $txtAutMla?>";
          }else{
            if (valeur == "chi") {
              var txtAut = "<?php echo $txtAutChi?>";
              var txtAut2 = "<?php echo $txtAutChi?>";
            }else{
              var txtAut = "<?php echo $txtAut?>";
              var txtAut2 = "<?php echo $txtAut?>";
            }
          }
          if (document.getElementById("spa").options[1].selected) {txtAut2 = txtAut.replace(/.; /g, "., ");}
          if (document.getElementById("spa").options[3].selected) {txtAut2 = txtAut.replace(/.; /g, ". ");}
          if (document.getElementById("spa").options[4].selected) {txtAut2 = txtAut.replace(/.; /g, ". -  ");}
          contApercu += "<span id=\"listAut\">"+txtAut2+"</span>";
          break;
        case "titre":
          contApercu += "<span id=\"listTit\"><?php echo($txtTit);?></span>";
          break;
        case "année":
          contApercu += "<span id=\"listAnn\"><?php echo($txtAnn);?></span>";
          break;
        case "revue":
          contApercu += "<span id=\"listRev\"><?php echo($txtRev);?></span>";
          break;
        case "volume":
          contApercu += "<span id=\"listVol\"><?php echo($txtVol);?></span>";
          break;
        case "numéro":
          contApercu += "<span id=\"listNum\"><?php echo($txtNum);?></span>";
          break;
        case "pages":
          contApercu += "<span id=\"listPag\"><?php echo($txtPag);?></span>";
          break;
      }
      if (selsep[idtest] == "- -") {
        contApercu += "";
      }else{
        contApercu += selsep[idtest];
      }
      //alert(contApercu);
      idtest++;
    }
    //var contApercu = "<span id=\"listAut\"><?php echo($txtAut);?></span>" + selsep[1];
    //contApercu += "<span id=\"listTit\"><?php echo($txtTit);?></span>";
    //contApercu += "<span id=\"listAnn\"><?php echo($txtAnn);?></span>";
    //contApercu += "<span id=\"listRev\"><?php echo($txtRev);?></span>";
    //contApercu += "<span id=\"listVol\"><?php echo($txtVol);?></span>";
    //contApercu += "<span id=\"listNum\"><?php echo($txtNum);?></span>";
    //contApercu += "<span id=\"listPag\"><?php echo($txtPag);?></span>";
    document.getElementById("apercu").innerHTML = contApercu;

    var listgp = "~|~";
    var idmaxi = 8;
    var idtest = 1;
    var selmpgpAut = "";
    var selmpgpTit = "";
    var selmpgpAnn = "";
    var selmpgpRev = "";
    var selmpgpVol = "";
    var selmpgpNum = "";
    var selmpgpPag = "";
    while (idtest < idmaxi) {
      selgp = document.getElementById("gp"+idtest);//Quel attribut de groupe ?
      var choixgp = selgp.selectedIndex;
      var valgp = document.getElementById("gp"+idtest).options[choixgp].value;
      listgp += valgp + "~|~";
      if (listgp.indexOf("auteurs") != -1 && selmpgpAut == "") {
        var selmpgpAut = '~|~';
        selmp = document.getElementById("mp"+idtest);//Quelles valeurs de mise en page ?
        for (var i = 0; i < selmp.length; i++) {
          if (selmp.options[i].selected) selmpgpAut += selmp.options[i].value + '~|~';
        }
        valcg = document.getElementById("cg"+idtest).value;//Quelle couleur de texte ?
        selmpgpAut += valcg + '~|~';
        document.getElementById("listAut").innerHTML = txtAut2;
        if (selmpgpAut.indexOf("- -") != -1) {//Réinitialisation
          mp(selmpgpAut, "- -", "listAut", "", "", txtAut2);
        }else{
          mp(selmpgpAut, "norm", "listAut", "", "", txtAut2);
          mp(selmpgpAut, "emin", "listAut", "", "", txtAut2);
          mp(selmpgpAut, "emaj", "listAut", "", "", txtAut2);
          mp(selmpgpAut, "effa", "listAut", "", "", txtAut2);
          mp(selmpgpAut, "gras", "listAut", "<b>", "</b>", txtAut2);
          mp(selmpgpAut, "soul", "listAut", "<u>", "</u>", txtAut2);
          mp(selmpgpAut, "ital", "listAut", "<i>", "</i>", txtAut2);
          mp(selmpgpAut, "epar", "listAut", "(", ")", txtAut2);
          mp(selmpgpAut, "ecro", "listAut", "[", "]", txtAut2);
          mp(selmpgpAut, "egui", "listAut", "\"", "\"", txtAut2);
          mp(selmpgpAut, "ecol", "listAut", valcg, "", txtAut2);
        }
      }
      if (listgp.indexOf("titre") != -1 && selmpgpTit == "") {
        var selmpgpTit = '~|~';
        selmp = document.getElementById("mp"+idtest);//Quelles valeurs de mise en page ?
        for (var i = 0; i < selmp.length; i++) {
          if (selmp.options[i].selected) selmpgpTit += selmp.options[i].value + '~|~';
        }
        valcg = document.getElementById("cg"+idtest).value;//Quelle couleur de texte ?
        selmpgpTit += valcg + '~|~';
        document.getElementById("listTit").innerHTML = "<?php echo $txtTit;?>";
        if (selmpgpTit.indexOf("- -") != -1) {//Réinitialisation
          mp(selmpgpTit, "- -", "listTit", "", "", "<?php echo $txtTit;?>");
        }else{
          mp(selmpgpTit, "norm", "listTit", "", "", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "emin", "listTit", "", "", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "emaj", "listTit", "", "", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "effa", "listTit", "", "", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "gras", "listTit", "<b>", "</b>", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "soul", "listTit", "<u>", "</u>", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "ital", "listTit", "<i>", "</i>", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "epar", "listTit", "(", ")", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "ecro", "listTit", "[", "]", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "egui", "listTit", "\"", "\"", "<?php echo $txtTit;?>");
          mp(selmpgpTit, "ecol", "listTit", valcg, "", "<?php echo $txtTit;?>");
        }
      }
      if (listgp.indexOf("année") != -1 && selmpgpAnn == "") {
        var selmpgpAnn = '~|~';
        selmp = document.getElementById("mp"+idtest);//Quelles valeurs de mise en page ?
        for (var i = 0; i < selmp.length; i++) {
          if (selmp.options[i].selected) selmpgpAnn += selmp.options[i].value + '~|~';
        }
        valcg = document.getElementById("cg"+idtest).value;//Quelle couleur de texte ?
        selmpgpAnn += valcg + '~|~';
        document.getElementById("listAnn").innerHTML = "<?php echo $txtAnn;?>";
        if (selmpgpAnn.indexOf("- -") != -1) {//Réinitialisation
          mp(selmpgpAnn, "- -", "listAnn", "", "", "<?php echo $txtAnn;?>");
        }else{
          mp(selmpgpAnn, "norm", "listAnn", "", "", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "emin", "listAnn", "", "", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "emaj", "listAnn", "", "", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "effa", "listAnn", "", "", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "gras", "listAnn", "<b>", "</b>", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "soul", "listAnn", "<u>", "</u>", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "ital", "listAnn", "<i>", "</i>", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "epar", "listAnn", "(", ")", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "ecro", "listAnn", "[", "]", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "egui", "listAnn", "\"", "\"", "<?php echo $txtAnn;?>");
          mp(selmpgpAnn, "ecol", "listAnn", valcg, "", "<?php echo $txtAnn;?>");
        }
      }
      if (listgp.indexOf("revue") != -1 && selmpgpRev == "") {
        var selmpgpRev = '~|~';
        selmp = document.getElementById("mp"+idtest);//Quelles valeurs de mise en page ?
        for (var i = 0; i < selmp.length; i++) {
          if (selmp.options[i].selected) selmpgpRev += selmp.options[i].value + '~|~';
        }
        valcg = document.getElementById("cg"+idtest).value;//Quelle couleur de texte ?
        selmpgpRev += valcg + '~|~';
        document.getElementById("listRev").innerHTML = "<?php echo $txtRev;?>";
        if (selmpgpRev.indexOf("- -") != -1) {//Réinitialisation
          mp(selmpgpRev, "- -", "listRev", "", "", "<?php echo $txtRev;?>");
        }else{
          mp(selmpgpRev, "norm", "listRev", "", "", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "emin", "listRev", "", "", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "emaj", "listRev", "", "", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "effa", "listRev", "", "", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "gras", "listRev", "<b>", "</b>", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "soul", "listRev", "<u>", "</u>", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "ital", "listRev", "<i>", "</i>", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "epar", "listRev", "(", ")", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "ecro", "listRev", "[", "]", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "egui", "listRev", "\"", "\"", "<?php echo $txtRev;?>");
          mp(selmpgpRev, "ecol", "listRev", valcg, "", "<?php echo $txtRev;?>");
        }
      }
      if (listgp.indexOf("volume") != -1 && selmpgpVol == "") {
        var selmpgpVol = '~|~';
        selmp = document.getElementById("mp"+idtest);//Quelles valeurs de mise en page ?
        for (var i = 0; i < selmp.length; i++) {
          if (selmp.options[i].selected) selmpgpVol += selmp.options[i].value + '~|~';
        }
        valcg = document.getElementById("cg"+idtest).value;//Quelle couleur de texte ?
        selmpgpVol += valcg + '~|~';
        document.getElementById("listVol").innerHTML = "<?php echo $txtVol;?>";
        if (selmpgpVol.indexOf("- -") != -1) {//Réinitialisation
          mp(selmpgpVol, "- -", "listVol", "", "", "<?php echo $txtVol;?>");
        }else{
          mp(selmpgpVol, "norm", "listVol", "", "", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "emin", "listVol", "", "", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "emaj", "listVol", "", "", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "effa", "listVol", "", "", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "gras", "listVol", "<b>", "</b>", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "soul", "listVol", "<u>", "</u>", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "ital", "listVol", "<i>", "</i>", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "epar", "listVol", "(", ")", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "ecro", "listVol", "[", "]", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "egui", "listVol", "\"", "\"", "<?php echo $txtVol;?>");
          mp(selmpgpVol, "ecol", "listVol", valcg, "", "<?php echo $txtVol;?>");
        }
      }
      if (listgp.indexOf("numéro") != -1 && selmpgpNum == "") {
        var selmpgpNum = '~|~';
        selmp = document.getElementById("mp"+idtest);//Quelles valeurs de mise en page ?
        for (var i = 0; i < selmp.length; i++) {
          if (selmp.options[i].selected) selmpgpNum += selmp.options[i].value + '~|~';
        }
        valcg = document.getElementById("cg"+idtest).value;//Quelle couleur de texte ?
        selmpgpNum += valcg + '~|~';
        document.getElementById("listNum").innerHTML = "<?php echo $txtNum;?>";
        if (selmpgpNum.indexOf("- -") != -1) {//Réinitialisation
          mp(selmpgpNum, "- -", "listNum", "", "", "<?php echo $txtNum;?>");
        }else{
          mp(selmpgpNum, "norm", "listNum", "", "", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "emin", "listNum", "", "", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "emaj", "listNum", "", "", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "effa", "listNum", "", "", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "gras", "listNum", "<b>", "</b>", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "soul", "listNum", "<u>", "</u>", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "ital", "listNum", "<i>", "</i>", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "epar", "listNum", "(", ")", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "ecro", "listNum", "[", "]", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "egui", "listNum", "\"", "\"", "<?php echo $txtNum;?>");
          mp(selmpgpNum, "ecol", "listNum", valcg, "", "<?php echo $txtNum;?>");
        }
      }
      if (listgp.indexOf("pages") != -1 && selmpgpPag == "") {
        var selmpgpPag = '~|~';
        selmp = document.getElementById("mp"+idtest);//Quelles valeurs de mise en page ?
        for (var i = 0; i < selmp.length; i++) {
          if (selmp.options[i].selected) selmpgpPag += selmp.options[i].value + '~|~';
        }
        valcg = document.getElementById("cg"+idtest).value;//Quelle couleur de texte ?
        selmpgpPag += valcg + '~|~';
        document.getElementById("listPag").innerHTML = "<?php echo $txtPag;?>";
        if (selmpgpPag.indexOf("- -") != -1) {//Réinitialisation
          mp(selmpgpPag, "- -", "listPag", "", "", "<?php echo $txtPag;?>");
        }else{
          mp(selmpgpPag, "norm", "listPag", "", "", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "emin", "listPag", "", "", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "emaj", "listPag", "", "", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "effa", "listPag", "", "", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "gras", "listPag", "<b>", "</b>", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "soul", "listPag", "<u>", "</u>", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "ital", "listPag", "<i>", "</i>", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "epar", "listPag", "(", ")", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "ecro", "listPag", "[", "]", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "egui", "listPag", "\"", "\"", "<?php echo $txtPag;?>");
          mp(selmpgpPag, "ecol", "listPag", valcg, "", "<?php echo $txtPag;?>");
        }
      }
      idtest++;
    }
  }
</script>
<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(["trackPageView"]);
  _paq.push(["enableLinkTracking"]);

  (function() {
    var u=(("https:" == document.location.protocol) ? "https" : "http") + "://visites.univ-rennes1.fr/";
    _paq.push(["setTrackerUrl", u+"piwik.php"]);
    _paq.push(["setSiteId", "467"]);
    var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
    g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Piwik Code -->
</body></html>
<?php
if ($typidh == "vis") {echo('<script type="text/javascript" charset="UTF-8">document.getElementById("detrac").style.display = "block";</script>');}
?>
