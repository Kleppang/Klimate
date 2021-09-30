<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");

// tabindex som skal brukes til å bestemme startpunkt i søkeresultat, denne endres hvis vi legger til flere elementer i navbar eller lignende
$tabindex = 7;


?>
<!DOCTYPE html>
<html lang="no">

    <head>
        <!-- Setter riktig charset -->
        <meta charset="UTF-8">
        <!-- Legger til viewport -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Setter tittelen på prosjektet -->
        <title>
            <?php if(isset($_GET['brukernavn']) || isset($_GET['epost']) || isset($_GET['interesse'])) { ?>
                Søk på bruker
            <?php } else if(isset($_GET['artTittel']) || isset($_GET['artForfatter'])) { ?>
                Søk på artikkel
            <?php } else if(isset($_GET['arrTittel']) || isset($_GET['arrDato']) || isset($_GET['fylke'])) { ?>
                Søk på arrangement
            <?php } else { ?>
                Avansert søk
            <?php } ?>
        </title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="sok_body" onload="sokRullegardin(), hentSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp'), sokTabbing()" onresize="hentSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp')">
        <?php include("inkluderes/navmeny.php") ?>

        <!-- Start på PHP IF-ELSE som utgjør siden -->
        <?php if(isset($_GET['brukernavn']) || isset($_GET['epost']) || isset($_GET['interesse'])) {

            /* ----------------------*/
            /* --------------------- */
            /* Del for søk på bruker */
            /* --------------------- */
            /* --------------------- */

            // Spørringen som endrer seg utifra brukers valg
            $sokPaaBr = "";

            // Visuelt viser hva bruker faktisk søkte på, vises på selve siden
            $infoOmSok = "";

            if (($_GET['brukernavn'] != "") && ($_GET['epost'] != "") && ($_GET['interesse'] == "")) {

                /* -----------------------------------*/
                /* Del for søk på brukernavn og epost */
                /* ---------------------------------- */
                
                $sokPaaBr = "select idbruker, brukernavn from bruker, preferanse where brukernavn LIKE '%" . $_GET['brukernavn'] . "%' and epost = '" . $_GET['epost'] . "'
                                and visepost = '1' and bruker.idbruker = preferanse.bruker and 
                                    idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) and
                                        brukertype != 4";
                $infoOmSok = "Du har søkt etter: " . $_GET['brukernavn'] . ", " . $_GET['epost'];


            } else if (($_GET['brukernavn'] != "") && ($_GET['epost'] == "") && ($_GET['interesse'] == "")) {

                /* ------------------------------*/
                /* Del for søk på kun brukernavn */
                /* ----------------------------- */
            
                $sokPaaBr = "select idbruker, brukernavn from bruker where brukernavn LIKE '%" . $_GET['brukernavn'] . "%' and 
                    idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) and
                        brukertype != 4 order by brukernavn ASC";
                $infoOmSok = "Du har søkt etter: " . $_GET['brukernavn'];


            } else if (($_GET['brukernavn'] == "") && ($_GET['epost'] != "") && ($_GET['interesse'] == "")) {

                /* -------------------------*/
                /* Del for søk på kun epost */
                /* ------------------------ */

                $sokPaaBr = "select idbruker, brukernavn from bruker, preferanse where epost = '" . $_GET['epost'] . "' 
                                and visepost = '1' and bruker.idbruker = preferanse.bruker and 
                                    idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) and
                                        brukertype != 4 order by brukernavn ASC";
                $infoOmSok = "Du har søkt etter: " . $_GET['epost'];
                    
                
            } else if (($_GET['brukernavn'] != "") && ($_GET['epost'] != "") && ($_GET['interesse'] != "")) {

                /* --------------------------------------------- */
                /* Del for søk på brukernavn, epost og interesse */
                /* --------------------------------------------- */

                $sokPaaBr = "select idbruker, brukernavn, interessenavn from bruker, interesse, brukerinteresse, preferanse where brukernavn LIKE '%" . $_GET['brukernavn'] . "%' and epost = '" . $_GET['epost'] . "' and interessenavn = '" . $_GET['interesse'] . "' and bruker.idbruker = brukerinteresse.bruker and brukerinteresse.interesse = interesse.idinteresse 
                                and visepost = '1' and visinteresser = '1' and bruker.idbruker = preferanse.bruker and 
                                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) and
                                        brukertype != 4 order by brukernavn ASC";
                $infoOmSok = "Du har søkt etter: " . $_GET['brukernavn'] . ", " . $_GET['epost'] . ", " . $_GET['interesse'];
                    
                
            } else if (($_GET['brukernavn'] != "") && ($_GET['epost'] == "") && ($_GET['interesse'] != "")) {

                /* ------------------------------------------ */
                /* Del for søk på kun brukernavn og interesse */
                /* ------------------------------------------ */

                $sokPaaBr = "select idbruker, brukernavn, interessenavn from bruker, interesse, brukerinteresse, preferanse where brukernavn LIKE '%" . $_GET['brukernavn'] . "%' and interessenavn = '" . $_GET['interesse'] . "' and bruker.idbruker = brukerinteresse.bruker and brukerinteresse.interesse = interesse.idinteresse 
                                and visinteresser = '1' and bruker.idbruker = preferanse.bruker and 
                                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) and
                                        brukertype != 4 order by brukernavn ASC";
                $infoOmSok = "Du har søkt etter: " . $_GET['brukernavn'] . ", " . $_GET['interesse']; 

                
            } else if (($_GET['brukernavn'] == "") && ($_GET['epost'] != "") && ($_GET['interesse'] != "")) {

                /* ------------------------------------- */
                /* Del for søk på kun epost og interesse */
                /* ------------------------------------- */

                $sokPaaBr = "select idbruker, brukernavn, interessenavn from bruker, interesse, brukerinteresse, preferanse where epost = '" . $_GET['epost'] . "' and interessenavn = '" . $_GET['interesse'] . "' and bruker.idbruker = brukerinteresse.bruker and brukerinteresse.interesse = interesse.idinteresse 
                                and visepost = '1' and visinteresser = '1' and bruker.idbruker = preferanse.bruker and 
                                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) and
                                        brukertype != 4 order by brukernavn ASC";
                $infoOmSok = "Du har søkt etter: " . $_GET['epost'] . ", " . $_GET['interesse']; 

                
            } else if (($_GET['brukernavn'] == "") && ($_GET['epost'] == "") && ($_GET['interesse'] != "")) {

                /* ---------------------------- */
                /* Del for søk på kun interesse */
                /* ---------------------------- */

                $sokPaaBr = "select idbruker, brukernavn, interessenavn from bruker, interesse, brukerinteresse, preferanse where interessenavn = '" . $_GET['interesse'] . "' and bruker.idbruker = brukerinteresse.bruker and brukerinteresse.interesse = interesse.idinteresse
                                and visinteresser = '1' and bruker.idbruker = preferanse.bruker and 
                                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) and
                                        brukertype != 4 order by brukernavn ASC";
                $infoOmSok = "Du har søkt etter: " . $_GET['interesse']; 

                
            } 



            if($sokPaaBr != "") {

                $stmtBr = $db->prepare($sokPaaBr);
                $stmtBr->execute();
                $resBr = $stmtBr->fetchAll(PDO::FETCH_ASSOC); 
                
                // Variabel som brukes til å fortelle når vi kan avslutte side_sok
                $avsluttTag = 0;
                $antallSider = 0;

                $resAntall = $stmtBr->rowCount(); ?>
                
                <header class="sok_header" onclick="lukkHamburgerMeny()">
                    <?php if ($resAntall > 1) { ?>
                        <h2><?php echo($resAntall)?> Resultater</h2>
                    <?php } else if ($resAntall == 1) { ?>
                        <h2><?php echo($resAntall)?> Resultat</h2>
                    <?php } else { ?>
                        <h2>Ingen resultater</h2>
                    <?php } ?>
                    <p><?php echo($infoOmSok) ?></p>
                </header>

                <main id="sok_main" onclick="lukkHamburgerMeny()"> 


                <?php if ($resAntall > 0 ) { ?>
                    <?php for ($j = 0; $j < count($resBr); $j++) {
                        // Hvis rest av $j delt på 8 er 0, start section (Ny side)
                        if ($j % 8 == 0) { ?>
                            <section class="side_sok">
                        <?php $antallSider++; } $avsluttTag++; ?>
                            <section class="brukerRes_sok" onClick="location.href='profil.php?bruker=<?php echo($resBr[$j]['idbruker']) ?>'" tabindex = <?php echo($tabindex); $tabindex++; ?>>
                                <figure class="infoBoksBr_sok">
                                    <?php // Henter bilde til bruker
                                    $hentBrBilde = "select hvor from bilder, brukerbilde where brukerbilde.bruker = " . $resBr[$j]['idbruker'] . " and brukerbilde.bilde = bilder.idbilder";
                                    $stmtBrBilde = $db->prepare($hentBrBilde);
                                    $stmtBrBilde->execute();
                                    $resBilde = $stmtBrBilde->fetch(PDO::FETCH_ASSOC);

                                    if (!$resBilde) { ?>
                                        <!-- Standard profilbilde om bruker ikke har lastet opp noe enda -->
                                        <img class="BildeBoksBr_sok" src="bilder/profil.png" alt="Profilbilde for <?php echo($resBr[$j]['brukernavn'])?>">
                                    <?php } else {
                                        // Tester på om filen faktisk finnes
                                        $testPaa = $resBilde['hvor'];
                                        if(file_exists("$lagringsplass/$testPaa")) { 
                                            // Profilbilde som resultat av spørring
                                            if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {  ?> 
                                                <!-- Hvis vi finner et miniatyrbilde bruker vi det -->
                                                <img class="BildeBoksBr_sok" src="bilder/opplastet/thumb_<?php echo($resBilde['hvor'])?>" alt="Profilbilde for <?php echo($resBr[$j]['brukernavn'])?>">
                                            <?php } else { ?>
                                                <img class="BildeBoksBr_sok" src="bilder/opplastet/<?php echo($resBilde['hvor'])?>" alt="Profilbilde for <?php echo($resBr[$j]['brukernavn'])?>">
                                            <?php } ?>
                                        <?php } else { ?>
                                            <img class="BildeBoksBr_sok" src="bilder/profil.png" alt="Profilbilde for <?php echo($resBr[$j]['brukernavn'])?>">
                                        <?php }
                                    } ?>
                                    <p class="infoResBr_sok"><?php echo($resBr[$j]['brukernavn'])?></p>
                                </figure>
                            </section>
                            <?php 
                            // Hvis telleren har nådd 8
                            if (($avsluttTag == 8) || $j == (count($resBr) - 1)) { ?>
                                </section>     
                            <?php 
                                // Sett telleren til 0, mulighet for mer enn 2 sider
                                $avsluttTag = 0;
                        }
                    }
                } ?>
                <section id="sok_bunnSection">
                    <?php if ($antallSider > 1) {?>
                        <p id="sok_antSider">Antall sider: <?php echo($antallSider) ?></p>
                    <?php } ?>
                    <button type="button" id="sok_tilbKnapp" onclick="visForrigeSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp')" tabindex = 100>Forrige</button>
                    <button type="button" id="sok_nesteKnapp" onclick="visNesteSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp')" tabindex = 101>Neste</button>
                    <button onclick="location.href='<?php echo($_SERVER['HTTP_REFERER']);?>'" class="lenke_knapp" tabindex = 102>Tilbake</button>
                    <button onclick="location.href='sok.php'" class="lenke_knapp" tabindex = 103>Til søk</button>
                </section>
            <?php } else {
                
                /* ------------------------------------------- */
                /* Del for om bruker har oppgitt tomme verdier */
                /* ------------------------------------------- */
                ?>

                <header class="sok_header" onclick="lukkHamburgerMeny()">
                    <h2>Ingen resultater</h2>
                </header>

                <main id="sok_main" onclick="lukkHamburgerMeny()"> 

                <section id="sok_bunnSection">
                    <button onclick="location.href='<?php echo($_SERVER['HTTP_REFERER']);?>'" class="lenke_knapp">Tilbake</button>
                    <button onclick="location.href='sok.php'" class="lenke_knapp">Til søk</button>
                </section>
            <?php } ?>
                
                

        <?php } else if(isset($_GET['artTittel']) || isset($_GET['artForfatter'])) {

            /* ------------------------*/
            /* ----------------------- */
            /* Del for søk på artikkel */
            /* ----------------------- */
            /* ----------------------- */

            // Spørringen som endrer seg utifra brukers valg
            $sokPaaArt = "";
            
            // Visuelt viser hva bruker faktisk søkte på, vises på selve siden
            $infoOmSok = "";

            // Enkel test hvis bruker kun ønsker å søke på tittel (Fra navmeny)
            $sokPaaKunTtl = true;
            if (isset($_GET['artForfatter'])) {
                $sokPaaKunTtl = false;
            }

            if (($_GET['artTittel'] != "") && ($sokPaaKunTtl == true)) {

                /* --------------------------*/
                /* Del for søk på kun tittel */
                /* ------------------------- */

                $tittel = filter_var($_GET['artTittel'], FILTER_SANITIZE_STRING);

                $sokPaaArt = "select idartikkel, bruker, artnavn, artingress, brukernavn, fnavn, enavn, brukertype from artikkel, bruker where artnavn LIKE '%" . $tittel . "%' and bruker = idbruker and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $tittel;


            } else if ((($_GET['artTittel'] != "") && $_GET['artForfatter'] != "") && ($sokPaaKunTtl == false)) {

                /* --------------------------------------------------*/
                /* Del for søk på kombinasjon av tittel og forfatter */
                /* ------------------------------------------------- */

                $sokPaaArt = "select idartikkel, bruker, artnavn, artingress, brukernavn, fnavn, enavn, brukertype from artikkel, bruker where artnavn LIKE '%" . $_GET['artTittel'] . "%' and (brukernavn LIKE '%" . $_GET['artForfatter'] . "%' or fnavn LIKE '%" . $_GET['artForfatter'] . "%' or enavn LIKE '%" . $_GET['artForfatter'] . "%') and bruker = idbruker and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['artTittel'] . ", " . $_GET['artForfatter'];


            } else if ((($_GET['artTittel'] != "") && $_GET['artForfatter'] == "") && ($sokPaaKunTtl == false)) {

                /* ----------------------*/
                /* Del for søk på tittel */
                /* --------------------- */

                $sokPaaArt = "select idartikkel, bruker, artnavn, artingress, brukernavn, fnavn, enavn, brukertype from artikkel, bruker where artnavn LIKE '%" . $_GET['artTittel'] . "%' and bruker = idbruker and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['artTittel'];


            } else if ((($_GET['artTittel'] == "") && isset($_GET['artForfatter']) && $_GET['artForfatter'] != "") && ($sokPaaKunTtl == false)) {

                /* -------------------------*/
                /* Del for søk på forfatter */
                /* ------------------------ */

                $sokPaaArt = "select idartikkel, bruker, artnavn, artingress, brukernavn, fnavn, enavn, brukertype from artikkel, bruker where (brukernavn LIKE '%" . $_GET['artForfatter'] . "%' or fnavn LIKE '%" . $_GET['artForfatter'] . "%' or enavn LIKE '%" . $_GET['artForfatter'] . "%') and bruker = idbruker and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['artForfatter'];

                
            }

            
            if ($sokPaaArt != "") {
                $stmtArt = $db->prepare($sokPaaArt);
                $stmtArt->execute();
                $resArt = $stmtArt->fetchAll(PDO::FETCH_ASSOC); 
                
                // Variabel som brukes til å fortelle når vi kan avslutte side_sok
                $avsluttTag = 0;
                $antallSider = 0;

                $resAntall = $stmtArt->rowCount(); 
            ?>
                
                <header class="sok_header" onclick="lukkHamburgerMeny()">
                    <?php if ($resAntall > 1) { ?>
                        <h2><?php echo($resAntall)?> Resultater</h2>
                    <?php } else if ($resAntall == 1) { ?>
                        <h2><?php echo($resAntall)?> Resultat</h2>
                    <?php } else { ?>
                        <h2>Ingen resultater</h2>
                    <?php } ?>
                    <p><?php echo($infoOmSok) ?></p>
                </header>

                <main id="sok_main" onclick="lukkHamburgerMeny()"> 

                <?php if ($resAntall > 0 ) { ?>
                    <?php for ($j = 0; $j < count($resArt); $j++) {
                        // Hvis rest av $j delt på 8 er 0, start section (Ny side)
                        if ($j % 8 == 0) { ?>
                            <section class="side_sok">
                        <?php $antallSider++; } $avsluttTag++; ?>
                        <section class="artRes_sok" onClick="location.href='artikkel.php?artikkel=<?php echo($resArt[$j]['idartikkel']) ?>'" tabindex = <?php echo($tabindex); $tabindex++; ?>>
                            <figure class="infoBoksArt_sok">
                                    <?php // Henter bilde til artikkelen
                                    $hentArtBildeQ = "select hvor from bilder, artikkelbilde where artikkelbilde.idartikkel = :idartikkel and artikkelbilde.idbilde = bilder.idbilder";
                                    $hentArtBildeSTMT = $db->prepare($hentArtBildeQ);

                                    $hentArtBildeSTMT->bindParam(':idartikkel', $resArt[$j]['idartikkel']);
                                    
                                    $hentArtBildeSTMT->execute();
                                    $resBilde = $hentArtBildeSTMT->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($resBilde) {
                                        // Tester på om filen faktisk finnes
                                        $testPaa = $resBilde['hvor'];
                                        if(file_exists("$lagringsplass/$testPaa")) { ?>
                                            <section class="bildeBoksArt_sok">
                                                <?php
                                                //Arrangementbilde som resultat av spørring
                                                if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {  ?>
                                                    <!-- Hvis vi finner et miniatyrbilde bruker vi det -->
                                                    <img class="artikkel_BildeBoks" src="bilder/opplastet/thumb_<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($resArt[$j]['artnavn'])?>">
                                                <?php } else { ?>
                                                    <img class="artikkel_BildeBoks" src="bilder/opplastet/<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($resArt[$j]['artnavn'])?>">
                                                <?php } ?>
                                            </section>
                                        <?php } ?>
                                    <?php } ?>

                                <h2 class="infoResArt_sok"><?php echo($resArt[$j]['artnavn'])?></h2>
                                <p class="infoResArt_sok"><?php echo($resArt[$j]['artingress'])?></p>
                                <?php 
                                
                                if($resArt[$j]['brukertype'] != 4) {
                                    // Henter personvern
                                    $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $resArt[$j]['bruker'];
                                    $personvernSTMT = $db->prepare($personvernQ);
                                    $personvernSTMT->execute();
                                    $personvernArtikkel = $personvernSTMT->fetch(PDO::FETCH_ASSOC); 
    
                                    $kanViseFornavn = false;
                                    $kanViseEtternavn = false;
    
                                    if(isset($personvernArtikkel['visfnavn']) && $personvernArtikkel['visfnavn'] == "1") {
                                        $kanViseFornavn = true;
                                    }
    
                                    if(isset($personvernArtikkel['visenavn']) && $personvernArtikkel['visenavn'] == "1") {
                                        $kanViseEtternavn = true;
                                    }
                                    
                                    if($kanViseFornavn == true && $kanViseEtternavn == false) {
                                        if(preg_match("/\S/", $resArt[$j]['fnavn']) == 1) {
                                            $navn = $resArt[$j]['fnavn'];  
                                        } else {
                                            $navn = $resArt[$j]['brukernavn'];
                                        }
                                    } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                                        if(preg_match("/\S/", $resArt[$j]['enavn']) == 1) {
                                            $navn = $resArt[$j]['enavn'];  
                                        } else {
                                            $navn = $resArt[$j]['brukernavn'];
                                        }
                                    } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                                        if(preg_match("/\S/", $resArt[$j]['enavn']) == 1) {
                                            $navn = $resArt[$j]['fnavn'] . " " . $resArt[$j]['enavn'];  
                                        } else {
                                            $navn = $resArt[$j]['brukernavn'];
                                        }
                                    } else {
                                        $navn = $resArt[$j]['brukernavn'];
                                    } ?>
    
                                    <p class="infoResArt_sok">Skrevet av <?php echo($navn)?></p>
                                <?php } else { ?>
                                    <p class="infoResArt_sok" style="font-style: italic;">Skrevet av en avregistrert bruker</p>
                                <?php } ?>


                            </figure>
                        </section>
                        <?php 
                        // Hvis telleren har nådd 8
                        if (($avsluttTag == 8) || $j == (count($resArt) - 1)) { ?>
                            </section>     
                        <?php 
                            // Sett telleren til 0, mulighet for mer enn 2 sider
                            $avsluttTag = 0;
                        }
                    }
                } ?>

                <section id="sok_bunnSection">
                    <?php if ($antallSider > 1) {?>
                        <p id="sok_antSider">Antall sider: <?php echo($antallSider) ?></p>
                    <?php } ?>
                    <button type="button" id="sok_tilbKnapp" onclick="visForrigeSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp')" tabindex = 100>Forrige</button>
                    <button type="button" id="sok_nesteKnapp" onclick="visNesteSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp')" tabindex = 101>Neste</button>
                    <button onclick="location.href='<?php echo($_SERVER['HTTP_REFERER']);?>'" class="lenke_knapp" tabindex = 102>Tilbake</button>
                    <button onclick="location.href='sok.php'" class="lenke_knapp" tabindex = 103>Til søk</button>
                </section>
            <?php 
            } else {
                
                /* ------------------------------------------- */
                /* Del for om bruker har oppgitt tomme verdier */
                /* ------------------------------------------- */
                ?>

                <header class="sok_header" onclick="lukkHamburgerMeny()">
                    <h2>Ingen resultater</h2>
                </header>

                <main id="sok_main" onclick="lukkHamburgerMeny()"> 

                <section id="sok_bunnSection">
                    <button onclick="location.href='<?php echo($_SERVER['HTTP_REFERER']);?>'" class="lenke_knapp">Tilbake</button>
                    <button onclick="location.href='sok.php'" class="lenke_knapp">Til søk</button>
                </section>
            <?php }

        } else if(isset($_GET['arrTittel']) || isset($_GET['arrDato']) || isset($_GET['fylke'])) {

            /* ---------------------------*/
            /* -------------------------- */
            /* Del for søk på arrangement */
            /* -------------------------- */
            /* -------------------------- */

            // Spørringen som endrer seg utifra brukers valg
            $sokPaaArr ="";
            
            // Visuelt viser hva bruker faktisk søkte på, vises på selve siden
            $infoOmSok = "";

            if (($_GET['arrTittel'] != "") && ($_GET['arrDato'] != "") && ($_GET['fylke'] != "")) {

                /* ----------------------------------------------------*/
                /* Del for søk på kombinasjon av tittel, dato og fylke */
                /* --------------------------------------------------- */

                $sokPaaArr = "select idevent, event.idbruker, eventnavn, tidspunkt, veibeskrivelse, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke where eventnavn LIKE '%" . $_GET['arrTittel'] . "%' and tidspunkt between NOW() and '" . $_GET['arrDato'] . "' and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and fylke.fylkenavn = '" . $_GET['fylke'] . "' and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['arrTittel'] . ", " . $_GET['arrDato'] . ", " . $_GET['fylke'];

            } else if (($_GET['arrTittel'] != "") && ($_GET['arrDato'] != "") && ($_GET['fylke'] == "")) {
                
                /* ---------------------------------------------*/
                /* Del for søk på kombinasjon av tittel og dato */
                /* -------------------------------------------- */

                $sokPaaArr = "select idevent, event.idbruker, eventnavn, tidspunkt, veibeskrivelse, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke where eventnavn LIKE '%" . $_GET['arrTittel'] . "%' and tidspunkt between NOW() and '" . $_GET['arrDato'] . "' and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and 
                 bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['arrTittel'] . ", " . $_GET['arrDato'];

            } else if (($_GET['arrTittel'] != "") && ($_GET['arrDato'] == "") && ($_GET['fylke'] == "")) {

                /* ----------------------*/
                /* Del for søk på tittel */
                /* --------------------- */

                $sokPaaArr = "select idevent, event.idbruker, eventnavn, tidspunkt, veibeskrivelse, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke where eventnavn LIKE '%" . $_GET['arrTittel'] . "%' and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['arrTittel'];

            } else if (($_GET['arrTittel'] == "") && ($_GET['arrDato'] != "") && ($_GET['fylke'] != "")) {

                /* -----------------------------*/
                /* Del for søk på dato og fylke */
                /* -----------------------------*/

                $sokPaaArr = "select idevent, event.idbruker, eventnavn, tidspunkt, veibeskrivelse, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke where tidspunkt between NOW() and '" . $_GET['arrDato'] . "' and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and fylke.fylkenavn = '" . $_GET['fylke'] . "' and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['arrDato'] . ", " . $_GET['fylke'];

            } else if (($_GET['arrTittel'] == "") && ($_GET['arrDato'] == "") && ($_GET['fylke'] != "")) {

                /* ---------------------*/
                /* Del for søk på fylke */
                /* ---------------------*/

                $sokPaaArr = "select idevent, event.idbruker, eventnavn, tidspunkt, veibeskrivelse, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke where event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and fylke.fylkenavn = '" . $_GET['fylke'] . "' and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['fylke'];

            } else if (($_GET['arrTittel'] == "") && ($_GET['arrDato'] != "") && ($_GET['fylke'] == "")) {

                /* --------------------*/
                /* Del for søk på dato */
                /* --------------------*/

                $sokPaaArr = "select idevent, event.idbruker, eventnavn, tidspunkt, veibeskrivelse, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke where tidspunkt between NOW() and '" . $_GET['arrDato'] . "' and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['arrDato'];

            } else if (($_GET['arrTittel'] != "") && ($_GET['arrDato'] == "") && ($_GET['fylke'] != "")) {

                /* -------------------------------*/
                /* Del for søk på tittel og fylke */
                /* -------------------------------*/

                $sokPaaArr = "select idevent, event.idbruker, eventnavn, tidspunkt, veibeskrivelse, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke where eventnavn LIKE '%" . $_GET['arrTittel'] . "%' and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and fylke.fylkenavn = '" . $_GET['fylke'] . "' and 
                    bruker.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $infoOmSok = "Du har søkt etter: " . $_GET['arrTittel'] . ", " . $_GET['fylke'];

            }

            if ($sokPaaArr != "") {

                $stmtArr = $db->prepare($sokPaaArr);
                $stmtArr->execute();
                $resArr = $stmtArr->fetchAll(PDO::FETCH_ASSOC); 
                
                // Variabel som brukes til å fortelle når vi kan avslutte side_sok
                $avsluttTag = 0;
                $antallSider = 0;

                $resAntall = $stmtArr->rowCount(); 
                ?>
                
                <header class="sok_header" onclick="lukkHamburgerMeny()">
                    <?php if ($resAntall > 1) { ?>
                        <h2><?php echo($resAntall)?> Resultater</h2>
                    <?php } else if ($resAntall == 1) { ?>
                        <h2><?php echo($resAntall)?> Resultat</h2>
                    <?php } else { ?>
                        <h2>Ingen resultater</h2>
                    <?php } ?>
                    <p><?php echo($infoOmSok) ?></p>
                </header>

                <main id="sok_main" onclick="lukkHamburgerMeny()"> 

                <?php if ($resAntall > 0 ) { ?>
                    <?php for ($j = 0; $j < count($resArr); $j++) {
                        // Hvis rest av $j delt på 8 er 0, start section (Ny side)
                        if ($j % 8 == 0) { ?>
                            <section class="side_sok">
                        <?php $antallSider++; } $avsluttTag++; ?>
                        <section class="arrRes_sok" onClick="location.href='arrangement.php?arrangement=<?php echo($resArr[$j]['idevent']) ?>'" tabindex = <?php echo($tabindex); $tabindex++; ?>>
                            <figure class="infoBoksArr_sok">

                                <?php // Henter bilde til arrangementet
                                $hentArrBilde = "select hvor from bilder, eventbilde where eventbilde.event = " . $resArr[$j]['idevent'] . " and eventbilde.bilde = bilder.idbilder";
                                $stmtArrBilde = $db->prepare($hentArrBilde);
                                $stmtArrBilde->execute();
                                $resBilde = $stmtArrBilde->fetch(PDO::FETCH_ASSOC);
                                
                                if(isset($resBilde['hvor'])) {
                                    $testPaa = $resBilde['hvor'];
                                } else {
                                    $testPaa = "";
                                }

                                // Tester på om filen faktisk finnes
                                if($testPaa != "" && file_exists("$lagringsplass/$testPaa")) {  
                                    //Arrangementbilde som resultat av spørring
                                    if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {  ?> 
                                        <img class="BildeBoksArr_sok" src="bilder/opplastet/thumb_<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($resArr[$j]['eventnavn'])?>">
                                    <?php } else { ?>
                                        <img class="BildeBoksArr_sok" src="bilder/opplastet/<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($resArr[$j]['eventnavn'])?>">
                                    <?php } ?>
                                <?php } else { ?>
                                    <!-- Standard arrangementbilde om arrangør ikke har lastet opp noe -->
                                    <img class="BildeBoksArr_sok" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                <?php } ?>

                                <h2 class="infoResArr_sok"><?php echo($resArr[$j]['eventnavn'])?></h2>
                                <p class="infoResArr_sok"><?php echo($resArr[$j]['tidspunkt'])?></p>
                                <p class="infoResArr_sok"><?php echo($resArr[$j]['veibeskrivelse'] . ", " . $resArr[$j]['fylkenavn'])?></p>
                                <?php 
                            

                                if($resArr[$j]['brukertype'] != 4) {
                                    // Henter personvern
                                    $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $resArr[$j]['idbruker'];
                                    $personvernSTMT = $db->prepare($personvernQ);
                                    $personvernSTMT->execute();
                                    $personvernArtikkel = $personvernSTMT->fetch(PDO::FETCH_ASSOC); 

                                    $kanViseFornavn = false;
                                    $kanViseEtternavn = false;

                                    if(isset($personvernArtikkel['visfnavn']) && $personvernArtikkel['visfnavn'] == "1") {
                                        $kanViseFornavn = true;
                                    }

                                    if(isset($personvernArtikkel['visenavn']) && $personvernArtikkel['visenavn'] == "1") {
                                        $kanViseEtternavn = true;
                                    }
                                    
                                    if($kanViseFornavn == true && $kanViseEtternavn == false) {
                                        if(preg_match("/\S/", $resArr[$j]['fnavn']) == 1) {
                                            $navn = $resArr[$j]['fnavn'];  
                                        } else {
                                            $navn = $resArr[$j]['brukernavn'];
                                        }
                                    } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                                        if(preg_match("/\S/", $resArr[$j]['enavn']) == 1) {
                                            $navn = $resArr[$j]['enavn'];  
                                        } else {
                                            $navn = $resArr[$j]['brukernavn'];
                                        }
                                    } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                                        if(preg_match("/\S/", $resArr[$j]['enavn']) == 1) {
                                            $navn = $resArr[$j]['fnavn'] . " " . $resArr[$j]['enavn'];  
                                        } else {
                                            $navn = $resArr[$j]['brukernavn'];
                                        }
                                    } else {
                                        $navn = $resArr[$j]['brukernavn'];
                                    } ?>
                                    
                                    <p class="infoResArr_sok">Arrangert av <?php echo($navn)?></p>
                                <?php } else { ?>
                                    <p class="infoResArr_sok" style="font-style: italic;">Arrangert av en avregistrert bruker</p>
                                <?php } ?>
                            </figure>
                        </section>
                        <?php 
                        // Hvis telleren har nådd 8
                        if (($avsluttTag == 8) || $j == (count($resArr) - 1)) { ?>
                            </section>     
                        <?php 
                            // Sett telleren til 0, mulighet for mer enn 2 sider
                            $avsluttTag = 0;
                        }
                    }
                } ?>

                <section id="sok_bunnSection">
                    <?php if ($antallSider > 1) {?>
                        <p id="sok_antSider">Antall sider: <?php echo($antallSider) ?></p>
                    <?php } ?>
                    <button type="button" id="sok_tilbKnapp" onclick="visForrigeSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp')" tabindex = 100>Forrige</button>
                    <button type="button" id="sok_nesteKnapp" onclick="visNesteSide('side_sok', 'sok_tilbKnapp', 'sok_nesteKnapp')" tabindex = 101>Neste</button>
                    <button onclick="location.href='<?php echo($_SERVER['HTTP_REFERER']);?>'" class="lenke_knapp" tabindex = 102>Tilbake</button>
                    <button onclick="location.href='sok.php'" class="lenke_knapp" tabindex = 103>Til søk</button>
                </section>
            <?php } else {
                
                /* ------------------------------------------- */
                /* Del for om bruker har oppgitt tomme verdier */
                /* ------------------------------------------- */
                ?>

                <header class="sok_header" onclick="lukkHamburgerMeny()">
                    <h2>Ingen resultater</h2>
                </header>

                <main id="sok_main" onclick="lukkHamburgerMeny()"> 

                <section id="sok_bunnSection">
                    <button onclick="location.href='<?php echo($_SERVER['HTTP_REFERER']);?>'" class="lenke_knapp">Tilbake</button>
                    <button onclick="location.href='sok.php'" class="lenke_knapp">Til søk</button>
                </section>
            <?php }
        } else { ?>
            <!-- Del for avansert søk -->
            <header class="sok_header" onclick="lukkHamburgerMeny()">
                <h2>Avansert søk</h2>
            </header>
            <main id="sok_main" onclick="lukkHamburgerMeny()"> 
                <section id="sok_seksjon"> 
                    <!-- Rullegardin for søk på bruker -->
                    <form method="GET">
                        <section class="innholdRullegardin">
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Brukernavn:</p>
                                <input type="text" class="sokBrukerFelt" tabindex = "-1" name="brukernavn" placeholder="Skriv inn brukernavn" autofocus>
                            </section>
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Epost:</p>
                                <input type="email" class="sokBrukerFelt" tabindex = "-1" name="epost" placeholder="Skriv inn epost">
                            </section>
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Interesse:</p>
                                <input name="interesse"  class="sokBrukerFelt" type="text" list="interesser" tabindex="-1">
                                <datalist id="interesser">
                                    <?php 
                                    // Henter interesser fra database
                                    $hentInt = "select interessenavn from interesse order by interessenavn ASC";
                                    $stmtInt = $db->prepare($hentInt);
                                    $stmtInt->execute();
                                    $interesse = $stmtInt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($interesse as $innhold) { ?>
                                        <option value="<?php echo($innhold['interessenavn'])?>"><?php echo($innhold['interessenavn'])?></option>
                                    <?php } ?>
                                </datalist>
                            </section>
                            <input type="submit" value="Søk på bruker"  class="sokBrukerFelt" tabindex ="-1">
                        </section>
                        <button type="button" id="brukerRullegardin" class="brukerRullegardin" tabindex ="8">Søk etter bruker</button>
                    </form>
                    <!-- Rullegardin for søk på artikkel -->
                    <form method="GET">
                        <section class="innholdRullegardin">
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Tittel:</p>
                                <input type="text" class="sokBrukerFelt" tabindex = "-1" name="artTittel" placeholder="Tittelen på artikkelen">
                            </section>
                            <!--
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Dato fra:</p>
                                <input type="date" class="sokBrukerFelt" tabIndex = "-1" name="artDato" title="Alle artikler publisert etter oppgitt dato">
                            </section>
                            -->
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Skrevet av:</p>
                                <input type="text" class="sokBrukerFelt" tabindex ="-1" name="artForfatter" placeholder="Forfatter av artikkelen">
                            </section>
                            <input type="submit" value="Søk på artikkel" class="sokBrukerFelt" tabindex ="-1">
                        </section>
                        <button type="button" id="artikkelRullegardin" class="artikkelRullegardin" tabindex ="9">Søk etter artikkel</button>
                    </form>
                    <!-- Rullegardin for søk på arrangement -->
                    <form method="GET">
                        <section class="innholdRullegardin">
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Tittel:</p>
                                <input type="text" class="sokBrukerFelt" tabindex = "-1" name="arrTittel" placeholder="Tittelen på arrangementet">
                            </section>
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Dato før:</p>
                                <input type="date" class="sokBrukerFelt" tabindex = "-1" name="arrDato" title="Alle arrangementer før oppgitt dato">
                            </section>
                            <section class="sok_inputBoks">
                                <p class="sokTittel">Fylke:</p>
                                <input name="fylke" class="sokBrukerFelt" type="text" list="fylker" tabindex="-1">
                                <datalist id="fylker">
                                    <?php 
                                    // Henter fylker fra database
                                    $hentFylke = "select fylkenavn from fylke order by fylkenavn ASC";
                                    $stmtFylke = $db->prepare($hentFylke);
                                    $stmtFylke->execute();
                                    $fylke = $stmtFylke->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($fylke as $innhold) { ?>
                                        <option value="<?php echo($innhold['fylkenavn'])?>"><?php echo($innhold['fylkenavn'])?></option>
                                <?php } ?>
                                </datalist>
                            </section>
                            <input type="submit" value="Søk på arrangement" class="sokBrukerFelt" tabindex ="-1">
                        </section>
                        <button type="button" id="arrangementRullegardin" class="arrangementRullegardin" tabindex ="10">Søk etter arrangement</button>
                    </form>
                </section>
            <?php } ?>
        </main>
        <?php include("inkluderes/footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Robin Kleppang, siste gang endret 17.04.2020 -->
<!-- Denne siden er kontrollert av Glenn Petter Pettersen, siste gang 04.06.2020 -->
</html>