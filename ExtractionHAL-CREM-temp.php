    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">
<html>



<head>
  <title>ExtrHAL : outil d’extraction des publications HAL d’une unité ou équipe de recherche</title>
  <meta name="Description" content="ExtrHAL : outil d’extraction des publications HAL d’une unité ou équipe de recherche">  
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="icon" type="type/ico" href="HAL_favicon.ico" />
  <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
  <STYLE type="text/css">
  a.info{
      position:relative; 
      z-index:24; background-color:#ccc;
      color:#000;
      text-decoration:none}

  a.info:hover{z-index:25; background-color:#ff0}

  a.info span{display: none}

  a.info:hover span{ 
  /*le contenu de la balise span ne sera visible que pour l'état a:hover */
  display:block; 
  position:absolute;
  top:2em; left:2em; width:15em;
  border:1px solid #6699cc;
  background-color:#eeeeee; color:#6699cc;
  text-align: justify;
  font-weight:none;
  padding:1px;
  }
  </STYLE>
</head>  

<?php
//Institut général
$institut = "";// -> univ-rennes1/ par exemple, mais est-ce vraiment nécessaire ?
//export CSV
$Fnm1 = "./HAL/extractionHAL.csv"; 
$inF = fopen($Fnm1,"w"); 
fseek($inF, 0);
$chaine = "\xEF\xBB\xBF";
fwrite($inF,$chaine);
//export en RTF
$Fnm = "./HAL/extractionHAL.rtf";
require_once ("./HAL/phprtflite-1.2.0/lib/PHPRtfLite.php");
PHPRtfLite::registerAutoloader();
$rtf = new PHPRtfLite();
$sect = $rtf->addSection();
$font = new PHPRtfLite_Font(10, 'Arial', '#000000', '#FFFFFF');
$fontlien = new PHPRtfLite_Font(10, 'Arial', '#0000FF', '#FFFFFF');
$fonth3 = new PHPRtfLite_Font(12, 'Arial', '#000000', '#FFFFFF');
$fonth2 = new PHPRtfLite_Font(14, 'Arial', '#000000', '#FFFFFF');
$parFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_JUSTIFY);
$root = 'http';
if ( isset ($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")	{
  $root.= "s";
}
$urlsauv = $root."://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

function cleanup_title($titre) {
  // présence de " et combien
  $nb = mb_substr_count ($titre, '"', 'UTF-8');
  if ($nb%2 == 0) {
    return $titre;  // nombre pair (ou 0) rien à faire
  }
  // on ajoute le " à la fin
  return $titre . '"';
}

function mb_ucwords($str) {
  $str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
  return ($str);
}

function prenomCompInit($prenom) {
  if (strpos(trim($prenom),"-") != false) {//Le prénom comporte un tiret
    $postiret = strpos(trim($prenom),"-");
    $autg = mb_substr($prenom,0,1, 'UTF-8');
    $autd = mb_substr($prenom,($postiret+1),1, 'UTF-8');
    $prenom = mb_ucwords($autg).".-".mb_ucwords($autd).".";
  }else{
    if (strpos(trim($prenom)," ") != false) {//plusieurs prénoms
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
    $autg = mb_substr($prenom,0,$postiret, 'UTF-8');
    $autd = mb_substr($prenom,($postiret+1),strlen($prenom), 'UTF-8');
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
    $autg = mb_substr($nom,0,$postiret, 'UTF-8');
    $autd = mb_substr($nom,($postiret+1),strlen($nom), 'UTF-8');
    $nom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    $nom = mb_ucwords($nom);
  }
  return $nom;
}

if (isset($_POST["soumis"])) {
  $team = $_POST["team"];
  $urlsauv .= "?team=".$team;
  $listaut = $_POST["listaut"];
  if ($listaut == "") {$listaut = $team;}
  $urlsauv .= "&listaut=".$listaut;
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

	//Création des listes des auteurs appartenant à la collection spécifiée pour la liste
  include "ExtractionHAL-auteurs-UR1.php";
  $listenominit = "~";
  $listenomcomp1 = "~";
  $listenomcomp2 = "~";
  foreach($AUTEURS_UR1 AS $i => $valeur) {
    if ($AUTEURS_UR1[$i]['collhal'] == $listaut || $AUTEURS_UR1[$i]['colleqhal'] == $listaut) {
      $listenomcomp1 .= nomCompEntier($AUTEURS_UR1[$i]['nom'])." ".prenomCompEntier($AUTEURS_UR1[$i]['prenom'])."~";
      $listenomcomp2 .= prenomCompEntier($AUTEURS_UR1[$i]['prenom'])." ".nomCompEntier($AUTEURS_UR1[$i]['nom'])."~";
      //si prénom composé et juste les ititiales
      $prenom = prenomCompInit($AUTEURS_UR1[$i]['prenom']);
      $listenominit .= nomCompEntier($AUTEURS_UR1[$i]['nom']).", ".$prenom.".~";
    }
  }
	if (isset($_POST['anneedeb'])) {$anneedeb = $_POST['anneedeb'];}else{$anneedeb = date('Y', time());$anneefin = date('Y', time());}
  if (isset($_POST['anneefin'])) {$anneefin = $_POST['anneefin'];}else{if (isset($_POST['anneedeb'])) {$anneefin = $_POST['anneedeb'];}else{$anneefin = $anneedeb;}}
  // vérification sur ordre des années si différentes
  if ($anneefin < $anneedeb) {$anneetemp = $anneedeb; $anneedeb = $anneefin; $anneefin = $anneetemp;}
  $urlsauv .= "&anneedeb=".$anneedeb;
  $urlsauv .= "&anneefin=".$anneefin;

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
	$typfor = $_POST["typfor"];
	$urlsauv .= "&typfor=".$typfor;
	$typdoi = $_POST["typdoi"];
	$urlsauv .= "&typdoi=".$typdoi;
	$typidh = $_POST["typidh"];
	$urlsauv .= "&typidh=".$typidh;
	$typreva = $_POST["typreva"];
	$urlsauv .= "&typreva=".$typreva;
	$typrevc = $_POST["typrevc"];
	$urlsauv .= "&typrevc=".$typrevc;
	$delim = $_POST["delim"];
	switch($delim) {
    case ",":
      $urlsauv .= "&delim=virg";
      break;
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
	$typeqp = $_POST["typeqp"];
	$urlsauv .= "&typeqp=".$typeqp;
	$nbeqp = $_POST["nbeqp"];
	$urlsauv .= "&nbeqp=".$nbeqp;
	
  $nomeqp[0] = $team;
  $typeqp = $_POST["typeqp"];
  if (isset($typeqp) && $typeqp == "oui") {//Numérotation/codification par équipe
    $nbeqp = $_POST['nbeqp'];
    $gr = "¤".$team."¤";
    for($i = 1; $i <= $nbeqp; $i++) { 
      //$gr = "¤GR¤GR1¤GR2¤GR3¤GR4¤GR5¤GR6¤GR7¤GR8¤GR9¤";
      $gr .= $_POST['eqp'.$i]."¤";
      $nomeqp[$i] = $_POST['eqp'.$i];
      $urlsauv .= "&eqp".$i."=".$nomeqp[$i];
    }
  }
}

if (isset($_GET["team"])) {
  $team = $_GET["team"];
  $listaut = $_GET["listaut"];
  if ($listaut == "") {$listaut = $team;}
  
  $publis = $_GET["publis"];//Articles de revue
  $tabpublis = explode("~", $publis);
  $i = 0;
  $choix_publis = "-";
  while (isset($tabpublis[$i])) {
    $choix_publis .= $tabpublis[$i]."-";
    $i++;
  }
  $comm = $_GET["comm"];//Communications / conférences
  $tabcomm = explode("~", $comm);
  $i = 0;
  $choix_comm = "-";
  while (isset($tabcomm[$i])) {
    $choix_comm .= $tabcomm[$i]."-";
    $i++;
  }
  $ouvr = $_GET["ouvr"];//Ouvrages
  $tabouvr = explode("~", $ouvr);
  $i = 0;
  $choix_ouvr = "-";
  while (isset($tabouvr[$i])) {
    $choix_ouvr .= $tabouvr[$i]."-";
    $i++;
  }
  $autr = $_GET["autr"];//Autres
  $tabautr = explode("~", $autr);
  $i = 0;
  $choix_autr = "-";
  while (isset($tabautr[$i])) {
    $choix_autr .= $tabautr[$i]."-";
    $i++;
  }
  
	//Création des listes des auteurs appartenant à la collection spécifiée pour la liste
  include "ExtractionHAL-auteurs-UR1.php";
  $listenominit = "~";
  $listenomcomp1 = "~";
  $listenomcomp2 = "~";
  foreach($AUTEURS_UR1 AS $i => $valeur) {
    if ($AUTEURS_UR1[$i]['collhal'] == $listaut || $AUTEURS_UR1[$i]['colleqhal'] == $listaut) {
      $listenomcomp1 .= nomCompEntier($AUTEURS_UR1[$i]['nom'])." ".prenomCompEntier($AUTEURS_UR1[$i]['prenom'])."~";
      $listenomcomp2 .= prenomCompEntier($AUTEURS_UR1[$i]['prenom'])." ".nomCompEntier($AUTEURS_UR1[$i]['nom'])."~";
      //si prénom composé et juste les ititiales
      $prenom = prenomCompInit($AUTEURS_UR1[$i]['prenom']);
      $listenominit .= nomCompEntier($AUTEURS_UR1[$i]['nom']).", ".$prenom.".~";
    }
  }
  
	if (isset($_GET['anneedeb'])) {$anneedeb = $_GET['anneedeb'];}else{$anneedeb = date('Y', time());$anneefin = date('Y', time());}
  if (isset($_GET['anneefin'])) {$anneefin = $_GET['anneefin'];}else{if (isset($_GET['anneedeb'])) {$anneefin = $_GET['anneedeb'];}else{$anneefin = $anneedeb;}}
  // vérification sur ordre des années si différentes
  if ($anneefin < $anneedeb) {$anneetemp = $anneedeb; $anneedeb = $anneefin; $anneefin = $anneetemp;}
  
  $typnum = $_GET["typnum"];
  $typaut = $_GET["typaut"];
  $typnom = $_GET["typnom"];
  $typcol = $_GET["typcol"];
  $typlim = $_GET["typlim"];
  $limaff = $_GET["limaff"];
  $typtit = $_GET["typtit"];
  $typann = $_GET["typann"];
  $typfor = $_GET["typfor"];
  $typdoi = $_GET["typdoi"];
  $typidh = $_GET["typidh"];
  $typreva = $_GET["typreva"];
  $typrevc = $_GET["typrevc"];
  $delim = $_GET["delim"];
  switch($delim) {
    case "virg":
      $delim = ",";
      break;
    case "pvir":
      $delim = ";";
      break;
    case "poun":
      $delim = "£";
      break;
    case "para":
      $delim = "§";
      break;
  }
  $nomeqp[0] = $team;
  $typeqp = $_GET["typeqp"];
  $nbeqp = $_GET["nbeqp"];
  if (isset($typeqp) && $typeqp == "oui") {//Numérotation/codification par équipe
    $gr = "¤".$team."¤";
    for($i = 1; $i <= $nbeqp; $i++) { 
      $gr .= $_GET['eqp'.$i]."¤";
      $nomeqp[$i] = $_GET['eqp'.$i];
    }
  }

}
?>

<body style="font-family:calibri,verdana">
<h1>ExtrHAL : outil d’extraction des publications HAL d’une unité ou équipe de recherche</h1>

<p>Cette page permet d’afficher et d’exporter en RTF et/ou CSV des listes de publications HAL d’une unité ou équipe de recherche, 
à partir d’un script PHP créé par <a target="_blank" href="http://igm.univ-mlv.fr/~gambette/ExtractionHAL/ExtractionHAL.php?collection=UPEC-UPEM">
Philippe Gambette</a>, repris et modifié par Olivier Troccaz pour l’Université de Rennes 1. 
Si vous souhaitez utiliser le script PHP pour une autre institution, consultez la 
<a target="_blank" href="http://www.bibliopedia.fr/wiki/D%C3%A9veloppements_HAL">page Bibliopedia</a> (ExtractionHAL).</p>

<form method="POST" accept-charset="utf-8" name="extrhal" action="ExtractionHAL.php#sommaire">
<p><b>Code collection HAL</b> <a class=info onclick='return false' href="#">(qu’est-ce que c’est ?)<span>Code visible dans l’URL d’une collection. 
Exemple : IPR-MOL est le code de la collection http://hal.archives-ouvertes.fr/<b>IPR-PMOL</b> de l’équipe Physique moléculaire 
de l’unité IPR UMR CNRS 6251</span></a> : 
<?php
if (isset($team) && $team != "") {$team1 = $team; $team2 = $team;}else{$team1 = "Entrez le code de votre collection"; $team2 = "";}
if (!isset($listaut)) {$listaut = "";}
?>
<input type="text" name="team" value="<?php echo $team1;?>" size="40" onClick="this.value='<?php echo $team2;?>';"><br>
<p>Code collection HAL pour la liste des auteurs à mettre en évidence <a class=info onclick='return false' href="#">(exemple)<span>Indiquez ici 
le code collection de votre labo ou de votre équipe, selon que vous souhaitez mettre en évidence le nom des auteurs du labo ou de l'équipe.
</span></a> :
<input type="text" name="listaut" value="<?php echo $listaut;?>" size="40"><br>
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
<b>Choix des listes de publications à afficher</b> <i>(sélection/désélection multiple en maintenant la touche 'Ctrl' (PC) ou 'Pomme' (Mac) enfoncée)</i>:
<table>
<tr><td valign="top">Articles de revue :</td>
<td><select size="10" name="publis[]" multiple>
<option value="TA" <?php echo $ta;?>>Tous les articles (sauf vulgarisation)</option>
<option value="ACL" <?php echo $acl;?>>Articles de revues à comité de lecture</option>
<option value="ASCL" <?php echo $ascl;?>>Articles de revues sans comité de lecture</option>
<option value="ARI" <?php echo $ari;?>>Articles de revues internationales</option>
<option value="ARN" <?php echo $arn;?>>Articles de revues nationales</option>
<option value="ACLRI" <?php echo $aclri;?>>Articles de revues à comité de lecture de revues internationales</option>
<option value="ACLRN" <?php echo $aclrn;?>>Articles de revues à comité de lecture de revues nationales</option>
<option value="ASCLRI" <?php echo $asclri;?>>Articles de revues sans comité de lecture de revues internationales</option>
<option value="ASCLRN" <?php echo $asclrn;?>>Articles de revues sans comité de lecture de revues nationales</option>
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
if (isset($choix_comm) && strpos($choix_comm, "-CINV-") !== false) {$cinv = "selected";}else{$cinv = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINV-") !== false) {$cnoninv = "selected";}else{$cnoninv = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CINVI-") !== false) {$cinvi = "selected";}else{$cinvi = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVI-") !== false) {$cnoninvi = "selected";}else{$cnoninvi = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CINVN-") !== false) {$cinvn = "selected";}else{$cinvn = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVN-") !== false) {$cnoninvn = "selected";}else{$cnoninvn = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CP-") !== false) {$cp = "selected";}else{$cp = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CPI-") !== false) {$cpi = "selected";}else{$cpi = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CPN-") !== false) {$cpn = "selected";}else{$cpn = "";}
if (isset($choix_comm) && strpos($choix_comm, "-CGP-") !== false) {$cgp = "selected";}else{$cgp = "";}
?>
<table>
<tr><td valign="top">Communications / conférences :</td>
<td><select size="19" name="comm[]" multiple>
<option value="TC" <?php echo $tc;?>>Toutes les communications (sauf grand public)</option>
<option value="CA" <?php echo $ca;?>>Communications avec actes</option>
<option value="CSA" <?php echo $csa;?>>Communications sans actes</option>
<option value="CI" <?php echo $ci;?>>Communications internationales</option>
<option value="CN" <?php echo $cn;?>>Communications nationales</option>
<option value="CAI" <?php echo $cai;?>>Communications avec actes internationales</option>
<option value="CSAI" <?php echo $csai;?>>Communications sans actes internationales</option>
<option value="CAN" <?php echo $can;?>>Communications avec actes nationales</option>
<option value="CSAN" <?php echo $csan;?>>Communications sans actes nationales</option>
<option value="CINV" <?php echo $cinv;?>>Communications invitées</option>
<option value="CNONINV" <?php echo $cnoninv;?>>Communications non invitées</option>
<option value="CINVI" <?php echo $cinvi;?>>Communications invitées internationales</option>
<option value="CNONINVI" <?php echo $cnoninvi;?>>Communications non invitées internationales</option>
<option value="CINVN" <?php echo $cinvn;?>>Communications invitées nationales</option>
<option value="CNONINVN" <?php echo $cnoninvn;?>>Communications non invitées nationales</option>
<option value="CP" <?php echo $cp;?>>Communications par affiches (posters)</option>
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
if (isset($choix_autr) && strpos($choix_autr, "-PWM-") !== false) {$pwm = "selected";}else{$pwm = "";}
if (isset($choix_autr) && strpos($choix_autr, "-AP-") !== false) {$ap = "selected";}else{$ap = "";}
?>
<table>
<tr><td valign="top">Autres productions scientifiques :</td>
<td><select size="4" name="autr[]" multiple>
<option value="BRE" <?php echo $bre;?>>Brevets</option>
<option value="RAP" <?php echo $rap;?>>Rapports</option>
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
?>
</select></td></tr></table><br>
<br>
<?php
if (isset($typnum) && $typnum == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typnum) && $typnum == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
if (isset($team) && (strpos($team, "CREM") !== false)) {//CREM
  $typnum = "vis";
  $vis = "checked";
  $inv = "";
}
?>
<b>Options d'affichage et d'export</b> :<br>
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
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Auteurs (tous): 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typaut" value="soul" <?php echo $soul;?>>soulignés
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typaut" value="gras" <?php echo $gras;?>>gras
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typaut" value="aucun" <?php echo $auc;?>>aucun
<br>
<?php
if (isset($typnom) && $typnom == "nominit" || !isset($team)) {$nominit = "checked";}else{$nominit = "";}
if (isset($typnom) && $typnom == "nomcomp1") {$nomcomp1 = "checked";}else{$nomcomp1 = "";}
if (isset($typnom) && $typnom == "nomcomp2") {$nomcomp2 = "checked";}else{$nomcomp2 = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Auteurs (tous): 
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
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Auteurs (de la collection): 
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
 premiers auteurs (« et al. »): 
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
if (isset($typidh) && $typidh == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typidh) && $typidh == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Identifiant HAL : 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typidh" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typidh" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($typreva) && $typreva == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typreva) && $typreva == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Rang revues HCERES : 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typreva" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typreva" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($typrevc) && $typrevc == "vis") {$vis = "checked";}else{$vis = "";}
if (isset($typrevc) && $typrevc == "inv" || !isset($team)) {$inv = "checked";}else{$inv = "";}
if (isset($team) && strpos($team, "CREM") !== false) {//Collection CREM
  $typrevc = "vis";
  $vis = "checked";
  $inv = "";
}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Rang revues CNRS : 
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typrevc" value="vis" <?php echo $vis;?>>visible
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="typrevc" value="inv" <?php echo $inv;?>>invisible
<br>
<?php
if (isset($delim) && $delim == ",") {$virg = "selected";}else{$virg = "";}
if (isset($delim) && $delim == ";") {$pvir = "selected";}else{$pvir = "";}
if (isset($delim) && $delim == "£") {$poun = "selected";}else{$poun = "";}
if (isset($delim) && $delim == "§") {$para = "selected";}else{$para = "";}
?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; Délimiteur export CSV : 
<select name="delim">
<option value=',' <?php echo $virg;?>>Virgule</option>
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
if (isset($typeqp) && $typeqp == "oui") {//Numérotation/codification par équipe
  if (isset($_POST["soumis"])) {
    for($i = 1; $i <= $nbeqp; $i++) {
      echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Nom HAL équipe '.$i.' : <input type="text" name="eqp'.$i.'" value = "'.$_POST['eqp'.$i].'" size="30"><br>');
    }
  }
  if (isset($_GET["team"])) {
    for($i = 1; $i <= $nbeqp; $i++) {
      echo('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Nom HAL équipe '.$i.' : <input type="text" name="eqp'.$i.'" value = "'.$_GET['eqp'.$i].'" size="30"><br>');
    }
  }
}
?>
</div>
<br><br>
<input type="submit" value="Valider" name="soumis">
</form>

<br>
<?php
//Quelques liens pour les utilitaires
if (isset($_POST["soumis"]) || isset($_GET["team"])) {
  //URL de sauvegarde raccourcie via Bitly
  if (strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {include_once('bitly_local.php');}
  if (strpos($_SERVER['HTTP_HOST'], 'ecobio') !== false) {include_once('bitly_ecobio.php');}
  if (strpos($_SERVER['HTTP_HOST'], 'halur1') !== false) {include_once('bitly_halur1.php');}
  $results = bitly_v3_shorten($urlsauv, 'a77347d33877d34446fa9a61d17bdcfafd70a087', 'bit.ly');
  //var_dump($results);
  $urlbitly = $results["url"];

  echo("<center><b><a target='_blank' href='./HAL/extractionHAL.rtf'>Exporter les données affichées en RTF</a></b> ou <b><a target='_blank' href='./HAL/extractionHAL.csv'>en CSV</a>");
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
if (isset($choix_publis) && strpos($choix_publis, "-ACLRI-") !== false) {echo('<li><a href="#ACLRI">Articles de revues à comité de lecture de revues internationales</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRN-") !== false) {echo('<li><a href="#ACLRN">Articles de revues à comité de lecture de revues nationales</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRI-") !== false) {echo('<li><a href="#ASCLRI">Articles de revues sans comité de lecture de revues internationales</a></li>');}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRN-") !== false) {echo('<li><a href="#ASCLRN">Articles de revues sans comité de lecture de revues nationales</a></li>');}
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
if (isset($choix_comm) && strpos($choix_comm, "-CINV-") !== false) {echo('<li><a href="#CINV">Communications invitées</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINV-") !== false) {echo('<li><a href="#CNONINV">Communications non invitées</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CINVI-") !== false) {echo('<li><a href="#CINVI">Communications invitées internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVI-") !== false) {echo('<li><a href="#CNONINVI">Communications non invitées internationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CINVN-") !== false) {echo('<li><a href="#CINVN">Communications invitées nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVN-") !== false) {echo('<li><a href="#CNONINVN">Communications non invitées nationales</a></li>');}
if (isset($choix_comm) && strpos($choix_comm, "-CP-") !== false) {echo('<li><a href="#CP">Communications par affiches (posters)</a></li>');}
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
if (isset($anneedeb) && isset($anneefin) && $anneedeb != $anneefin) {
  $iann = $anneedeb;
  while ($iann <= $anneefin) {
    if ($iann == $anneedeb) {$specificRequestCode .= "%20AND%20(";}else{$specificRequestCode .= "%20OR%20";}
    $specificRequestCode .= 'producedDateY_i:"'.$iann.'"';
    $iann++;
  }
  $specificRequestCode .= ')';
}else{
  if (!isset($anneedeb)) {$anneedeb = date('Y', time());}
  $specificRequestCode .= '%20AND%20producedDateY_i:"'.$anneedeb.'"';
}

function getReferences($infoArray,$sortArray,$docType,$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp){
   include "ExtractionHAL-rang-AERES-SHS.php";
   include "ExtractionHAL-rang-CNRS.php";
   $docType_s=$docType;
   $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=0");
   //echo "http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=0";
   if ($docType_s=="OUV+COUV"){
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\")".$specificRequestCode."&rows=0");
   }
   if ($docType_s=="OUV+DOUV"){
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=0");
   }
   if ($docType_s=="OUV+COUV+DOUV"){
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=0");
   }
   if ($docType_s=="UNDEF"){
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20docType_s:\"UNDEFINED\"".$specificRequestCode."&rows=0");
   }
   if ($docType_s!="OUV+COUV" && $docType_s!="OUV+DOUV" && $docType_s!="OUV+COUV+DOUV" && $docType_s!="UNDEF"){
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=0");
    }
   $contents = utf8_encode($contents);
   $results = json_decode($contents);
   $numFound=$results->response->numFound;
   
   
   //Extracted fields depend on type of reference:
   $fields="docid,authFirstName_s,authLastName_s,authFullName_s,title_s,files_s,label_s,seeAlso_s";
   if ($docType_s=="ART"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,journalTitle_s,journalIssn_s,volume_s,issue_s,page_s,producedDateY_i,proceedings_s,files_s,label_s,doiId_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
   }
   if ($docType_s=="COMM"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,comment_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,conferenceStartDateD_i,conferenceStartDateM_i,conferenceStartDateY_i,conferenceEndDateD_i,conferenceEndDateM_i,conferenceEndDateY_i,collCode_s";
   }
   if ($docType_s=="POSTER"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
   }
   if ($docType_s=="OTHER" or $docType_s=="OTHERREPORT"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,comment_s,files_s,label_s,description_s,seeAlso_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
   }
   if ($docType_s=="REPORT"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,conferenceTitle_s,city_s,country_s,conferenceStartDate_s,producedDateY_i,proceedings_s,comment_s,files_s,label_s,description_s,seeAlso_s,halId_s,pubmedId_s,arxivId_s,reportType_s,number_s,authorityInstitution_s,page_s,collCode_s";
   }
   if ($docType_s=="OUV" or $docType_s=="DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
   }
   if ($docType_s=="COUV" or $docType_s=="DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
   }
   if ($docType_s=="PATENT"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,number_s,producedDateY_i,producedDateY_i,seeAlso_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
   }
   //Cas particulierS pour combinaison OUV et UNDEFINED
   if ($docType_s=="OUV+COUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\")".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s=="OUV+DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s=="OUV+COUV+DOUV"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,bookTitle_s,scientificEditor_s,publisher_s,producedDateY_i,proceedings_s,files_s,label_s,halId_s,pubmedId_s,arxivId_s,collCode_s";
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20(docType_s:\"OUV\"%20OR%20docType_s:\"COUV\"%20OR%20docType_s:\"DOUV\")".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s=="UNDEF"){
      $fields="docid,authFirstName_s,authLastName_s,authFullName_s,authAlphaLastNameFirstNameId_fs,title_s,journalTitle_s,volume_s,issue_s,page_s,producedDateY_i,proceedings_s,files_s,label_s,doiId_s,halId_s,pubmedId_s,arxivId_s,seeAlso_s,localReference_s,collCode_s";
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20docType_s:\"UNDEFINED\"".$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
   }
   if ($docType_s!="OUV+COUV" && $docType_s!="OUV+DOUV" && $docType_s!="OUV+COUV+DOUV" && $docType_s!="UNDEF"){
      $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc");
      //$contents = utf8_encode($contents);
    }
   //echo "http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$collCode_s."%20AND%20docType_s:".$docType_s.$specificRequestCode."&rows=".$numFound."&fl=".$fields."&sort=auth_sort%20asc";
   $results = json_decode($contents);
   //var_dump($results);
   foreach($results->response->docs as $entry){
      $img="";
      $chaine1 = "";
      $chaine2 = "";
      if($entry->files_s){
         $img="<a href=\"".$entry->files_s[0]."\"><img
         src=\"http://haltools-new.inria.fr/images/Haltools_pdf.png\"/></a>";
      }
      $img.=" <a href=\"http://api.archives-ouvertes.fr/search/".$institut."?q=docid:".$entry->docid."&wt=bibtex\"><img
      src=\"http://haltools-new.inria.fr/images/Haltools_bibtex3.png\"/></a>";
   
      $entryInfo = "";
      
      //Adding collCode_s for specific case GR
      $listColl = "~";
      if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {
        foreach($entry->collCode_s as $coll){
        if (strpos($listColl, "~".$coll."~") === false) {
          $listColl .= "~".$coll."~";
          //echo 'toto : '.$coll.'<br>'.$entry->title_s[0].'<br>';
            for($i = 1; $i <= $nbeqp; $i++) {
              if (isset($_POST["soumis"])) {
                if ($coll == $_POST['eqp'.$i]) {
                  $entryInfo .= "GR".$i." - ¤ - ";
                  $eqpgr = $_POST['eqp'.$i];
                  break;
                }
              }
              if (isset($_GET["team"])) {
                if ($coll == $_GET['eqp'.$i]) {
                  $entryInfo .= "GR".$i." - ¤ - ";
                  $eqpgr = $_GET['eqp'.$i];
                  break;
                }
              }
            }
          }
        }
        $chaine1 .= "Collection";
        $chaine2 .= $entryInfo;
      }
      //Adding authors:
      $initial = 1;
      $i = 0;
      foreach($entry->authLastName_s as $nom){
        //$nom = ucwords(mb_strtolower($nom, 'UTF-8'));
        $nom = nomCompEntier($nom);
        $prenom = ucfirst(mb_strtolower($entry->authFirstName_s[$i], 'UTF-8'));
        //Si, Nom, initiale du prénom
        if ($typnom == "nominit") {
          //si prénom composé et initiales
          $prenom = prenomCompInit($prenom);
          if ($initial == 1){
            $initial = 0;
            $authors = "";
          }else{
            $authors .= ", ";
          }
          if (strpos($listenominit, $nom.", ".$prenom) === false) {
          $deb = "";$fin = "";
          }else{
            if ($typcol == "soul") {$deb = "<u>";$fin = "</u>";}
            if ($typcol == "gras") {$deb = "<b>";$fin = "</b>";}
            if ($typcol == "aucun") {$deb = "";$fin = "";}
          }
          $authors .= $deb.$nom.", ".$prenom.$fin;
        }else{//Si nom/prénom complets
          if ($typnom == "nomcomp1") {//Nom Prénom
            if ($initial == 1){
              $initial = 0;
              $authors = "";
            }else{
              $authors .= ", ";
            }
            $prenom = prenomCompEntier($prenom);
            if (strpos($listenomcomp1, $nom." ".$prenom) === false) {
              $deb = "";$fin = "";
            }else{
              if ($typcol == "soul") {$deb = "<u>";$fin = "</u>";}
              if ($typcol == "gras") {$deb = "<b>";$fin = "</b>";}
              if ($typcol == "aucun") {$deb = "";$fin = "";}
            }
            $authors .= $deb.$nom." ".$prenom.$fin;
          }else{//Prénom Nom
            if ($initial == 1){
              $initial = 0;
              $authors = "";
            }else{
              $authors .= ", ";
            }
            $prenom = prenomCompEntier($prenom);
            if (strpos($listenomcomp2, $prenom." ".$nom) === false) {
              $deb = "";$fin = "";
            }else{
              if ($typcol == "soul") {$deb = "<u>";$fin = "</u>";}
              if ($typcol == "gras") {$deb = "<b>";$fin = "</b>";}
              if ($typcol == "aucun") {$deb = "";$fin = "";}
            }
            $authors .= $deb.$prenom." ".$nom.$fin;
          }
        }
        $i++;
      }
      //Limiting to 5, 10, 15 or 20 authors + et al.
      if (isset($typlim) && $typlim == "oui") {
        $cpt = 1;
        $pospv = 0;
        $lim_aut_ok = 1;
        if ($typnom == "nominit") {$limvirg = 2*$limaff;}else{$limvirg = $limaff;}
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
          $extract = mb_substr($authors, 0, $pospv, 'UTF-8');
          $extract .= " <i> et al.</i>";
        }else{
          if ($typnom != "nominit") {
            $extract .= ".";
          }
        }
      }else{
        $extract = $authors;
      }
      if ($typaut == "soul") {$extract = "<u>".$extract."</u>";}
      if ($typaut == "gras") {$extract = "<b>".$extract."</b>";}

      $entryInfo .= $extract;
      if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {
        $chaine1 .= $delim."Auteurs";
        $chaine2 .= $delim.strip_tags($extract);
      }else{
        $chaine1 .= "Auteurs";
        $chaine2 .= strip_tags($extract);
      }

      //Adding producedDateY_i:
      $chaine1 .= $delim."Année";
      if ($typann == "apres") {//Année après les auteurs
        if ($docType_s=="ART" || $docType_s=="UNDEF" || $docType_s=="COMM" || $docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" or $docType_s=="OUV+COUV" or $docType_s=="OUV+DOUV" or $docType_s=="OUV+COUV+DOUV" or $docType_s=="OTHER" or $docType_s=="OTHERREPORT" or $docType_s=="REPORT"){
           $entryInfo = $entryInfo." (".$entry->producedDateY_i.")";
           $chaine2 .= $delim.$entry->producedDateY_i;
        }else{
          $chaine2 .= $delim;
        }
      }else{
        $entryInfo .= ", ";
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
      $titre = cleanup_title($entry->title_s[0]);
      $entryInfo = $entryInfo.$point.$deb.$titre.$fin;
      $chaine2 .= $delim.$titre;
      
      //Adding bookTitle_s:
      $chaine1 .= $delim."Titre ouvrage";
      if ($docType_s=="COUV"){
        $entryInfo = $entryInfo.", <i>".$entry->bookTitle_s."</i>";
        $chaine2 .= $delim.$entry->bookTitle_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding journalTitle_s:
      $chaine1 .= $delim."Titre journal";
      if ($docType_s=="ART"){
        $entryInfo = $entryInfo.". <i>".$entry->journalTitle_s."</i>";
        $chaine2 .= $delim.$entry->journalTitle_s;
      }else{
        $chaine2 .= $delim;
      }

      $chaine1 .= $delim."Année";
      if ($typann == "avant") {//Année avant le numéro de volume
        if ($docType_s=="ART" || $docType_s=="UNDEF"){
          $entryInfo = $entryInfo.", ".$entry->producedDateY_i.",";
          $chaine2 .= $delim.$entry->producedDateY_i;
        }else{
          $chaine2 .= $delim;
        }
        if ($docType_s=="COMM"){
          $entryInfo = $entryInfo.", ".$entry->producedDateY_i;
          $chaine2 .= $delim.$entry->producedDateY_i;
        }else{
          $chaine2 .= $delim;
        }
      }else{
        $entryInfo .= ", ";
        $chaine2 .= $delim;
      }
   
      $hasVolumeOrNumber=0;
      $toAppear=0;
      //Adding volume_s:
      $vol = "";
      $chaine1 .= $delim."Volume";
      if ($docType_s=="ART"){
         if(!is_array($entry->volume_s)){
            if($entry->volume_s!="" and $entry->volume_s!=" " and $entry->volume_s!="-" and $entry->volume_s!="()"){
               if(toAppear($entry->volume_s)){
                  $toAppear=1;
               } else {
                  if ($typfor == "typ2") {
                    $entryInfo = $entryInfo." ".$entry->volume_s;
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
      if ($docType_s=="ART"){
         if(!is_array($entry->issue_s[0])){
            if($entry->issue_s[0]!="" and $entry->issue_s[0]!=" " and $entry->issue_s[0]!="-" and $entry->issue_s[0]!="()"){
               if(toAppear($entry->issue_s[0])){
                  $toAppear=1;
               }else{
                  if ($typfor == "typ2") {
                    $entryInfo = $entryInfo."(".$entry->issue_s[0].")";
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
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV"){
         if(count($entry->scientificEditor_s)>0){
            $initial = 1;
            foreach($entry->scientificEditor_s as $editor){
               if ($initial==1){
                  $entryInfo = $entryInfo.", ".$editor;
                  $chaine2 .= $delim.$entry->scientificEditor_s;
                  $initial=0;
               } else {
                  $entryInfo = $entryInfo.", ".$editor;
                  $chaine2 .= $delim.$entry->scientificEditor_s;
               }
            }
         }else{
          $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }
         
      //Adding publisher_s:
      $chaine1 .= $delim."Editeur revue";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV"){
         if(!$entry->publisher_s[0]==""){
            $entryInfo = $entryInfo.", ".$entry->publisher_s[0];
            $chaine2 .= $delim.$entry->publisher_s[0];
         }else{
          $chaine2 .= $delim.$entry->publisher_s[0];
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding page_s:
      $chaine1 .= $delim."Volume, Issue, Pages";
      if ($docType_s=="ART" or $docType_s=="COUV"){
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
         if(mb_substr($page,0,1, 'UTF-8')==" "){
            $page=mb_substr($page,-(strlen($page)-1), 'UTF-8');
         }
         if(toAppear($page)){
            $toAppear=1;
         }
         if($toAppear==1){
            $entryInfo = $entryInfo.", to appear";
            $chaine2 .= $delim."to appear";
         } else {
            if(!($page=="?" or $page=="-" or $page=="" or $page==" " or $page=="–")){
              if ($typfor == "typ2") {
               if($hasVolumeOrNumber==1){
                  $entryInfo = $entryInfo.":".$page;
                  $chaine2 .= $delim.$page;
               }else{
                  $entryInfo = $entryInfo." ".$page;
                  $chaine2 .= $delim.$page;
               }
              }else{
                  if ($vol != "") {$entryInfo .= " vol ".$vol;$chaine2 .= $delim." vol ".$vol;}else{$chaine2 .= $delim;}
                  if ($iss != "") {$entryInfo .= ", n°".$iss;$chaine2 .= " ,n° ".$iss;}
                  if ($page != "") {
                    if (is_numeric(mb_substr($page,1, 'UTF-8'))) {
                      $entryInfo .= ", pp. ".$page;
                      $chaine2 .= ", pp. ".$page;
                    }else{
                      $entryInfo .= ", ".$page;
                      $chaine2 .= $page;
                    }
                  }
              }
            }else{
              $chaine2 .= $delim;
            }
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding conferenceTitle_s:
      $chaine1 .= $delim."Titre conférence";
      if ($docType_s=="COMM" or $docType_s=="POSTER"){
         $entryInfo = $entryInfo.", ".$entry->conferenceTitle_s;
         $chaine2 .= $delim.$entry->conferenceTitle_s;
      }else{
         $chaine2 .= $delim;
      }
       
      //Adding comment:
      $chaine1 .= $delim."Commentaire";
      if (($docType_s=="COMM" and $specificRequestCode=="%20AND%20invitedCommunication_s:1") or ($docType_s=="OTHER") or ($docType_s=="OTHERREPORT")){
         if ($entry->comment_s!="" and $entry->comment_s!=" " and $entry->comment_s!="-" and $entry->comment_s!="?"){
           $entryInfo = $entryInfo.", ".$entry->comment_s;
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
      if ($docType_s=="COMM" or $docType_s=="POSTER"){
        if ($entry->conferenceStartDateY_i != "" && $entry->conferenceStartDateY_i == $entry->conferenceEndDateY_i) {//même année
          if ($entry->conferenceStartDateM_i != "" && $entry->conferenceStartDateM_i == $entry->conferenceEndDateM_i) {//même mois
            if ($entry->conferenceStartDateD_i != "" && $entry->conferenceStartDateD_i == $entry->conferenceEndDateD_i) {//même jour
              $entryInfo .= ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              $chaine2 .= $delim.$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
            }else{//jours différents
              if ($entry->conferenceStartDateD_i != "") {
                $entryInfo .= ", ".$entry->conferenceStartDateD_i;
                $chaine2 .= $delim.$entry->conferenceStartDateD_i;
              }
              if ($entry->conferenceEndDateD_i != "" && $entry->conferenceEndDateM_i != "" && $entry->conferenceEndDateY_i != "") {
                $entryInfo .= "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
                $chaine2 .= $delim.$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              }
            }
          }else{//mois différents
            if ($entry->conferenceStartDateD_i != "" && $entry->conferenceStartDateM_i != "") {
              $entryInfo .= ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i];
              $chaine2 .= $delim.$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i];
            }
            if ($entry->conferenceEndDateD_i != "" && $entry->conferenceEndDateM_i != "" && $entry->conferenceEndDateY_i != "") {
              $entryInfo .= "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
              $chaine2 .= $delim.$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
            }
          }
        }else{//années différentes
          if ($entry->conferenceStartDateD_i != "" && $entry->conferenceStartDateM_i != "" && $entry->conferenceStartDateY_i != "") {
            $entryInfo .= ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i]." ".$entry->conferenceStartDateY_i;
            $chaine2 .= $delim.$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i]." ".$entry->conferenceStartDateY_i;
          }
          if ($entry->conferenceEndDateD_i != "" && $entry->conferenceEndDateM_i != "" && $entry->conferenceEndDateY_i != "") {
            $entryInfo .= " - ".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
            $chaine2 .= $delim.$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
          }
        }
      }else{
        $chaine2 .= $delim;
      }

      //Adding city_s:
      $chaine1 .= $delim."Ville";
      if ($docType_s=="COMM" or $docType_s=="POSTER"){
         if($entry->city_s!=""){
            $entryInfo = $entryInfo.", ".$entry->city_s;
            $chaine2 .= $delim.$entry->city_s;
         }else{
        $chaine2 .= $delim;
        }
      }else{
        $chaine2 .= $delim;
      }
   
      //Adding country_t:
      $chaine1 .= $delim."Pays";
      if ($docType_s=="COMM" or $docType_s=="POSTER"){
         if($entry->country_s!=""){
           $entryInfo = $entryInfo." (".$countries[$entry->country_s].").";
           $chaine2 .= $delim.$countries[$entry->country_s];
         }else{
           $entryInfo = $entryInfo.".";
           $chaine2 .= $delim;
         }
      }else{
        $chaine2 .= $delim;
      }

      //Adding conferenceStartDate_s:
      //if ($docType_s=="COMM" or $docType_s=="POSTER"){
         //$entryInfo = $entryInfo.", ".$entry->conferenceStartDate_s;
      //}

      //Adding patent number:
      $chaine1 .= $delim."Patent n°";
      if ($docType_s=="PATENT"){
        $entryInfo = $entryInfo." Patent n°".$entry->number_s[0];
        $chaine2 .= $delim.$entry->number_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Adding producedDateY_i:
      $chaine1 .= $delim."Date de publication";
      if ($docType_s=="PATENT"){
        $entryInfo = $entryInfo." (".$entry->producedDateY_i.")";
        $chaine2 .= $delim.$entry->producedDateY_i;
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
        $entryInfo = $entryInfo.". ".$reportType;
        $chaine2 .= $delim.$reportType;
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding number_s:
      $chaine1 .= $delim."N°";
      if ($docType_s=="REPORT" && isset($entry->number_s)) {
         $entryInfo = $entryInfo.", N°".$entry->number_s[0];
         $chaine2 .= $delim.$entry->number_s[0];
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding authorityInstitution_s:
      $chaine1 .= $delim."Organisme de délivrance";
      if ($docType_s=="REPORT" && isset($entry->authorityInstitution)) {
         $entryInfo = $entryInfo.". ".$entry->authorityInstitution;
         $chaine2 .= $delim.$entry->authorityInstitution;
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding page_s:
      $chaine1 .= $delim."Pages";
      if ($docType_s=="REPORT" && isset($entry->page_s)) {
         $entryInfo = $entryInfo.". ".$entry->page_s;
         $chaine2 .= $delim.$entry->page_s;
         if (strpos($entry->page_s, "p") === false) {$entryInfo .= "p.";}
      }else{
        $chaine2 .= $delim;
      }

      //Adding producedDateY_i:
      $chaine1 .= $delim."Date de publication";
      if ($docType_s=="OUV" or $docType_s=="DOUV" or $docType_s=="COUV" or $docType_s=="OUV+COUV" or $docType_s=="OUV+DOUV" or $docType_s=="OUV+COUV+DOUV" or $docType_s=="OTHER" or ($docType_s=="OTHERREPORT") or ($docType_s=="REPORT")){
         if ($typann == "avant") {
            $entryInfo = $entryInfo.", ".$entry->producedDateY_i.".";
            $chaine2 .= $delim.$entry->producedDateY_i;
         }else{
        $chaine2 .= $delim;
      }
      }else{
        $chaine2 .= $delim;
      }
      
      //Corrections diverses
      $entryInfo =str_replace("..", ".", $entryInfo);
      $entryInfo =str_replace("?.", "?", $entryInfo);
      $entryInfo =str_replace("?,", "?", $entryInfo);
      $entryInfo =str_replace(", , ", ", ", $entryInfo);
      $entryInfo =str_replace("<br>. ", ".<br>", $entryInfo);
      $rtfInfo = $entryInfo;

      //Adding DOI
      $rtfdoi = "";
      $chaine1 .= $delim."DOI";
      if (isset($entry->doiId_s) && $typdoi == "vis") {
        $entryInfo = $entryInfo.". doi: <a target='_blank' href='http://dx.doi.org/".$entry->doiId_s."'>".$entry->doiId_s."</a>";
        $rtfdoi = $entry->doiId_s;
        $chaine2 .= $delim.$entry->doiId_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding Pubmed ID
      $rtfpubmed = "";
      $chaine1 .= $delim."Pubmed";
      if (isset($entry->pubmedId_s)) {
        $entryInfo = $entryInfo.". Pubmed: <a target='_blank' href='http://www.ncbi.nlm.nih.gov/pubmed/".$entry->pubmedId_s."'>".$entry->pubmedId_s."</a>";
        $rtfpubmed = $entry->pubmedId_s;
        $chaine2 .= $delim.$entry->pubmedId_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding localReference_s
      $rtflocref = "";
      $chaine1 .= $delim."Référence";
      if ($docType_s=="UNDEF" && isset($entry->localReference_s)) {
        $entryInfo = $entryInfo.". Référence: ".$entry->localReference_s[0];
        $rtflocref = $entry->localReference_s[0];
        $chaine2 .= $delim.$entry->localReference_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Adding ArXiv ID
      $rtfarxiv = "";
      $chaine1 .= $delim."ArXiv";
      if (isset($entry->arxivId_s) && $typidh != "vis") {
        $entryInfo = $entryInfo.". ArXiv: <a target='_blank' href='http://arxiv.org/abs/".$entry->arxivId_s."'>".$entry->arxivId_s."</a>";
        $rtfarxiv = $entry->arxivId_s;
        $chaine2 .= $delim.$entry->arxivId_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding description_s
      $rtfdescrip = "";
      $chaine1 .= $delim."Description";
      if ($docType_s=="OTHER" && isset($entry->description_s)) {
        $entryInfo = $entryInfo.". ".ucfirst($entry->description_s);
        $rtfdescrip = $entry->description_s;
        $chaine2 .= $delim.$entry->description_s;
      }else{
        $chaine2 .= $delim;
      }

      //Adding seeAlso_s
      $rtfalso = "";
      $chaine1 .= $delim."Voir aussi";
      if (($docType_s=="PATENT" || $docType_s=="REPORT" || $docType_s=="UNDEF" || $docType_s=="OTHER") && isset($entry->seeAlso_s)) {
        $entryInfo = $entryInfo.". URL: <a target='_blank' href='".$entry->seeAlso_s[0]."'>".$entry->seeAlso_s[0]."</a>";
        $rtfalso = $entry->seeAlso_s[0];
        $chaine2 .= $delim.$entry->seeAlso_s[0];
      }else{
        $chaine2 .= $delim;
      }

      //Adding référence HAL
      $rtfrefhal = "";
      $chaine1 .= $delim."Réf. HAL";
      if (isset($entry->halId_s) && $typidh == "vis") {
        $entryInfo = $entryInfo.". Réf. HAL: <a target='_blank' href='https://hal-univ-rennes1.archives-ouvertes.fr/".$entry->halId_s."'>".$entry->halId_s."</a>";
        $rtfrefhal = $entry->halId_s;
        $chaine2 .= $delim.$entry->halId_s;
      }else{
        $chaine2 .= $delim;
      }
      
      //Adding rang HCERES
      $rtfaeres = "";
      $chaine1 .= $delim."Rang HCERES";
      if ($docType_s=="ART" && isset($entry->journalIssn_s) && $typreva == "vis") {
        foreach($AERES_SHS AS $i => $valeur) {
          if (($AERES_SHS[$i]['issn'] == $entry->journalIssn_s) && ($AERES_SHS[$i]['rang'] != "")) {
            $entryInfo = $entryInfo.". Rang HCERES: ".$AERES_SHS[$i]['rang'];
            $rtfaeres = $AERES_SHS[$i]['rang'];
            $chaine2 .= $delim.$AERES_SHS[$i]['rang'];
            break;
          }
        }
        $chaine2 .= $delim;
      }else{
        $chaine2 .= $delim;
      }
       
      //Adding rang CNRS      
      $rtfcnrs = "";
      $chaine1 .= $delim."Rang CNRS";
      if ($docType_s=="ART" && $typrevc == "vis") {
        foreach($CNRS AS $i => $valeur) {
          if (($CNRS[$i]['titre'] == $entry->journalTitle_s) && ($CNRS[$i]['rang'] != "")) {
            $entryInfo = $entryInfo.". Rang CNRS: ".$CNRS[$i]['rang'];
            $rtfcnrs = $CNRS[$i]['rang'];
            $chaine2 .= $delim.$CNRS[$i]['rang'];
            break;
          }
        }
        $chaine2 .= $delim;
      }else{
        $chaine2 .= $delim;
      }
      
      //Corrections diverses
      $entryInfo =str_replace("..", ".", $entryInfo);
      $entryInfo =str_replace(", .", ".", $entryInfo);
              
      //Adding the reference to the array
      array_push($infoArray,$entryInfo);      
      if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {
        //créer un tableau avec GR1,2,3... + (10000 - année) + premier auteur + année et faire un tri ensuite dessus ?
        array_push($sortArray,substr(10000-($entry->producedDateY_i),0,5)."-".$eqpgr."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$entry->producedDateY_i);
      }else{
        array_push($sortArray,substr(10000-($entry->producedDateY_i),0,5)."-".$entry->authAlphaLastNameFirstNameId_fs[0]."-".$entry->title_s[0]."-".$entry->producedDateY_i);
      }
      //array_push($sortArray,$entry->producedDateY_i);
      array_push($rtfArray,$rtfInfo."~".$rtfdoi."~".$rtfpubmed."~".$rtflocref."~".$rtfarxiv."~".$rtfdescrip."~".$rtfalso."~".$rtfrefhal."~".$rtfaeres."~".$rtfcnrs."~".$chaine1."~".$chaine2);
   }
   $result=array();
   array_push($result,$infoArray);
   array_push($result,$sortArray);
   array_push($result,$rtfArray);
   return $result;
}

function toAppear($string){
   $toAppear=0;
   if (strtolower($string)=="accepted" or strtolower($string)=="accepté" or strtolower($string)=="to appear" or strtolower($string)=="accepted manuscript"){
      $toAppear=1;
   }
   return $toAppear;
}

function displayRefList($docType_s,$collCode_s,$specificRequestCode,$countries,$refType,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp){
   $infoArray = array();
   $sortArray = array();
   $rtfArray = array();
   
   if ($docType_s=="COMPOSTER"){
      //Request on a union of HAL types
      //COMM ACTI
      $result = getReferences($infoArray,$sortArray,"COMM",$collCode_s,"%20AND%20proceedings_s:1%20AND%20audience_s:2".$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
      //$result = getReferences($infoArray,$sortArray,"COMM",$collCode_s,$specificRequestCode,$countries);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
      //COMM ACTN
      $result = getReferences($infoArray,$sortArray,"COMM",$collCode_s,"%20AND%20proceedings_s:1%20AND%20audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0".$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
      //$result = getReferences($infoArray,$sortArray,"COMM",$collCode_s,$specificRequestCode,$countries);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
      //COMM COM
      $specificRequestCode = '%20AND%20proceedings_s:0';
      $result = getReferences($infoArray,$sortArray,"COMM",$collCode_s,"%20AND%20proceedings_s:0".$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
      //$result = getReferences($infoArray,$sortArray,"COMM",$collCode_s,$specificRequestCode,$countries);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
      //COMM POSTER
      $result = getReferences($infoArray,$sortArray,"POSTER",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
      $infoArray = $result[0];
      $sortArray = $result[1];
      $rtfArray = $result[2];
   } else {
      if ($docType_s=="VULG"){
      //Request on a union of HAL types
         $result = getReferences($infoArray,$sortArray,"COUV",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
         $infoArray = $result[0];
         $sortArray = $result[1];
         $rtfArray = $result[2];
         $result = getReferences($infoArray,$sortArray,"OUV",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
         $infoArray = $result[0];
         $sortArray = $result[1];
         $rtfArray = $result[2];
      } else {   
         if ($docType_s=="OTHER"){
         //Request on a union of HAL types
            $result = getReferences($infoArray,$sortArray,"OTHER",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
            $infoArray = $result[0];
            $sortArray = $result[1];
            $rtfArray = $result[2];
            $result = getReferences($infoArray,$sortArray,"OTHERREPORT",$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
            $infoArray = $result[0];
            $sortArray = $result[1];
            $rtfArray = $result[2];
         } else {
            //Request on a simple HAL type
            $result = getReferences($infoArray,$sortArray,$docType_s,$collCode_s,$specificRequestCode,$countries,$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
            $infoArray = $result[0];
            $sortArray = $result[1];
            $rtfArray = $result[2];
            //var_dump($result[2]);
         }
      }
   }
   
   array_multisort($sortArray, $infoArray, $rtfArray);
  // var_dump($result);
   
   $currentYear="99999";
   $i=0;
   static $indgr = array();
   static $crogr = array();
   static $drefl = array();
   if ($drefl[0]  == "") {
     for ($j = 1; $j <= $nbeqp; $j++) {
       $indgr[$j] = 1;
       $crogr[$j] = 0;
     }
   }

   $yearNumbers = array();
   foreach($infoArray as $entryInfo){
      if (strcmp($currentYear,substr($sortArray[$i],-4))==0){ // Même année
         $rtf = explode("~", $rtfArray[$i]);
         if (isset($collCode_s) && (strpos($collCode_s, "CREM") !== false)) {//CREM
           $table->addRows(1);
         }
         if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {//GR
           $rtfval = $rtf[0];
           $rtfcha = $rtf[11];
           for ($j = 1; $j <= $nbeqp; $j++) {
             if (strpos($entryInfo,"GR".$j." - ¤ -") !== false) {
               $entryInfo = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $entryInfo);
               $rtfval = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $rtfval);
               $rtfcha = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j], $rtfcha);
               $indgr[$j] += 1;
               if (strpos($entryInfo, " - GR") !== false) {$crogr[$j] += 1;} //publication croisée
             }
           }
         }
         for ($j = 1; $j <= $nbeqp; $j++) {
           $entryInfo = str_replace("GR".$j, $nomeqp[$j], $entryInfo);
           $rtfval = str_replace("GR".$j, $nomeqp[$j], $rtfval);
           $rtfcha = str_replace("GR".$j, $nomeqp[$j], $rtfcha);
         }
         if ($typnum == "vis") {
           $ind = $i + 1;
           echo "<p>".$ind.". ".$entryInfo."</p>";
           if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){//GR
             if (isset($collCode_s) && (strpos($collCode_s, "CREM") !== false)) {//CREM
               $table->writeToCell($ind, 1, $ind);
               $table->writeToCell($ind, 2, $rtfval);
             }else{
               $sect->writeText($ind.". ".$rtfval, $font);
             }
           }else{
             $sect->writeText($ind.". ".$rtf[0], $font);
           }
         }else{
           echo "<p>".$entryInfo."</p>";
           if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){//GR
             $sect->writeText($rtfval, $font);
           }else{
             $sect->writeText($rtf[0], $font);
           }
         }
         if ($rtf[1] != "") {
            if (isset($collCode_s) && (strpos($collCode_s, "CREM") === false)) {// non CREM
              $sect->writeText(". doi: ", $font);
              $sect->writeHyperLink("http://dx.doi.org/".$rtf[1], "<u>".$rtf[1]."</u>", $fontlien);
            }
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
            $sect->writeHyperLink("https://hal-univ-rennes1.archives-ouvertes.fr/".$rtf[7], "<u>".$rtf[7]."</u>", $fontlien);
         }
         if ($rtf[8] != "") {
            $sect->writeText(". Rang HCERES: ".$rtf[8], $font);
         }
         if ($rtf[9] != "") {
            if (isset($collCode_s) && (strpos($collCode_s, "CREM") !== false)) {//CREM
              $table->writeToCell($ind, 3, $rtf[9]);
            }else{
              $sect->writeText(". Rang CNRS: ".$rtf[9], $font);
            }         }
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
         $Fnm1 = "./HAL/extractionHAL.csv"; 
         $inF = fopen($Fnm1,"a+"); 
         fseek($inF, 0);
         fwrite($inF,$chaine);
      } else { //Année différente
         $rtf = explode("~", $rtfArray[$i]);
         echo "<h3>".substr($sortArray[$i],-4)."</h3>";
         $yearNumbers[substr($sortArray[$i],-4)]=1;
         $currentYear=substr($sortArray[$i],-4);
         $sect->writeText("<b>".substr($sortArray[$i],-4)."</b><br><br>", $fonth3);
         if (isset($collCode_s) && (strpos($collCode_s, "CREM") !== false)) {//CREM
           $table = $sect->addTable();
           $table->addRows(1);
           $table->addColumnsList(array(1,12,1.5,1.5));
         }
         if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)) {//GR
           $rtfval = $rtf[0];
           $rtfcha = $rtf[11];
           for ($j = 1; $j <= $nbeqp; $j++) {
             if (strpos($entryInfo,"GR".$j." - ¤ -") !== false) {
               $entryInfo = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $entryInfo);
               $rtfval = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j]." -", $rtfval);
               $rtfcha = str_replace("GR".$j." - ¤ -", "GR".$j." - ".$indgr[$j], $rtfcha);
               $indgr[$j] += 1;
               if (strpos($entryInfo, " - GR") !== false) {$crogr[$j] += 1;} //publication croisée
             }
           }
         }
         for ($j = 1; $j <= $nbeqp; $j++) {
           $entryInfo = str_replace("GR".$j, $nomeqp[$j], $entryInfo);
           $rtfval = str_replace("GR".$j, $nomeqp[$j], $rtfval);
           $rtfcha = str_replace("GR".$j, $nomeqp[$j], $rtfcha);
         }
         if ($typnum == "vis") {
           $ind = $i + 1;
           echo "<p>".$ind.". ".$entryInfo."</p>";
           if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){//GR
             if (isset($collCode_s) && (strpos($collCode_s, "CREM") !== false)) {//CREM
               $table->writeToCell($ind, 1, $ind);
               $table->writeToCell($ind, 2, $rtfval);
             }else{
               $sect->writeText($ind.". ".$rtfval, $font);
             }
           }else{
             $sect->writeText($ind.". ".$rtf[0], $font);
           }
         }else{
           echo "<p>".$entryInfo."</p>";
           if (isset($collCode_s) && isset($gr) && (strpos($gr, $collCode_s) !== false)){
             $sect->writeText($rtfval, $font);
           }else{
             $sect->writeText($rtf[0], $font);
           }
         }
         if ($rtf[1] != "") {
            if (isset($collCode_s) && (strpos($collCode_s, "CREM") === false)) {// non CREM
              $sect->writeText(". doi: ", $font);
              $sect->writeHyperLink("http://dx.doi.org/".$rtf[1], "<u>".$rtf[1]."</u>", $fontlien);
            }
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
            $sect->writeHyperLink("https://hal-univ-rennes1.archives-ouvertes.fr/".$rtf[7], "<u>".$rtf[7]."</u>", $fontlien);
         }
         if ($rtf[8] != "") {
            $sect->writeText(". Rang HCERES: ".$rtf[8], $font);
         }
         if ($rtf[9] != "") {
            if (isset($collCode_s) && (strpos($collCode_s, "CREM") !== false)) {//CREM
              $table->writeToCell($ind, 3, $rtf[9]);
            }else{
              $sect->writeText(". Rang CNRS: ".$rtf[9], $font);
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
         $Fnm1 = "./HAL/extractionHAL.csv"; 
         $inF = fopen($Fnm1,"a+"); 
         fseek($inF, 0);
         fwrite($inF,$chaine);
      }
      $i++;
   }
   $Fnm1 = "./HAL/extractionHAL.csv"; 
   $inF = fopen($Fnm1,"a+"); 
   fseek($inF, 0);
   fwrite($inF,chr(13).chr(10));
   $drefl[0] = $yearNumbers;//le nombre de publications
   $drefl[1] = $crogr;//le nombre de publications croisées
   //return $yearNumbers;
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
// ?!?
"xx" => "inconnu");

$numbers=array();

if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {
  $sect->writeText(substr($sortArray[$i],-4)."<br><br>", $font);

  echo "<a name=\"TA\"></a><h2>Tous les articles (sauf vulgarisation) <a href=\"#sommaire\">&#8683;</a></h2>";
  $sect->writeText("<b>Tous les articles (sauf vulgarisation)</b><br><br>", $fonth2);
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Tous les articles (sauf vulgarisation)".chr(13).chr(10));
  list($numbers["TA"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"TA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ACL-") !== false) {
  echo "<a name=\"ACL\"></a><h2>Articles de revues à comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues à comité de lecture".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues à comité de lecture</b><br><br>", $fonth2);
  list($numbers["ACL"],$crores) = displayRefList("ART",$team,"%20AND%20peerReviewing_s:1".$specificRequestCode,$countries,"ACL",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ASCL-") !== false) {
  echo "<a name=\"ASCL\"></a><h2>Articles de revues sans comité de lecture <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues sans comité de lecture".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues sans comité de lecture</b><br><br>", $fonth2);
  list($numbers["ASCL"],$crores) = displayRefList("ART",$team,"%20AND%20peerReviewing_s:0".$specificRequestCode,$countries,"ASCL",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ARI-") !== false) {
  echo "<a name=\"ARI\"></a><h2>Articles de revues internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues internationales".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues internationales</b><br><br>", $fonth2);
  list($numbers["ARI"],$crores) = displayRefList("ART",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"ARI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ARN-") !== false) {
  echo "<a name=\"ARN\"></a><h2>Articles de revues nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues nationales".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues nationales</b><br><br>", $fonth2);
  list($numbers["ARN"],$crores) = displayRefList("ART",$team,"%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)".$specificRequestCode,$countries,"ARN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRI-") !== false) {
  echo "<a name=\"ACLRI\"></a><h2>Articles de revues à comité de lecture de revues internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues à comité de lecture de revues internationales".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues à comité de lecture de revues internationales</b><br><br>", $fonth2);
  list($numbers["ACLRI"],$crores) = displayRefList("ART",$team,"%20AND%20audience_s:2%20AND%20peerReviewing_s:1".$specificRequestCode,$countries,"ACLRI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ACLRN-") !== false) {
  echo "<a name=\"ACLRN\"></a><h2>Articles de revues à comité de lecture de revues nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues à comité de lecture de revues nationales".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues à comité de lecture de revues nationales</b><br><br>", $fonth2);
  list($numbers["ACLRN"],$crores) = displayRefList("ART",$team,"%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20peerReviewing_s:1".$specificRequestCode,$countries,"ACLRN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRI-") !== false) {
  echo "<a name=\"ASCLRI\"></a><h2>Articles de revues sans comité de lecture de revues internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues sans comité de lecture de revues internationales".chr(13).chr(10));
  $sect->writeText("<b>Articles de revues sans comité de lecture de revues internationales</b><br><br>", $fonth2);
  list($numbers["ASCLRI"],$crores) = displayRefList("ART",$team,"%20AND%20audience_s:2%20AND%20peerReviewing_s:0".$specificRequestCode,$countries,"ASCLRI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_publis) && strpos($choix_publis, "-ASCLRN-") !== false) {
  echo "<a name=\"ASCLRN\"></a><h2>Articles de revues sans comité de lecture de revues nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de revues sans comité de lecture de revues nationales".chr(13).chr(10));
  list($numbers["ASCLRN"],$crores) = displayRefList("ART",$team,"%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20peerReviewing_s:0".$specificRequestCode,$countries,"ASCLRN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
  $sect->writeText("<b>Articles de revues sans comité de lecture de revues nationales</b><br><br>", $fonth2);
}
if (isset($choix_publis) && strpos($choix_publis, "-AV-") !== false) {
  echo "<a name=\"AV\"></a><h2>Articles de vulgarisation <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Articles de vulgarisation".chr(13).chr(10));
  $sect->writeText("<b>Articles de vulgarisation</b><br><br>", $fonth2);
  list($numbers["AV"],$crores) = displayRefList("ART",$team,"%20AND%20popularLevel_s:1".$specificRequestCode,$countries,"AV",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-TC-") !== false) {
  echo "<a name=\"TC\"></a><h2>Toutes les communications (sauf grand public) <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Toutes les communications (sauf grand public)".chr(13).chr(10));
  $sect->writeText("<b>Toutes les communications (sauf grand public)</b><br><br>", $fonth2);
  list($numbers["TC"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"TC",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CA-") !== false) {
  echo "<a name=\"CA\"></a><h2>Communications avec actes <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications avec actes".chr(13).chr(10));
  $sect->writeText("<b>Communications avec actes</b><br><br>", $fonth2);
  list($numbers["CA"],$crores) = displayRefList("COMM",$team,"%20AND%20proceedings_s:1".$specificRequestCode,$countries,"CA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CSA-") !== false) {
  echo "<a name=\"CSA\"></a><h2>Communications sans actes <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications sans actes".chr(13).chr(10));
  $sect->writeText("<b>Communications sans actes</b><br><br>", $fonth2);
  list($numbers["CSA"],$crores) = displayRefList("COMM",$team,"%20AND%20proceedings_s:0".$specificRequestCode,$countries,"CSA",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CI-") !== false) {
  echo "<a name=\"CI\"></a><h2>Communications internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications internationales</b><br><br>", $fonth2);
  list($numbers["CI"],$crores) = displayRefList("COMM",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"CI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CN-") !== false) {
  echo "<a name=\"CN\"></a><h2>Communications nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications nationales</b><br><br>", $fonth2);
  list($numbers["CN"],$crores) = displayRefList("COMM",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"CN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CAI-") !== false) {
  echo "<a name=\"CAI\"></a><h2>Communications avec actes internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications avec actes internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications avec actes internationales</b><br><br>", $fonth2);
  list($numbers["CAI"],$crores) = displayRefList("COMM",$team,"%20AND%20proceedings_s:1%20AND%20audience_s:2".$specificRequestCode,$countries,"CAI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CSAI-") !== false) {
  echo "<a name=\"CSAI\"></a><h2>Communications sans actes internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications sans actes internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications sans actes internationales</b><br><br>", $fonth2);
  list($numbers["CSAI"],$crores) = displayRefList("COMM",$team,"%20AND%20proceedings_s:0%20AND%20audience_s:2".$specificRequestCode,$countries,"CSAI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CAN-") !== false) {
  echo "<a name=\"CAN\"></a><h2>Communications avec actes nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications avec actes nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications avec actes nationales</b><br><br>", $fonth2);
  list($numbers["CAN"],$crores) = displayRefList("COMM",$team,"%20AND%20proceedings_s:1%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"CAN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CSAN-") !== false) {
  echo "<a name=\"CSAN\"></a><h2>Communications sans actes nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications sans actes nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications sans actes nationales</b><br><br>", $fonth2);
  list($numbers["CSAN"],$crores) = displayRefList("COMM",$team,"%20AND%20proceedings_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"CSAN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINV-") !== false) {
  echo "<a name=\"CINV\"></a><h2>Communications invitées <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications invitées".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées</b><br><br>", $fonth2);
  list($numbers["CINV"],$crores) = displayRefList("COMM",$team,"%20AND%20invitedCommunication_s:1".$specificRequestCode,$countries,"CINV",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINV-") !== false) {
  echo "<a name=\"CNONINV\"></a><h2>Communications non invitées <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications non invitées".chr(13).chr(10));
  $sect->writeText("<b>Communications non invitées</b><br><br>", $fonth2);
  list($numbers["CNONINV"],$crores) = displayRefList("COMM",$team,"%20AND%20invitedCommunication_s:0".$specificRequestCode,$countries,"CINV",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINVI-") !== false) {
  echo "<a name=\"CINVI\"></a><h2>Communications invitées internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications invitées internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées internationales</b><br><br>", $fonth2);
  list($numbers["CINVI"],$crores) = displayRefList("COMM",$team,"%20AND%20invitedCommunication_s:1%20AND%20audience_s:2".$specificRequestCode,$countries,"CINVI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVI-") !== false) {
  echo "<a name=\"CNONINVI\"></a><h2>Communications non invitées internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications non invitées internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications non invitées internationales</b><br><br>", $fonth2);
  list($numbers["CNONINVI"],$crores) = displayRefList("COMM",$team,"%20AND%20invitedCommunication_s:0%20AND%20audience_s:2".$specificRequestCode,$countries,"CINVI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CINVN-") !== false) {
  echo "<a name=\"CINVN\"></a><h2>Communications invitées nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications invitées nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications invitées nationales</b><br><br>", $fonth2);
  list($numbers["CINVN"],$crores) = displayRefList("COMM",$team,"%20AND%20invitedCommunication_s:1%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"CINVN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CNONINVN-") !== false) {
  echo "<a name=\"CNONINVN\"></a><h2>Communications non invitées nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications non invitées nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications non invitées nationales</b><br><br>", $fonth2);
  list($numbers["CNONINVN"],$crores) = displayRefList("COMM",$team,"%20AND%20invitedCommunication_s:0%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"CINVN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CP-") !== false) {
  echo "<a name=\"CP\"></a><h2>Communications par affiches (posters) <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications pas affiches (posters)".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches (posters)</b><br><br>", $fonth2);
  list($numbers["CP"],$crores) = displayRefList("POSTER",$team,"".$specificRequestCode,$countries,"CP",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CPI-") !== false) {
  echo "<a name=\"CPI\"></a><h2>Communications par affiches internationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications par affiches internationales".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches internationaes</b><br><br>", $fonth2);
  list($numbers["CPI"],$crores) = displayRefList("POSTER",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"CPI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CPN-") !== false) {
  echo "<a name=\"CPN\"></a><h2>Communications par affiches nationales <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Communications par affiches nationales".chr(13).chr(10));
  $sect->writeText("<b>Communications par affiches nationales</b><br><br>", $fonth2);
  list($numbers["CPN"],$crores) = displayRefList("POSTER",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"CPN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_comm) && strpos($choix_comm, "-CGP-") !== false) {
  echo "<a name=\"CGP\"></a><h2>Conférences grand public <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Conférences grand public".chr(13).chr(10));
  $sect->writeText("<b>Conférences grand public</b><br><br>", $fonth2);
  list($numbers["CGP"],$crores) = displayRefList("COMM",$team,"%20AND%20popularLevel_s:1".$specificRequestCode,$countries,"CGP",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-TO-") !== false) {
  echo "<a name=\"TO\"></a><h2>Tous les ouvrages (sauf vulgarisation) <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Tous les ouvrages (sauf vulgarisation)".chr(13).chr(10));
  $sect->writeText("<b>Tous les ouvrages (sauf vulgarisation)</b><br><br>", $fonth2);
  list($numbers["TO"],$crores) = displayRefList("OUV",$team,"%20AND%20popularLevel_s:0".$specificRequestCode,$countries,"TO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPI-") !== false) {
  echo "<a name=\"OSPI\"></a><h2>Ouvrages scientifiques de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages scientifiques de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages scientifiques de portée internationale</b><br><br>", $fonth2);
  list($numbers["OSPI"],$crores) = displayRefList("OUV",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"OSPI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OSPN-") !== false) {
  echo "<a name=\"OSPN\"></a><h2>Ouvrages scientifiques de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages scientifiques de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages scientifiques de portée nationale</b><br><br>", $fonth2);
  list($numbers["OSPN"],$crores) = displayRefList("OUV",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"OSPN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COS-") !== false) {
  echo "<a name=\"COS\"></a><h2>Chapitres d’ouvrages scientifiques <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Chapitres d'ouvrages scientifiques".chr(13).chr(10));
  $sect->writeText("<b>Chapitres d’ouvrages scientifiques</b><br><br>", $fonth2);
  list($numbers["COS"],$crores) = displayRefList("COUV",$team,"".$specificRequestCode,$countries,"COS",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSI-") !== false) {
  echo "<a name=\"COSI\"></a><h2>Chapitres d’ouvrages scientifiques de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Chapitres d’ouvrages scientifiques de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Chapitres d’ouvrages scientifiques de portée internationale</b><br><br>", $fonth2);
  list($numbers["COSI"],$crores) = displayRefList("COUV",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"COSI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-COSN-") !== false) {
  echo "<a name=\"COSN\"></a><h2>Chapitres d’ouvrages scientifiques de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Chapitres d’ouvrages scientifiques de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Chapitres d’ouvrages scientifiques de portée nationale</b><br><br>", $fonth2);
  list($numbers["COSN"],$crores) = displayRefList("COUV",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"COSN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOS-") !== false) {
  echo "<a name=\"DOS\"></a><h2>Directions d’ouvrages scientifiques <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Directions d’ouvrages scientifiques".chr(13).chr(10));
  $sect->writeText("<b>Directions d’ouvrages scientifiques</b><br><br>", $fonth2);
  list($numbers["DOS"],$crores) = displayRefList("DOUV",$team,"".$specificRequestCode,$countries,"DOS",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSI-") !== false) {
  echo "<a name=\"DOSI\"></a><h2>Directions d’ouvrages scientifiques de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Directions d’ouvrages scientifiques de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Directions d’ouvrages scientifiques de portée internationale</b><br><br>", $fonth2);
  list($numbers["DOSI"],$crores) = displayRefList("DOUV",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"DOSI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
 
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-DOSN-") !== false) {
  echo "<a name=\"\"></a><h2>Directions d’ouvrages scientifiques de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Directions d’ouvrages scientifiques de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Directions d’ouvrages scientifiques de porté nationale</b><br><br>", $fonth2);
  list($numbers["DOSN"],$crores) = displayRefList("DOUV",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"DOSN",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCO-") !== false) {
  echo "<a name=\"OCO\"></a><h2>Ouvrages ou chapitres d’ouvrages <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou chapitres d’ouvrages".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres d’ouvrages</b><br><br>", $fonth2);
  list($numbers["OCO"],$crores) = displayRefList("OUV+COUV",$team,"".$specificRequestCode,$countries,"OCO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCOI-") !== false) {
  echo "<a name=\"OCOI\"></a><h2>Ouvrages ou chapitres d’ouvrages de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou chapitres d’ouvrages de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres d’ouvrages de portée internationale</b><br><br>", $fonth2);
  list($numbers["OCOI"],$crores) = displayRefList("OUV+COUV",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"OCOI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCON-") !== false) {
  echo "<a name=\"OCON\"></a><h2>Ouvrages ou chapitres d’ouvrages de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou chapitres d’ouvrages de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres d’ouvrages de portée nationale</b><br><br>", $fonth2);
  list($numbers["OCON"],$crores) = displayRefList("OUV+COUV",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"OCON",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODO-") !== false) {
  echo "<a name=\"ODO\"></a><h2>Ouvrages ou directions d’ouvrages <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou directions d’ouvrages".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou directions d’ouvrages</b><br><br>", $fonth2);
  list($numbers["ODO"],$crores) = displayRefList("OUV+DOUV",$team,"".$specificRequestCode,$countries,"ODO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODOI-") !== false) {
  echo "<a name=\"ODOI\"></a><h2>Ouvrages ou directions d’ouvrages de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou directions d’ouvrages de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou directions d’ouvrages de portée internationale</b><br><br>", $fonth2);
  list($numbers["ODOI"],$crores) = displayRefList("OUV+DOUV",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"ODOI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-ODON-") !== false) {
  echo "<a name=\"ODON\"></a><h2>Ouvrages ou directions d’ouvrages de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou directions d’ouvrages de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou directions d’ouvrages de portée nationale</b><br><br>", $fonth2);
  list($numbers["ODON"],$crores) = displayRefList("OUV+DOUV",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"ODON",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDO-") !== false) {
  echo "<a name=\"OCDO\"></a><h2>Ouvrages ou chapitres ou directions d’ouvrages <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou chapitres ou directions d’ouvrages".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres ou directions d’ouvrages</b><br><br>", $fonth2);
  list($numbers["OCDO"],$crores) = displayRefList("OUV+COUV+DOUV",$team,"".$specificRequestCode,$countries,"OCDO",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDOI-") !== false) {
  echo "<a name=\"OCDOI\"></a><h2>Ouvrages ou chapitres ou directions d’ouvrages de portée internationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou chapitres ou directions d’ouvrages de portée internationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres ou directions d’ouvrages de portée internationale</b><br><br>", $fonth2);
  list($numbers["OCDOI"],$crores) = displayRefList("OUV+COUV+DOUV",$team,"%20AND%20audience_s:2".$specificRequestCode,$countries,"OCDOI",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCDON-") !== false) {
  echo "<a name=\"OCDON\"></a><h2>Ouvrages ou chapitres ou directions d’ouvrages de portée nationale <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou chapitres ou directions d’ouvrages de portée nationale".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres ou directions d’ouvrages de portée nationale</b><br><br>", $fonth2);
  list($numbers["OCDON"],$crores) = displayRefList("OUV+COUV+DOUV",$team,"%20AND%20(audience_s:3%20OR%20audience_s:1%20OR%20audience_s:0)".$specificRequestCode,$countries,"OCDON",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_ouvr) && strpos($choix_ouvr, "-OCV-") !== false) {
  echo "<a name=\"OCV\"></a><h2>Ouvrages ou chapitres de vulgarisation <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Ouvrages ou chapitres de vulgarisation".chr(13).chr(10));
  $sect->writeText("<b>Ouvrages ou chapitres de vulgarisation</b><br><br>", $fonth2);
  list($numbers["OCV"],$crores) = displayRefList("OUV+COUV",$team,"%20AND%20popularLevel_s:1".$specificRequestCode,$countries,"OCV",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_autr) && strpos($choix_autr, "-BRE-") !== false) {
  echo "<a name=\"BRE\"></a><h2>Brevets <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Brevets".chr(13).chr(10));
  $sect->writeText("<b>Brevets</b><br><br>", $fonth2);
  list($numbers["BRE"],$crores) = displayRefList("PATENT",$team,"".$specificRequestCode,$countries,"BRE",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_autr) && strpos($choix_autr, "-RAP-") !== false) {
  echo "<a name=\"RAP\"></a><h2>Rapports <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Rapports".chr(13).chr(10));
  $sect->writeText("<b>Rapports</b><br><br>", $fonth2);
  list($numbers["RAP"],$crores) = displayRefList("REPORT",$team,"".$specificRequestCode,$countries,"RAP",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_autr) && strpos($choix_autr, "-PWM-") !== false) {
  echo "<a name=\"PWM\"></a><h2>Preprints, working papers, manuscrits non publiés <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Preprints, working papers, manuscrits non publiés".chr(13).chr(10));
  $sect->writeText("<b>Preprints, working papers, manuscrits non publiés</b><br><br>", $fonth2);
  list($numbers["PWM"],$crores) = displayRefList("UNDEF",$team,"".$specificRequestCode,$countries,"PWM",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
}
if (isset($choix_autr) && strpos($choix_autr, "-AP-") !== false) {
  echo "<a name=\"AP\"></a><h2>Autres publications <a href=\"#sommaire\">&#8683;</a></h2>";
  $Fnm1 = "./HAL/extractionHAL.csv"; 
  $inF = fopen($Fnm1,"a+"); 
  fseek($inF, 0);
  fwrite($inF,"Autres publications".chr(13).chr(10));
  $sect->writeText("<b>Autres publications</b><br><br>", $fonth2);
  list($numbers["AP"],$crores) = displayRefList("OTHER",$team,"".$specificRequestCode,$countries,"AP",$institut,$typnum,$typaut,$typnom,$typcol,$typlim,$limaff,$typtit,$typann,$typfor,$typdoi,$typidh,$typreva,$typrevc,$listenominit,$listenomcomp1,$listenomcomp2,$sect,$Fnm,$delim,$rtfArray,$font,$fontlien,$fonth2,$fonth3,$gr,$nbeqp,$nomeqp);
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
  $rtf->save($Fnm);

  if (isset($_POST["soumis"]) || isset($_GET["team"])) {
    //Création de graphes
    //Librairies pChart
    include("./pChart/class/pData.class.php");
    include("./pChart/class/pDraw.class.php");
    include("./pChart/class/pImage.class.php");

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
    $myPicture->setFontProperties(array("FontName"=>"./pChart/fonts/corbel.ttf","FontSize"=>10));

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
    $myPicture->render("mypic1.png");
    echo('<center><img src="mypic1.png"></center><br>');
    

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
    $myPicture->setFontProperties(array("FontName"=>"./pChart/fonts/corbel.ttf","FontSize"=>10));

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
    $myPicture->render("mypic2.png");
    echo('<center><img src="mypic2.png"></center><br>');  

    //Si choix sur tous les articles, camembert avec détails
    if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {
      include("./pChart/class/pPie.class.php");
      $i = 3;
      foreach($availableYears as $year => $nb){
        $MyData = new pData();

        $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$team."%20AND%20docType_s:ART%20AND%20audience_s:2%20AND%20peerReviewing_s:1%20AND%20producedDateY_i:".$year);
        $results = json_decode($contents);
        $ACLRI=$results->response->numFound;

        $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$team."%20AND%20docType_s:ART%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20peerReviewing_s:1%20AND%20producedDateY_i:".$year);
        $results = json_decode($contents);
        $ACLRN=$results->response->numFound;
        
        $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$team."%20AND%20docType_s:ART%20AND%20audience_s:2%20AND%20peerReviewing_s:0%20AND%20producedDateY_i:".$year);
        $results = json_decode($contents);
        $ASCLRI=$results->response->numFound;
        
        $contents = file_get_contents("http://api.archives-ouvertes.fr/search/".$institut."?q=collCode_s:".$team."%20AND%20docType_s:ART%20AND%20(audience_s:3%20OR%20audience_s:0%20OR%20audience_s:1)%20AND%20peerReviewing_s:0%20AND%20producedDateY_i:".$year);
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
        $myPicture->setFontProperties(array("FontName"=>"./pChart/fonts/corbel.ttf","FontSize"=>10));
        $myPicture->drawText(175,40,"Détail TA".$year,array("FontSize"=>20,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));

        /* Set the default font properties */ 
        $myPicture->setFontProperties(array("FontName"=>"./pChart/fonts/corbel.ttf","FontSize"=>10,"R"=>80,"G"=>80,"B"=>80));

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
        $myPicture->setFontProperties(array("FontName"=>"./pChart/fonts/corbel.ttf","FontSize"=>10));
        $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>20));

        /* Write the legend box */ 
        $myPicture->setFontProperties(array("FontName"=>"./pChart/fonts/corbel.ttf","FontSize"=>10,"R"=>0,"G"=>0,"B"=>0));
        $PieChart->drawPieLegend(30,200,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

        $myPicture->render('mypic'.$i.'.png');
        echo('<center><img src="mypic'.$i.'.png"></center><br>');
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
          echo('&nbsp;&nbsp;&nbsp;ACLRI = Articles de revues à comité de lecture de revues internationales<br>');
          break;
        case "ACLRN" :
          echo('&nbsp;&nbsp;&nbsp;ACLRN = Articles de revues à comité de lecture de revues nationales<br>');
          break;
        case "ASCLRI" :
          echo('&nbsp;&nbsp;&nbsp;ASCLRI = Articles de revues sans comité de lecture de revues internationales<br>');
          break;
        case "ASCLRN" :
          echo('&nbsp;&nbsp;&nbsp;ASCLRN = Articles de revues sans comité de lecture de revues nationales<br>');
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
        case "CINV" :
          echo('&nbsp;&nbsp;&nbsp;CINV = Communications invitées<br>');
          break;
        case "CNONINV" :
          echo('&nbsp;&nbsp;&nbsp;CNONINV = Communications non invitées<br>');
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
        case "CP" :
          echo('&nbsp;&nbsp;&nbsp;CP = Communications par affiches (posters)<br>');
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
        case "PWM" :
          echo('&nbsp;&nbsp;&nbsp;PWM = Preprints, working papers, manuscrits non publiés<br>');
          break;
        case "AP" :
          echo('&nbsp;&nbsp;&nbsp;AP = Autres publications<br>');
          break;
      }
    }
    if (isset($choix_publis) && strpos($choix_publis, "-TA-") !== false) {
      echo('&nbsp;&nbsp;&nbsp;ACLRI = Articles de revues à comité de lecture de revues internationales<br>');
      echo('&nbsp;&nbsp;&nbsp;ACLRN = Articles de revues à comité de lecture de revues nationales<br>');
      echo('&nbsp;&nbsp;&nbsp;ASCLRI = Articles de revues sans comité de lecture de revues internationales<br>');
      echo('&nbsp;&nbsp;&nbsp;ASCLRN = Articles de revues sans comité de lecture de revues nationales<br>');
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
      $myPicture->setFontProperties(array("FontName"=>"./pChart/fonts/corbel.ttf","FontSize"=>10));

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
      $myPicture->render("mypic_crogr.png");
      echo('<center><img src="mypic_crogr.png"></center><br>');
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
<script type="text/javascript" charset="UTF-8">
document.getElementById("deteqp").style.display = "none";
function affich_form() {
  document.getElementById("deteqp").style.display = "block";
}

function cacher_form() {
  document.getElementById("deteqp").style.display = "none";
  document.getElementById("eqp").style.display = "none";
}

function affich_form_suite() {
  nbeqpval = document.extrhal.nbeqp.value;
  var eqpaff = '';
  for (i=1; i<=nbeqpval; i++) {
    eqpaff += '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;. Nom HAL équipe '+i+' : <input type="text" name="eqp'+i+'" size="30"><br>';
  }
  document.getElementById("eqp").innerHTML = eqpaff;
}

$("#nbeqpid").keyup(function(event) {affich_form_suite();});
</script>
<br>
</body></html>