<?php
header('Content-type: text/html; charset=UTF-8');

function monthname($lemois) {
   $lemois = str_replace(array(1,2,3,4,5,6,7,8,9,10,11,12),array("Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"),$lemois);
   return $lemois;
}

function bibtex($recu,$extr) {
  //$extr = $label_bibtex[$i];
  //$recu = "SERIES";
  $pos1 = strpos($extr, $recu);
  $pos2 = strpos($extr, "},", $pos1);
  $pos3 = $pos1+strlen($recu)+4;
  $extr = substr($extr, $pos3, ($pos2-$pos3));
  return $extr;
} 

$dom = new DomDocument;
//$URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructCode_s:"UMR6553"&fq=producedDate_s:"2014"&fl=title_s,label_s,uri_s,abstract_s,docType_s,doiId_s,label_bibtex,keyword_s,authFullName_s&sort=auth_sort asc';
$URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructAcronym_s:"GR"&rows=100000&fq=producedDate_s:"2014"&fl=title_s,label_s,producedDate_s,uri_s,journalTitle_s,volume_s,issue_s,journalPublisher_s,page_s,abstract_s,docType_s,doiId_s,label_bibtex,keyword_s,authFullName_s,bookTitle_s,conferenceTitle_s,conferenceStartDateM_i,scientificEditor_s,publisher_s&sort=auth_sort asc';
$dom->load($URL);

$i = 1;
$res0 = $dom->getElementsByTagName('doc');
foreach($res0 as $resgen0) {
  $res1 = $resgen0->getElementsByTagName('str');
  foreach($res1 as $resgen1) {
    if ($resgen1->hasAttribute("name")) {
      $quoi = $resgen1->getAttribute("name");
      if (strpos("label_bibtex",$quoi) !== false) {$label_bibtex[$i] = $resgen1->nodeValue;}
      if (strpos("uri_s",$quoi) !== false) {$uri[$i] = $resgen1->nodeValue;}
      if (strpos("docType_s",$quoi) !== false) {$doctype[$i] = $resgen1->nodeValue;}
      if (strpos("label_s",$quoi) !== false) {$label[$i] = $resgen1->nodeValue;}
      if (strpos("doiId_s",$quoi) !== false) {$doi[$i] = $resgen1->nodeValue;}
      if (strpos("journalTitle_s",$quoi) !== false) {$journal[$i] = $resgen1->nodeValue;}
      if (strpos("bookTitle_s",$quoi) !== false) {$livre[$i] = $resgen1->nodeValue;}
      if (strpos("conferenceTitle_s",$quoi) !== false) {$colloque[$i] = $resgen1->nodeValue;}
      if (strpos("volume_s",$quoi) !== false) {$volume[$i] = $resgen1->nodeValue;}
      if (strpos("journalPublisher_s",$quoi) !== false) {$journalPublisher[$i] = $resgen1->nodeValue;}
      if (strpos("page_s",$quoi) !== false) {$page[$i] = $resgen1->nodeValue;}
      if (strpos("producedDate_s",$quoi) !== false) {$prodate[$i] = $resgen1->nodeValue;}
    }
  }
  $res2 = $resgen0->getElementsByTagName('arr');
  foreach($res2 as $resgen2) {
    if ($resgen2->hasAttribute("name")) {
      $quoi = $resgen2->getAttribute("name");
      if (strpos("abstract_s",$quoi) !== false) {$abstract[$i] = $resgen2->nodeValue;}
      if (strpos("publisher_s",$quoi) !== false) {$publication[$i] = $resgen2->nodeValue;}
      if (strpos("scientificEditor_s",$quoi) !== false) {$editeur[$i] = $resgen2->nodeValue;}
      if (strpos("issue_s",$quoi) !== false) {$issue[$i] = $resgen2->nodeValue;}
      if (strpos("title_s",$quoi) !== false) {
        $titleliste = "";
        $enfants = $resgen2->childNodes;
        foreach($enfants as $enfant) {
          $titleliste = $enfant->nodeValue;
        }
        $title[$i] = $titleliste;
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
        $autliste = "";
        $enfants = $resgen2->childNodes;
        foreach($enfants as $enfant) {
          $autliste .= $enfant->nodeValue . ", ";
        }
        $autliste = substr($autliste, 0, (strlen($autliste)-2));
        $auteurs[$i] = $autliste;
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
  $rvnp[$i] = "";
  //Si COUV
  if ($doctype[$i] == "COUV") {
    if (isset($journal[$i])) {$rvnp[$i] .= "<i>".$journal[$i]."</i>, ";}
    if (isset($journalPublisher[$i])) {$rvnp[$i] .= $journalPublisher[$i].", ";}
    if (isset($editeur[$i])) {$rvnp[$i] .= "<i>".$editeur[$i]."</i>, ";}
    if (isset($publication[$i])) {$rvnp[$i] .= $publication[$i].", ";}
    if (isset($livre[$i])) {$rvnp[$i] .= $livre[$i].", ";}
    if (isset($page[$i])) {$rvnp[$i] .= "pp.".$page[$i].", ";}
    if (isset($prodate[$i])) {$rvnp[$i] .= $prodate[$i].", ";}
    $rvnp[$i] .= bibtex('SERIES', $label_bibtex[$i]);
  }
  
  //Si ART
  if ($doctype[$i] == "ART") {
    if (isset($journal[$i])) {$rvnp[$i] .= "<i>".$journal[$i]."</i>, ";}
    if (isset($journalPublisher[$i])) {$rvnp[$i] .= $journalPublisher[$i].", ";}
    if (isset($prodate[$i])) {$rvnp[$i] .= $prodate[$i].", ";}
    if (isset($volume[$i])) {$rvnp[$i] .= $volume[$i];}
    if (isset($issue[$i])) {$rvnp[$i] .= "(".$issue[$i]."), ";}else{$rvnp[$i] .= ", ";}
    if (isset($page[$i])) {$rvnp[$i] .= "pp.".$page[$i];}
  }
  
  //Si COMM
  if ($doctype[$i] == "COMM") {
    if (isset($colloque[$i])) {$rvnp[$i] .= "<i>".$colloque[$i]."</i>, ";}
    if (isset($collmois[$i])) {$rvnp[$i] .= monthname($collmois[$i])." ";}
    if (isset($prodate[$i])) {$rvnp[$i] .= $prodate[$i].", ";}
    $rvnp[$i] .= bibtex('ADDRESS', $label_bibtex[$i]).", ";
    //if (isset($livre[$i])) {$rvnp[$i] .= $livre[$i].", ";}
    if (isset($page[$i])) {$rvnp[$i] .= $page[$i];}
  }

  //Si OTHER
  if ($doctype[$i] == "OTHER") {
    if (isset($colloque[$i])) {$rvnp[$i] .= "<i>".$colloque[$i]."</i>, ";}
    if (isset($collmois[$i])) {$rvnp[$i] .= monthname($collmois[$i])." ";}
    if (isset($prodate[$i])) {$rvnp[$i] .= $prodate[$i].", ";}
    //if (isset($livre[$i])) {$rvnp[$i] .= $livre[$i].", ";}
    if (isset($page[$i])) {$rvnp[$i] .= $page[$i];}
  }
    
  $i++;
  
}
var_dump($doctype);
?>
