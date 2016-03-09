<?php
header('Content-type: text/html; charset=UTF-8');

$URL = "http://api-preprod.archives-ouvertes.fr/search/?wt=rss&q=labStructCode_s:%22UMR6553%22&fq=producedDate_s:%222014%22&fl=title_s,label_s,uri_s,abstract_s,docType_s,doiId_s,label_bibtex,keyword_s&sort=auth_sort%20asc";

$xml = new SimpleXMLElement($URL, Null, True);  
  
// yeah! xpath!  
$nodes = $xml->xpath('//channel/item');  
  
foreach($nodes as $ua) {  
  // attention, l'appel à String est case sensitive !  
  echo $ua->String . "\n";  
}  
?>
