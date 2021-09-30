<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");

// Kun innloggede brukere kan se meldinger
if (!isset($_SESSION['idbruker'])) {
    $_SESSION['default_melding'] = "Du må logge inn før du kan se denne siden";
    header("Location: default.php");
}

$meldinger_melding = "";
if(isset($_SESSION['meldinger_melding'])) {
    $meldinger_melding = $_SESSION['meldinger_melding'];
    unset($_SESSION['meldinger_melding']);
}

// Browser må validere cache med server før cached kopi kan benyttes
// Dette gjør at man kan gå frem og tilbake i innboksen uten at man får ERR_CACHE_MISS
header("Cache-Control: no cache");

if(isset($_POST['mottatt'])) {
    // Er vi her henter vi ting som brukes i visning av valgt melding
    $samtaleMeldingerQ = "select idmelding, tittel, tekst, tid, lest, sender, papirkurv
                            from melding where idmelding = " . $_POST['mottatt'] . " and mottaker = " . $_SESSION['idbruker'];
    $samtaleMeldingerSTMT = $db->prepare($samtaleMeldingerQ);
    $samtaleMeldingerSTMT->execute();
    $resMld = $samtaleMeldingerSTMT->fetch(PDO::FETCH_ASSOC); 

    $antMld = $samtaleMeldingerSTMT->rowCount();

    if($antMld > 0) {
        // Fant meldingen i databasen, setter variabel som testes på senere til true
        $fantSamtale = true;

        // Henter info om senderen
        $senderInfoQ = "select idbruker, brukernavn, fnavn, enavn, brukertype from bruker where bruker.idbruker = " . $resMld['sender'];
        $senderInfoSTMT = $db->prepare($senderInfoQ);
        $senderInfoSTMT->execute();
        $resInfo = $senderInfoSTMT->fetch(PDO::FETCH_ASSOC); 

        if($resInfo['brukertype'] != 4) {
            // Henter personvern
            $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $resMld['sender'];
            $personvernSTMT = $db->prepare($personvernQ);
            $personvernSTMT->execute();
            $personvernSender = $personvernSTMT->fetch(PDO::FETCH_ASSOC); 

            // Tar utgangspunkt i at det ikke kan vises
            $kanViseFornavn = false;
            $kanViseEtternavn = false;

            // Sjekker om svar fra DB ikke er false og bruker har valgt å vise fornavn
            if(isset($personvernSender['visfnavn']) && $personvernSender['visfnavn'] == "1") {
                $kanViseFornavn = true;
            }

            // Sjekker om svar fra DB ikke er false og bruker har valgt å vise etternavn
            if(isset($personvernSender['visenavn']) && $personvernSender['visenavn'] == "1") {
                $kanViseEtternavn = true;
            }
            
            // Tester på om vi kan vise fulle navnet, bare fornavn, bare etternavn eller kun brukernavn
            if($kanViseFornavn == true && $kanViseEtternavn == false) {
                if(preg_match("/\S/", $resInfo['fnavn']) == 1) {
                    $navn = $resInfo['fnavn'];  
                } else {
                    $navn = $resInfo['brukernavn'];
                }
            } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                    $navn = $resInfo['enavn'];  
                } else {
                    $navn = $resInfo['brukernavn'];
                }
            } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                    $navn = $resInfo['fnavn'] . " " . $resInfo['enavn'];  
                } else {
                    $navn = $resInfo['brukernavn'];
                }
            } else {
                $navn = $resInfo['brukernavn'];
            }
        } else {
            $navn = "Avregistrert bruker";
        }

        // Test på om bruker har tidligere lest denne meldingen
        if($resMld['lest'] == null || $resMld['lest'] == 0) {
            // Setter meldingen til lest
            $lestQ = "update melding set lest = 1 where idmelding = " . $resMld['idmelding'];
            $lestSTMT = $db->prepare($lestQ);
            $lestSTMT->execute();
        }
        
        // Henter bildet til brukeren
        $hentBildeQ = "select hvor from bilder, brukerbilde, bruker where brukerbilde.bruker = " . $resMld['sender'] . " and brukerbilde.bilde = bilder.idbilder and brukerbilde.bruker = bruker.idbruker and bruker.brukertype != 4";
        $stmtBildeSTMT = $db->prepare($hentBildeQ);
        $stmtBildeSTMT->execute();
        $senderBilde = $stmtBildeSTMT->fetch(PDO::FETCH_ASSOC);
        $funnetSenderBilde = $stmtBildeSTMT->rowCount();

    } else {
        // Fant ikke meldingen i databasen, setter variabel som testes på senere til false
        $fantSamtale = false;
    }

} else if(isset($_POST['utboks'])) {
    // Er vi her henter vi ting som brukes i innboksen
    $sendteMeldingerQ = "select idmelding, tittel, tid, lest, mottaker
                            from melding where sender = " . $_SESSION['idbruker'] . 
                                " order by tid DESC";
    $sendteMeldingerSTMT = $db->prepare($sendteMeldingerQ);
    $sendteMeldingerSTMT->execute();
    $resMld = $sendteMeldingerSTMT->fetchAll(PDO::FETCH_ASSOC); 
    $antMld = $sendteMeldingerSTMT->rowCount();

} else if(isset($_POST['papirkurv'])) {
    // Er vi her henter vi ting som brukes i innboksen
    $mottattMeldingerQ = "select idmelding, tittel, tid, lest, sender
                            from melding where mottaker = " . $_SESSION['idbruker'] . " and (papirkurv = 1)" . 
                                " order by tid DESC";
    $mottattMeldingerSTMT = $db->prepare($mottattMeldingerQ);
    $mottattMeldingerSTMT->execute();
    $resMld = $mottattMeldingerSTMT->fetchAll(PDO::FETCH_ASSOC); 

    $antMld = $mottattMeldingerSTMT->rowCount();

} else {
    // Er vi her henter vi ting som brukes i innboksen
    $mottattMeldingerQ = "select idmelding, tittel, tid, lest, sender
                            from melding where mottaker = " . $_SESSION['idbruker'] . " and (papirkurv is null or papirkurv = 0)" . 
                                " order by tid DESC";
    $mottattMeldingerSTMT = $db->prepare($mottattMeldingerQ);
    $mottattMeldingerSTMT->execute();
    $resMld = $mottattMeldingerSTMT->fetchAll(PDO::FETCH_ASSOC); 

    $antMld = $mottattMeldingerSTMT->rowCount();
}

// Del for å legge til en ny melding, brukes både i ny melding og svar på melding
if(isset($_POST['sendMelding'])) {
    if(isset($_POST['brukernavn'])) {
        // Henter idbruker til brukeren som ble oppgitt
        $hentIDQ = "select idbruker from bruker where brukernavn = '" . $_POST['brukernavn'] . "'";
        $hentIDSTMT = $db->prepare($hentIDQ);
        $hentIDSTMT->execute();
        $resID = $hentIDSTMT->fetch(PDO::FETCH_ASSOC); 

        $_POST['idbruker'] = $resID['idbruker'];
    }
    // Legger til en ny melding 
    $nyMeldingQ = "insert into melding(tittel, tekst, tid, lest, sender, mottaker) 
                        values('" . filter_var($_POST['tittel'], FILTER_SANITIZE_STRING) . "', '" . filter_var($_POST['tekst'], FILTER_SANITIZE_STRING) . "', 
                            NOW(), 0, " . $_SESSION['idbruker'] . ", " . $_POST['idbruker'] . ")";
    $nyMeldingSTMT = $db->prepare($nyMeldingQ);
    $nyMeldingSTMT->execute();
    $sendt = $nyMeldingSTMT->rowCount();
    
    if($sendt > 0) {
        // Melding sendt, gir tilbakemelding
        header("location: meldinger.php?meldingsendt");
    } else {
        // Error 1, melding ikke sendt
        $_SESSION['meldinger_melding'] = "Kunne ikke sende meldingen";
        header("Location: meldinger.php");
    }
}

// Del for å legge en melding i søplekurven
if(isset($_POST['slettMelding'])) {
    // Bare tillate at innlogget bruker kan slette sine egne meldinger
    $sjekkPaaQ = "select idmelding from melding where idmelding = " . $_POST['slettMelding'] . " and mottaker = " . $_SESSION['idbruker'];
    $sjekkPaaSTMT = $db->prepare($sjekkPaaQ);
    $sjekkPaaSTMT->execute();
    $funnetMelding = $sjekkPaaSTMT->rowCount();

    if($funnetMelding > 0) {
        // Oppdaterer meldingen, setter også lest til 1
        $slettMeldingQ = "update melding set papirkurv = 1, lest = 1 where idmelding = " . $_POST['slettMelding'];
        $slettMeldingSTMT = $db->prepare($slettMeldingQ);
        $slettMeldingSTMT->execute();

        $endretMelding = $slettMeldingSTMT->rowCount();
        if($endretMelding > 0) { 
            header("Location: meldinger.php?meldingslettet"); /* Melding slettet, OK */ 
        } else {
            /* Error 2, melding ikke slettet */ 
            $_SESSION['meldinger_melding'] = "Kunne ikke slette meldingen";
            header("Location: meldinger.php");
        }
    } else {
        /* Error 2, melding ikke slettet */ 
        $_SESSION['meldinger_melding'] = "Kunne ikke slette meldingen";
        header("Location: meldinger.php");
    }
}

// Del for å gjenopprette en slettet melding
if(isset($_POST['gjenopprettMelding'])) {
    // Bare tillate at innlogget bruker kan gjenopprette sine egne meldinger
    $sjekkPaaQ = "select idmelding from melding where idmelding = " . $_POST['gjenopprettMelding'] . " and mottaker = " . $_SESSION['idbruker'];
    $sjekkPaaSTMT = $db->prepare($sjekkPaaQ);
    $sjekkPaaSTMT->execute();
    $funnetMelding = $sjekkPaaSTMT->rowCount();

    if($funnetMelding > 0) {
        // Oppdaterer meldingen
        $gjenopprettMeldingQ = "update melding set papirkurv = 0 where idmelding = " . $_POST['gjenopprettMelding'];
        $gjenopprettMeldingSTMT = $db->prepare($gjenopprettMeldingQ);
        $gjenopprettMeldingSTMT->execute();

        $endretMelding = $gjenopprettMeldingSTMT->rowCount();
        if($endretMelding > 0) { 
            header("Location: meldinger.php"); /* Melding gjenopprettet, OK */ 
        } else { 
            /* Error 3, melding ikke gjenopprettet */ 
            $_SESSION['meldinger_melding'] = "Kunne ikke gjenopprette meldingen";
            header("Location: meldinger.php");
        }
    } else { 
        /* Error 3, melding ikke gjenopprettet */ 
        $_SESSION['meldinger_melding'] = "Kunne ikke gjenopprette meldingen";
        header("Location: meldinger.php");
    }
}

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
            <?php if(isset($_POST['mottatt'])) { ?>
                Melding fra <?php echo($navn); ?>
            <?php } else if(isset($_POST['ny'])) { ?>
                Ny melding
            <?php } else if(isset($_POST['utboks'])) { ?>
                Utboks
            <?php } else { ?>
                Innboks
            <?php } ?>
        </title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body class="innhold" onload="meldingTabbing()" onclick="lukkMelding('mldFEIL_boks')">
        <?php include("inkluderes/navmeny.php") ?>
        <?php 
        if(isset($_POST['mottatt'])) { 
            /*--------------------------------*/
            /*--------------------------------*/
            /*--Del for å vise valgt melding--*/
            /*--------------------------------*/
            /*--------------------------------*/ ?>

            <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
            <!-- Kan ikke legge denne direkte i body -->
            <header id="meldinger_header" onclick="lukkHamburgerMeny()">
                <img src="bilder/meldingIkon.png" alt="Ikon for meldinger">
                <h1><?php if(isset($navn)) { ?>Melding fra <?php echo($navn); } ?></h1>
                <form method="POST" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="utboks" title="Meldingene du har sendt" value="Utboks" tabindex = "8">
                </form>
                <form method="POST" id="meldinger_header_papirkurv" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="papirkurv" title="Meldingene du har slettet" value="Papirkurv" tabindex = "9">
                </form>
            </header>

            <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
            <main id="meldinger_main" onclick="lukkHamburgerMeny()"> 
                <?php if($fantSamtale == true) { ?>
                    <section id="meldinger_samtale_toppDel">
                        <?php if ($funnetSenderBilde != 0) {
                            $testPaa = $senderBilde['hvor'];
                            // Tester på om filen faktisk finnes
                            if(file_exists("$lagringsplass/$testPaa")) {
                                // Profilbilde som resultat av spørring
                                if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {
                                    // Hvis vi finner et miniatyrbilde bruker vi det ?>
                                    <img id="meldinger_sender_bilde" onClick="location.href='profil.php?bruker=<?php echo($resInfo['idbruker']) ?>'" src="bilder/opplastet/thumb_<?php echo($senderBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                <?php } else { ?>
                                    <img id="meldinger_sender_bilde" onClick="location.href='profil.php?bruker=<?php echo($resInfo['idbruker']) ?>'" src="bilder/opplastet/<?php echo($senderBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                <?php } ?>
                            <?php } else { ?>
                                <img id="meldinger_sender_bilde" onClick="location.href='profil.php?bruker=<?php echo($resInfo['idbruker']) ?>'" src="bilder/profil.png" alt="Standard profilbilde">
                            <?php } ?>
                        <?php } else { ?>
                            <img id="meldinger_sender_bilde" onClick="location.href='profil.php?bruker=<?php echo($resInfo['idbruker']) ?>'" src="bilder/profil.png" alt="Standard profilbilde">
                        <?php } ?>
                        <p id="meldinger_samtale_navn"><?php echo($navn) ?></p>
                        <p id="meldinger_samtale_tid"><?php echo(date_format(date_create($resMld['tid']), "j F Y H:i")) ?></p>
                        <?php if($resMld['papirkurv'] == 0) { ?>
                            <img src="bilder/soppelIkon.png" alt="Papirkurvikon" class="meldinger_valgt_soppel" id="meldinger_samtale_soppel" title="Slett denne meldingen" onclick="slettSamtale(<?php echo($resMld['idmelding']) ?>)" tabindex = "10">
                            <form method="POST" id="meldinger_innboks_soppel">
                                <input type="hidden" id="meldinger_innboks_soppel_valgt" name="slettMelding" value="">
                            </form>
                        <?php } else { ?>
                            <img src="bilder/restoreIkon.png" alt="Gjenopprettikon" class="meldinger_valgt_restore" id="meldinger_samtale_restore" title="Gjenopprett denne meldingen" onclick="gjenopprettSamtale(<?php echo($resMld['idmelding']) ?>)" tabindex = "10">
                            <form method="POST" id="meldinger_innboks_restore">
                                <input type="hidden" id="meldinger_innboks_restore_valgt" name="gjenopprettMelding" value="">
                            </form>
                        <?php } ?>
                    </section>
                    <p id="meldinger_samtale_tittel"><?php echo($resMld['tittel']) ?></p>
                    <p id="meldinger_samtale_tekst"><?php echo($resMld['tekst']) ?></p>

                    <form method="POST" id="meldinger_form_samtale" action="meldinger.php">
                        <input type="hidden" name="idbruker" value="<?php echo($resInfo['idbruker']) ?>">
                        <input type="hidden" name="tittel" value="Re: <?php echo(substr($resMld['tittel'], 0, 40)) ?>"> 
                        <textarea id="meldinger_samtale_svar" type="textbox" maxlength="1024" name="tekst" placeholder="Skriv her..." title="Oppgi innholdet til svaret" required></textarea>
                        <input id="meldinger_samtale_knapp" type="submit" name="sendMelding" value="">
                    </form>
                <?php } else { ?>
                    <p>Kunne ikke vise denne meldingen</p>
                <?php } ?>
                <button onclick="location.href='meldinger.php'" id="meldinger_samtale_lenke" class="lenke_knapp" title="Meldingene du har mottatt">Tilbake til innboks</button>
            </main>
        

        <?php } else if(isset($_POST['ny'])) { 
            /*--------------------------------*/
            /*--------------------------------*/
            /*---Del for å vise ny melding----*/
            /*--------------------------------*/
            /*--------------------------------*/ ?>

            <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
            <!-- Kan ikke legge denne direkte i body -->
            <header id="meldinger_header" onclick="lukkHamburgerMeny()">
                <img src="bilder/meldingIkon.png" alt="Ikon for meldinger">
                <h1>Ny melding</h1>
                <form method="POST" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="utboks" title="Meldingene du har sendt" value="Utboks">
                </form>
                <form method="POST" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="papirkurv" title="Meldingene du har slettet" value="Papirkurv">
                </form>
            </header>

            <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
            <main id="meldinger_main_ny" onclick="lukkHamburgerMeny()"> 

                <form method="POST" action="meldinger.php">
                    <input name="brukernavn"  id="meldinger_ny_bruker" type="text" list="brukere" placeholder="Skriv inn brukernavn" title="Brukernavnet du ønsker å sende melding til" autofocus required>
                    <datalist id="brukere">
                        <?php 
                        // Henter brukernavn fra database
                        $hentNavnQ = "select brukernavn from bruker where brukertype != 4 and idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) order by brukernavn DESC";
                        $hentNavnSTMT = $db->prepare($hentNavnQ);
                        $hentNavnSTMT->execute();
                        $liste = $hentNavnSTMT->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($liste as $brukernavn) { ?>
                            <option value="<?php echo($brukernavn['brukernavn'])?>"><?php echo($brukernavn['brukernavn'])?></option>
                        <?php } ?>
                    </datalist>

                    <input type="text" id="meldinger_ny_tittel" name="tittel" maxlength="45" placeholder="Skriv inn tittel" title="Tittelen på meldingen" required>
                    <textarea id="meldinger_ny_tekst" type="textbox" maxlength="1024" name="tekst" placeholder="Skriv inn innhold" title="Innholdet i meldingen" required></textarea>
                    <input id="meldinger_ny_knapp" type="submit" name="sendMelding" value="Send melding" title="Send denne meldingen">
                </form>

                <button onclick="location.href='meldinger.php'" class="lenke_knapp" title="Meldingene du har mottatt">Tilbake til innboks</button>
            </main>
        

        <?php } else if(isset($_POST['utboks'])) { 
            /*--------------------------------*/
            /*--------------------------------*/
            /*-----Del for å vise utboksen----*/
            /*--------------------------------*/
            /*--------------------------------*/ ?>

            <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
            <!-- Kan ikke legge denne direkte i body -->
            <header id="meldinger_header_utboks" onclick="lukkHamburgerMeny()">
                <img src="bilder/meldingIkon.png" alt="Ikon for meldinger">
                <h1>Utboks</h1>
                <form method="POST" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="papirkurv" title="Meldingene du har slettet" value="Papirkurv" tabindex = "8">
                </form>
            </header>

            <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
            <main id="meldinger_main" onclick="lukkHamburgerMeny()">  
                <?php
                if($antMld > 0) { ?>
                    <form method="POST" id="meldinger_form_utboks" action="meldinger.php">
                        <input type="hidden" id="meldinger_innboks_valgt" name="mottatt" value="">
                        <?php 
                        for($i = 0; $i < count($resMld); $i++) {
                            $senderInfoQ = "select brukernavn, fnavn, enavn, brukertype from bruker where bruker.idbruker = " . $resMld[$i]['mottaker'];
                            $senderInfoSTMT = $db->prepare($senderInfoQ);
                            $senderInfoSTMT->execute();
                            $resInfo = $senderInfoSTMT->fetch(PDO::FETCH_ASSOC); 

                            // Henter bildet til brukeren
                            $mottakerBildeQ = "select hvor from bilder, brukerbilde, bruker where brukerbilde.bruker = " . $resMld[$i]['mottaker'] . " and brukerbilde.bilde = bilder.idbilder and brukerbilde.bruker = bruker.idbruker and bruker.brukertype != 4";
                            $mottakerBildeSTMT = $db->prepare($mottakerBildeQ);
                            $mottakerBildeSTMT->execute();
                            $mottakerBilde = $mottakerBildeSTMT->fetch(PDO::FETCH_ASSOC);
                            $funnetMottakerBilde = $mottakerBildeSTMT->rowCount();
                            
                            if($resInfo['brukertype'] != 4) {
                                // Henter personvern
                                $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $resMld[$i]['mottaker'];
                                $personvernSTMT = $db->prepare($personvernQ);
                                $personvernSTMT->execute();
                                $personvernSender = $personvernSTMT->fetch(PDO::FETCH_ASSOC); 

                                $kanViseFornavn = false;
                                $kanViseEtternavn = false;

                                if(isset($personvernSender['visfnavn']) && $personvernSender['visfnavn'] == "1") {
                                    $kanViseFornavn = true;
                                }

                                if(isset($personvernSender['visenavn']) && $personvernSender['visenavn'] == "1") {
                                    $kanViseEtternavn = true;
                                }
                                
                                if($kanViseFornavn == true && $kanViseEtternavn == false) {
                                    if(preg_match("/\S/", $resInfo['fnavn']) == 1) {
                                        $navn = $resInfo['fnavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                                        $navn = $resInfo['enavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                                        $navn = $resInfo['fnavn'] . " " . $resInfo['enavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else {
                                    $navn = $resInfo['brukernavn'];
                                } 
                            } else {
                                $navn = "Avregistrert bruker";
                            } ?>

                            <section class="meldinger_innboks_samtale">
                                <?php if($funnetMottakerBilde > 0) {
                                    $testPaa = $mottakerBilde['hvor'];
                                    // Tester på om filen faktisk finnes
                                    if(file_exists("$lagringsplass/$testPaa")) {
                                        // Profilbilde som resultat av spørring
                                        if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {
                                            // Hvis vi finner et miniatyrbilde bruker vi det ?>
                                            <img class="meldinger_innboks_bilde" src="bilder/opplastet/thumb_<?php echo($mottakerBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                        <?php } else { ?>
                                            <img class="meldinger_innboks_bilde" src="bilder/opplastet/<?php echo($mottakerBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img class="meldinger_innboks_bilde" src="bilder/profil.png" alt="Standard profilbilde">
                                    <?php } ?>
                                <?php } else { ?>
                                    <img class="meldinger_innboks_bilde" src="bilder/profil.png" alt="Standard profilbilde">
                                <?php } ?>
                                <p class="meldinger_innboks_navn">Til: <?php echo($navn) ?></p>
                                <p class="meldinger_innboks_tid"><?php echo(date_format(date_create($resMld[$i]['tid']), "j M H:i")) ?></p>
                        
                                <p class="meldinger_innboks_tittel"><?php echo($resMld[$i]['tittel']) ?></p>
                            </section>
                        <?php } ?>
                    </form>

                <?php } else { ?>
                    <p>Utboksen din er tom</p>
                <?php } ?>
                <form method="POST" id="meldinger_form_ny" action="meldinger.php">
                    <input type="submit" id="meldinger_nyKnapp" name="ny" title="Skriv en ny melding" value="Ny melding">
                </form>
                <button onclick="location.href='meldinger.php'" class="lenke_knapp" title="Meldingene du har mottatt">Tilbake til innboks</button>
            </main>


        <?php } else if(isset($_POST['papirkurv'])) {
            /*--------------------------------*/
            /*--------------------------------*/
            /*----Del for å vise papirkurv----*/
            /*--------------------------------*/
            /*--------------------------------*/ ?>

            <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
            <!-- Kan ikke legge denne direkte i body -->
            <header id="meldinger_header" onclick="lukkHamburgerMeny()">
                <img src="bilder/meldingIkon.png" alt="Ikon for meldinger">
                <h1>Papirkurv</h1>
                <form method="POST" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="utboks" title="Meldingene du har sendt" value="Utboks" tabindex = "8">
                </form>
            </header>

            <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
            <main id="meldinger_main" onclick="lukkHamburgerMeny()">  

                <?php
                $tabMld = 9;
                $tabGjen = 10;

                if($antMld > 0) { ?>
                    <form method="POST" id="meldinger_form_innboks" action="meldinger.php">
                        <input type="hidden" id="meldinger_innboks_valgt" name="mottatt" value="">
                        <?php 
                        for($i = 0; $i < count($resMld); $i++) {
                            $senderInfoQ = "select brukernavn, fnavn, enavn, brukertype from bruker where bruker.idbruker = " . $resMld[$i]['sender'];
                            $senderInfoSTMT = $db->prepare($senderInfoQ);
                            $senderInfoSTMT->execute();
                            $resInfo = $senderInfoSTMT->fetch(PDO::FETCH_ASSOC); 

                            // Henter bildet til brukeren
                            $senderBildeQ = "select hvor from bilder, brukerbilde, bruker where brukerbilde.bruker = " . $resMld[$i]['sender'] . " and brukerbilde.bilde = bilder.idbilder and brukerbilde.bruker = bruker.idbruker and bruker.brukertype != 4";
                            $senderBildeSTMT = $db->prepare($senderBildeQ);
                            $senderBildeSTMT->execute();
                            $senderBilde = $senderBildeSTMT->fetch(PDO::FETCH_ASSOC);
                            $funnetSenderBilde = $senderBildeSTMT->rowCount();
                            
                            if($resInfo['brukertype'] != 4) {

                                // Henter personvern
                                $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $resMld[$i]['sender'];
                                $personvernSTMT = $db->prepare($personvernQ);
                                $personvernSTMT->execute();
                                $personvernSender = $personvernSTMT->fetch(PDO::FETCH_ASSOC); 

                                $kanViseFornavn = false;
                                $kanViseEtternavn = false;

                                if(isset($personvernSender['visfnavn']) && $personvernSender['visfnavn'] == "1") {
                                    $kanViseFornavn = true;
                                }

                                if(isset($personvernSender['visenavn']) && $personvernSender['visenavn'] == "1") {
                                    $kanViseEtternavn = true;
                                }
                                
                                if($kanViseFornavn == true && $kanViseEtternavn == false) {
                                    if(preg_match("/\S/", $resInfo['fnavn']) == 1) {
                                        $navn = $resInfo['fnavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                                        $navn = $resInfo['enavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                                        $navn = $resInfo['fnavn'] . " " . $resInfo['enavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else {
                                    $navn = $resInfo['brukernavn'];
                                }
                            } else {
                                $navn = "Avregistrert bruker";
                            }
                            ?>
                            <section class="meldinger_innboks_samtale" title="Vis denne meldingen" onclick="aapneSamtale(<?php echo($resMld[$i]['idmelding']) ?>)" tabindex = "<?php echo($tabMld); $tabMld++; $tabMld++; ?>">
                                <?php if($funnetSenderBilde > 0) {
                                    $testPaa = $senderBilde['hvor'];
                                    // Tester på om filen faktisk finnes
                                    if(file_exists("$lagringsplass/$testPaa")) {
                                        // Profilbilde som resultat av spørring
                                        if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {
                                            // Hvis vi finner et miniatyrbilde bruker vi det ?>
                                            <img class="meldinger_innboks_bilde" src="bilder/opplastet/thumb_<?php echo($senderBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                        <?php } else { ?>
                                            <img class="meldinger_innboks_bilde" src="bilder/opplastet/<?php echo($senderBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img class="meldinger_innboks_bilde" src="bilder/profil.png" alt="Standard profilbilde">
                                    <?php } ?>
                                <?php } else { ?>
                                    <img class="meldinger_innboks_bilde" src="bilder/profil.png" alt="Standard profilbilde">
                                <?php } ?>
                                <p class="meldinger_innboks_navn"><?php echo($navn) ?></p>
                                <p class="meldinger_innboks_tid"><?php echo(date_format(date_create($resMld[$i]['tid']), "j M H:i")) ?></p>
                        
                                <p class="meldinger_innboks_tittel"><?php echo($resMld[$i]['tittel']) ?></p>
                            </section>
                                <img src="bilder/restoreIkon.png" alt="Gjenopprettikon" title="Gjenopprett denne meldingen" class="meldinger_innboks_restore" onclick="gjenopprettSamtale(<?php echo($resMld[$i]['idmelding']) ?>)" tabindex = "<?php echo($tabGjen); $tabGjen++; $tabGjen++; ?>">
                        <?php } ?>
                    </form>
                    <form method="POST" id="meldinger_innboks_restore">
                        <input type="hidden" id="meldinger_innboks_restore_valgt" name="gjenopprettMelding" value="">
                    </form>

                <?php } else { ?>
                    <p>Papirkurven din er tom</p>
                <?php } ?>

                <form method="POST" id="meldinger_form_ny" action="meldinger.php">
                    <input type="submit" id="meldinger_nyKnapp" name="ny" title="Skriv en ny melding" value="Ny melding">
                </form>
                <button onclick="location.href='meldinger.php'" class="lenke_knapp" title="Meldingene du har mottatt">Tilbake til innboks</button>

            </main>
        <?php } else { 
            /*--------------------------------*/
            /*--------------------------------*/
            /*----Del for å vise innboksen----*/
            /*--------------------------------*/
            /*--------------------------------*/ ?>

            <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
            <!-- Kan ikke legge denne direkte i body -->
            <header id="meldinger_header" onclick="lukkHamburgerMeny()">
                <img src="bilder/meldingIkon.png" alt="Ikon for meldinger">
                <h1>Innboks</h1>
                <form method="POST" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="utboks" title="Meldingene du har sendt" value="Utboks" tabindex = "8">
                </form>
                <form method="POST" action="meldinger.php">
                    <input type="submit" class="lenke_knapp" name="papirkurv" title="Meldingene du har slettet"  value="Papirkurv" tabindex = "9">
                </form>
            </header>

            <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
            <main id="meldinger_main" onclick="lukkHamburgerMeny()">  
                <?php if(isset($_GET['meldingsendt'])) { ?>
                    <p id="mldOK">Melding sendt</p>

                <?php } else if(isset($_GET['meldingslettet'])) { ?>
                    <p id="mldOK">Melding sendt til papirkurv</p>

                <?php } 
                
                $tabMld = 10;
                $tabSoppel = 11;

                if($antMld > 0) { ?>
                    <form method="POST" id="meldinger_form_innboks" action="meldinger.php">
                        <input type="hidden" id="meldinger_innboks_valgt" name="mottatt" value="">
                        <?php 
                        for($i = 0; $i < count($resMld); $i++) {
                            $senderInfoQ = "select brukernavn, fnavn, enavn, brukertype from bruker where bruker.idbruker = " . $resMld[$i]['sender'];
                            $senderInfoSTMT = $db->prepare($senderInfoQ);
                            $senderInfoSTMT->execute();
                            $resInfo = $senderInfoSTMT->fetch(PDO::FETCH_ASSOC); 

                            // Henter bildet til brukeren
                            $senderBildeQ = "select hvor from bilder, brukerbilde, bruker where brukerbilde.bruker = " . $resMld[$i]['sender'] . " and brukerbilde.bilde = bilder.idbilder and brukerbilde.bruker = bruker.idbruker and bruker.brukertype != 4";
                            $senderBildeSTMT = $db->prepare($senderBildeQ);
                            $senderBildeSTMT->execute();
                            $senderBilde = $senderBildeSTMT->fetch(PDO::FETCH_ASSOC);
                            $funnetSenderBilde = $senderBildeSTMT->rowCount();

                            if($resInfo['brukertype'] != 4) {
                                // Henter personvern
                                $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $resMld[$i]['sender'];
                                $personvernSTMT = $db->prepare($personvernQ);
                                $personvernSTMT->execute();
                                $personvernSender = $personvernSTMT->fetch(PDO::FETCH_ASSOC); 
    
                                $kanViseFornavn = false;
                                $kanViseEtternavn = false;
    
                                if(isset($personvernSender['visfnavn']) && $personvernSender['visfnavn'] == "1") {
                                    $kanViseFornavn = true;
                                }
    
                                if(isset($personvernSender['visenavn']) && $personvernSender['visenavn'] == "1") {
                                    $kanViseEtternavn = true;
                                }
                                
                                if($kanViseFornavn == true && $kanViseEtternavn == false) {
                                    if(preg_match("/\S/", $resInfo['fnavn']) == 1) {
                                        $navn = $resInfo['fnavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                                        $navn = $resInfo['enavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $resInfo['enavn']) == 1) {
                                        $navn = $resInfo['fnavn'] . " " . $resInfo['enavn'];  
                                    } else {
                                        $navn = $resInfo['brukernavn'];
                                    }
                                } else {
                                    $navn = $resInfo['brukernavn'];
                                }
                            } else {
                                $navn = "Avregistrert bruker";
                            }


                            if($resMld[$i]['lest'] == 1) { ?>
                                <section class="meldinger_innboks_samtale" title="Vis denne meldingen" onclick="aapneSamtale(<?php echo($resMld[$i]['idmelding']) ?>)" tabindex = "<?php echo($tabMld); $tabMld++; $tabMld++; ?>">
                            <?php } else { ?>
                                <section class="meldinger_innboks_samtale_ulest" title="Vis denne uleste meldingen" onclick="aapneSamtale(<?php echo($resMld[$i]['idmelding']) ?>)" tabindex = "<?php echo($tabMld); $tabMld++; $tabMld++; ?>">
                            <?php } 
                                if($funnetSenderBilde > 0) {
                                    $testPaa = $senderBilde['hvor'];
                                    // Tester på om filen faktisk finnes
                                    if(file_exists("$lagringsplass/$testPaa")) {
                                        // Profilbilde som resultat av spørring
                                        if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {
                                            // Hvis vi finner et miniatyrbilde bruker vi det ?>
                                            <img class="meldinger_innboks_bilde" src="bilder/opplastet/thumb_<?php echo($senderBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                        <?php } else { ?>
                                            <img class="meldinger_innboks_bilde" src="bilder/opplastet/<?php echo($senderBilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img class="meldinger_innboks_bilde" src="bilder/profil.png" alt="Standard profilbilde">
                                    <?php } ?>
                                <?php } else { ?>
                                    <img class="meldinger_innboks_bilde" src="bilder/profil.png" alt="Standard profilbilde">
                                <?php } ?>
                                <p class="meldinger_innboks_navn"><?php echo($navn) ?></p>
                                <p class="meldinger_innboks_tid"><?php echo(date_format(date_create($resMld[$i]['tid']), "j M H:i")) ?></p>
                        
                                <p class="meldinger_innboks_tittel"><?php echo($resMld[$i]['tittel']) ?></p>
                            </section>
                                <img src="bilder/soppelIkon.png" alt="Søppelikon" title="Slett denne meldingen" class="meldinger_innboks_soppel" onclick="slettSamtale(<?php echo($resMld[$i]['idmelding']) ?>)" tabindex = "<?php echo($tabSoppel); $tabSoppel++; $tabSoppel++; ?>">
                        <?php } ?>
                    </form>
                    <form method="POST" id="meldinger_innboks_soppel">
                        <input type="hidden" id="meldinger_innboks_soppel_valgt" name="slettMelding" value="">
                    </form>

                <?php } else { ?>
                    <p>Innboksen din er tom</p>
                <?php } ?>

                <form method="POST" id="meldinger_form_ny" action="meldinger.php">
                    <input type="submit" id="meldinger_nyKnapp" name="ny" title="Skriv en ny melding"  value="Ny melding">
                </form>

            </main>

        <?php } ?>
        <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($meldinger_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
            <section id="mldFEIL_innhold">
                <p id="mldFEIL"><?php echo($meldinger_melding) ?></p>  
                <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                <button id="mldFEIL_knapp" autofocus>Lukk</button>
            </section>  
        </section>
        <?php include("inkluderes/footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Robin Kleppang, siste gang endret 02.06.2020 -->
<!-- Denne siden er kontrollert av Aron Snekkestad, siste gang 04.06.2020 -->
</html>