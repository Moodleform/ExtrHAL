<body style="font-family:calibri,verdana">
<?php
function utf8_fopen_read($fileName) {
    $fc = file_get_contents($fileName);
    $handle=fopen("php://memory", "rw");
    fwrite($handle, $fc);
    fseek($handle, 0);
    return $handle;
}

$Fnm = "./JCR.php";
$inF = fopen($Fnm,"w");
fseek($inF, 0);
$chaine = "";
$chaine .= '<?php'.chr(13);
$chaine .= '$JCR_LISTE = array('.chr(13);
fwrite($inF,$chaine);
$handle = utf8_fopen_read("./JCR.csv");
if ($handle) {
  $ligne = 0;
  $total = count(file("./JCR.csv"));
  while($tab = fgetcsv($handle, 0, ',')) {
    if (is_numeric($tab[0])) {
      $chaine = $ligne.' => array("Rank"=>"'.$tab[0].'", ';
      $chaine .= '"Full Journal Title"=>"'.$tab[1].'", ';
      $chaine .= '"Total Cites"=>"'.$tab[2].'", ';
      $chaine .= '"Journal Impact Factor"=>"'.$tab[3].'", ';
      $chaine .= '"Eigenfactor Score"=>"'.$tab[4].'")';
      if ($ligne != $total-1) {$chaine .= ',';}
      $chaine .= chr(13);
      echo $chaine.'<br>';
      fwrite($inF,$chaine);
      $ligne++;
    }
  }
  $chaine = ');'.chr(13);
  $chaine .= '?>';
  fwrite($inF,$chaine);
  fclose($inF);
  fclose($handle);
}
echo '<br><b>Fichier JCR.php créé.<br><br>'
?>
</body>
