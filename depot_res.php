<?php
header('Content-type: text/html; charset=UTF-8');
mb_internal_encoding("UTF-8");

function mb_ucwords($str) {
  $str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
  return ($str);
}

function prenomCompInit($prenom) {
  $prenom = str_replace("  ", " ",$prenom);
  if (strpos(trim($prenom),"-") !== false) {//Le prénom comporte un tiret
    $postiret = strpos(trim($prenom),"-");
    $prenomg = trim(mb_substr($prenom,0,($postiret-1),'UTF-8'));
    $prenomd = trim(mb_substr($prenom,($postiret+1),strlen($prenom),'UTF-8'));
    $autg = mb_substr($prenomg,0,1,'UTF-8');
    $autd = mb_substr($prenomd,0,1,'UTF-8');
    $prenom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    if (strpos(trim($prenom)," ") !== false) {//plusieurs prénoms
      $posespace = strpos(trim($prenom)," ");
      $tabprenom = explode(" ", trim($prenom));
      $p = 0;
      $prenom = "";
      while (isset($tabprenom[$p])) {
        if ($p == 0) {
          $prenom .= mb_ucwords(mb_substr($tabprenom[$p], 0, 1, 'UTF-8'));
        }else{
          $prenom .= " ".mb_ucwords(mb_substr($tabprenom[$p], 0, 1, 'UTF-8'));
        }
        $p++;
      }
    }else{
      $prenom = mb_ucwords(mb_substr($prenom, 0, 1, 'UTF-8'));
    }
  }
  return $prenom;
}

function prenomCompEntier($prenom) {
  $prenom = trim($prenom);
  if (strpos($prenom,"-") !== false) {//Le prénom comporte un tiret
    $postiret = strpos($prenom,"-");
    $autg = substr($prenom,0,$postiret);
    $autd = substr($prenom,($postiret+1),strlen($prenom));
    $prenom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    if (strpos(trim($prenom)," ") !== false) {//plusieurs prénoms
      $posespace = strpos(trim($prenom)," ");
      $tabprenom = explode(" ", trim($prenom));
      $p = 0;
      $prenom = "";
      while (isset($tabprenom[$p])) {
        if ($p == 0) {
          $prenom .= mb_ucwords($tabprenom[$p]);
        }else{
          $prenom .= " ".mb_ucwords($tabprenom[$p]);
        }
        $p++;
      }
    }else{
      $prenom = mb_ucwords($prenom);
    }
  }
  return $prenom;
}

function nomCompEntier($nom) {
  $nom = trim(mb_strtolower($nom,'UTF-8'));
  if (strpos($nom,"-") !== false) {//Le nom comporte un tiret
    $postiret = strpos($nom,"-");
    $autg = substr($nom,0,$postiret);
    $autd = substr($nom,($postiret+1),strlen($nom));
    $nom = mb_ucwords($autg)."-".mb_ucwords($autd);
  }else{
    if (strpos(trim($nom)," ") !== false) {//plusieurs noms
      $posespace = strpos(trim($nom)," ");
      $tabnom = explode(" ", trim($nom));
      $p = 0;
      $nom = "";
      while (isset($tabnom[$p])) {
        if ($p == 0) {
          $nom .= mb_ucwords($tabnom[$p]);
        }else{
          $nom .= " ".mb_ucwords($tabnom[$p]);
        }
        $p++;
      }
    }else{
      $nom = mb_ucwords($nom);
    }
  }
  return $nom;
}

if ($_FILES['depot']['name'] != "") {
  $extension = strrchr($_FILES['depot']['name'], '.');
  if ($extension != ".csv") {
    //Mauvaise extension
  }else{
    if ($_FILES['depot']['size'] > "10000000") {
      //Fichier trop gros
    }else{
      $csv = $_FILES['depot']['tmp_name'];
    }
  }
}
move_uploaded_file($csv, ("./depot_troli.csv"));
$row = 0;
$nom = array();
$prenom = array();
if (($handle = fopen("./depot_troli.csv", "r")) !== FALSE)
{
  if ($handle) {
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE)
    {
      $imax = count($data);
      //echo "<p> $imax champs à la ligne $row: <br /></p>\n";
      $nom[$row] = $data[0];
      $prenom[$row] = $data[1];
      //for ($i = 0; $i <= $imax ; $i++)
      //{
        //echo $data[$i]." ";
      //}
      $row++;
    }
  }else{
    die("<font color='red'><big><big>Votre fichier source est incorrect.</big></big></font>");
  }
  fclose($handle);
}
//var_dump($nom);

$i = 1;
$text = "";
while($nom[$i])
{
  $text .= 'authFullName_t:"'.trim(str_replace("?", " ", prenomCompEntier($prenom[$i]))).' '.trim(str_replace("?", " ", nomCompEntier($nom[$i]))).'" OR ';
  $text .= 'authFullName_t:"'.trim(str_replace("?", " ", prenomCompInit($prenom[$i]))).' '.trim(str_replace("?", " ", nomCompEntier($nom[$i]))).'" OR ';
  $i++;
}
$textAff1 = substr($text, 0, strlen($text)-4);
$textAff2 = "";
if ($_POST['dir_t'] == "oui")
{
  $textAff2 = " OR ".str_replace("authFullName_t", "director_t", $textAff1);
}

echo "<font style='font-family: corbel'>".$textAff1.$textAff2."</font>";
?>
