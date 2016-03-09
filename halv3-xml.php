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
$presbib = "";
$mailto = "";

$dom = new DomDocument;
//$URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructCode_s:"UMR6553"&fq=producedDate_s:"2014"&fl=title_s,label_s,uri_s,abstract_s,docType_s,doiId_s,label_bibtex,keyword_s,authFullName_s&sort=auth_sort asc';
//$URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructAcronym_s:"GR"&rows=100000&fq=producedDate_s:"2014" AND producedDate_s:"2013"&fl=title_s,label_s,producedDate_s,uri_s,journalTitle_s,abstract_s,docType_s,doiId_s,keyword_s,authFullName_s,bookTitle_s,conferenceTitle_s,&sort=auth_sort asc';
$URL = 'http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructName_s:"Géosciences Rennes"&rows=100000&fq=producedDate_s:"2014"&fl=title_s,label_s,producedDate_s,uri_s,journalTitle_s,abstract_s,docType_s,doiId_s,keyword_s,authFullName_s,bookTitle_s,conferenceTitle_s,files_s&sort=auth_sort asc';
$dom->load($URL);

$i = 1;
$res0 = $dom->getElementsByTagName('doc');
foreach($res0 as $resgen0) {
  $res1 = $resgen0->getElementsByTagName('str');
  foreach($res1 as $resgen1) {
    if ($resgen1->hasAttribute("name")) {
      $quoi = $resgen1->getAttribute("name");
      //if (strpos("label_bibtex",$quoi) !== false) {$label_bibtex[$i] = $resgen1->nodeValue;}
      if (strpos("uri_s",$quoi) !== false) {$uri[$i] = $resgen1->nodeValue;}
      if (strpos("docType_s",$quoi) !== false) {$typdoctab[$i] = $resgen1->nodeValue;}
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
      if (strpos("files_s",$quoi) !== false) {
        $ficpdfliste = "";
        $enfants = $resgen2->childNodes;
        foreach($enfants as $enfant) {
          echo $enfant->nodeValue."<br>";
          $ficpdfliste .= $enfant->nodeValue . ",";
        }
        $ficpdfliste = substr($ficpdfliste, 0, (strlen($ficpdfliste)-1));
        $ficpdf[$i] = $ficpdfliste;
      }
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
  if ($presbib == "") {$presbib = "-";}
  if(isset($uri[$i]) && isset($titreseul[$i])) {$titrehref[$i] = "<a target='_blank' href='https://".$uri[$i]."'>".$titreseul[$i]."</a>".$presbib;}
  if(isset($doi[$i])) {$doinit[$i] = $doi[$i]; $doi[$i] = "<a target='_blank' href='http://dx.doi.org/".$doi[$i]."'>".$doi[$i]."</a>";}
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
    $bibtex[$i] = "<a target='_blank' href='http://hal.archives-ouvertes.fr/Public/AfficheBibTex.php?id=".$url."'><img alt='BibTex' src='http://hal.archives-ouvertes.fr/images/Haltools_bibtex3.png' border='0'  title='BibTex' /></a>";
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
  $rvnp[$i] = $test;
  //Demande reprint par mail
  if ($mailto == "") {$mailto="toto@titi.fr";}
  if ($mailto != "aucun") {
    $repr = "&nbsp;<a href='mailto:".$mailto."?subject=Reprint request&amp;body=Would you please send me a copy of the following article: ";
    $repr .= str_replace("'","’",strip_tags($auteurs[$i]));
    $repr .= " - ";
    $repr .= str_replace("'","’",strip_tags($titreseul[$i]));
    $repr .= " - ";
    $repr .= str_replace("'","’",strip_tags($rvnp[$i]));
    $repr .= " Many thanks for considering my request.";
    $repr .= "'><img border='0' src='http://ecobio.univ-rennes1.fr/e107_images/custom/ReprintRequest.jpg' alt='Reprint request: Subject to availability' title='Reprint request: Subject to availability'></a>";
    $repr .= "<br><br>";
  }else{
    $repr = "&nbsp;";
  }
  $reprint[$i] = $repr;

  $i++;
}
var_dump($rvnp);
?>