<?php
// Version 7.0 du 01/10/14
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
  $typdocHAL = array("1" => "Articles",
                "2" => "Communications",
                "3" => "Chapitres d'ouvrages scientifiques",
                "4" => "Thèses",
                "5" => "Autres publications",
                "6" => "Autres",
                "7" => "Rapports de recherche",
                "8" => "Images",
                "9" => "Ouvrages scientifiques",
                "10" => "Direction d'ouvrages scientifiques",
                "11" => "Mémoire",
                "12" => "HDR (Habilitation à Diriger des Recherches)",
                "13" => "Brevet",
                "14" => "Poster",
                "15" => "Cours",
                "16" => "Conférences invitées");
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
  $result3bis = "Affinez vos critères de recherche: plus de 5000 publications constituent l'extraction initiale.";
  $result3_1 = "<u>Remarque :</u> Seuls les ";
  $result3_2 = " premiers auteurs sont affichés.";
  $result4 = "";
  $result5 = " publication(s)";
  $result6 = "Exporter les données affichées en CSV";
  $result7 = "Exporter les données affichées en RTF";
}else{//anglais
  $typdocHAL = array("1" => "Article",
                "2" => "Workshop communications",
                "3" => "Scientific book chapter",
                "4" => "PhD thesis",
                "5" => "Other publication",
                "6" => "Other",
                "7" => "Research report",
                "8" => "Picture",
                "9" => "Scientific book",
                "10" => "Edition of book or proceedings",
                "11" => "Preprint, Working Paper, ...",
                "12" => "Habilitation research",
                "13" => "Patent",
                "14" => "Poster",
                "15" => "Lecture",
                "16" => "Invited conference talk");
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
  $result3bis = "Refine your search criteria: more than 5000 publications constitute the initial extraction.";
  $result3_1 = "<u>Note:</u> In accordance with your wishes, when necessary, only the first ";
  $result3_2 = " authors are displayed.";
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
  $equipe_recherche_exp = $_GET['equipe_recherche_exp'];
}
$auteur_exp = "";
if (isset($_GET['auteur_exp']) && ($_GET['auteur_exp'] != "")) {
  $auteur_exp = wd_remove_accents(ucwords($_GET['auteur_exp']));
  //$auteur_exp = str_replace("'", "\'", $auteur_exp);
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
//quand les publis ne portent pas forcément l'affiliation à collection_exp > ex: &tous=oui
$tous = "";
if (isset($_GET['tous']) && ($_GET['tous'] != "")) {
  $tous = $_GET['tous'];
}

//pour le formulaire simple, année minimale pour l'affichage > ex: &annee_publideb=1998 > on limitera toujours l'affichage à 8 années au maximum
$annee_publideb = "";
if (isset($_GET['annee_publideb']) && ($_GET['annee_publideb'] != "")) {
  $annee_publideb = $_GET['annee_publideb'];
}

//précision quant à l'année de publication initiale > ex: &anneedep=2001 > aucune limite dans le nombre d'années à afficher
$anneedep = "";
if (isset($_GET['anneedep']) && ($_GET['anneedep'] != "")) {
  $anneedep = $_GET['anneedep'];
}

//pour limiter le nombre d'auteurs à afficher si liste trop longue >  n premiers + et al.
$lim_aut = "";
if (isset($_GET['lim_aut']) && ($_GET['lim_aut'] != "")) {
  $lim_aut = $_GET['lim_aut'];
  //On élimine le cas = 0
  if ($lim_aut == 0) {$lim_aut = "";}
}

//années à exclure > ex: &annee_excl=(2013,2010)
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
//$plong = 9;
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
//if (isset($_GET['typdoc']) && ($_GET['typdoc'] != "") && ($typform == $form9s)) {$typdocinit = $_GET['typdoc']; $typdoc = "('".$_GET['typdoc']."')";}else{$typdocinit = ""; $typdoc = "";}
if (isset($_GET['typdoc']) && ($_GET['typdoc'] != "")) {$typdocinit = $_GET['typdoc']; $typdoc = $_GET['typdoc'];}else{$typdocinit = ""; $typdoc = "";}
if (isset($_GET['anneedeb'])) {$anneedeb = $_GET['anneedeb'];}else{$anneedeb = date('Y', time());$anneefin = date('Y', time());}
if (isset($_GET['anneefin'])) {$anneefin = $_GET['anneefin'];}else{if (isset($_GET['anneedeb'])) {$anneefin = $_GET['anneedeb'];}else{$anneefin = $anneedeb;}}
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
  if($typdoc == "ART") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='ART'".$txt."}>".$typdocHAL['1']."</option>\r\n";
  if($typdoc == "COMM") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='COMM'".$txt.">".$typdocHAL['2']."</option>\r\n";
  if($typdoc == "COUV") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='COUV'".$txt.">".$typdocHAL['3']."</option>\r\n";
  if($typdoc == "THESE") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='THESE'".$txt.">".$typdocHAL['4']."</option>\r\n";
  if($typdoc == "UNDEFINED") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='UNDEFINED'".$txt.">".$typdocHAL['5']."</option>\r\n";
  if($typdoc == "OTHER") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='OTHER'".$txt.">".$typdocHAL['6']."</option>\r\n";
  if($typdoc == "REPORT") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='REPORT'".$txt.">".$typdocHAL['7']."</option>\r\n";
  if($typdoc == "IMG") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='IMG'".$txt.">".$typdocHAL['8']."</option>\r\n";
  if($typdoc == "OUV") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='OUV'".$txt.">".$typdocHAL['9']."</option>\r\n";
  if($typdoc == "DOUV") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='DOUV'".$txt.">".$typdocHAL['10']."</option>\r\n";
  if($typdoc == "MEM") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='MEM'".$txt.">".$typdocHAL['11']."</option>\r\n";
  if($typdoc == "HDR") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='HDR'".$txt.">".$typdocHAL['12']."</option>\r\n";
  if($typdoc == "PATENT") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='PATENT'".$txt.">".$typdocHAL['13']."</option>\r\n";
  if($typdoc == "POSTER") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='POSTER'".$txt.">".$typdocHAL['14']."</option>\r\n";
  if($typdoc == "LECTURE") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='LECTURE'".$txt.">".$typdocHAL['15']."</option>\r\n";
  if($typdoc == "PRESCONF") {$txt = " selected";}else{$txt = "";}
  $text .= "<option value='PRESCONF'".$txt.">".$typdocHAL['16']."</option>\r\n";
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
  $text .= "<input type='hidden' name='lim_aut' value='".$lim_aut."'>\r\n";
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
      $text .= "<a href=\"?labo=".$labo."&collection_exp=".$collection_exp."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&mailto=".$mailto."&lang=".$lang."&css=".$css."&form=".$form."&tous=".$tous."&annee_publideb=".$annee_publideb."&anneedep=".$anneedep."&lim_aut=".$lim_aut."&annee_excl=".$annee_excl."&bt=".$bt."&presbib=".$presbib."&labocrit=".$labocrit."&typdoc=".$typdoc."&typform=".$typform."&anneedeb=".$i."&anneefin=".$i."&titre=".$titre."&aut=".$aut."&ipas=".$ipas."\">".$i."</a>&nbsp;&nbsp;&nbsp;\r\n";
    }
    $i--;
  }
  $text .= "\r\n</div>\r\n";
  if ($form != "aucun") {
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
    $text .= "<input type='hidden' name='lim_aut' value='".$lim_aut."'>\r\n";
    $text .= "<input type='hidden' name='annee_excl' value='".$annee_excl."'>\r\n";
    $text .= "<input type='hidden' name='ipas' value='".$ipas."'>\r\n";
    $text .= "<input type='hidden' name='ideb' value='1'>\r\n";
    $text .= "<input type='hidden' name='typform' value='".$form9c."'>\r\n";
    $text .= "<br><input type='submit' value='".$form8."'>&nbsp;&nbsp;&nbsp;";
    $text .= "<input type='submit' name='typform' value='".$form9s."'>";
    $text .= "</form><br>";
  }
}

if ((isset($_GET['aut']) && $_GET['aut'] != "") || (isset($_GET['titre']) && $_GET['titre'] != "") || (isset($_GET['typdoc']) && $_GET['typdoc'] != "" && $_GET['typdoc'] != "(\'ART\',\'COMM\')")) {
  $text .= "<center><a href='".$_SERVER['PHP_SELF']."?labo=".$labo."&collection_exp=".$collection_exp."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&mailto=".$mailto."&lang=".$lang."&css=".$css."&form=".$form."&tous=".$tous."&annee_publideb=".$annee_publideb."&anneedep=".$anneedep."&lim_aut=".$lim_aut."&annee_excl=".$annee_excl."&bt=".$bt."&typform=".$typform."'>".$reinit."</a></center><br><br>\r\n";
}

$labo2 = $labocrit;

if ($form != "aucun") {
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
  
  //if ($lang == "fr") {
    //if ($tous == "oui") {
      //$HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Francais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    //}else{
      //$HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&collection_exp=".$labocrit."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Francais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    //}
  //}else{
    //if ($tous == "oui") {
      //$HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Anglais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    //}else{
      //$HAL_URL = "http://hal.archives-ouvertes.fr/Public/afficheRequetePubli.php?typdoc=".$typdoc."&annee_publideb=".$anneedeb."&annee_publifin=".$anneefin."&collection_exp=".$labocrit."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&CB_typdoc=oui&CB_auteur=oui&CB_titre=oui&CB_article=oui&CB_DOI=oui&langue=Anglais&tri_exp=annee_publi&tri_exp2=typdoc&tri_exp3=date_publi&ordre_aff=TA&Fen=Aff";
    //}
  //}
  
  //Extraction des résultats
  $dom = new DomDocument;
  //$URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructCode_s:"UMR6553"&fq=producedDate_s:"2014"&fl=title_s,label_s,uri_s,abstract_s,docType_s,doiId_s,label_bibtex,keyword_s,authFullName_s&sort=auth_sort asc';
  //$URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructAcronym_s:"GR"&rows=100000&fq=producedDate_s:"2014" AND producedDate_s:"2013"&fl=title_s,label_s,producedDate_s,uri_s,journalTitle_s,abstract_s,docType_s,doiId_s,keyword_s,authFullName_s,bookTitle_s,conferenceTitle_s,&sort=auth_sort asc';
  $URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml';
  if ($tous == "oui") {
    $URL .= '&rows=100000&fq=';
  }else{
    $URL .= '&q=labStructAcronym_s:"'.$labocrit.'"';
  }
  if (isset($equipe_recherche_exp)) {
    $URL .= '&rteamStructName_s:"'.$equipe_recherche_exp.'"';
  }
  $URL .= '&rows=100000&fq=';
  if ($anneedeb != $anneefin) {
    $iann = $anneedeb;
    while ($iann <= $anneefin) {
      if ($iann == $anneedeb) {$URL .= " (";}else{$URL .= " OR";}
      $URL .= ' producedDate_s:"'.$iann.'"';
      $iann++;
    }
    $URL .= ')';
  }else{
    $URL .= 'producedDate_s:"'.$anneedeb.'"';
    //if ($anneefin != "") {$URL .= ' OR producedDate_s:"'.$anneefin.'"';}
  }
  if ($auteur_exp != "") {
    if (strpos($auteur_exp, ",") === false) {
      $URL .= ' AND authFullName_s:"'.$auteur_exp.'"';
    }else{
      $diffaut = explode(",", $auteur_exp);
      $iaut = 0;
      while (isset($diffaut[$iaut])) {
        if ($iaut == 0) {$URL .= " AND (";}else{$URL .= " OR";}
        $auteur_exp = $diffaut[$iaut];
        $URL .= ' authFullName_s:"'.$auteur_exp.'"';
        $iaut++;
      }
      $URL .= ')';
    }
  }
  if ($typdoc != "") {
    if (strpos($typdoc, ",") === false) {
      $URL .= ' AND docType_s:"'.$typdoc.'"';
    }else{
      $diffdoc = explode(",", $typdoc);
      $idoc = 0;
      while (isset($diffdoc[$idoc])) {
        if ($idoc == 0) {$URL .= " AND (";}else{$URL .= " OR";}
        $typdoc = $diffdoc[$idoc];
        $URL .= ' docType_s:"'.$typdoc.'"';
        $idoc++;
      }
      $URL .= ')';
    }
  }
  $URL .= '&fl=title_s,label_s,producedDate_s,uri_s,journalTitle_s,abstract_s,docType_s,doiId_s,keyword_s,authFullName_s,bookTitle_s,conferenceTitle_s,fileMain_s&sort=auth_sort asc';
  $URL = str_replace(" ", "%20", $URL);
  //echo $URL;
  
  //$dom->load($URL);
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $URL);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'SCD (https://halur1.univ-rennes1.fr)');
  curl_setopt($ch, CURLOPT_USERAGENT, 'PROXY (http://siproxy.univ-rennes1.fr)');
  $resultat = curl_exec($ch);
  //$resultat = str_replace(">", ">\r\n", $resultat); 
  //echo "Début<br>".$resultat."Fin<br>";
  curl_close($ch);
  $dom = new DOMDocument();
  $dom->loadXML($resultat);


  $i = 1;
  $res0 = $dom->getElementsByTagName('doc');
  foreach($res0 as $resgen0) {
    $res1 = $resgen0->getElementsByTagName('str');
    foreach($res1 as $resgen1) {
      if ($resgen1->hasAttribute("name")) {
        $quoi = $resgen1->getAttribute("name");
        //if (strpos("label_bibtex",$quoi) !== false) {$label_bibtex[$i] = $resgen1->nodeValue;}
        if (strpos("uri_s",$quoi) !== false) {$uri[$i] = $resgen1->nodeValue;}
        if (strpos("fileMain_s",$quoi) !== false) {$pdf1[$i] = $resgen1->nodeValue;}
        if (strpos("docType_s",$quoi) !== false) {$typdocxml[$i] = $resgen1->nodeValue;}
        if (strpos("label_s",$quoi) !== false) {$label[$i] = $resgen1->nodeValue;}
        if (strpos("doiId_s",$quoi) !== false) {$doi[$i] = $resgen1->nodeValue;}
        if (strpos("journalTitle_s",$quoi) !== false) {$journal[$i] = $resgen1->nodeValue;}
        if (strpos("bookTitle_s",$quoi) !== false) {$livre[$i] = $resgen1->nodeValue;}
        if (strpos("conferenceTitle_s",$quoi) !== false) {$colloque[$i] = $resgen1->nodeValue;}
        //if (strpos("volume_s",$quoi) !== false) {$volume[$i] = $resgen1->nodeValue;}
        //if (strpos("journalPublisher_s",$quoi) !== false) {$journalPublisher[$i] = $resgen1->nodeValue;}
        //if (strpos("page_s",$quoi) !== false) {$page[$i] = $resgen1->nodeValue;}
        //if (strpos("producedDate_s",$quoi) !== false) {$prodate[$i] = $resgen1->nodeValue;}
      }
    }
    $res2 = $resgen0->getElementsByTagName('arr');
    foreach($res2 as $resgen2) {
      if ($resgen2->hasAttribute("name")) {
        $quoi = $resgen2->getAttribute("name");
        if (strpos("abstract_s",$quoi) !== false) {$abstract[$i] = $resgen2->nodeValue;}
        //if (strpos("publisher_s",$quoi) !== false) {$publication[$i] = $resgen2->nodeValue;}
        //if (strpos("scientificEditor_s",$quoi) !== false) {$editeur[$i] = $resgen2->nodeValue;}
        //if (strpos("issue_s",$quoi) !== false) {$issue[$i] = $resgen2->nodeValue;}
        if (strpos("title_s",$quoi) !== false) {
          $titleliste = "";
          $enfants = $resgen2->childNodes;
          foreach($enfants as $enfant) {
            $titleliste = $enfant->nodeValue;
          }
          $titreseul[$i] = $titleliste;
        }
        if (strpos("keyword_s",$quoi) !== false) {
          $keywliste = "";
          $enfants = $resgen2->childNodes;
          foreach($enfants as $enfant) {
            $keywliste .= $enfant->nodeValue . ", ";
          }
          $keywliste = substr($keywliste, 0, (strlen($keywliste)-2));
          $keyword[$i] = $keywliste;
        }
        if (strpos("authFullName_s",$quoi) !== false) {
          $cpt = 1;
          $autliste = "";
          $autetal = "";
          $enfants = $resgen2->childNodes;
          foreach($enfants as $enfant) {
            $autliste .= $enfant->nodeValue . ", ";
            if($cpt <= 5) {$autetal .= $enfant->nodeValue . ", ";}
            $cpt++;
          }
          $cpt--;
          $testetal = substr($autetal, (strlen($autetal)-8), 6);
          if($testetal != "Et Al.") {
            if($cpt > 5) {
              $autetal .= "et al.";
            }else{
              $autetal = substr($autetal, 0, (strlen($autetal)-2));
            }
          }else{
            $autetal = substr($autetal, 0, (strlen($autetal)-2));
          }
          $auteursetal[$i] = $autetal;
          //$autliste = substr($autliste, 0, (strlen($autliste)-2));
          $autliste = wd_remove_accents($autliste);
          
          if ($lim_aut != "") {
            $cpt = 1;
            $pospv = 0;
            $lim_aut_ok = 1;
            while ($cpt <= $lim_aut) {
              if (strpos($autliste, ",", $pospv+1) !== false) {
                $pospv = strpos($autliste, ",", $pospv+1);
                $cpt ++;
              }else{
                $lim_aut_ok = 0;
                break;
              }
            }
            if ($lim_aut_ok != 0) {
              $auteurs[$i] = substr($autliste, 0, $pospv);
              $auteurs[$i] .= " <i> et al.</i>";
            }else{
              if ($presbib == "<br>") {
                $auteurs[$i] = substr($autliste, 0, (strlen($autliste)-2));
              }else{
                $auteurs[$i] = $autliste;
              }
            }
            //echo $lim_aut_ok."<br>";
            //echo $auteur_temp."<br>";
          }else{
            if ($presbib == "<br>") {
              $auteurs[$i] = substr($autliste, 0, (strlen($autliste)-2));
            }else{
              $auteurs[$i] = $autliste;
            }
          }
          $auteurs[$i] .= $presbib;
        }
      }
    }
    $res3 = $resgen0->getElementsByTagName('int');
    foreach($res3 as $resgen3) {
      if ($resgen3->hasAttribute("name")) {
        $quoi = $resgen3->getAttribute("name");
        if (strpos("conferenceStartDateM_i",$quoi) !== false) {$collmois[$i] = $resgen3->nodeValue;}
      }
    }
    $i++;
  }
  
  $i = 1;
  while (isset($label[$i])) {
    if(isset($uri[$i]) && isset($titreseul[$i])) {
      if(strpos($uri[$i], "http") !== false) {
        $uri[$i] = str_replace(array("http://","https://","http//","https//"), "", $uri[$i]);
      }
      $titrehref[$i] = "<a target='_blank' href='https://".$uri[$i]."'>".$titreseul[$i]."</a>".$presbib;
    }
    if(isset($doi[$i])) {$doinit[$i] = $doi[$i]; $doi[$i] = "DOI : <a target='_blank' href='http://dx.doi.org/".$doi[$i]."'>".$doi[$i]."</a>".$presbib;}
    if(isset($pdf1[$i])) {
      if(strpos($pdf1[$i], "http") !== false) {
          $pdf1[$i] = str_replace(array("http://","https://","http//","https//"), "", $pdf1[$i]);
      }
      $pdf1[$i] = "<a target='_blank' href='https://".$pdf1[$i]."'><img alt='PDF' src='http://hal.archives-ouvertes.fr/images/Haltools_pdf.png' border='0'  title='PDF' /></a>";
    }
    if(isset($typdocxml[$i])) {
      if($typdocxml[$i] == "ART") {$typdoctab[$i] = $typdocHAL[1];}
      if($typdocxml[$i] == "COMM") {$typdoctab[$i] = $typdocHAL[2];}
      if($typdocxml[$i] == "COUV") {$typdoctab[$i] = $typdocHAL[3];}
      if($typdocxml[$i] == "THESE") {$typdoctab[$i] = $typdocHAL[4];}
      if($typdocxml[$i] == "UNDEFINED") {$typdoctab[$i] = $typdocHAL[5];}
      if($typdocxml[$i] == "OTHER") {$typdoctab[$i] = $typdocHAL[6];}
      if($typdocxml[$i] == "REPORT") {$typdoctab[$i] = $typdocHAL[7];}
      if($typdocxml[$i] == "IMG") {$typdoctab[$i] = $typdocHAL[8];}
      if($typdocxml[$i] == "OUV") {$typdoctab[$i] = $typdocHAL[9];}
      if($typdocxml[$i] == "DOUV") {$typdoctab[$i] = $typdocHAL[10];}
      if($typdocxml[$i] == "MEM") {$typdoctab[$i] = $typdocHAL[11];}
      if($typdocxml[$i] == "HDR") {$typdoctab[$i] = $typdocHAL[12];}
      if($typdocxml[$i] == "PATENT") {$typdoctab[$i] = $typdocHAL[13];}
      if($typdocxml[$i] == "POSTER") {$typdoctab[$i] = $typdocHAL[14];}
      if($typdocxml[$i] == "LECTURE") {$typdoctab[$i] = $typdocHAL[15];}
      if($typdocxml[$i] == "PRESCONF") {$typdoctab[$i] = $typdocHAL[16];}
    }
    $test = $label[$i];
    $test = str_replace("..", ".", $test);
    $test = str_replace($auteursetal[$i].". ", "", $test);
    $test = str_replace($auteursetal[$i], "", $test);
    $test = str_replace($titreseul[$i].". ", "", $test);
    if (isset($doinit[$i])) {$test = str_replace("&lt;".$doinit[$i]."&gt;", "", $test);}
    if (isset($uri[$i])) {
      $url = str_replace(array("http://", "https://"), "",$uri[$i]);
      $pos = strpos($url, "/")+1;
      $url = substr($url, $pos, (strlen($url)-$pos));
      $bibtex[$i] = "<a target='_blank' href='http://hal.archives-ouvertes.fr/Public/AfficheBibTex.php?id=".$url."'><img alt='BibTex' src='http://hal.archives-ouvertes.fr/images/Haltools_bibtex3.png' border='0' title='BibTex' /> </a>";
      $test = str_replace("&lt;".$url."&gt;", "", $test);
      $test = str_replace(", et al. ", "", $test);
      $test = str_replace(". .", ".", $test);
      $test = str_replace(", .", ".", $test);
    }
    if(isset($journal[$i])) {$test = str_replace($journal[$i], "<i>".$journal[$i]."</i>", $test);}
    if(isset($livre[$i])) {$test = str_replace($livre[$i], "<i>".$livre[$i]."</i>", $test);}
    if(isset($colloque[$i])) {$test = str_replace($colloque[$i], "<i>".$colloque[$i]."</i>", $test);}
    //echo $i.' => '.$label[$i].'<br>';
    //echo $i.'bis => '.$test.'<br>';
    $rvnp[$i] = $test.$presbib;
    //Demande reprint par mail
    if ($mailto != "aucun") {
      $repr = "&nbsp;<a href='mailto:".$mailto."?subject=Reprint request&amp;body=Would you please send me a copy of the following article: ";
      $repr .= str_replace("'","’",strip_tags($auteurs[$i]));
      $repr .= " - ";
      $repr .= str_replace("'","’",strip_tags($titreseul[$i]));
      $repr .= " - ";
      $repr .= str_replace("'","’",strip_tags($rvnp[$i]));
      $repr .= " Many thanks for considering my request.";
      $repr .= "'><img border='0' src='http://ecobio.univ-rennes1.fr/e107_images/custom/ReprintRequest.jpg' alt='Reprint request: Subject to availability' title='Reprint request: Subject to availability'></a>";
      $repr .= "<br>";
    }else{
      $repr = "&nbsp;";
    }
    $reprint[$i] = $repr;

    $i++;
  }


  $affin = "0";
  //if (strpos($resultat, "Affinez vos critères") !== false) {
    //$affin = "1";
  //}

  if ($labocrit2 == "") {$labocrit2 = $labocrit;}else{$labocrit2 .= ";".$labocrit;}
  $ii++;
}

$imax = $i-1;
$irec = $imax;

//Création d'un tableau avec juste le nom du premier auteur
for ($i = 1; $i <= $imax; $i++) {
  $totaut = $auteurs[$i];
  $pos1 = strpos($totaut, " ");
  $pos2 = strpos($totaut, ",");
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
  if (!isset($pdf[$i])) {$pdf[$i] = "-";}
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
//var_dump($indtab);

if (($titre != "") && ($aut != "")) {//si recherche sur un mot du titre et un auteur
  $irec = 0;
  for ($i = 0; $i < $imax; $i++) {
    if ((stripos($titrehref[$i], $titre) !== false) && (stripos($auteurs[$i], $aut) !== false)) {$irec++;$indtab[$irec]=$i;}
  }
}
if (($titre != "") && ($aut == "")) {//si recherche juste sur le titre
  $irec = 0;
  for ($i = 0; $i < $imax; $i++) {
    if (stripos($titrehref[$i], $titre) !== false) {$irec++;$indtab[$irec]=$i;}
  }
}
if (($titre == "") && ($aut != "")) {//si recherche juste sur l'auteur
  $irec = 0;
  for ($i = 0; $i < $imax; $i++) {
    if (stripos($auteurs[$i], $aut) !== false) {$irec++;$indtab[$irec]=$i;}
  }
}

if ($irec == 0) {
  if ($affin == "1") {
    $text .= "<b>".$result3bis."</b><br><br>";
  }else{
    $text .= "<b>".$result3."</b><br><br>";
  }
}else{
  $text .= "<b>".$result4.$irec.$result5." :</b><br><br>";
}

if ($lim_aut != "") {
  $text .= "<i>".$result3_1.$lim_aut.$result3_2."</i><br><br>";
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
      $auteur_exp_aff = wd_remove_accents(ucwords($_GET['auteur_exp']));
      $auteur_exp_aff_tab = explode(",", $auteur_exp_aff);
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
        //$autexp0 = ucwords(strtolower($autexp0));
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
  $text .= "<a href=\"?labo=".$labo."&collection_exp=".$collection_exp."&equipe_recherche_exp=".$equipe_recherche_exp."&auteur_exp=".$auteur_exp."&mailto=".$mailto."&lang=".$lang."&css=".$css."&form=".$form."&tous=".$tous."&annee_publideb=".$annee_publideb."&anneedep=".$anneedep."&lim_aut=".$lim_aut."&annee_excl=".$annee_excl."&bt=".$bt."&presbib=".$presbib."&labocrit=".$labocrit."&typdoc=".$typdocinit."&anneedeb=".$anneedeb."&anneefin=".$anneefin."&titre=".$titre."&aut=".$aut."&ipas=".$ipas."&ideb=".$ideb."&ifin=".$ifin."&typform=".$typform."\">".$ideb."-".$ifin."</a>&nbsp;&nbsp;&nbsp;\r\n";
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