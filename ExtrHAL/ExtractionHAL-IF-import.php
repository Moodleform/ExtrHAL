    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
            "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>ExtrHAL : outil d’extraction des publications HAL d’une unité, d'une équipe de recherche ou d'un auteur</title>
  <meta name="Description" content="ExtrHAL : outil d’extraction des publications HAL d’une unité, d'une équipe de recherche ou d'un auteur">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="icon" type="type/ico" href="HAL_favicon.ico">
  <link rel="stylesheet" href="./ExtractionHAL.css">
</head>
<body style="font-family:calibri,verdana">
<?php
function utf8_fopen_read($fileName) {
    $fc = file_get_contents($fileName);
    $handle=fopen("php://memory", "rw");
    fwrite($handle, $fc);
    fseek($handle, 0);
    return $handle;
}

//fichier CSV ou txt
if ($_FILES['import']['name'] != "") {
  $ext = strtolower(strrchr($_FILES['import']['name'], '.'));
  if ($ext != ".csv" && $ext != ".txt"){
    header("location:"."ExtractionHAL-IF.php?erreur=extfic"); exit;
  }else{
    if ($_FILES['import']['size'] == "0") {
      header("location:"."ExtractionHAL-IF.php?erreur=nulfic"); exit;
    }else{
      $temp = $_FILES['import']['tmp_name'];
    }
  }
}

$Fnm = "./pvt/JCR.php";
$inF = fopen($Fnm,"w");
fseek($inF, 0);
$chaine = "";
$chaine .= '<?php'.chr(13);
$chaine .= '$JCR_LISTE = array('.chr(13);
fwrite($inF,$chaine);
$handle = utf8_fopen_read($temp);
if ($handle) {
  $ligne = 0;
  $total = count(file($temp));
  while($tab = fgetcsv($handle, 0, ';')) {
    if ($ligne != 0) {//Exclure les noms des colonnes
      $ind = $ligne - 1;
      $chaine = $ind.' => array("Rank"=>"'.$tab[0].'", ';
      $chaine .= '"Full Journal Title"=>"'.$tab[1].'", ';
      $chaine .= '"Total Cites"=>"'.$tab[2].'", ';
      $chaine .= '"Journal Impact Factor"=>"'.$tab[3].'", ';
      $chaine .= '"Eigenfactor Score"=>"'.$tab[4].'")';
      if ($ligne != $total-1) {$chaine .= ',';}
      $chaine .= chr(13);
      fwrite($inF,$chaine);
    }
    $ligne++;
  }
  $chaine = ');'.chr(13);
  $chaine .= '?>';
  fwrite($inF,$chaine);
  fclose($inF);
  fclose($handle);
}
echo '<br><b>Le fichier nécessaire à l\'affichage des IF a été créé avec succès.<br><br>'
?>
</body>
</html>