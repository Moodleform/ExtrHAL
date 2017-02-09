$resArray[$iRA]["GR"] = $extract;
$resArray[$iRA]["authors"] = $extract;
$resArray[$iRA]["annee"] = $dateprod;
$resArray[$iRA]["titre"] = cleanup_title($entry->title_s[0]);
$resArray[$iRA]["revue"] = $entry->journalTitle_s;
$resArray[$iRA]["volume"] = $entry->volume_s;
$resArray[$iRA]["issue"] = $entry->issue_s[0];
editor
  $resArray[$iRA]["editor"] = $editor;
    -et-
  $resArray[$iRA]["editor"] .= "~ ".$editor;
$resArray[$iRA]["bookTitle"] = $entry->bookTitle_s;
$resArray[$iRA]["bookCollection"] = $entry->bookCollection_s;
$resArray[$iRA]["publisher"] = $entry->publisher_s[0];
page
  $resArray[$iRA]["page"] = ":".$page;
    -ou-
  $resArray[$iRA]["page"] = ", ".$page;
    -ou-
  $resArray[$iRA]["page"] = ", pp. ".$page;
    -ou-
  $resArray[$iRA]["page"] = $page;
$resArray[$iRA]["isbn"] = $entry->isbn_s;
$resArray[$iRA]["conferenceTitle"] = $entry->conferenceTitle_s;
$resArray[$iRA]["commentaire"] = $entry->comment_s;
congressDates :
  $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;;
    -ou-
  $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i;
    -ou-
  $resArray[$iRA]["congressDates"] = "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
    -ou-
  $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i];
    -ou-
  $resArray[$iRA]["congressDates"] = "-".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
    -ou-
  $resArray[$iRA]["congressDates"] = ", ".$entry->conferenceStartDateD_i." ".$mois[$entry->conferenceStartDateM_i]." ".$entry->conferenceStartDateY_i;
    -ou-
  $resArray[$iRA]["congressDates"] = " - ".$entry->conferenceEndDateD_i." ".$mois[$entry->conferenceEndDateM_i]." ".$entry->conferenceEndDateY_i;
$resArray[$iRA]["city"] = $entry->city_s;
$resArray[$iRA]["countries"] = $countries[$entry->country_s];
source:
  $resArray[$iRA]["source"] = $entry->source_s;
    -ou-
  $resArray[$iRA]["source"] = $entry->bookTitle_s;
avec ou sans acte :
  $resArray[$iRA]["avsa"] = " <i>(sans acte)</i>";
    -ou-
  $resArray[$iRA]["avsa"] = " <i>(avec acte)</i>";
$resArray[$iRA]["reportType"] = $reportType;
$resArray[$iRA]["reportNumber"] = ", N°".$entry->number_s[0];
$resArray[$iRA]["authorityInstitution"] = $entry->authorityInstitution;
$resArray[$iRA]["thesisDirector"] = "Dir : ".$entry->director_s[0].".";
$resArray[$iRA]["authorityInstitution"] = $entry->authorityInstitution_s[0];
$resArray[$iRA]["defenseDate"] = $entry->defenseDateY_i;