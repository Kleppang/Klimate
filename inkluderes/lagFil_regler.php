<?php
if(!file_exists("./generert/regler.html") && isset($_SESSION['idbruker'])) {
    $hentRegler = "select regeltekst from Regel";
    $stmtRegler = $db->prepare($hentRegler);
    $stmtRegler->execute();
    $tellingRegler = $stmtRegler->rowcount();
    $regler = $stmtRegler->fetchAll(PDO::FETCH_ASSOC);

    $handle = fopen("./generert/regler.html",'w');

    // Listedata med regler og innholdsboksen 
    fwrite($handle,"<article id='regler_article'>" .
                    "\n\t<p>Som bruker hos Klimate må du følge reglene spesifisert under.</p>" .
                    "\n\t<ol id='reglerdata'>");

    foreach($regler as $rad) {           
        fwrite($handle,"\n\t\t<li>" . $rad['regeltekst'] . "</li>");
    }   

    // Avslutter article
    fwrite($handle,"\n\t</ol>\n</article>");

    // Lukker koblingen til filen
    fclose($handle);
}

/* Denne siden er utviklet av Aron Snekkestad, siste gang endret 02.06.2020 */
/* Denne siden er kontrollert av Robin Kleppang, siste gang 02.06.2020 */
?>