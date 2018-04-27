<?php
header('Content-type: text/html; charset=UTF-8');
mb_internal_encoding("UTF-8");
?>
<body style='font-family: corbel'>
<form enctype="multipart/form-data" action="depot_res.php" method="post" accept-charset="UTF-8">
<b>Fichier dépôt (csv)</b> (<a href="./template.csv">voir un exemple de template</a>) : <input name="depot" type="file" /><br/>
Ajout de la version avec "director_t" : <input type="checkbox" name="dir_t" value="oui" /><br/>
<input type="submit" value="Envoyer">
</body>