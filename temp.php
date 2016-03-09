<?php
header('Content-type: text/html; charset=UTF-8');
function wd_remove_accents($str, $charset='utf-8')
{
    $str = htmlentities($str, ENT_NOQUOTES, $charset);
    
    $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
    
    return $str;
}
//Constantes générales
if (isset($_GET['lang']) && ($_GET['lang'] != "")) {
  $lang = $_GET['lang'];
}else{
  $lang = "fr";
}
if ($lang == "fr") {//français
  $typdocHAL = array("1" => "Articles dans des revues avec comité de lecture",
                "2" => "Articles dans des revues sans comité de lecture",
                "3" => "Communications avec actes",
                "4" => "Communications sans actes",
                "5" => "Directions d'ouvrages scientifiques",
                "6" => "Ouvrages scientifiques",
                "7" => "Chapitres d'ouvrages scientifiques",
                "8" => "Thèses",
                "9" => "HDR",
                "10" => "Brevets",
                "11" => "Rapport de recherche",
                "12" => "Conférences invitées",
                "13" => "Cours",
                "14" => "Preprint, Working Paper, Document sans référence, etc.",
                "15" => "Autres publications",
                "16" => "Autres");
  $form1 = "Tri par année : de ";
  $form2 = " à ";
  $form3 = "Nombre de publications par page : " ;
  $form4c = "Recherche sur un auteur particulier : ";
  $form4s = "Auteur : ";
  $form5c = "Recherche sur un mot du titre : ";
  $form5s = "Mot du titre : ";
  $form6 = "Type de publication (tous par défaut) : ";
  $form7 = "Présentation bibliographique (auteurs.titre.réf biblio) : ";
  $form8 = "Valider";
  $form9c = "Formulaire simple";
  $form9s = "Formulaire complet";
  $reinit = "Revenir à la liste complète des publications pour l'année en cours";
  $consult1 = "Consultez nos ";
  $consult2 = "publications en libre accès sur HAL";
  $result1 = "De ";
  $result2 = " à ";
  $result3 = "Aucune publication";
  $result4 = "";
  $result5 = " publication(s)";
  $result6 = "Exporter les données affichées en CSV";
  $result7 = "Exporter les données affichées en RTF";
}else{//anglais
  $typdocHAL = array("1" => "Article in peer-reviewed journal",
                "2" => "Article in non peer-reviewed journal",
                "3" => "Conference proceedings",
                "4" => "Conference, seminar, workshop communications",
                "5" => "Edition of book or proceedings",
                "6" => "Scientific Book",
                "7" => "Scientific Book chapter",
                "8" => "PhD thesis",
                "9" => "Habilitation research",
                "10" => "Patent",
                "11" => "Research report",
                "12" => "Invited conference talk",
                "13" => "Lecture",
                "14" => "Preprint, Working Paper, ...",
                "15" => "Other publication",
                "16" => "Other");
  $form1 = "Years: from ";
  $form2 = " to ";
  $form3 = "Number of publications per page: ";
  $form4c = "Return articles authored by: ";
  $form4s = "Author: ";
  $form5c = "Search for title words: ";
  $form5s = "Title words: ";
  $form6 = "Publication type (default: all): ";
  $form7 = "Bibliographic display (ie. full bibliographic citation): ";
  $form8 = "Submit";
  $form9c = "Basic form";
  $form9s = "Expanded form";
  $reinit = "Return to the full list of publications for the current year";
  $consult1 = "Check our ";
  $consult2 = "Open Access Repository";
  $result1 = "From ";
  $result2 = " to ";
  $result3 = "No publication";
  $result4 = "";
  $result5 = " publication(s)";
  $result6 = "Export data displayed in CSV";
  $result7 = "Export data displayed in RTF";
}

$labo = "";
$collection_exp = "";
if (isset($_GET['labo']) && ($_GET['labo'] != "")) {
  $labo = strtoupper($_GET['labo']);
  $priorite = "labo";
  $entite = $labo;
}
if (isset($_GET['collection_exp']) && ($_GET['collection_exp'] != "")) {
  $collection_exp = strtoupper($_GET['collection_exp']);
  $priorite = "collection_exp";
  $entite = $collection_exp;
}
$equipe_recherche_exp = "";
if (isset($_GET['equipe_recherche_exp']) && ($_GET['equipe_recherche_exp'] != "")) {
  $equipe_recherche_exp = strtoupper($_GET['equipe_recherche_exp']);
}
$auteur_exp = "";
if (isset($_GET['auteur_exp']) && ($_GET['auteur_exp'] != "")) {
  $auteur_exp = strtoupper($_GET['auteur_exp']);
}
if (isset($_GET['mailto']) && ($_GET['mailto'] != "")) {
  $mailto = $_GET['mailto'];
}else{
  $mailto = "toto.titi@univ-rennes1.fr";
}
if (isset($_GET['css']) && ($_GET['css'] != "")) {
  $css = $_GET['css'];
}else{
  $css = "http://ecobio.univ-rennes1.fr/HAL_SCD.css";
}
if (isset($_GET['bt']) && ($_GET['bt'] != "")) {
  $bt = $_GET['bt'];
}else{
  $bt = "oui";
}
$form = "";
if (isset($_GET['form']) && ($_GET['form'] != "")) {
  $form = $_GET['form'];
}
//quand les publis ne portent pas forcément l'affiliation à collection_exp
$tous = "";
if (isset($_GET['tous']) && ($_GET['tous'] != "")) {
  $tous = $_GET['tous'];
}

//pour le formulaire simple, année minimale pour l'affichage
$annee_publideb = "";
if (isset($_GET['annee_publideb']) && ($_GET['annee_publideb'] != "")) {
  $annee_publideb = $_GET['annee_publideb'];
}

//précision quant à l'année de publication initiale
$anneedep = "";
if (isset($_GET['anneedep']) && ($_GET['anneedep'] != "")) {
  $anneedep = $_GET['anneedep'];
}

//années à exclure
$annee_excl = "";
if (isset($_GET['annee_excl']) && ($_GET['annee_excl'] != "")) {
  $annee_excl = $_GET['annee_excl'];
  //$annee_excl_tab = explode(",",$annee_excl);
}
if (isset($_GET['typform']) && ($_GET['typform'] != "")) {$typform = $_GET['typform'];}else{$typform = "Formulaire simple";}
if ($annee_publideb != "" || $anneedep != "") {
  if ($annee_publideb != "") {
    $anneedep = $annee_publideb;
    $nbanneesfs = date('Y', time()) - $annee_publideb;
    if ($nbanneesfs >= 8) {$nbanneesfs = 8;}
  }else{
    $nbanneesfs = date('Y', time()) - $anneedep;
  }
}else{
  if ($typform == $form9s) {//formulaire complet
    $anneedep = 1970;//année jusqu'où remonter dans le formulaire complet
  }else{
    $nbanneesfs = 8;//nombre d'années à afficher dans le formulaire simplifié
  }
}

$premautab = array();
$auteurs = array();
$typdoctab = array();
$titrehref = array();
$rvnp = array();
$doi = array();
$bibtex = array();
$pdf1 = array();
$pdf2 = array();
$pdf3 = array();
$pdf4 = array();
$pdf5 = array();
$reprint = array();
$indtab = array();

//recherche jusqu'à quelle année il y a des publications > routine trop longue !!! > d'où $nbanneesfc ...
$a = date('Y', time());
$plong = 1;
//while ($plong >= 1) {
  if ($priorite == "collection_exp") {
    $HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=&auteur_exp=&annee_publideb=".$a."&annee_publifin=".$a."&collection_exp=".$collection_exp."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Francais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
  }else{
    $HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=&auteur_exp=&annee_publideb=".$a."&annee_publifin=".$a."&labo=".$labo."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Francais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
  }
  //echo "HAL_URL1 = ".$HAL_URL. "<br>";
  
  set_time_limit(0);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $HAL_URL);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'SCD (https://halur1.univ-rennes1.fr)');
  curl_setopt($ch, CURLOPT_USERAGENT, 'PROXY (http://siproxy.univ-rennes1.fr)');
  $resultat = curl_exec($ch);
  $resultat = str_replace("&","and",$resultat);
  curl_close($ch);
  
  
  
  $HAL_Page = new DOMDocument();
  @$HAL_Page->loadHTML($resultat);

  $p = $HAL_Page->getElementsByTagName("p");
  $plong = $p->length;
  $a--;
//}
//echo $a;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>Les publications HAL <?php echo($entite);?></title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <link rel="stylesheet" href="<?php echo($css);?>" type="text/css">
  <STYLE type="text/css"> 
HTML, BODY, A, TABLE, TR, TD, FORM, INPUT, SELECT, TEXTAREA, DIV, P, SPAN, H1, H2, H3, H4, H5, B, UL, LI, DD, DL, DT {
	font-family: sans-serif, Times, "Times New Roman";
}

BODY {
	margin: 0px;
	border: 0px;
	padding: 0px;
	background-color: #ffffff;
	color: black;
	font-size: 12px;
}

a {
    color: #A71817;
    text-decoration: none;
    font-weight: bold;
}
</STYLE>
</head>
<body>
<?php
$mailtocrit = "";
if ((isset($_GET['ipas']))  && ($typform == $form9s)) {$ipas = $_GET['ipas'];}else{$ipas = 10;}
if (isset($_GET['ideb'])) {$ideb = $_GET['ideb'];}else{$ideb = 1;}
if (isset($_GET['ifin'])) {$ifin = $_GET['ifin'];}else{$ifin = $ideb + $ipas - 1;}
if (isset($_GET['presbib']) && ($_GET['presbib'] != "<br>")) {$presbibtxt = " checked";$presbib = "&nbsp;-&nbsp;";}else{$presbibtxt = "";$presbib = "<br>";}
if (isset($_GET['labocrit'])) {
  $labosur = explode(";", $labo);
  $mailtosur = explode(";", $mailto);
  $labocrit = $_GET['labocrit'];
  $ii = 0;
  //while ($labosur[$ii] != "") {
  while (isset($labosur[$ii])) {
    if ($labocrit == $labosur[$ii]) {$mailtocrit = $mailtosur[$ii];}
    $ii++;
  }
}else{
  if ($priorite == "collection_exp") {
    $labocrit = $collection_exp;
  }else{
    $labocrit = $labo;
  }
}

unset($labosur, $mailtosur);

if (isset($_GET['aut'])) {$aut = mb_convert_case($_GET['aut'],MB_CASE_LOWER,"UTF-8");}else{$aut = "";}
if (isset($_GET['titre'])) {$titre = mb_convert_case($_GET['titre'],MB_CASE_LOWER,"UTF-8");}else{$titre = "";}
if (isset($_GET['typdoc']) && ($_GET['typdoc'] != "") && ($typform == $form9s)) {$typdocinit = $_GET['typdoc']; $typdoc = "('".$_GET['typdoc']."')";}else{$typdocinit = ""; $typdoc = "";}
if (isset($_GET['anneedeb'])) {$anneedeb = $_GET['anneedeb'];}else{$anneedeb = date('Y', time());$anneefin = date('Y', time());}
if (isset($_GET['anneefin'])) {$anneefin = $_GET['anneefin'];}else{$anneefin = $_GET['anneedeb'];}

// vérification sur ordre des années si différentes
if ($anneefin < $anneedeb) {$anneetemp = $anneedeb; $anneedeb = $anneefin; $anneefin = $anneetemp;}

//$text = "<div id='res_script'><div align='center'><h2><b>".$labo." - Publications</b></h2></div><br>\r\n";
$text = "<br>";

if ($typform == $form9s) {//formulaire de recherche complet
  $text .= "<div align='justify'><form method='GET' accept-charset='utf-8' action='".$_SERVER['REQUEST_URI']."'>\r\n";
  //année de publication
  $text .= $form1;
  $text .= "<select size='1' name='anneedeb'>\r\n";
  $annee = date('Y', time());
  while($annee >= $anneedep) {
    if ($anneedeb == $annee) {$txt = " selected";}else{$txt = "";}
    if (strpos($annee_excl, strval($annee)) === false) {
      $text .= "<option value='".$annee."'".$txt.">".$annee."</option>\r\n";
    }
    $annee--;
  }
  $text .= "</select>\r\n";
  $text .= $form2;
  $text .= "<select size='1' name='anneefin'>\r\n";
  $annee = date('Y', time());
  while($annee >= $anneedep) {
    if ($anneefin == $annee) {$txt = " selected";}else{$txt = "";}
    if (strpos($annee_excl, strval($annee)) === false) {
      $text .= "<option value='".$annee."'".$txt.">".$annee."</option>\r\n";
    }
    $annee--;
  }
  $text .= "</select><br>\r\n";
  //intervalle d'affichage des résultats
  //$text .= $form3."\r\n";
  //$text .= "<input type='text' name='ipas' value='".$ipas."' size='1'><br>\r\n";
  //recherche sur un auteur
  $text .= $form4c."\r\n";
  $text .= "<input type='text' name='aut' value='".$aut."' size='20'><br>\r\n";
  //recherche sur un mot du titre
  $text .= $form5c."\r\n";
  $text .= "<input type='text' name='titre' value='".$titre."' size='20'><br>\r\n";
  //recherche par type de support
  $text .= $form6."<br>\r\n";
  $text .= "<select size='3' name='typdoc'>";
  if($typdoc == "('ART_ACL')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='ART_ACL'".$txt."}>".$typdocHAL['1']."</option>\r\n";
  if($typdoc == "('ART_SCL')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='ART_SCL'".$txt.">".$typdocHAL['2']."</option>\r\n";
  if($typdoc == "('COMM_ACT')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='COMM_ACT'".$txt.">".$typdocHAL['3']."</option>\r\n";
  if($typdoc == "('COMM_SACT')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='COMM_SACT'".$txt.">".$typdocHAL['4']."</option>\r\n";
  if($typdoc == "('DOUV')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='DOUV'".$txt.">".$typdocHAL['5']."</option>\r\n";
  if($typdoc == "('OUVS')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='OUVS'".$txt.">".$typdocHAL['6']."</option>\r\n";
  if($typdoc == "('COVS')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='COVS'".$txt.">".$typdocHAL['7']."</option>\r\n";
  if($typdoc == "('THESE')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='THESE'".$txt.">".$typdocHAL['8']."</option>\r\n";
  if($typdoc == "('HDR')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='HDR'".$txt.">".$typdocHAL['9']."</option>\r\n";
  if($typdoc == "('PATENT')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='PATENT'".$txt.">".$typdocHAL['10']."</option>\r\n";
  if($typdoc == "('REPORT')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='REPORT'".$txt.">".$typdocHAL['11']."</option>\r\n";
  if($typdoc == "('CONF_INV')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='CONF_INV'".$txt.">".$typdocHAL['12']."</option>\r\n";
  if($typdoc == "('COURS')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='COURS'".$txt.">".$typdocHAL['13']."</option>\r\n";
  if($typdoc == "('UNDEFINED')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='UNDEFINED'".$txt.">".$typdocHAL['14']."</option>\r\n";
  if($typdoc == "('OTHER')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='OTHER'".$txt.">".$typdocHAL['15']."</option>\r\n";
  if($typdoc == "('autre')") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='autre'".$txt.">".$typdocHAL['16']."</option>\r\n";
  $text .= "</select><br>\r\n";
  //présentation bibliographique
  $text .= $form7."\r\n";
  $text .= "<input type='checkbox' name='presbib' value='ok' ".$presbibtxt."><br>\r\n";
  $text .= "<input type='hidden' name='labo' value='".$labo."'>\r\n";
  $text .= "<input type='hidden' name='collection_exp' value='".$collection_exp."'>\r\n";
  $text .= "<input type='hidden' name='equipe_recherche_exp' value='".$equipe_recherche_exp."'>\r\n";
  $text .= "<input type='hidden' name='auteur_exp' value='".$auteur_exp."'>\r\n";
  $text .= "<input type='hidden' name='mailto' value='".$mailto."'>\r\n";
  $text .= "<input type='hidden' name='lang' value='".$lang."'>\r\n";
  $text .= "<input type='hidden' name='css' value='".$css."'>\r\n";
  $text .= "<input type='hidden' name='bt' value='".$bt."'>\r\n";
  $text .= "<input type='hidden' name='form' value='".$form."'>\r\n";
  $text .= "<input type='hidden' name='tous' value='".$tous."'>\r\n";
  $text .= "<input type='hidden' name='annee_publideb' value='".$annee_publideb."'>\r\n";
  if ($typform != $form9s) {//formulaire simple
    $text .= "<input type='hidden' name='anneedep' value='".$anneedep."'>\r\n";
  }
  $text .= "<input type='hidden' name='annee_excl' value='".$annee_excl."'>\r\n";
  $text .= "<input type='hidden' name='ideb' value='1'>\r\n";
  $text .= "<input type='hidden' name='typform' value='".$form9s."'>\r\n";
  $text .= "<br><input type='submit' value='".$form8."'>&nbsp;&nbsp;&nbsp;";
  $text .= "<input type='submit' name='typform' value='".$form9c."'>";
  $text .= "</form><br>";
}else{//formulaire de recherche simplifié
  //années
  if ($anneedeb != $anneefin) {$anneedeb = date('Y', time()); $anneefin = date('Y', time());}
  $text .= "<div align='center'>\r\n";
  $i = date('Y', time());
  while ($i >= date('Y', time()) - $nbanneesfs) {
    //on vérifie si ce n'est pas une année à exclure
    if (strpos($annee_excl, strval($i)) === false) {
      $text .= "<a href='?labo=".$labo."&collection_exp=".$collection_exp."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&mailto=".$mailto."&lang=".$lang."&css=".$css."&form=".$form."&tous=".$tous."&annee_publideb=".$annee_publideb."&anneedep=".$anneedep."&annee_excl=".$annee_excl."&bt=".$bt."&presbib=".$presbib."&labocrit=".$labocrit."&typdoc=".$typdoc."&typform=".$typform."&anneedeb=".$i."&anneefin=".$i."&titre=".$titre."&aut=".$aut."&ipas=".$ipas."'>".$i."</a>&nbsp;&nbsp;&nbsp;\r\n";
    }
    $i--;
  }
  $text .= "\r\n</div>\r\n";
  if ($form != "non") {
    $text .= "<br><div align='justify'><form method='GET' accept-charset='utf-8' action='".$_SERVER['REQUEST_URI']."'>\r\n";
    //recherche sur un auteur
    $text .= $form4s."\r\n";
    $text .= "<input type='text' name='aut' value='".$aut."' size='10'>&nbsp;&nbsp;&nbsp;\r\n";
    //recherche sur un mot du titre
    $text .= $form5s."\r\n";
    $text .= "<input type='text' name='titre' value='".$titre."' size='10'>&nbsp;&nbsp;&nbsp;\r\n<br>";
    $text .= $form7."\r\n";
    $text .= "<input type='checkbox' name='presbib' value='ok' ".$presbibtxt."><br>\r\n";
    $text .= "<input type='hidden' name='labo' value='".$labo."'>\r\n";
    $text .= "<input type='hidden' name='collection_exp' value='".$collection_exp."'>\r\n";
    $text .= "<input type='hidden' name='equipe_recherche_exp' value='".$equipe_recherche_exp."'>\r\n";
    $text .= "<input type='hidden' name='auteur_exp' value='".$auteur_exp."'>\r\n";
    $typdoc2 = str_replace(array("(","'",")"),"",$typdoc);
    $text .= "<input type='hidden' name='typdoc' value='".$typdoc2."'>\r\n";
    $text .= "<input type='hidden' name='mailto' value='".$mailto."'>\r\n";
    $text .= "<input type='hidden' name='lang' value='".$lang."'>\r\n";
    $text .= "<input type='hidden' name='css' value='".$css."'>\r\n";
    $text .= "<input type='hidden' name='bt' value='".$bt."'>\r\n";
    $text .= "<input type='hidden' name='form' value='".$form."'>\r\n";
    $text .= "<input type='hidden' name='tous' value='".$tous."'>\r\n";
    $text .= "<input type='hidden' name='annee_publideb' value='".$annee_publideb."'>\r\n";
    $text .= "<input type='hidden' name='anneedep' value='".$anneedep."'>\r\n";
    $text .= "<input type='hidden' name='annee_excl' value='".$annee_excl."'>\r\n";
    $text .= "<input type='hidden' name='ipas' value='".$ipas."'>\r\n";
    $text .= "<input type='hidden' name='ideb' value='1'>\r\n";
    $text .= "<input type='hidden' name='typform' value='".$form9c."'>\r\n";
    $text .= "<br><input type='submit' value='".$form8."'>&nbsp;&nbsp;&nbsp;";
    $text .= "<input type='submit' name='typform' value='".$form9s."'>";
    $text .= "</form><br>";
  }
}

if ((isset($_GET['aut']) && $_GET['aut'] != "") || (isset($_GET['titre']) && $_GET['titre'] != "") || (isset($_GET['typdoc']) && $_GET['typdoc'] != "" && $_GET['typdoc'] != "(\'ART_ACL\',\'ART_SCL\',\'COMM_ACT\')")) {
  $text .= "<center><a href='".$_SERVER['PHP_SELF']."?labo=".$labo."&collection_exp=".$collection_exp."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&mailto=".$mailto."&lang=".$lang."&css=".$css."&form=".$form."&tous=".$tous."&annee_publideb=".$annee_publideb."&anneedep=".$anneedep."&annee_excl=".$annee_excl."&bt=".$bt."'>".$reinit."</a></center><br><br>\r\n";
}

$labo2 = $labocrit;

if ($form != "non") {
  $text .= $consult1."<a target='_blank' href='http://hal-univ-rennes1.archives-ouvertes.fr/".$labo2."/' class='noicon'>".$consult2."</a>.<br>\r\n";
}

if ($anneedeb == $anneefin) {
  $text .= "<br><p class='Rubrique'>".$anneedeb."</p><br>\r\n";
}else{
  $text .= "<br><p class='Rubrique'>".$result1.$anneedeb.$result2.$anneefin."</p><br>\r\n";
}

if ($labocrit != "" && $labocrit != $labo) {
  $labosur[0] = $labocrit;
  $mailtosur[0] = $mailtocrit;
}else{
  $labosur = explode(";", $labo);
  $mailtosur = explode(";", $mailto);
}

$ii = 0;
$i = 1;
$labocrit2 = "";

//while ($labosur[$ii] != "") {
while (isset($labosur[$ii])) {
  $labocrit = $labosur[$ii];
  //$mailto = $mailtosur[$ii];
  
  if ($lang == "fr") {
    if ($tous == "oui") {
      $HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Francais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    }else{
      $HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&collection_exp=".$labocrit."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Francais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    }
  }else{
    if ($tous == "oui") {
      $HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Anglais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    }else{
      $HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&collection_exp=".$labocrit."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Anglais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    }
  }
  //echo "HAL_URL2 = ".$HAL_URL;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $HAL_URL);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'SCD (https://halur1.univ-rennes1.fr)');
  curl_setopt($ch, CURLOPT_USERAGENT, 'PROXY (http://siproxy.univ-rennes1.fr)');
  $resultat = curl_exec($ch);
  $resultat = str_replace("&","and",$resultat);
  $resultat = str_replace(" < "," &#60; ",$resultat);
  $resultat = str_replace("T<","T&#60;",$resultat);
  //$resultat = str_replace("1<","1&#60;",$resultat);
  $resultat = str_replace("(<","(&#60;",$resultat);
  $resultat = str_replace(" > "," &#62; ",$resultat);
  $resultat = str_replace("T>","T&#62;",$resultat);
  //$resultat = str_replace("1>","1&#62;",$resultat);
  $resultat = str_replace("(>","(&#62;",$resultat);
  $resultat = str_replace(" <= "," &#8804; ",$resultat);
  $resultat = str_replace("T<=","T&#8804;",$resultat);
  $resultat = str_replace("(<=","(&#8804;",$resultat);
  $resultat = str_replace(" >= "," &#8805; ",$resultat);
  $resultat = str_replace("T>=","T&#8805;",$resultat);
  $resultat = str_replace("(>=","(&#8805;",$resultat);
  $resultat = str_replace("T&#60;/i>","</i>",$resultat);
  //echo ("Deb:<br>".$resultat."<br>Fin");
  curl_close($ch);

  $HAL_Page = new DOMDocument();
  $HAL_Page->loadHTML($resultat);

  foreach($HAL_Page->getElementsByTagName("dl") as $dl) {
    foreach($dl->getElementsByTagName("dd") as $dd) {
      //Type de documents
      if($dd->getAttribute("class") == "ValeurRes Type_de_document") {
        $typdoctab[$i] = $dd->textContent;
        if ($typdoctab[$i] == "Article in peer-reviewed journal") {
          $typdoctab[$i] = "Article in  peer-reviewed journal";
        }
      }    
      //Titre + lien HAL
      if($dd->getAttribute("class") == "ValeurRes Titre") {
        foreach($dd->getElementsByTagName("a") as $a) {
          $titrehref[$i] = "<a target='_blank' href='".$a->getAttribute('href')."'>".$a->textContent."</a>".$presbib;
          $titreseul[$i] = $a->textContent;
        }
      }
      //Auteurs
      if($dd->getAttribute("class") == "ValeurRes Auteurs") {
        $auteur_temp = $dd->textContent.$presbib;
        //$auteurs[$i] = $dd->textContent.$presbib;
        $auteur_temp = wd_remove_accents($auteur_temp);
        $auteurs[$i] = substr($auteur_temp, 0, (strlen($auteur_temp)-2));
      }
      //Revue, vol, num et pp
      if($dd->getAttribute("class") == "ValeurRes Detail") {
        $rvnp[$i] = "<i>".substr($dd->textContent,0,strpos($dd->textContent,","))."</i>".substr($dd->textContent,(strpos($dd->textContent,",")),strlen($dd->textContent)).$presbib;
      }
      //DOI
      if($dd->getAttribute("class") == "ValeurRes DOI") {
        foreach($dd->getElementsByTagName("a") as $a) {
          $doi[$i] = "DOI : <a target='_blank' href='".$a->getAttribute('href')."'>".$a->textContent."</a>".$presbib;
        }
      }
      //Fichier joint
      if($dd->getAttribute("class") == "ValeurRes Fichier_joint") {
        //si PDF + BibTex
        //Lien Bibtex
        $BibTex = "";
        foreach($dd->getElementsByTagName("span") as $span) {
          if($span->getAttribute("class") == "LienBibtexACoteFulltext") {
            foreach($span->getElementsByTagName("a") as $a) {
              $BibTex = $a->getAttribute("href");
              $bibtex[$i] = "<a target='_blank' href='".$a->getAttribute('href')."'><img alt='BibTex' src='http://hal.archives-ouvertes.fr/images/Haltools_bibtex3.png' border='0'  title='BibTex' /></a>";
            }
          }
        }
        //Lien PDF
        $j = 1;
        foreach($dd->getElementsByTagName("a") as $a) {
          if($a->getAttribute("href") != $BibTex) {
            ${"pdf".$j}[$i] = "&nbsp;<a target='_blank' href='".$a->getAttribute('href')."'><img alt='PDF' src='http://hal.archives-ouvertes.fr/images/Haltools_pdf.png' border='0'  title='PDF' /></a>";
          }
          $j++;
        }
        //si uniquement BibTex
      }elseif($dd->getAttribute("class") == "ValeurRes LienBibtex") {
        foreach($dd->getElementsByTagName("a") as $a) {
          $bibtex[$i] = "<a target='_blank' href='".$a->getAttribute('href')."'><img alt='BibTex' src='http://hal.archives-ouvertes.fr/images/Haltools_bibtex3.png' border='0'  title='BibTex' /></a>";
        }
      }
    }
    //Demande reprint par mail
    $repr = "&nbsp;<a href='mailto:".$mailto."?subject=Reprint request&amp;body=Would you please send me a copy of the following article: ";
    $repr .= str_replace("'","’",strip_tags($auteurs[$i]));
    $repr .= " - ";
    $repr .= str_replace("'","’",strip_tags($titreseul[$i]));
    $repr .= " - ";
    $repr .= str_replace("'","’",strip_tags($rvnp[$i]));
    $repr .= " Many thanks for considering my request.";
    $repr .= "'><img border='0' src='http://ecobio.univ-rennes1.fr/e107_images/custom/ReprintRequest.jpg' alt='Reprint request: Subject to availability' title='Reprint request: Subject to availability'></a>";
    $repr .= "<br><br>";
    $reprint[$i] = $repr;
    $i++;
  }
  if ($labocrit2 == "") {$labocrit2 = $labocrit;}else{$labocrit2 .= ";".$labocrit;}
  $ii++;
}

$imax = $i-1;
$irec = $imax;

//Création d'un tableau avec juste le nom du premier auteur
for ($i = 1; $i <= $imax; $i++) {
  $totaut = $auteurs[$i];
  $pos1 = strpos($totaut, " ");
  $pos2 = strpos($totaut, ";");
  if ($pos2 !== false) {
    $premaut = substr($totaut, $pos1, ($pos2 - $pos1));
  }else{
    $premaut = substr($totaut, $pos1, (strlen($totaut) - $pos1));
  }
  $premautab[$i] = $premaut;
}
//Remplissage des valeurs vides par '-' pour pouvoir ordonnancer les tableaux
for ($i = 1; $i <= $imax; $i++) {
  if (!isset($premautab[$i])) {$premautab[$i] = "-";}
  if (!isset($auteurs[$i])) {$auteurs[$i] = "-";}
  if (!isset($typdoctab[$i])) {$typdoctab[$i] = "-";}
  if (!isset($titrehref[$i])) {$titrehref[$i] = "-";}
  if (!isset($rvnp[$i])) {$rvnp[$i] = "-";}
  if (!isset($doi[$i])) {$doi[$i] = "-";}
  if (!isset($bibtex[$i])) {$bibtex[$i] = "-";}
  for($j = 1; $j <= 5; $j++)  {
    //if (${"pdf".$j}[$i] == "") {${"pdf".$j}[$i] = "-";}
    if (!isset(${"pdf".$j}[$i])) {${"pdf".$j}[$i] = "-";}
  }
  if ($reprint[$i] == "") {$reprint[$i] = "-";}
}
//var_dump($auteurs);
//tri des tableaux selon leurs clés => réindexation ordonnée
ksort($premautab);
ksort($auteurs);
ksort($typdoctab);
ksort($titrehref);
ksort($rvnp);
ksort($doi);
ksort($bibtex);
ksort($pdf1);
ksort($pdf2);
ksort($pdf3);
ksort($pdf4);
ksort($pdf5);
ksort($reprint);
//ksort($indtab);

//array_multisort($typdoctab, $premautab, $auteurs, $titrehref, $rvnp, $doi, $bibtex, $pdf1, $pdf2, $pdf3, $pdf4, $pdf5, $reprint, $indtab);
array_multisort($typdoctab, $premautab, $auteurs, $titrehref, $rvnp, $doi, $bibtex, $pdf1, $pdf2, $pdf3, $pdf4, $pdf5, $reprint);

//pour la correspondance entre index
for ($i = 1; $i <= $imax; $i++) {
  $indtab[$i] = $i-1;
}

if (($titre != "") && ($aut != "")) {//si recherche sur un mot du titre et un auteur
  $irec = 0;
  for ($i = 0; $i <= $imax; $i++) {
    if ((stripos($titrehref[$i], $titre) !== false) && (stripos($auteurs[$i], $aut) !== false)) {$irec++;$indtab[$irec]=$i;}
  }
}
if (($titre != "") && ($aut == "")) {//si recherche juste sur le titre
  $irec = 0;
  for ($i = 0; $i <= $imax; $i++) {
    if (stripos($titrehref[$i], $titre) !== false) {$irec++;$indtab[$irec]=$i;}
  }
}
if (($titre == "") && ($aut != "")) {//si recherche juste sur l'auteur
  $irec = 0;
  for ($i = 0; $i <= $imax; $i++) {
    if (stripos($auteurs[$i], $aut) !== false) {$irec++;$indtab[$irec]=$i;}
  }
}

if ($irec == 0) {
  $text .= "<b>".$result3."</b><br><br>";
}else{
  $text .= "<b>".$result4.$irec.$result5." :</b><br><br>";
}

//export en CSV
$Fnm1 = "./HAL/publisHAL.csv"; 
$inF = fopen($Fnm1,"w"); 

fseek($inF, 0);
$chaine = "\xEF\xBB\xBF";
if (isset($_GET['presbib']) && ($_GET['presbib'] != "<br>")) {
  if ($bt == "oui") {
    $chaine .= "Auteurs;Titre;RVNP;DOI;bibtex;pdf1;pdf2;pdf3;pdf4;pdf5;reprint";
  }else{
    $chaine .= "Auteurs;Titre;RVNP;DOI;pdf1;pdf2;pdf3;pdf4;pdf5;reprint";
  }
}else{
  if ($bt == "oui") {
    $chaine .= "Titre;Auteurs;RVNP;DOI;bibtex;pdf1;pdf2;pdf3;pdf4;pdf5;reprint";
  }else{
    $chaine .= "Titre;Auteurs;RVNP;DOI;pdf1;pdf2;pdf3;pdf4;pdf5;reprint";
  }
}
fwrite($inF,$chaine.chr(13).chr(10));
$chaine = "";

//export en RTF
$Fnm2 = "./HAL/publisHAL.rtf";
require_once ("./HAL/phprtflite-1.2.0/lib/PHPRtfLite.php");

PHPRtfLite::registerAutoloader();
$rtf = new PHPRtfLite();
$sect = $rtf->addSection();
$font = new PHPRtfLite_Font(10, 'Arial', '#000000', '#FFFFFF');
$fontlien1 = new PHPRtfLite_Font(10, 'Arial', '#A71817', '#FFFFFF');
$fontlien1->setUnderline();
$fontlien2 = new PHPRtfLite_Font(10, 'Arial', '#0071bb', '#FFFFFF');
$fontlien2->setUnderline();
$parFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_JUSTIFY);

$rubr = "";
$cpt = $ideb;
if ($ifin > $irec) {$ifin = $irec;}
$rubr = "";
for ($k = $ideb; $k <= $ifin; $k++) {
  $ok = "non";
  $i = $indtab[$k];
  if (($titre != "") && ($aut != "")) {//si recherche sur un mot du titre et un auteur
    if ((stripos($titrehref[$i], $titre) !== false) && (stripos($auteurs[$i], $aut) !== false)){$ok = "oui";}
  }
  if (($titre != "") && ($aut == "")) {//si recherche sur un mot du titre
    if (stripos($titrehref[$i], $titre) !== false){$ok = "oui";}
  }
  if (($titre == "") && ($aut != "")) {//si recherche sur un auteur
    if (stripos($auteurs[$i], $aut) !== false){$ok = "oui";}
  }
  if (($titre == "") && ($aut == "")) {//aucune recherche sur un titre ou un auteur
    $ok = "oui";
  }
  if ($ok == "oui") { //si la référence est retenue, on continue la routine
    if ($rubr == "") {
      $rubr = $typdoctab[$i];
      $text .= "<p class='SousRubrique'><b>".$typdoctab[$i]."</b></p>\r\n";
    }
    if ($rubr != $typdoctab[$i]) {
      $text .= "<p class='SousRubrique'><b>".$typdoctab[$i]."</b></p>\r\n";
      $rubr = $typdoctab[$i];
    }
    //mise en évidence des recherches
    $titreaff1 = "<b><font color=#009900>".$titre."</font></b>";
    $titreaff2 = "<b><font color=#009900>".ucfirst($titre)."</font></b>";
    $titreaff3 = "<b><font color=#009900>".strtoupper($titre)."</font></b>";
    $titreaff4 = "<b><font color=#009900>".strtolower($titre)."</font></b>";
    $autaff1 = "<b><font color=#009900>".$aut."</font></b>";
    $autaff2 = "<b><font color=#009900>".ucfirst($aut)."</font></b>";
    $autaff3 = "<b><font color=#009900>".strtoupper($aut)."</font></b>";
    $autaff4 = "<b><font color=#009900>".strtolower($aut)."</font></b>";
    //si nom composé
    $postiret = strpos($aut,"-");
    $autg = "";
    $autd = "";
    $autgd = "";
    $autaff5 = "";
    if ($postiret != 0) {
      $autg = substr($aut,0,($postiret));
      $autd = substr($aut,($postiret+1),(strlen($aut)-$postiret));
      $autgd = ucfirst($autg)."-".ucfirst($autd);
      $autaff5 = "<b><font color=#009900>".$autgd."</font></b>";
    }
    //si recherche sur plusieurs auteurs
    $autaff = $auteurs[$i];
    if (isset($_GET['auteur_exp']) && ($_GET['auteur_exp'] != "")) {
      $auteur_exp_aff = $_GET['auteur_exp'];
      $auteur_exp_aff_tab = explode(";", $auteur_exp_aff);
      $ii = 0;
      while (isset($auteur_exp_aff_tab[$ii]) && $auteur_exp_aff_tab[$ii] != "") {
        $autexp0 = str_replace(","," ",$auteur_exp_aff_tab[$ii]);
        //si nom composé
          $postiret = strpos($autexp0,"-");
          if ($postiret != 0) {
            $autg = substr($autexp0,0,($postiret));
            $autd = substr($autexp0,($postiret+1),(strlen($autexp0)-$postiret));
            $autgd0 = ucfirst($autg)."-".ucfirst($autd);
            $autgd1 = "<b><font color=#009900>".$autgd0."</font></b>";
            $autaff = str_replace($autgd0, $autgd1, $autaff);
          }
        $autexp0 = ucwords(strtolower($autexp0)); 
        $autexp1 = "<b><font color=#009900>".$autexp0."</font></b>";
        $autaff = str_replace($autexp0, $autexp1, $autaff);
        $ii += 1;
      }
    }else{
      $autaff = str_replace(array($aut, ucfirst($aut), strtoupper($aut), strtolower($aut), $autgd),array($autaff1, $autaff2, $autaff3, $autaff4, $autaff5),$auteurs[$i]);    
    }

    $titreaff = str_replace(array($titre, ucfirst($titre), strtoupper($titre), strtolower($titre)),array($titreaff1, $titreaff2, $titreaff3, $titreaff4),$titrehref[$i]);
    if (isset($_GET['presbib']) && ($_GET['presbib'] != "<br>")) {
      //$textaff = "<dt class='ChampRes'>Indice</dt><dd class='ValeurRes Indice' style='display: inline; margin-left: 0%; font-size: 1em;'>".$cpt ."&nbsp;-&nbsp;</dd>";
      $textaff = "<dd class='ValeurRes Indice' style='display: inline; margin-left: 0%; font-size: 1em;'>".$cpt ."&nbsp;-&nbsp;</dd>";
      //$textaff .= "<dt class='ChampRes'>Auteurs</dt><dd class='ValeurRes Auteurs' style='display: inline; margin-left: 0%; font-size: 1em;'>".$autaff."</dd>";
      $textaff .= "<dd class='ValeurRes Auteurs' style='display: inline; margin-left: 0%; font-size: 1em;'>".$autaff."</dd>";
      //$textaff .= "<dt class='ChampRes'>Titre</dt><dd class='ValeurRes Titre' style='display: inline; margin-left: 0%; font-size: 1em;'>".$titreaff."</dd>";
      $textaff .= "<dd class='ValeurRes Titre' style='display: inline; margin-left: 0%; font-size: 1em;'>".$titreaff."</dd>";
      //$textaff .= "<dt class='ChampRes'>Détail</dt><dd class='ValeurRes Detail' style='display: inline; margin-left: 0%; font-size: 1em;'>".$rvnp[$i]."</dd>";
      $textaff .= "<dd class='ValeurRes Detail' style='display: inline; margin-left: 0%; font-size: 1em;'>".$rvnp[$i]."</dd>";
      if ($doi[$i] == "-") {$doiaff = "";}else{$doiaff = $doi[$i];} 
      //$textaff .= "<dt class='ChampRes'>DOI</dt><dd class='ValeurRes DOI' style='display: inline; margin-left: 0%; font-size: 1em;'>".$doiaff."</dd>";
      $textaff .= "<dd class='ValeurRes DOI' style='display: inline; margin-left: 0%; font-size: 1em;'>".$doiaff."</dd>";
      //$textaff .= "<dt class='ChampRes'>Accès au bibtex</dt><dd class='ValeurRes LienBibtex' style='display: inline; margin-left: 0%; font-size: 1em;'>".$bibtex[$i]."</dd>";
      if ($bt == "oui") {
        $textaff .= "<dd class='ValeurRes LienBibtex' style='display: inline; margin-left: 0%; font-size: 1em;'>".$bibtex[$i]."</dd>";
      }else{
        $textaff .= "<dd class='ValeurRes LienBibtex' style='display: inline; margin-left: 0%; font-size: 1em;'></dd>";
      }
      $text .= "<dl class='NoticeRes'><div style='margin-left: 3%;'>";
    }else{
      //$textaff = "<dt class='ChampRes'>Indice</dt><dd class='ValeurRes Indice' style='float: left; font-size: 1em;'>".$cpt ."&nbsp;-&nbsp;</dd>";
      $textaff = "<dd class='ValeurRes Indice' style='float: left; font-size: 1em;'>".$cpt ."&nbsp;-&nbsp;</dd>";
      //$textaff .= "<dt class='ChampRes'>Auteurs</dt><dd class='ValeurRes Titre' style='font-size: 1em;'>".$titreaff."</dd>";
      $textaff .= "<dd class='ValeurRes Titre' style='font-size: 1em;'>".$titreaff."</dd>";
      //$textaff .= "<dt class='ChampRes'>Titre</dt><dd class='ValeurRes Auteurs' style='font-size: 1em;'>".$autaff."</dd>";
      $textaff .= "<dd class='ValeurRes Auteurs' style='font-size: 1em;'>".$autaff."</dd>";
      //$textaff .= "<dt class='ChampRes'>Détail</dt><dd class='ValeurRes Detail' style='font-size: 1em;'>".$rvnp[$i]."</dd>";
      $textaff .= "<dd class='ValeurRes Detail' style='font-size: 1em;'>".$rvnp[$i]."</dd>";
      if ($doi[$i] == "-") {$doiaff = "";}else{$doiaff = $doi[$i];} 
      //$textaff .= "<dt class='ChampRes'>DOI</dt><dd class='ValeurRes DOI' style='font-size: 1em;'>".$doiaff."</dd>";
      $textaff .= "<dd class='ValeurRes DOI' style='font-size: 1em;'>".$doiaff."</dd>";
      //$textaff .= "<dt class='ChampRes'>Accès au bibtex</dt><dd class='ValeurRes' style='display: inline; font-size: 1em;'>".$bibtex[$i]."</dd>";
      if ($bt == "oui") {
        $textaff .= "<dd class='ValeurRes' style='display: inline; font-size: 1em;'>".$bibtex[$i]."</dd>";
      }else{
        $textaff .= "<dd class='ValeurRes' style='display: inline; font-size: 1em;'></dd>";
      }
      $text .= "<dl class='NoticeRes'>";
    }
    $text .= $textaff;
    //export en CSV et RTF
    //Auteurs - titre
    if (isset($_GET['presbib']) && ($_GET['presbib'] != "<br>")) {
      $chaine = strip_tags(str_replace(";",",",str_replace($presbib,"",$auteurs[$i]))).";";
      $chaine .= strip_tags(str_replace(";",",",str_replace($presbib,"",$titrehref[$i]))).";";
      $sect->writeText($cpt." - ".strip_tags(str_replace($presbib,"",$auteurs[$i])), $font);
      $sect->writeText($presbib, $font);
      $crit = $titrehref[$i];
      $txt1 = strip_tags($crit);
      $txt1 = str_replace($presbib,"",$txt1);
      $txt2 = substr($crit,strpos($crit,"href='")+6,strpos($crit,"'>")-strpos($crit,"href='")-6);
      $sect->writeHyperLink($txt2, $txt1, $fontlien1);
    }else{
      $chaine = strip_tags(str_replace(";",",",str_replace($presbib,"",$titrehref[$i]))).";";
      $chaine .= strip_tags(str_replace(";",",",str_replace($presbib,"",$auteurs[$i]))).";";
      $sect->writeText($cpt." - ", $font);
      $crit = $titrehref[$i];
      $txt1 = strip_tags($crit);
      $txt1 = str_replace($presbib,"",$txt1);
      $txt2 = substr($crit,strpos($crit,"href='")+6,strpos($crit,"'>")-strpos($crit,"href='")-6);
      $sect->writeHyperLink($txt2, $txt1, $fontlien1);
      $sect->writeText($presbib, $font);
      $sect->writeText(strip_tags(str_replace($presbib,"",$auteurs[$i])), $font);
    }
    //RVNP
    $chaine .= strip_tags(str_replace(";",",",str_replace($presbib,"",$rvnp[$i]))).";";
    $sect->writeText($presbib.str_replace($presbib,"",strip_tags($rvnp[$i])), $font);
    $sect->writeText($presbib, $font);
    //DOI
    $chaine .= strip_tags(str_replace(";",",",str_replace($presbib,"",$doi[$i]))).";";
    $crit = $doi[$i];
    $sect->writeText("DOI : ", $font);
    if ($crit != "-") {
      $txt1 = str_replace("DOI : ","",strip_tags($crit));
      $txt1 = str_replace($presbib,"",$txt1);
      $txt2 = substr($crit,strpos($crit,"href='")+6,strpos($crit,"'>")-strpos($crit,"href='")-6);
      $sect->writeHyperLink($txt2, $txt1, $fontlien2);
    }else{
      $sect->writeText($crit, $font);
    }
    //Bibtex
    if ($bt == "oui") {
      $chaine .= str_replace(";",",",str_replace($presbib,"",str_replace(array("&nbsp;","target='_blank' "),"",$bibtex[$i])));
      $sect->writeText($presbib, $font);
      $crit = $bibtex[$i];
      $txt1 = "Bibtex";
      $txt2 = substr($crit,strpos($crit,"href='")+6,strpos($crit,"'>")-strpos($crit,"href='")-6);
      $sect->writeHyperLink($txt2, $txt1, $fontlien2);
    }else{
      $sect->writeText($presbib, $font);
    }
    
    //PDF
    $j = 1;
    $cpt++;
    //si plusieurs PDF
    while (isset(${"pdf".$j}[$i])) {
      if (${"pdf".$j}[$i] != "-")  {$text .= ${"pdf".$j}[$i];}
      $j++;
    }
    for($j = 1; $j <= 5; $j++) {
      $chaine .= ";".str_replace(";",",",str_replace(array("&nbsp;","target='_blank' "),"",${"pdf".$j}[$i]));
      $crit = ${"pdf".$j}[$i];
      if ($crit != "-") {
        $sect->writeText(" - ", $font);
        $txt1 = "PDF".$j;
        $txt2 = substr($crit,strpos($crit,"href='")+6,strpos($crit,"'>")-strpos($crit,"href='")-6);
        $sect->writeHyperLink($txt2, $txt1, $fontlien2);
      }
    }
    //reprint
    $chaine .= ";".str_replace(";",",",str_replace($presbib,"",str_replace("&nbsp;","",$reprint[$i])));
    $crit = $reprint[$i];
    if ($crit != "-") {
      $sect->writeText(" - ", $font);
      $txt1 = "Reprint";
      $txt2 = substr($crit,strpos($crit,"href='")+6,strpos($crit,"'>")-strpos($crit,"href='")-6);
      $sect->writeHyperLink($txt2, $txt1, $fontlien2);
    }
    //Affichage    
    if (isset($_GET['presbib']) && ($_GET['presbib'] != "<br>")) {
      //$text .= "<dt class='ChampRes'>Reprint</dt><dd class='ValeurRes Reprint' style='display: inline; margin-left: 0%;'>".$reprint[$i]."</dd></div></dl>\r\n";
      $text .= "<dd class='ValeurRes Reprint' style='display: inline; margin-left: 0%;'>".$reprint[$i]."</dd></div></dl>\r\n";
    }else{
      //$text .= "<dt class='ChampRes'>Reprint</dt><dd class='ValeurRes Reprint' style='display: inline; margin-left: 0%;'>".$reprint[$i]."</dd></dl>\r\n";
      $text .= "<dd class='ValeurRes Reprint' style='display: inline; margin-left: 0%;'>".$reprint[$i]."</dd></dl>\r\n";
    }
    //export en CSV
    fwrite($inF,$chaine.chr(13).chr(10));
    
    //export en RTF
    $sect->writeText("<br><br>", $font);
    $rtf->save($Fnm2);
  }
}

//navigation
$text .= "<br><br><center>\r\n";

$i = 0;
if ($priorite == "collection_exp") {
  if ($labocrit2 == $collection_exp) {$labocrit2 = "";}
}else{
  if ($labocrit2 == $labo) {$labocrit2 = "";}
}
while((($ipas * $i) + 1) <= $irec) {
  $ideb = ($ipas * $i) + 1;
  $ifin = $ideb + $ipas - 1;
  if ($ifin > $irec) {$ifin = $irec;}
  $text .= "<a href='?labo=".$labo."&collection_exp=".$collection_exp."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&mailto=".$mailto."&lang=".$lang."&css=".$css."&form=".$form."&tous=".$tous."&annee_publideb=".$annee_publideb."&anneedep=".$anneedep."&annee_excl=".$annee_excl."&bt=".$bt."&presbib=".$presbib."&labocrit=".$labocrit."&typdoc=".$typdocinit."&anneedeb=".$anneedeb."&anneefin=".$anneefin."&titre=".$titre."&aut=".$aut."&ipas=".$ipas."&ideb=".$ideb."&ifin=".$ifin."&typform=".$typform."'>".$ideb."-".$ifin."</a>&nbsp;&nbsp;&nbsp;\r\n";
  $i++;
}
$text .= "<br><br></center></div></div></div></div>\r\n";

fclose($inF);
if ($irec != 0) {
  $text .= "<center><b><a target='_blank' href='http://".$_SERVER['HTTP_HOST']."/HAL/publisHAL.csv'>".$result6."</a></b>\r\n";
  $text .= " - ";
  $text .= "<b><a target='_blank' href='http://".$_SERVER['HTTP_HOST']."/HAL/publisHAL.rtf'>".$result7."</a></b><br><br></center>\r\n";
}
echo $text;
?>
</body>
</html>