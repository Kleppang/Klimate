<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");

// Browser må validere cache med server før cached kopi kan benyttes
// Dette gjør at man kan gå frem og tilbake i innboksen uten at man får ERR_CACHE_MISS
header("Cache-Control: no cache");


// Enkel test som gjør det mulig å beholde brukerinput etter siden er lastet på nytt (Form submit)
$input_tittel = "";
$input_innhold = "";
$input_tidspunkt = "";
$input_adresse = "";
$input_fylke = "";
if (isset($_SESSION['input_tittel'])) {
    // Legger innhold i variable som leses senere på siden
    $input_tittel = $_SESSION['input_tittel'];
    $input_innhold = $_SESSION['input_innhold'];
    $input_tidspunkt = $_SESSION['input_tidspunkt'];
    $input_adresse = $_SESSION['input_adresse'];
    $input_fylke = $_SESSION['input_fylke'];
    // Sletter innholdet så dette ikke eksisterer utenfor denne siden
    unset($_SESSION['input_tittel']);
    unset($_SESSION['input_innhold']);
    unset($_SESSION['input_tidspunkt']);
    unset($_SESSION['input_adresse']);
    unset($_SESSION['input_fylke']);
}

$arrangement_melding = "";
if(isset($_SESSION['arrangement_melding'])) {
    $arrangement_melding = $_SESSION['arrangement_melding'];
    unset($_SESSION['arrangement_melding']);
}

if (isset($_POST['publiserArrangement'])) {
    $_SESSION['input_tittel'] = $_POST['tittel'];
    $_SESSION['input_innhold'] = $_POST['innhold'];
    $_SESSION['input_tidspunkt'] = $_POST['tidspunkt'];
    $_SESSION['input_adresse'] = $_POST['adresse'];
    $_SESSION['input_fylke'] = $_POST['fylke'];

    if (strlen($_POST['tittel']) <= 45 && strlen($_POST['tittel']) > 0) {
        if (strlen($_POST['innhold']) <= 1000 && strlen($_POST['innhold']) > 0) {
            if ($_POST['tidspunkt'] != "") {
                if (strtotime($_POST['tidspunkt']) > strtotime(date("Y-m-d H:i:s"))) {
                    if(strlen($_POST['adresse']) <= 250 && strlen($_POST['adresse']) > 0) {
                        if($_POST['fylke'] != "") {
                        
                            // Tar utgangspunkt i at bruker ikke har lastet opp bilde
                            $harBilde = false;

                            // Sanitiserer innholdet før det blir lagt i databasen
                            $tittel = filter_var($_POST['tittel'], FILTER_SANITIZE_STRING);
                            $innhold = filter_var($_POST['innhold'], FILTER_SANITIZE_STRING);
                            $tidspunkt = $_POST['tidspunkt'];
                            $adresse = filter_var($_POST['adresse'], FILTER_SANITIZE_STRING);

                            // Henter IDen til fylket som ble valgt
                            $hentFylkeQ = "select idfylke from fylke where fylkenavn = '" . $_POST['fylke'] . "'";                        
                            $hentFylkeSTMT = $db->prepare($hentFylkeQ);
                            $hentFylkeSTMT->execute();
                            $idfylke = $hentFylkeSTMT->fetch(PDO::FETCH_ASSOC); 
                            
                            // Spørringen som oppretter arrangementet
                            $nyttArrangementQ = "insert into event(eventnavn, eventtekst, tidspunkt, veibeskrivelse, idbruker, fylke) values('" . $tittel . "', '" . $innhold . "', '" . $tidspunkt . "', '" . $adresse . "', '" . $_SESSION['idbruker'] . "', '" . $idfylke['idfylke'] . "')";
                            $nyttArrangementSTMT = $db->prepare($nyttArrangementQ);
                            $nyttArrangementSTMT->execute();
                            $idevent = $db->lastInsertId();
                            
                            // Del for filopplastning
                            if (is_uploaded_file($_FILES['bilde']['tmp_name'])) {
                                // Kombinerer artikkel med den siste idevent'en
                                $navn = "event" . $idevent;
                                // Henter filtypen
                                $filtype = "." . substr($_FILES['bilde']['type'], 6, 4);
                                // Kombinerer navnet med filtypen
                                $bildenavn = $navn . $filtype;
                                // Selve prosessen som flytter bildet til bestemt lagringsplass
                                if (move_uploaded_file($_FILES['bilde']['tmp_name'], "$lagringsplass/$bildenavn")) {
                                    $harbilde = true;
                                }
                            }
                            if ($harbilde == true) {
                                // Legger til bildet i databasen, dette kan være sin egne spørring
                                $nyttBildeQ = "insert into bilder(hvor) values('" . $bildenavn . "')";
                                $nyttBildeSTMT = $db->prepare($nyttBildeQ);
                                $nyttBildeSTMT->execute();
                                // Returnerer siste bildeid'en
                                $bildeid = $db->lastInsertId();

                                // Spørringen som lager koblingen mellom bilder og arrangement
                                $nyKoblingQ = "insert into eventbilde(event, bilde) values('" . $idevent . "', '" . $bildeid . "')";
                                $nyKoblingSTMT = $db->prepare($nyKoblingQ);
                                $nyKoblingSTMT->execute();

                                // Del for å laste opp thumbnail
                                $valgtbilde = getimagesize($lagringsplass . "/" . $bildenavn);
                                $bildenavnMini = "thumb_" . $navn . $filtype;
                                
                                if(strtolower($valgtbilde['mime']) == "image/png") {
                                    $img = imagecreatefrompng($lagringsplass . "/" . $bildenavn);
                                    $new = imagecreatetruecolor($valgtbilde[0]/2, $valgtbilde[1]/2);
                                    imagecopyresampled($new, $img, 0, 0, 0, 0, $valgtbilde[0]/2, $valgtbilde[1]/2, $valgtbilde[0], $valgtbilde[1]);
                                    imagepng($new, $lagringsplass . "/" . $bildenavnMini, 9);

                                } else if(strtolower($valgtbilde['mime']) == "image/jpeg") {
                                    $img = imagecreatefromjpeg($lagringsplass . "/" . $bildenavn);
                                    $new = imagecreatetruecolor($valgtbilde[0]/2, $valgtbilde[1]/2);
                                    imagecopyresampled($new, $img, 0, 0, 0, 0, $valgtbilde[0]/2, $valgtbilde[1]/2, $valgtbilde[0], $valgtbilde[1]);
                                    imagejpeg($new, $lagringsplass . "/" . $bildenavnMini);
                                }
                            }
                            
                            // Sletter innholdet så dette ikke eksisterer utenfor denne siden
                            unset($_SESSION['input_tittel']);
                            unset($_SESSION['input_innhold']);
                            unset($_SESSION['input_tidspunkt']);
                            unset($_SESSION['input_adresse']);
                            unset($_SESSION['input_fylke']);

                            header('Location: arrangement.php?arrangement=' . $idevent);
                        } else { $_SESSION['arrangement_melding'] = "Du har ikke oppgitt et fylke"; header("Location: arrangement.php?nyarrangement"); } // Fylke ikke oppgitt
                    } else { $_SESSION['arrangement_melding'] = "Du har enten ikke oppgitt en adresse, eller adressen er for lang"; header("Location: arrangement.php?nyarrangement"); } // Adresse tomt / for langt
                } else { $_SESSION['arrangement_melding'] = "Du har oppgitt en dato tilbake i tid"; header("Location: arrangement.php?nyarrangement"); } // Dato tilbake i tid
            } else { $_SESSION['arrangement_melding'] = "Du har ikke oppgitt et tidspunkt"; header("Location: arrangement.php?nyarrangement"); } // Tidspunkt ikke oppgitt
        } else { $_SESSION['arrangement_melding'] = "Du har enten ikke oppgitt en beskrivelse, eller beskrivelsen er for lang"; header("Location: arrangement.php?nyarrangement"); } // Innholdt tomt / for langt
    } else { $_SESSION['arrangement_melding'] = "Du har enten ikke oppgitt en tittel, eller tittelen er for lang"; header("Location: arrangement.php?nyarrangement"); } // Tittel tomt / for langt
}

if(isset($_POST['inviterTil'])) {
    if($_POST['inviterTil'] == $_GET['arrangement']) {
        // Henter eventinfo
        $hentInfoQ = "select eventnavn from event where idevent = " . $_GET['arrangement'];
        $hentInfoSTMT = $db->prepare($hentInfoQ);
        $hentInfoSTMT->execute();
        $eventinfo = $hentInfoSTMT->fetch(PDO::FETCH_ASSOC); 

        // Henter idbruker
        $hentIdQ= "select idbruker from bruker where brukernavn = '" . $_POST['brukernavn'] . "'";
        $hentIdSTMT = $db->prepare($hentIdQ);
        $hentIdSTMT->execute();
        $bruker = $hentIdSTMT->fetch(PDO::FETCH_ASSOC); 

        $tekst = "Hei " . $_POST['brukernavn'] . ", du har blitt invitert til " . $eventinfo['eventnavn'] . " <br><br>Følg linken her: <a href= " . $_SERVER['PHP_SELF'] . "?arrangement=" . $_GET['arrangement'] . ">Trykk meg</a>";

        if(isset($bruker['idbruker'])) {
            // Inviterer bruker
            $nyMeldingQ = "insert into melding(tittel, tekst, tid, lest, sender, mottaker) 
                            values('Invitasjon til " . $eventinfo['eventnavn'] . "', '" . $tekst  . "'," . 
                                "NOW(), 0, " . $_SESSION['idbruker'] . ", " . $bruker['idbruker'] . ")";
            $nyMeldingSTMT = $db->prepare($nyMeldingQ);
            $nyMeldingSTMT->execute(); 
            $sendt = $nyMeldingSTMT->rowCount();
    
            if($sendt > 0) {
                // Inviterer bruker
                $leggTilInvQ = "insert into påmelding(event_id, bruker_id, interessert) values(" . $_GET['arrangement'] . ", " . $bruker['idbruker'] . ", 'Invitert')";
                $leggTilInvSTMT = $db->prepare($leggTilInvQ);
                $leggTilInvSTMT->execute(); 
                $invitert = $leggTilInvSTMT->rowCount();

                if($invitert > 0) {
                    header("location: arrangement.php?arrangement=" . $_GET['arrangement'] . "&invitert");
                }
            } else {
                // Error 1
                $_SESSION['arrangement_melding'] = "Feil oppsto ved invitasjon"; 
                header("Location: arrangement.php?arrangement=" . $_GET['arrangement']);
            }
        } else {
            // Error 2
            $_SESSION['arrangement_melding'] = "Fant ikke bruker ved invitering"; 
            header("Location: arrangement.php?arrangement=" . $_GET['arrangement']);
        }

        
    }
}

if(isset($_POST['skal'])) {
    if($_POST['skal'] == "Skal") {
        $paameldingSkal = "insert into påmelding(event_id, bruker_id, interessert) values(" . $_GET['arrangement'] . ", " . $_SESSION['idbruker'] . ", 'Skal')" ;
        $paameldingSkalSTMT = $db->prepare($paameldingSkal);
        $paameldingSkalSTMT->execute();
    } 
}

if(isset($_POST['kanskje'])) {
    if($_POST['kanskje'] == "Kanskje") {
        $paameldingKanskje = "insert into påmelding(event_id, bruker_id, interessert) values(" . $_GET['arrangement'] . ", " . $_SESSION['idbruker'] . ", 'Kanskje')" ;
        $paameldingKanskjeSTMT = $db->prepare($paameldingKanskje);
        $paameldingKanskjeSTMT->execute();

    } 
}

if(isset($_POST['kanIkke'])) {
    if($_POST['kanIkke'] == "KanIkke") {
        $paameldingKanIkke = "insert into påmelding(event_id, bruker_id, interessert) values(" . $_GET['arrangement'] . ", " . $_SESSION['idbruker'] . ", 'Kan ikke')" ;
        $paameldingKanIkkeSTMT = $db->prepare($paameldingKanIkke);
        $paameldingKanIkkeSTMT->execute();

    } 
}


if(isset($_POST['avmeld'])) {
    if($_POST['avmeld'] == "Paameldt") {
       $avmeldingQ = "delete from påmelding where event_id = " . $_GET['arrangement'] . " and bruker_id = " . $_SESSION['idbruker'];
       $avmeldingSTMT = $db->prepare($avmeldingQ);
       $avmeldingSTMT->execute();
       
   }
}

if(isset($_POST['invitasjon'])) {
    if($_POST['invitasjon'] == "Godkjenn") {
        $avslaaQ = "update påmelding set interessert = 'Skal' where event_id = " . $_GET['arrangement'] . " and bruker_id = " . $_SESSION['idbruker'];
        $avslaaSTMT = $db->prepare($avslaaQ);
        $avslaaSTMT->execute();

    } else if($_POST['invitasjon'] == "Avslå") {
        $avslaaQ = "update påmelding set interessert = 'Kan ikke' where event_id = " . $_GET['arrangement'] . " and bruker_id = " . $_SESSION['idbruker'];
        $avslaaSTMT = $db->prepare($avslaaQ);
        $avslaaSTMT->execute();
    }
}

if (isset($_POST['slettDenne'])) {
    // Sjekker om vi fortsatt er på riktig side
    if ($_POST['slettDenne'] == $_GET['arrangement']) {
        // Henter henvisningen til bildet fra databasen.
        $slettBildeFQ = "select hvor from eventbilde, bilder where eventbilde.bilde = bilder.idbilder and eventbilde.event = " . $_POST['slettDenne'];
        $slettBildeFSTMT = $db->prepare($slettBildeFQ);
        $slettBildeFSTMT->execute();
        $bildenavn = $slettBildeFSTMT->fetch(PDO::FETCH_ASSOC); 

        $testPaa = $bildenavn['hvor'];
        // Test om det finnes en fil med samme navn
        if(file_exists("$lagringsplass/$testPaa")) {
            // Sletter bildet
            unlink("$lagringsplass/$testPaa");
        }

        $navnMini = "thumb_" . $testPaa;
        // Test på om miniatyrbildet finnes
        if(file_exists("$lagringsplass/$navnMini")) {
            // Dropp i så fall
            unlink("$lagringsplass/$navnMini");

        }

        // Begynner med å slette referansen til bildet arrangementet har
        $slettBildeQ = "delete from eventbilde where event = " . $_POST['slettDenne'];
        $slettBildeSTMT = $db->prepare($slettBildeQ);
        $slettBildeSTMT->execute();

        // Sletter alle som har meldt seg på arrangementet
        $slettPaameldingQ = "delete from påmelding where event_id = " . $_POST['slettDenne'];
        $slettPaameldingSTMT = $db->prepare($slettPaameldingQ);
        $slettPaameldingSTMT->execute();

        // Sletter så arrangementet
        $slettingQ = "delete from event where idevent = " . $_POST['slettDenne'];
        $slettingSTMT= $db->prepare($slettingQ);
        $slettingSTMT->execute();

        $antallSlettet = $slettingSTMT->rowCount();

        if ($antallSlettet > 0) {
            header('location: arrangement.php?slettingok');
        } else {
            header('location: arrangement.php?slettingfeil');
        }
    }
}


// tabindex som skal brukes til å bestemme startpunkt på visningen av arrangementene, denne endres hvis vi legger til flere elementer i navbar eller lignende
$tabindex = 8;

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
            <?php
                if(isset($_GET['nyarrangement'])) { ?>
                    Nytt arrangement
                <?php } else if (isset($_GET['arrangement'])) {
                    $hentTittelQ = "select eventnavn from event where idevent = " . $_GET['arrangement'] . " and 
                        idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                    $hentTittelSTMT = $db -> prepare($hentTittelQ);
                    $hentTittelSTMT->execute();
                    $arrangement_title = $hentTittelSTMT->fetch(PDO::FETCH_ASSOC);
                    if($arrangement_title) {
                        echo($arrangement_title['eventnavn']);
                    } else {
                        echo("Arrangement ikke funnet");
                    }
                } else { ?>
                    Arrangementer
            <?php } ?>
        </title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="stoppScroll" class="arrangement_body" onclick="lukkMelding('mldFEIL_boks')" onload="hentSide('arrangement_hovedsection', 'arrangement_tilbKnapp', 'arrangement_nesteKnapp'), arrTabbing(), erTouch()" onresize="hentSide('side_arrangement', 'arrangement_tilbKnapp', 'arrangement_nesteKnapp')">
        <?php include("inkluderes/navmeny.php") ?>
        <script language="JavaScript">
            // Funksjon som sjekker om brukeren har en touch-støttet enhet
            function erTouch() {
                if (kanTouchBrukes()) {
                    //document.getElementById('overskrift').innerHTML = "Touch er støttet";
                    //var brukTouch = true;
                } else {
                    //document.getElementById('overskrift').innerHTML = "Touch ikke støttet";
                    //var brukTouch = false;
                }
            }
        </script>

        <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
       
            <?php if(isset($_GET['arrangement'])){
                // Henter arrangementet bruker ønsker å se
                $hent = "select idevent, eventnavn, eventtekst, tidspunkt, veibeskrivelse, event.idbruker as idbruker, brukernavn, fnavn, enavn, epost, telefonnummer, fylkenavn from event, bruker, fylke 
                    where idevent = '" . $_GET['arrangement'] . "' and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and 
                        event.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                $stmt = $db->prepare($hent);
                $stmt->execute();
                $arrangement = $stmt->fetch(PDO::FETCH_ASSOC);
                $antallArrangement = $stmt->rowCount();
                // rowCount() returnerer antall resultater fra database, er dette null finnes det ikke noe arrangement med denne eventid'en i databasen
                if ($antallArrangement == 0) { ?>
                    <!-- Del for å vise feilmelding til bruker om at arrangementet ikke eksisterer -->
                    <header class="arrangement_header" onclick="lukkHamburgerMeny()">
                        <h1>Arrangement ikke funnet</h1>
                    </header>
                    <main id="arrangement_main" onclick="lukkHamburgerMeny()"> 
                    <section id="arrangement_bunnSection">
                        <button onclick="location.href='arrangement.php'" class="lenke_knapp">Tilbake til arrangementer</button>  
                    </section>
                    <!-- -------------------------------- -->
                    <!-- Del for å vise påmeldte brukere -->
                    <!-- -------------------------------- -->
                <?php } else if(isset($_POST['paameldteBrukere'])) {

                    $hentPåmeldte = "select event_id, idbruker, brukernavn, interessert, brukertype from påmelding, bruker where påmelding.bruker_id=bruker.idbruker and not interessert='Kan ikke' and event_id = " . $_GET['arrangement'] . "  and 
                        påmelding.bruker_id NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                    $hentPåmeldteSTMT = $db->prepare($hentPåmeldte);
                    $hentPåmeldteSTMT->execute();
                    $påmeldtBrukere = $hentPåmeldteSTMT->fetchAll(PDO::FETCH_ASSOC);
                    ?>
            
                    
                    <header class="arrangement_header" onclick="lukkHamburgerMeny()">
        
                    </header>

                    <section class="påmeldt_header">
                        <p class="påmeldtOverskrift"><?php echo($arrangement['eventnavn'])?></p>
                    </section>
                    <main id="arrangement_mainPåmeldt" onclick="lukkHamburgerMeny()">

                    <section class="p_section">
                    <?php for($i = 0; $i < count($påmeldtBrukere); $i++) {?>
                        <section class="påmeldteBrukere" onClick="location.href='profil.php?bruker=<?php echo($påmeldtBrukere[$i]['idbruker']) ?>'">
                            <?php
                            $hentBildeQ = "select hvor from brukerbilde, bilder, bruker where bilder.idbilder = brukerbilde.bilde and brukerbilde.bruker = " . $påmeldtBrukere[$i]['idbruker'] . " and brukerbilde.bruker = bruker.idbruker and bruker.brukertype != 4";
                            $hentBildeSTMT = $db->prepare($hentBildeQ);
                            $hentBildeSTMT->execute();
                            $brukerbilde = $hentBildeSTMT->fetch(PDO::FETCH_ASSOC);
                            $antallBilderFunnet = $hentBildeSTMT->rowCount();


                            if ($antallBilderFunnet != 0) {
                                $testPaa = $brukerbilde['hvor'];
                                // Tester på om filen faktisk finnes
                                if(file_exists("$lagringsplass/$testPaa")) {
                                    // Profilbilde som resultat av spørring
                                    if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {
                                        // Hvis vi finner et miniatyrbilde bruker vi det ?>
                                        <img id="profilPåmeldt" src="bilder/opplastet/thumb_<?php echo($brukerbilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                    <?php } else { ?>
                                        <img id="profilPåmeldt" src="bilder/opplastet/<?php echo($brukerbilde['hvor']) ?>" alt="Profilbilde til <?php echo($navn) ?>">
                                    <?php } ?>
                                <?php } else { ?>
                                    <img id="profilPåmeldt" src="bilder/profil.png" alt="Standard profilbilde">
                                <?php } ?>
                            <?php } else { ?>
                                <img id="profilPåmeldt" src="bilder/profil.png" alt="Standard profilbilde">
                            <?php } ?>
                            <?php if($påmeldtBrukere[$i]['brukertype'] == 4) { echo("<p class='p_bruker' style='font-style: italic;'>Avregistrert bruker"); } else { echo("<p class='p_bruker'>" . $påmeldtBrukere[$i]['brukernavn']); } ?></p>

                            <?php if($påmeldtBrukere[$i]['interessert'] == "Kanskje") {?>
                                <p class="påmeldtType" style="background-color: rgb(255, 191, 0);"><?php echo($påmeldtBrukere[$i]['interessert']) ?></p>
                            <?php } else if ($påmeldtBrukere[$i]['interessert'] == "Invitert") { ?>
                                <p class="påmeldtType" style="background-color: rgba(0, 128, 255);"><?php echo($påmeldtBrukere[$i]['interessert']) ?></p>
                            <?php } else { ?>
                                <p class="påmeldtType"><?php echo($påmeldtBrukere[$i]['interessert']) ?></p>
                            <?php }?>
                        </section>
                   
                    <?php }?>
                    </section>

                    <button id="PIbruker_tilbKnapp" onClick="location.href='arrangement.php?arrangement=<?php echo($_GET['arrangement'])?>'">Tilbake</button>
                    </main>

                <?php } else { 
                    // Del for å vise et spesifikt arrangement
                    // Henter bilde fra database utifra eventid
                    $hentBilde = "select hvor from eventbilde, bilder where eventbilde.event = " . $_GET['arrangement'] . " and eventbilde.bilde = bilder.idbilder";
                    $stmtBilde = $db->prepare($hentBilde);
                    $stmtBilde->execute();
                    $bilde = $stmtBilde->fetch(PDO::FETCH_ASSOC);
                    $antallBilderFunnet = $stmtBilde->rowCount();
                    // rowCount() returnerer antall resultater fra database, er dette null finnes det ikke noe bilde i databasen
                    ?> 
                    <main id="arrangement_main" style="margin-top: 6em;" onclick="lukkHamburgerMeny()"> 
                        <section id="arrangement_spes"> 
                                    
                        <!-- -----------------påklikket arrangement---------------------  -->
                        <section id="arrangement_omEvent">
                            <section id="argInf_meta">
                                <?php if ($antallBilderFunnet != 0) {
                                    // Tester på om filen faktisk finnes
                                    $testPaa = $bilde['hvor'];
                                    if(file_exists("$lagringsplass/$testPaa")) {  ?>  
                                        <!-- Hvis vi finner et bilde til arrangementet viser vi det -->
                                        <img id="arrangement_fullSizeBilde" src="bilder/opplastet/<?php echo($bilde["hvor"]) ?>" alt="Bilde av arrangementet">
                                    <?php } else { ?>
                                        <img id="arrangement_fullSizeBilde" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                    <?php } ?>
                                <?php } else { ?>
                                    <img id="arrangement_fullSizeBilde" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                <?php }

                                $interesserte = "select event_id, bruker_id, interessert from påmelding where not interessert='Kan ikke' and event_id=" . $_GET['arrangement'] . " and 
                                    påmelding.bruker_id NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                                $interesserteSTMT = $db->prepare($interesserte);
                                $interesserteSTMT->execute();
                                $antallInteresserte = $interesserteSTMT->rowCount();
                                
                                
                                // Henter personvern
                                $personvernQ = "select visfnavn, visenavn, visepost from preferanse where bruker = " . $arrangement['idbruker'];
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
                                
                                // Sjekker om svar fra DB ikke er false og bruker har valgt å vise epost
                                if(isset($personvernSender['visepost']) && $personvernSender['visepost'] == "1") {
                                    $kanViseEpost = true;
                                }
                                
                                // Tester på om vi kan vise fulle navnet, bare fornavn, bare etternavn eller kun brukernavn
                                if($kanViseFornavn == true && $kanViseEtternavn == false) {
                                    if(preg_match("/\S/", $arrangement['fnavn']) == 1) {
                                        $navn = $arrangement['fnavn'];  
                                    } else {
                                        $navn = $arrangement['brukernavn'];
                                    }
                                } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $arrangement['enavn']) == 1) {
                                        $navn = $arrangement['enavn'];  
                                    } else {
                                        $navn = $arrangement['brukernavn'];
                                    }
                                } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $arrangement['enavn']) == 1) {
                                        $navn = $arrangement['fnavn'] . " " . $arrangement['enavn'];  
                                    } else {
                                        $navn = $arrangement['brukernavn'];
                                    }
                                } else {
                                    $navn = $arrangement['brukernavn'];
                                }

                                
                                ?>

                                <form method="POST" id="arrangement_invitert" action="arrangement.php?arrangement=<?php echo($_GET['arrangement'])?>">
                                </form>

                                <form method="POST" id="arrangement_paamelding" action="arrangement.php?arrangement=<?php echo($_GET['arrangement'])?>">
                                    <?php if(isset($_SESSION['idbruker'])) {
                                        $hentPaameldteQ = "select bruker_id, interessert from påmelding where påmelding.bruker_id = " . $_SESSION['idbruker'] . " and event_id = " . $_GET['arrangement'];
                                        $hentPaameldteSTMT = $db->prepare($hentPaameldteQ);
                                        $hentPaameldteSTMT->execute();
                                        $paameldt = $hentPaameldteSTMT->fetch(PDO::FETCH_ASSOC);
                                        
                                        if(isset($paameldt['interessert'])) {
                                            if($paameldt['interessert'] == "Skal") { ?>
                                                <button id="arrangement_paameldt" name="avmeld" value="Paameldt" onmouseenter="visAvmeld('Avmeld')" onmouseout="visAvmeld('Skal')">Skal</button>
                                            
                                            <?php } else if ($paameldt['interessert'] == "Kanskje") { ?>
                                                <button id="arrangement_paameldt" name="avmeld" value="Paameldt" onmouseenter="visAvmeld('Avmeld')" onmouseout="visAvmeld('Kanskje')">Kanskje</button>
                                            
                                            <?php } else if ($paameldt['interessert'] == "Kan ikke") { ?>
                                                <button id="arrangement_paameldt" style="background-color: red; color: white;" name="avmeld" value="Paameldt" onmouseenter="visAvmeld('Avmeld')" onmouseout="visAvmeld('KanIkke')">Kan ikke</button>                                         
                                            
                                            <?php } else if ($paameldt['interessert'] == "Invitert") { ?>
                                                <button id="arrangement_paameld" form="arrangement_invitert" name="invitasjon" value="Godkjenn">Godkjenn</button>
                                                <button id="arrangement_avslaa" form="arrangement_invitert" name="invitasjon" value="Avslå">Avslå</button>
                         
                                            <?php } else { ?>
                                                <button class="arrangement_paameld" form="arrangement_paamelding" name="skal" value="Skal" >Skal</button>
                                                <button class="arrangement_paameld" form="arrangement_paamelding" name="kanskje" value="Kanskje" >Kanskje</button>
                                                <button class="arrangement_paameld" form="arrangement_paamelding" name="kanIkke" value="KanIkke" >Kan ikke</button> 
                         
                                            <?php } 
                                        } else { ?>
                                            <button class="arrangement_paameld" form="arrangement_paamelding" name="skal" value="Skal" >Skal</button>
                                            <button class="arrangement_paameld" form="arrangement_paamelding" name="kanskje" value="Kanskje" >Kanskje</button>
                                            <button class="arrangement_paameld" form="arrangement_paamelding" name="kanIkke" value="KanIkke" >Kan ikke</button>     
                                    <?php } ?>
                                        <input type="button" id="arrangement_inviterKnapp" onclick="bekreftMelding('arrangement_bekreftInviter')" value="Inviter">  
                                    <?php } ?>
                                </form>
                                
                                <section class="argInf_dato">
                                    <img class="arrangementInnhold_rFloatBilde" src="bilder/datoIkon.png" alt="Ikon for kalender">
                                    <h2>Dato</h2>
                                    <p id="arrangement_dato"><?php echo(substr($arrangement['tidspunkt'], 0, 10) . " kl: "); echo(substr($arrangement['tidspunkt'], 11, 5)) ?></p>
                                </section>
                                
                                <section class="argInf_sted">
                                    <img class="arrangementInnhold_rFloatBilde" src="bilder/stedIkon.png">
                                    <h2>Sted</h2>
                                    <?php 
                                        $dato = date_create($arrangement['tidspunkt']);
                                    ?>
                                    <!-- Lenke som leder til Google Maps med adresse -->
                                    <p class="arrangement_adresse"><a href="http://maps.google.com/maps?q=<?php echo($arrangement['veibeskrivelse'] . ", " . $arrangement['fylkenavn']);?>"><?php echo($arrangement['veibeskrivelse']) ?></a></p>
                                    <p class="arrangement_adresse"><?php echo($arrangement['fylkenavn']) ?> fylke</p>
                                </section>
                                <section class="argInf_interesserte">
                                    <img class="arrangementInnhold_rFloatBilde" src="bilder/interesserteIkon.png">
                                    <h2>Antall interesserte: <?php echo($antallInteresserte) ?></h2>
                                    
                                <form method="POST" id="arrangement_form_påmeldte" action="arrangement.php?arrangement=<?php echo($_GET['arrangement'])?>">
                                    <input type="submit" class="arrangement_paameld" name="paameldteBrukere"  value="Se påmeldte brukere">
                                </form>


                                <?php if(isset($_GET['invitert'])) { ?>
                                    <p id="mldOK">Invitasjon sendt</p>
                                <?php }  ?>
                            
                            </section>
                        </section>

                        <?php 
                        // Sjekker om brukeren er avregistrert
                        $sjekkBrukertypeQ = "select brukertype from bruker where idbruker = " . $arrangement['idbruker'];
                        $sjekkBrukertypeSTMT = $db -> prepare($sjekkBrukertypeQ);
                        $sjekkBrukertypeSTMT -> execute();
                        $brukertype = $sjekkBrukertypeSTMT -> fetch(PDO::FETCH_ASSOC);
                        ?>
                        
                        <section id="argInf_om">
                            <h1><?php echo($arrangement['eventnavn'])?></h1>
                            <h2>Beskrivelse</h2>
                            <p id="arrangement_tekst"><?php echo($arrangement['eventtekst'])?></p>
                            <h2>Arrangør</h2>
                            <?php if($brukertype['brukertype'] == 4) {echo("<p id='arrangement_navn' style='font-style: italic;'>Avregistrert bruker");} else {echo("<p id='arrangement_navn'>" . $navn); ?></p>
                            <?php if(isset($kanViseEpost) && $kanViseEpost == true) { ?>
                                <h2>Kontakt</h2>
                                <p id="arrangement_mail"><a href="mailto:<?php echo($arrangement['epost'])?>"><?php echo($arrangement['epost'])?></a></p>   
                            <?php } } ?>
                        </section>

                        <section class="arg_tilbInv_knapp">
                            <button id="arrangementValgt_tilbKnapp" onClick="location.href='arrangement.php'">Tilbake</button>
                        </section>
                        <?php 
                        if(isset($_SESSION['idbruker'])) {
                            $hentEierQ = "select idbruker from event where idbruker = " . $_SESSION['idbruker'] . " and idevent = " . $_GET['arrangement'];
                            $hentEierSTMT = $db->prepare($hentEierQ);
                            $hentEierSTMT->execute();
                            $arrangementEier = $hentEierSTMT->fetch(PDO::FETCH_ASSOC);

                            if ($arrangementEier != false || $_SESSION['brukertype'] == 1) { ?>
                                <input type="button" id="arrangement_slettKnapp" onclick="bekreftMelding('arrangement_bekreftSlett')" value="Slett dette arrangementet">
                                <section id="arrangement_bekreftSlett" style="display: none;">
                                    <section id="arrangement_bekreftSlettInnhold">
                                        <h2>Sletting</h2>
                                        <p>Er du sikker på av du vil slette dette arrangementet?</p>
                                        <form method="POST" action="arrangement.php?arrangement=<?php echo($_GET['arrangement'])?>">
                                            <button id="arrangement_slettDenne" name="slettDenne" value="<?php echo($_GET['arrangement']) ?>">Slett</button>
                                        </form>
                                        <button id="arrangement_avbrytKnapp" onclick="bekreftMelding('arrangement_bekreftSlett')">Avbryt</button>
                                    </section>
                                </section>
                            <?php } ?>
                            
                            <section id="arrangement_bekreftInviter" style="display: none;">
                                <section id="arrangement_bekreftInviterInnhold">
                                    <h2>Invitering</h2>
                                    <form method="POST" action="arrangement.php?arrangement=<?php echo($_GET['arrangement'])?>">
                                        <input name="brukernavn" id="arrangement_inviter_bruker" type="text" list="brukere" placeholder="Skriv inn brukernavn" title="Brukeren du ønsker å invitere" autofocus required>
                                        <datalist id="brukere">
                                            <?php 
                                            // Henter brukernavn fra database
                                            $hentNavnQ = "select idbruker, brukernavn from bruker where not exists(select * from påmelding where idbruker=bruker_id and event_id=" . $_GET['arrangement'] . ") and 
                                                idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                                            $hentNavnSTMT = $db->prepare($hentNavnQ);
                                            $hentNavnSTMT->execute();
                                            $liste = $hentNavnSTMT->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($liste as $brukernavn) { ?>
                                                <option value="<?php echo($brukernavn['brukernavn'])?>"><?php echo($brukernavn['brukernavn'])?></option>
                                            <?php } ?>
                                        </datalist>
                                        <button id="arrangement_inviterKnappVindu" name="inviterTil" value="<?php echo($_GET['arrangement']) ?>">Inviter</button>
                                    </form>
                                    <button id="arrangement_inviterAvbrytKnapp" onclick="bekreftMelding('arrangement_bekreftInviter')">Avbryt</button>
                                </section>
                            </section>
                        <?php } ?>
                    </section>
                    <?php } ?>
                </section>
            <?php  } else if (isset($_GET['nyarrangement']) && ($_SESSION['brukertype'] == 2 || $_SESSION['brukertype'] == 1)) { ?>      
            
                <header class="arrangement_header" onclick="lukkHamburgerMeny()">
                    <h1>Nytt arrangement</h1>
                </header>

                <main id="arrangement_mainNy" onclick="lukkHamburgerMeny()">
                    <article id="arrangement_arrangementNy">
                        <form method="POST" action="arrangement.php" enctype="multipart/form-data">
                            <h2>Tittel</h2>
                            <input id="arrangement_inputTittel" type="text" maxlength="45" name="tittel" value="<?php echo($input_tittel) ?>" placeholder="Skriv inn tittel" autofocus required>
                            <h2>Innhold</h2>
                            <textarea id="arrangement_inputInnhold" maxlength="1000" name="innhold" rows="5" cols="35" placeholder="Skriv litt hva arrangementet handler om" required><?php echo($input_innhold) ?></textarea>
                            <h2>Dato</h2>
                            <input id="arrangement_inputDato" type="datetime-local" name="tidspunkt" value="<?php echo($input_tidspunkt) ?>" required>
                            <h2>Adresse</h2>
                            <input id="arrangement_inputAdresse" type="text" maxlength="250" name="adresse" value="<?php echo($input_adresse) ?>" placeholder="Oppgi adresse" required>
                            <select id="arrangement_inputFylke" name="fylke" required>
                                <?php if($input_fylke != "") { ?><option value="<?php echo($input_fylke) ?>"><?php echo($input_fylke) ?></option>
                                <?php } else { ?>
                                    <option value="">Velg fylke</option>
                                <?php }
                                    // Henter fylker fra database
                                    $hentFylke = "select fylkenavn from fylke order by fylkenavn ASC";
                                    $stmtFylke = $db->prepare($hentFylke);
                                    $stmtFylke->execute();
                                    $fylkeListe = $stmtFylke->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($fylkeListe as $fylke) { ?>
                                        <option value="<?php echo($fylke['fylkenavn'])?>"><?php echo($fylke['fylkenavn'])?></option>
                                <?php } ?>
                            </select>
                            <h2>Bilde</h2>
                            <input type="file" name="bilde" id="bilde" accept=".jpg, .jpeg, .png">

                            <a href="arrangement.php" id="arrangement_lenke_knapp">Tilbake til arrangementer</a> 
                            <input id="arrangement_submitNy" type="submit" name="publiserArrangement" value="Opprett Arrangement">
                        </form> 
                    </article>
           <?php } else {

                // Del for å vise alle arrangement 
                $hentAlleArr = "select idevent, eventnavn, tidspunkt, veibeskrivelse, event.idbruker as idbruker, brukernavn, fnavn, enavn, fylkenavn, brukertype from event, bruker, fylke 
                    where tidspunkt >= NOW() and event.idbruker = bruker.idbruker and event.fylke = fylke.idfylke and 
                        event.idbruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))
                    order by tidspunkt asc";
            
                $stmtArr = $db->prepare($hentAlleArr);
                $stmtArr->execute();
                $resArr = $stmtArr->fetchAll(PDO::FETCH_ASSOC); 
                
                // Variabel som brukes til å fortelle når vi kan avslutte side_sok
                $avsluttTag = 0;
                $antallSider = 0;

                $resAntall = $stmtArr->rowCount(); 
                ?>
                
                <header class="arrangement_header" onclick="lukkHamburgerMeny()">
                    <h1>Arrangementer</h1>
                </header>
                <main id="arrangement_main" onclick="lukkHamburgerMeny()">
                    <section id="arrangement_redpanel">
                        <?php if(isset($_SESSION['brukertype']) && ($_SESSION['brukertype'] == 2 || $_SESSION['brukertype'] == 1)) { ?>
                        <a href="arrangement.php?nyarrangement" tabindex="-1"><p>Nytt arrangement</p></a>
                        <a href="arrangement.php?nyarrangement" tabindex="7">
                            <img src="bilder/plussIkon.png" alt="Plussikon for å opprette nytt arrangement" tabindex="-1">
                        </a>
                        <?php } ?>
                    </section>
                    <?php if(isset($_GET['slettingok'])) { ?> <p id="mldOK">Du har slettet arrangementet</p> <?php } ?>
                    <?php if(isset($_GET['slettingfeil'])) { ?> <p id="mldFEIL">Kunne ikke slette arrangement</p> <?php } ?>
                    
                <?php if ($resAntall > 0 ) { ?>
                    <?php for ($j = 0; $j < count($resArr); $j++) {
                        // Henter personvern
                        $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $resArr[$j]['idbruker'];
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
                        }

                        // Hvis rest av $j delt på 8 er 0, start section (Ny side)
                        if ($j % 8 == 0) { ?>
                            <section class="arrangement_hovedsection">
                        <?php $antallSider++; } $avsluttTag++; ?>
                        <section class="arrangement_ressection" onClick="location.href='arrangement.php?arrangement=<?php echo($resArr[$j]['idevent']) ?>'" tabindex = <?php echo($tabindex); $tabindex++; ?>>
                            <figure class="arrangement_infoBoks">

                                <?php // Henter bilde til arrangementet
                                $hentArrBilde = "select hvor from bilder, eventbilde where eventbilde.event = " . $resArr[$j]['idevent'] . " and eventbilde.bilde = bilder.idbilder";
                                $stmtArrBilde = $db->prepare($hentArrBilde);
                                $stmtArrBilde->execute();
                                $resBilde = $stmtArrBilde->fetch(PDO::FETCH_ASSOC);
                                
                                if (!$resBilde) { ?>
                                    <!-- Standard arrangementbilde om arrangør ikke har lastet opp noe enda -->
                                    <img class="arrangement_BildeBoks" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                <?php } else {
                                    // Tester på om filen faktisk finnes
                                    $testPaa = $resBilde['hvor'];
                                    if(file_exists("$lagringsplass/$testPaa")) {  
                                        //Arrangementbilde som resultat av spørring
                                        if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {  ?> 
                                            <!-- Hvis vi finner et miniatyrbilde bruker vi det -->
                                            <img class="arrangement_BildeBoks" src="bilder/opplastet/thumb_<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($resArr[$j]['eventnavn'])?>">
                                        <?php } else { ?>
                                            <img class="arrangement_BildeBoks" src="bilder/opplastet/<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($resArr[$j]['eventnavn'])?>">
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img class="arrangement_BildeBoks" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                    <?php }
                                } ?>
                            </figure>

                            <p class="arrangement_tidspunkt">
                                <?php 
                                    $dato = date_create($resArr[$j]['tidspunkt']);
                                    echo(date_format($dato,"d/m/Y"));
                                ?>
                            </p>
                            <img class="arrangement_rFloatBilde" src="bilder/datoIkon.png" alt="Ikon for kalender">
                            <p class="arrangement_fylke"><?php echo($resArr[$j]['fylkenavn'])?></p>
                            <img class="arrangement_rFloatBilde" src="bilder/stedIkon.png">
                            <img class="arrangement_navn" src="bilder/brukerIkonS.png">
                            <?php if($resArr[$j]['brukertype'] == 4) {echo("<p class='arrangement_navn' style='font-style: italic;'>Avregistrert bruker");} else {echo("<p class='arrangement_navn'>" . $navn);} ?></p>
                            <h2><?php echo($resArr[$j]['eventnavn'])?></h2>
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

                <section id="arrangement_bunnSection">
                    <?php if ($antallSider > 1) {?>
                        <p id="sok_antSider">Antall sider: <?php echo($antallSider) ?></p>
                        <button type="button" id="arrangement_tilbKnapp" onclick="visForrigeSide('arrangement_hovedsection', 'arrangement_tilbKnapp', 'arrangement_nesteKnapp')">Forrige</button>
                        <button type="button" id="arrangement_nesteKnapp" onclick="visNesteSide('arrangement_hovedsection', 'arrangement_tilbKnapp', 'arrangement_nesteKnapp')">Neste</button>
                    <?php } ?>
                </section>

            <?php }?>
            <!-- Håndtering av feilmeldinger -->

            <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($arrangement_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                <section id="mldFEIL_innhold">
                    <p id="mldFEIL"><?php echo($arrangement_melding) ?></p>  
                    <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                    <button id="mldFEIL_knapp" autofocus>Lukk</button>
                </section>  
            </section>
        </main>
        <?php include("inkluderes/footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Robin Kleppang, Ajdin Bajrovic siste gang endret 06.03.2020 -->
<!-- Denne siden er kontrollert av Aron Snekkestad, siste gang 17.04.2020 -->
</html>