<?php
ini_set('auto_detect_line_endings',TRUE);
function mb_ucwords($str) {
  $str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
  return ($str);
}
//fichier CSV ou txt
$fic = '';
if ($_FILES['importfic']['name'] != "") {
  $ext = strtolower(strrchr($_FILES['importfic']['name'], '.'));
  if ($ext != ".csv" && $ext != ".txt"){
    header("location:"."ExtractionHAL-liste-auteurs.php?erreur=extfic"); exit;
  }else{
      $temp = $_FILES['importfic']['tmp_name'];
      $fic = 'ok';
  }
}
if ($fic != '') {
  $handle = fopen($temp, 'r');//Ouverture du fichier
  if ($handle)  {//Si on a réussi à ouvrir le fichier
    $ligne = 1;
    $total = count(file($temp));
    //export liste php et CSV
    $Fnm = "./pvt/ExtractionHAL-auteurs.php"; 
    $Fnm1 = "./pvt/ExtractionHAL-auteurs.csv";
    $inF = fopen($Fnm,"w"); 
    $inF1 = fopen($Fnm1,"w");
    fseek($inF, 0);
    fseek($inF1, 0);
    $chaine = "\xEF\xBB\xBF";
    $chaine1 = "\xEF\xBB\xBF";
    $chaine1 .= "Nom;Prénom;Secteur;Titre;Unité;UMR;Grade;Numeq;Eqrec;Collection HAL;Collection équipe HAL;Arrivée;Départ".chr(13);
    $chaine .= '<?php'.chr(13);
    $chaine .= '$AUTEURS_LISTE = array('.chr(13);
    fwrite($inF,$chaine);
    fwrite($inF1,$chaine1);
    while($tab = fgetcsv($handle, 0, ';')) {
      if ($ligne != 1) {//On exclut la première ligne > noms des colonnes
        $i = $ligne - 1;
        $chaine = $i.' => array("nom"=>"'.mb_ucwords($tab[0]).'", ';
        $chaine .= '"prenom"=>"'.mb_ucwords($tab[1]).'", ';
        $chaine .= '"secteur"=>"'.$tab[2].'", ';
        $chaine .= '"titre"=>"'.$tab[3].'", ';
        $chaine .= '"unite"=>"'.$tab[4].'", ';
        $chaine .= '"umr"=>"'.$tab[5].'", ';
        $chaine .= '"grade"=>"'.$tab[6].'", ';
        $chaine .= '"numeq"=>"'.$tab[7].'", ';
        $chaine .= '"eqrec"=>"'.$tab[8].'", ';
        $chaine .= '"collhal"=>"'.$tab[9].'", ';
        $chaine .= '"colleqhal"=>"'.$tab[10].'", ';
        $chaine .= '"arriv"=>"'.$tab[11].'", ';
        $chaine .= '"depar"=>"'.$tab[12].'")';
        //export csv
        $chaine1 = mb_ucwords($tab[0]).';';
        $chaine1 .= mb_ucwords($tab[1]).';';
        $chaine1 .= $tab[2].';';
        $chaine1 .= $tab[3].';';
        $chaine1 .= $tab[4].';';
        $chaine1 .= $tab[5].';';
        $chaine1 .= $tab[6].';';
        $chaine1 .= $tab[7].';';
        $chaine1 .= $tab[8].';';
        $chaine1 .= $tab[9].';';
        $chaine1 .= $tab[10].';';
        $chaine1 .= $tab[11].';';
        $chaine1 .= $tab[12];
        if ($i != $total-1) {$chaine .= ',';}
        $chaine .= chr(13);
        $chaine1 .= chr(13);
        fwrite($inF,$chaine);
        fwrite($inF1,$chaine1);
      }
    $ligne ++;
    }
  }
  $chaine = ');'.chr(13);
  $chaine .= '?>';
  fwrite($inF,$chaine);
  fclose($inF);
  fclose($inF1);
  fclose($handle);//fermeture du fichier
}
header("location:"."ExtractionHAL-liste-auteurs.php"); exit;
?>