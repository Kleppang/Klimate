<?php

// Henter brukertype-navn (Så man kan endre navnet til brukertype 1 og fremdeles få riktig "Innlogget som" melding)
// Dette må selvfølgelig settes innenfor en isset-test da noen sider ikke krever at man er innlogget
if(isset($_SESSION['idbruker'])) {
    $hentBrukertypenavnQ = "select brukertypenavn from brukertype where idbrukertype = " . $_SESSION['brukertype'];
    $hentBrukertypenavnSTMT = $db -> prepare($hentBrukertypenavnQ);
    $hentBrukertypenavnSTMT -> execute();
    $brukertypenavn = $hentBrukertypenavnSTMT->fetch(PDO::FETCH_ASSOC);
}

// Teller på antallet uleste meldinger, vises i navmeny og ved backend
if(isset($_SESSION['idbruker'])) {
    $ulesteMldQ = "select count(idmelding) as antall from melding 
                    where mottaker = " . $_SESSION['idbruker'] . "  and (lest is null or lest = 0)";
    $ulesteMldSTMT = $db->prepare($ulesteMldQ);
    $ulesteMldSTMT->execute();
    $antUlest = $ulesteMldSTMT->fetch(PDO::FETCH_ASSOC); 
}

?>
<!-- Begynnelse på øvre navigasjonsmeny -->
        <nav class="default_navTop"> 
            <!-- Bruker et ikon som skal åpne gardinmenyen, henviser til funksjonen hamburgerMeny i javascript.js -->
            <!-- javascript:void(0) blir her brukt så siden ikke scroller til toppen av seg selv når du trykker på hamburger-ikonet -->
            <a class="bildeKontroll" href="javascript:void(0)" onclick="hamburgerMeny()" tabindex="6">
                <img src="bilder/hamburgerIkon.svg" alt="Hamburger-menyen" class="hamburgerKnapp">
            </a>
            <?php 
            // Legger til knapper for å registrere ny bruker eller innlogging
            // Om bruker er innlogget, vis kun en 'Logg ut' knapp
            if (isset($_SESSION['idbruker'])) {

                // Vises når bruker er innlogget

                /* -------------------------------*/
                /* Del for visning av profilbilde */
                /* -------------------------------*/

                // Henter bilde fra database utifra brukerid
                $hentBilde = "select hvor from bilder, brukerbilde where brukerbilde.bruker = " . $_SESSION['idbruker'] . " and brukerbilde.bilde = bilder.idbilder";
                $stmtBilde = $db->prepare($hentBilde);
                $stmtBilde->execute();
                $bilde = $stmtBilde->fetch(PDO::FETCH_ASSOC);
                $antallBilderFunnet = $stmtBilde->rowCount();

                // rowCount() returnerer antall resultater fra database, er dette null finnes det ikke noe bilde i databasen
                if ($antallBilderFunnet != 0) {
                    // Hvis vi finner et bilde til bruker viser vi det ?>
                    <a class="bildeKontroll" href="javascript:void(0)" onClick="location.href='profil.php?bruker=<?php echo($_SESSION['idbruker']) ?>'" tabindex="5">
                        <?php
                        $testPaa = $bilde['hvor'];
                        // Tester på om filen faktisk finnes
                        if(file_exists("$lagringsplass/$testPaa")) {   
                            if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {
                                if ($_SESSION['brukertype'] == 2) {
                                    // Setter redaktør border "Grønn" ?>
                                    <img src="bilder/opplastet/thumb_<?php echo($bilde['hvor'])?>" alt="Profilbilde"  class="profil_navmeny" style="border: 2px solid green;">
                                <?php } else if ($_SESSION['brukertype'] == 1) {
                                    // Setter administrator border "Rød" ?>
                                    <img src="bilder/opplastet/thumb_<?php echo($bilde['hvor'])?>" alt="Profilbilde"  class="profil_navmeny" style="border: 2px solid red;">
                                <?php } else if ($_SESSION['brukertype'] == 3) {
                                    // Setter vanlig profil bilde ?> 
                                    <img src="bilder/opplastet/thumb_<?php echo($bilde['hvor'])?>" alt="Profilbilde"  class="profil_navmeny"> 
                                <?php }
                            } else { 
                                if ($_SESSION['brukertype'] == 2) {
                                    // Setter redaktør border "Grønn" ?>
                                    <img src="bilder/opplastet/<?php echo($bilde['hvor'])?>" alt="Profilbilde"  class="profil_navmeny" style="border: 2px solid green;">
                                <?php } else if ($_SESSION['brukertype'] == 1) {
                                    // Setter administrator border "Rød" ?>
                                    <img src="bilder/opplastet/<?php echo($bilde['hvor'])?>" alt="Profilbilde"  class="profil_navmeny" style="border: 2px solid red;">
                                <?php } else if ($_SESSION['brukertype'] == 3) {
                                    // Setter vanlig profil bilde ?> 
                                    <img src="bilder/opplastet/<?php echo($bilde['hvor'])?>" alt="Profilbilde"  class="profil_navmeny"> 
                                <?php }
                            }
                        } else { 
                            // Om filen ikke ble funnet, vis standard profilbilde
                            if ($_SESSION['brukertype'] == 2) {
                                // Setter redaktør border "Grønn" ?>
                                <img src="bilder/profil.png" alt="Profilbilde" class="profil_navmeny" style="border: 2px solid green;">
                            <?php } else if ($_SESSION['brukertype'] == 1) {
                                // Setter administrator border "Rød" ?>
                                <img src="bilder/profil.png" alt="Profilbilde" class="profil_navmeny" style="border: 2px solid red;"> 
                            <?php } else if ($_SESSION['brukertype'] != 1 || 2) {
                                // Setter vanlig profil bilde ?>
                                <img src="bilder/profil.png" alt="Profilbilde" class="profil_navmeny"> 
                            <?php }
                        } ?>
                    </a>
                <?php } else { ?>
                    <a class="bildeKontroll" href="javascript:void(0)" onClick="location.href='profil.php?bruker=<?php echo($_SESSION['idbruker']) ?>'" tabindex="5">
                        <?php if ($_SESSION['brukertype'] == 2) {
                            // Setter redaktør border "Grønn" ?>
                            <img src="bilder/profil.png" alt="Profilbilde" class="profil_navmeny" style="border: 2px solid green;">

                        <?php } else if ($_SESSION['brukertype'] == 1) {
                            // Setter administrator border "Rød" ?>
                            <img src="bilder/profil.png" alt="Profilbilde" class="profil_navmeny" style="border: 2px solid red;"> 

                        <?php } else if ($_SESSION['brukertype'] != 1 || 2) {
                            // Setter vanlig profil bilde ?> 
                            <img src="bilder/profil.png" alt="Profilbilde" class="profil_navmeny"> 
                        <?php } ?>
                    </a>

                <?php }
                // Legger til en knapp for å logge ut når man er innlogget ?>
                <form method="POST" action="default.php">
                    <button name="loggUt" id="registrerKnapp" tabindex="4">LOGG UT</button>
                </form>
            <?php } else {
                // Vises når bruker ikke er innlogget ?>
                <button id="registrerKnapp" onClick="location.href='registrer.php'" tabindex="5">REGISTRER</button>
                <button id="logginnKnapp" onClick="location.href='logginn.php'" tabindex="4">LOGG INN</button>
            <?php } ?>

            <form id="sokForm_navmeny" action="sok.php">
                <!-- <img src="bilder/sokWhiteIkon.png" alt="Ikon for søk i navigasjonsmeny" id="sokBtn_navmeny"> -->
                <input id="sokBtn_navmeny" type="submit" value="" tabindex="3">
                <input id="sokInp_navmeny" type="text" name="artTittel" placeholder="Søk på artikkel" tabindex="2">
            </form>
            <a href="javascript:void(0)" onClick="location.href='sok.php'" tabindex="-1">
                <img src="bilder/sokIkon.png" alt="Søkeikon" class="sok_navmeny" tabindex="2">
            </a>
            <!-- Logoen øverst i venstre hjørne -->
            <a href="default.php" tabindex="1">
                <img class="Logo_navmeny" src="bilder/klimateNoText.png" alt="Klimate logo">
            </a>
        <!-- Slutt på navigasjonsmeny-->
        </nav>

        <!-- Gardinmenyen, denne går over alt annet innhold ved bruk av z-index -->
        <section id="navMeny" class="hamburgerMeny" onclick="lukkHamburgerMeny()">
            <section class="avbrySeksjon">
                <img class="xikon" src="bilder/xikon.png" alt="Lukk hamburgermeny">   
            </section>
            
            <!-- innholdet i hamburger-menyen -->
            <!-- -1 tabIndex som standard da menyen er lukket -->
            <section class="hamburgerInnhold">
                <?php if (isset($_SESSION['idbruker'])) { ?>
                    <!-- Hva som vises om bruker er innlogget -->
                    <!-- Administrator meny -->
                    
                    <?php if ($_SESSION['brukertype'] == 1) { ?>
                    <a class = "menytab" tabIndex = "-1" href="administrator.php" style="margin-bottom: 0.2em; font-weight: bold;"><?php echo($brukertypenavn['brukertypenavn']) ?> innstillinger</a>
                    <?php } ?>
                    <!-- For alle brukere -->
                    <a class = "menytab" tabIndex = "-1" href="backend.php" accesskey="1">Min oversikt</a>
                    <a class = "menytab" tabIndex = "-1" href="arrangement.php" accesskey="3">Arrangementer</a>
                    <a class = "menytab" tabIndex = "-1" href="artikkel.php" accesskey="2">Artikler</a>
                    <a class = "menytab" tabIndex = "-1" href="meldinger.php" accesskey="7">Innboks<?php if($antUlest['antall'] > 0) { ?><p id="hamburger_antUlest"><?php echo($antUlest['antall']);?></p><?php } ?></a>
                    <a class = "menytab" tabIndex = "-1" href="profil.php?bruker=<?php echo($_SESSION['idbruker']) ?>" accesskey="6">Profil</a>
                    <a class = "menytab" tabIndex = "-1" href="konto.php" accesskey="5">Konto</a>
                    <a class = "menytab" tabIndex = "-1" href="sok.php" accesskey="4">Avansert Søk</a>
                    
                <?php } else { ?>
                    <!-- Hvis bruker ikke er innlogget -->
                    <a class = "menytab" tabIndex = "-1" href="arrangement.php">Arrangementer</a>
                    <a class = "menytab" tabIndex = "-1" href="artikkel.php">Artikler</a>
                    <a class = "menytab" tabIndex = "-1" href="sok.php">Avansert Søk</a>
                <?php } ?>
            </section>
        </section>
<?php
// Denne siden er utviklet av Robin Kleppang, Glenn Petter Pettersen, siste gang endret 03.06.2020
// Denne siden er kontrollert av Glenn Petter Pettersen, siste gang 04.06.2020
?>