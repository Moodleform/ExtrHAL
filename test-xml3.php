<?php
header('Content-type: text/html; charset=UTF-8');

$URL = "http://api-preprod.archives-ouvertes.fr/search/?wt=xml-tei&q=labStructCode_s:%22UMR6553%22&fq=producedDate_s:%222014%22&fl=title_s,label_s,uri_s,abstract_s,docType_s,doiId_s,label_bibtex,keyword_s&sort=auth_sort%20asc";

// On ouvre le fichier
$xml = simplexml_load_file($URL);
 
// On lance l'arbre
recursivite($xml);
 
/**
 
	Fonction récursive
 
*/
function recursivite($racine, $niveau = 0) {
	// Pour chaque item, on récupere le nom et l'objet SimpleXML de la balise
	foreach($racine as $nom=>$elem) {
		// On vérifie qu'il y a un noeud enfant
		if(trim($elem) == "") {
			// si oui...
			for($i=1;$i<=$niveau;$i++) { echo "  "; }  // Pour la mise en forme wink.gif
			// on affiche le nom
			echo "La balise <strong>".$nom."</strong> ";
			// on récupere les enfants 
			$enfants = $elem->children();
			// on récupere les attributs s'ils sont présents
			$str = "";
			$attributs = $elem->attributes();
			if(trim($attributs) != "") {
				$str = "(";
				foreach($attributs as $index=>$contenu) {
					$str .= "[<strong>".$index."</strong>] <em>".$contenu."</em>, ";
				}
				$str = substr($str, 0, -2).")"; // Pour la mise en forme à nouveau
			}
			echo $str." 
";
			// comme on a un enfant, on réappelle la fonction (le niveau sert juste à la mise en forme)
			recursivite($enfants, $niveau + 1);
		} else {
			// si on n'a pas d'enfant, on affiche ce qu'il y a dedans wink.gif
			for($i=1;$i<=$niveau;$i++) { echo "  "; }
			echo "La balise <strong>".$nom."</strong> contient <em>".$elem."</em><br>
";
		}
	}
} 
?>
