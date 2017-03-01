<STYLE type="text/css">
.keysearch
{
color: #FF0000;
}
</STYLE>
<?php
function mise_en_evidence($phrase, $string, $deb, $fin) {
  $non_letter_chars = '/[^\pL]/iu';
  $words = preg_split($non_letter_chars, $phrase);

  $search_words = array();
  foreach ($words as $word) {
    if (strlen($word) > 2 && !preg_match($non_letter_chars, $word)) {
      $search_words[] = $word;
    }
  }

  $search_words = array_unique($search_words);

  $patterns = array(
    /* à répéter pour chaque caractère accentué possible */
    '/(ae|æ)/iu' => '(ae|æ)',
    '/(oe|œ)/iu' => '(oe|œ)',
    '/[aàáâãäå]/iu' => '[aàáâãäå]',
    '/[cç]/iu' => '[cç]',
    '/[eèéêëeeeee]/iu' => '[eèéêëeeeee]',
    '/[iìíîïiiiii]/iu' => '[iìíîïiiiii]',
    '/[nñ]/iu' => '[nñ]',
    '/[oòóôõö]/iu' => '[oòóôõö]',
    '/[sš]/iu' => '[sš]',
    '/[uùúûü]/iu' => '[uùúûü]',
    '/[yýÿ]/iu' => '[yýÿ]',
    '/[zž]/iu' => '[zž]',
  );

  foreach ($search_words as $word) {
    $search = preg_quote($word);

    $search = preg_replace(array_keys($patterns), $patterns, $search);

    $string = preg_replace('/\b' . $search . '(e?s)?\b/iu', $deb.'$0'.$fin, $string);
    return $string;
  }
}
//$string = "now my problem is to find a way ( I imagine with some kind
//of regular expression ) to achieve in php a search and replace
//accent-insensitive, so that i can find the word 'cafe' in a string
//also if it is 'café', or 'CAFÉ', or 'CAFE', and vice-versa.";

$deb = "<b>";
$fin = "</b>";
$string = "ColinéttroliespHtrolipoint, Renault D., Roussel D. (2017). Cold acclimation allows Drosophila flies to maintain mitochondrial functioning under cold stress. Insect Biochemistry and Molecular Biology, 80:52-60. doi: https://doi.org/10.1016/j.ibmb.2016.11.007";
$string2 = mise_en_evidence("colinettroliesphtrolipoint", $string, $deb, $fin);
echo str_replace(array("troliesp", "trolipoint"), array(" ", ".") , $string2);
?>
