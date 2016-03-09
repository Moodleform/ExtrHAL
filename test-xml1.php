<?php
header('Content-type: text/html; charset=UTF-8');

function lit_xml($fichier,$item,$champs) {
   // on lit le fichier
   if($chaine = @implode("",@file($fichier))) {
      // on explode sur <item>
      // Dans l'exemple il s'agit de 'profil'
      $tmp = preg_split("/<\/?".$item.">/",$chaine);
      // pour chaque <item> donc tous les profils
      for($i=1;$i<sizeof($tmp)-1;$i+=2)
         // on lit les champs demandés <champ> donc il s'agit de 'id' et 'prenom'
         foreach($champs as $champ) {
            $tmp2 = preg_split("/<\/?".$champ.">/",$tmp[$i]);
            // on ajoute l'élément au tableau
            $tmp3[$i-1][] = @$tmp2[1];
         }
      // et on retourne le tableau dans la fonction
      return $tmp3;
   }
}

$URL = "http://api-preprod.archives-ouvertes.fr/search/?wt=xml&q=labStructCode_s:%22UMR6553%22&fq=producedDate_s:%222014%22&fl=title_s,label_s,uri_s,abstract_s,docType_s,doiId_s,label_bibtex,keyword_s&sort=auth_sort%20asc";

$xml = lit_xml($URL,'doc',array('str name="label_bibtex"','arr name="title_s"','arr name="abstract_s"','str name="uri_s"','str name="docType_s"','str name="label_s"','str name="doiId_s'));
//$xml = lit_xml($URL,'item',array('title','description','pubDate'));

foreach($xml as $row) {

  echo '0 - '.$row[0].'<br><br>';
  echo '1 - '.$row[1].'<br><br>';
  echo '2 - '.$row[2].'<br><br>';

  }
?>
