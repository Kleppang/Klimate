<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");


// Browser må validere cache med server før cached kopi kan benyttes
// Dette gjør at man kan gå frem og tilbake i adminpanelet uten at man får ERR_CACHE_MISS
header("Cache-Control: no cache");

// Forsikrer seg om kun tilgang for administrator
if (!isset($_SESSION['idbruker'])) {
    // En utlogget bruker har forsøkt å nå adminpanelet
    $_SESSION['default_melding'] = "Du må logge inn før du kan se denne siden";
    header("Location: default.php");
} else if ($_SESSION['brukertype'] != '1') {
    // En innlogget bruker som ikke er administrator har forsøkt å åpne adminpanelet, loggfører dette
    $leggTilMisbrukQ = "insert into misbruk(tekst, bruker) values('Oppdaget misbruk, forsøkte nå adminpanel', :bruker)";
    $leggTilMisbrukSTMT = $db -> prepare($leggTilMisbrukQ);
    $leggTilMisbrukSTMT -> bindparam(":bruker", $_SESSION['idbruker']);
    $leggTilMisbrukSTMT -> execute();

    // Sender melding til alle administratorere 

    $hentAdminQ = "select idbruker from bruker where brukertype = 1";
    $hentAdminSTMT = $db -> prepare($hentAdminQ);
    $hentAdminSTMT -> execute();
    $administratorer = $hentAdminSTMT -> fetchAll(PDO::FETCH_ASSOC);

    foreach ($administratorer as $admin) {
        $nyMeldingQ = "insert into melding(tittel, tekst, tid, lest, sender, mottaker) values('Oppdaget misbruk', 'Automatisk misbruk oppdaget, " . $_SESSION['brukernavn'] . " med ID " . $_SESSION['idbruker'] . " forsøkte nå Admin-panelet.', NOW(), 0, :sender, :mottaker)";
        $nyMeldingSTMT = $db->prepare($nyMeldingQ);
        $nyMeldingSTMT -> bindparam(":sender", $admin['idbruker']);
        $nyMeldingSTMT -> bindparam(":mottaker", $admin['idbruker']);
        $nyMeldingSTMT->execute();
    }

    session_destroy();
    session_start();
    $_SESSION['default_melding'] = "Du kan ikke se denne siden";
    header("Location: default.php");
}

$input_brukernavn = "";
$input_epost = "";
if (isset($_SESSION['input_brukernavn'])) {
    $input_brukernavn = $_SESSION['input_brukernavn'];
    $input_epost = $_SESSION['input_epost'];
    unset($_SESSION['input_brukernavn']);
    unset($_SESSION['input_epost']);
}

$admin_melding = "";
if(isset($_SESSION['admin_melding'])) {
    $admin_melding = $_SESSION['admin_melding'];
    unset($_SESSION['admin_melding']);
}

if (isset($_POST['subRegistrering'])) {
    $_SESSION['input_brukernavn'] = $_POST['brukernavn'];
    $_SESSION['input_epost'] = $_POST['epost'];
    $_SESSION['admin_melding'] = "";
    // Tester på om passordene er like
    if ($_POST['passord'] == $_POST['passord2']) {
        // Tester på om administrator har fyllt ut alle de obligatoriske feltene
        if ($_POST['brukernavn'] != "" && $_POST['epost'] != "") {
            // Tester på om en gyldig epost ("NAVN@NAVN.DOMENE") er oppgitt
            if (filter_var($_POST["epost"], FILTER_VALIDATE_EMAIL)) {
                try {
                    $br = filter_var($_POST['brukernavn'], FILTER_SANITIZE_STRING);

                    // Hvis ikke bruker har forsøkt å skrive inn HTML kode i brukernavn-feltet så fortsetter vi
                    if($br == $_POST['brukernavn']) {
                        $pw = $_POST['passord'];
                        if($_POST['brukertype'] != "4") {
                            $btype = $_POST['brukertype'];
                        } else {
                            $btype = "3";
                        }

                        // Validering av passordstyrke, server validering
                        $storebokstaver = preg_match('@[A-Z]@', $pw);
                        $smaabokstaver = preg_match('@[a-z]@', $pw);
                        $nummer = preg_match('@[0-9]@', $pw);

                        if ($pw == "") {
                            // Ikke noe passord skrevet
                            $_SESSION['admin_melding'] = "Oppgi et passord";
                        } else if (!$storebokstaver || !$smaabokstaver || !$nummer || strlen($pw) < 8) {
                            // Ikke tilstrekkelig passord skrevet
                            $_SESSION['admin_melding'] = "Minimum 8 tegn, 1 liten og 1 stor bokstav";
                        } else {
                            // Sjekker om brukernavn er opptatt (Brukes så lenge brukernavn ikke er satt til UNIQUE i db)
                            $lbr = strtolower($_POST['brukernavn']);
                            $sjekkbnavn = "select lower(brukernavn) as brukernavn from bruker where lower(brukernavn)='" . $lbr . "'";
                            $sjekket = $db->prepare($sjekkbnavn);
                            $sjekket->execute();
                            $testbnavn = $sjekket->fetch(PDO::FETCH_ASSOC);

                            // Hvis resultatet over er likt det bruker har oppgitt som brukernavn stopper vi og advarer bruker om at brukernavnet er allerede tatt
                            if (!isset($testbnavn['brukernavn']) || $testbnavn['brukernavn'] != $lbr) {
                                // OK, vi forsøker å registrere bruker
                                // Salter passorder
                                $kombinert = $salt . $pw;
                                // Krypterer saltet passord
                                $spw = sha1($kombinert);

                                $opprettBrukerQ = "insert into bruker(brukernavn, passord, epost, brukertype) VALUES(:bruker, :passord, :epost, :brukertype)";
                                $opprettBrukerSTMT = $db -> prepare($opprettBrukerQ);
                                $opprettBrukerSTMT -> bindparam(":bruker", $_POST['brukernavn']);
                                $opprettBrukerSTMT -> bindparam(":passord", $spw);
                                $opprettBrukerSTMT -> bindparam(":epost", $_POST['epost']);
                                $opprettBrukerSTMT -> bindparam(":brukertype", $btype);
                                $opprettBrukerSTMT -> execute();

                                // Fikk lagt til bruker, fortsetter med sjekk på preferanser
                                if ($opprettBrukerSTMT) {
                                
                                    $opprettetID = $db -> lastInsertId();

                                    // Fjerner session variable for brukerinput om ingen feil oppstår
                                    unset($_SESSION['input_brukernavn']);
                                    unset($_SESSION['input_epost']);

                                    // Sjekker på om bruker har registrert preferanser
                                    $sjekkPrefQ = "select idpreferanse from preferanse where bruker = " . $_SESSION['idbruker'];
                                    $sjekkPrefSTMT = $db->prepare($sjekkPrefQ);
                                    $sjekkPrefSTMT->execute();
                                    $resPref = $sjekkPrefSTMT->fetch(PDO::FETCH_ASSOC); 

                                    // Bruker har ikke preferanser, oppretter de
                                    // Variabelen $personvern kommer fra innstillinger
                                    if(!$resPref) {
                                        $opprettPrefQ = "insert into preferanse(visfnavn, visenavn, visepost, visinteresser, visbeskrivelse, vistelefonnummer, bruker) values('" . 
                                                            $personvern[0] . "', '" . $personvern[1] . "', '" . $personvern[2] . "', '" . $personvern[3] . "', '" . $personvern[4] . "', '" . $personvern[5] . "', " .
                                                                $_SESSION['idbruker'] . ")";

                                        $opprettPrefSTMT = $db->prepare($opprettPrefQ);
                                        $opprettPrefSTMT->execute();
                                    }

                                    header("location: administrator.php?bruker=" . $opprettetID);
                                }
                            } else {
                                // Brukernavnet er tatt
                                $_SESSION['admin_melding'] = "Brukernavnet er opptatt";
                                header("Location: administrator.php?nybruker");
                            }
                        }
                    } else {
                        // Ugyldig brukernavn
                        $_SESSION['admin_melding'] = "Brukernavnet er ugyldig";
                        header("Location: administrator.php?nybruker");
                    }
                }
                catch (PDOException $ex) {
                    if ($ex->getCode() == 23000) {
                        // 23000, Duplikat, tenkes brukt til brukernavn da det ønskes å være satt UNIQUE i db
                        $_SESSION['admin_melding'] = "Brukernavnet er opptatt";
                        header("Location: administrator.php?nybruker");
                    } else if ($ex->getCode() == '42S22') {
                        // 42S22, Kolonne eksisterer ikke
                        $_SESSION['admin_melding'] = "Systemfeil, vennligst oppgi følgende kode til administrator: 42S22";
                        header("Location: administrator.php?nybruker");
                    }
                } 
            } else {
                // Feilmelding 7, bruker har oppgitt en ugyldig epost
                $_SESSION['admin_melding'] = "Eposten er ikke gyldig";
                header("Location: administrator.php?nybruker");
            }
        } else {
            // Feilmelding 6, bruker har ikke skrevet noe i ett av de obligatoriske feltene
            $_SESSION['admin_melding'] = "Fyll ut alle feltene";
            header("Location: administrator.php?nybruker");
        }
    } else {
        // Feilmelding 2 = passord ikke like
        $_SESSION['admin_melding'] = "Passordene er ikke like";
        header("Location: administrator.php?nybruker");
    }
}

if(isset($_GET['slettregel'])) {
    $slettregelQ = "delete from regel where idregel = :regelen";
    $slettregelSTMT = $db -> prepare($slettregelQ);
    $slettregelSTMT->bindParam(':regelen', $_GET['slettregel']);
    $slettregelSTMT->execute();

    $slettet = $slettregelSTMT->rowCount();

    if($slettet == 0) {
        $_SESSION['admin_melding'] = "Kunne ikke slette regel";
        header("Location: administrator.php");
    } else {
        header("location: administrator.php");
    }
    $filpath = "generert/regler.html";
    // Bruker unlink() function for å slette filen regler.html
    unlink($filpath);
}

if(isset($_POST['advaring'])) {
    if($_POST['advaring'] != "") {
        if($_POST['advartbruker'] != "") {
            // Sjekker om brukeren er av type administrator, tillater ikke administratorer å utføre handling på en administrator
            $sjekkAdminQ = "select idbruker, brukertype from bruker where idbruker = :bruker and brukertype = 1";
            $sjekkAdminSTMT = $db -> prepare($sjekkAdminQ);
            $sjekkAdminSTMT -> bindparam(":bruker", $_POST['advartbruker']);
            $sjekkAdminSTMT -> execute();
            $resAdmin = $sjekkAdminSTMT->fetch(PDO::FETCH_ASSOC); 

            if(!$resAdmin) {
                // Bruker er ikke administrator
                $advarBrukerQ = "insert into advarsel(advarseltekst, bruker, administrator) values(:tekst, :bruker, :admin)";
                $advarBrukerSTMT = $db -> prepare($advarBrukerQ);
                $advarBrukerSTMT -> bindparam(":tekst", $_POST['advaring']);
                $advarBrukerSTMT -> bindparam(":bruker", $_POST['advartbruker']);
                $advarBrukerSTMT -> bindparam(":admin", $_SESSION['idbruker']);
                $advarBrukerSTMT -> execute();

                if($advarBrukerSTMT) {
                    $_SESSION['admin_melding'] = "Bruker advart";
                    header("location: administrator.php?bruker=" . $_POST['advartbruker']);
                } else {
                    $_SESSION['admin_melding'] = "Feil oppsto ved advaring av bruker";
                    header("Location: administrator.php?bruker=" . $_POST['advartbruker']);
                }
            } else {
                $_SESSION['admin_melding'] = "Du kan ikke advare en administrator";
                header("Location: administrator.php?bruker=" . $_POST['advartbruker']);
            }
        }
    }
}

if(isset($_POST['ekskludering'])) {
    if($_POST['ekskludering'] != "") {
        if($_POST['ekskludertbruker'] != "") {
            // Sjekker om brukeren er av type administrator, tillater ikke administratorer å utføre handling på en administrator
            $sjekkAdminQ = "select idbruker, brukertype from bruker where idbruker = :bruker and brukertype = 1";
            $sjekkAdminSTMT = $db -> prepare($sjekkAdminQ);
            $sjekkAdminSTMT -> bindparam(":bruker", $_POST['ekskludertbruker']);
            $sjekkAdminSTMT -> execute();
            $resAdmin = $sjekkAdminSTMT->fetch(PDO::FETCH_ASSOC); 

            if(!$resAdmin) {
                // Bruker er ikke administrator, sjekker om bruker allerede er utestengt
                $sjekkTidQ = "select datotil from eksklusjon where bruker = :bruker and (datotil is null or datotil > NOW())";
                $sjekkTidSTMT = $db -> prepare($sjekkTidQ);
                $sjekkTidSTMT -> bindparam(":bruker", $_POST['ekskludertbruker']);
                $sjekkTidSTMT -> execute();

                $antTid = $sjekkTidSTMT->rowCount(); 

                if($antTid == 0) {
                    if($_POST['datotil'] != "") {
                        if($_POST['datotil'] > date("Y-m-d")) {
                            $ekskluderBrukerQ = "insert into eksklusjon(grunnlag, bruker, administrator, datofra, datotil) values(:tekst, :bruker, :admin, NOW(), :datotil)";
                            $ekskluderBrukerSTMT = $db -> prepare($ekskluderBrukerQ);
                            $ekskluderBrukerSTMT -> bindparam(":datotil", $_POST['datotil']);
                        } else {
                            $_SESSION['admin_melding'] = "Du kan bare ekskludere en bruker fremover i tid";
                            header("Location: administrator.php?bruker=" . $_POST['ekskludertbruker']);
                        }
                    } else {
                        $ekskluderBrukerQ = "insert into eksklusjon(grunnlag, bruker, administrator, datofra) values(:tekst, :bruker, :admin, NOW())";
                        $ekskluderBrukerSTMT = $db -> prepare($ekskluderBrukerQ);
                    }
    
                    $ekskluderBrukerSTMT -> bindparam(":tekst", $_POST['ekskludering']);
                    $ekskluderBrukerSTMT -> bindparam(":bruker", $_POST['ekskludertbruker']);
                    $ekskluderBrukerSTMT -> bindparam(":admin", $_SESSION['idbruker']);
                    $ekskluderBrukerSTMT -> execute();
    
                    if($ekskluderBrukerSTMT) {
                        $_SESSION['admin_melding'] = "Bruker utestengt";
                        header("Location: administrator.php?bruker=" . $_POST['ekskludertbruker']);
                    } else {
                        $_SESSION['admin_melding'] = "Feil oppsto ved utestenging av bruker";
                        header("Location: administrator.php?bruker=" . $_POST['ekskludertbruker']);
                    }
                } else {
                    $_SESSION['admin_melding'] = "Denne brukeren er allerede utestengt";
                    header("Location: administrator.php?bruker=" . $_POST['ekskludertbruker']);
                }
            } else {
                $_SESSION['admin_melding'] = "Du kan ikke utestenge en administrator";
                header("Location: administrator.php?bruker=" . $_POST['ekskludertbruker']);
            }
        }
    }
}



if(isset($_POST['regRegistrering'])) {
    if($_POST['regTekst'] != "" && strlen($_POST['regTekst']) <= 255) {
        $nyRegelQ = "insert into regel(regeltekst, idbruker) values(:tekst, :bruker)";
        $nyRegelSTMT = $db -> prepare($nyRegelQ);
        $nyRegelSTMT -> bindparam(":tekst", $_POST['regTekst']);
        $nyRegelSTMT -> bindparam(":bruker", $_SESSION['idbruker']);
        $nyRegelSTMT -> execute();

        if($nyRegelSTMT) {
            header("Location: administrator.php");
        } else {
            $_SESSION['admin_melding'] = "Feil oppsto ved oppretting av regel";
            header("Location: administrator.php?nyregel");
        }
    } else {
        $_SESSION['admin_melding'] = "Ingen tekst oppgitt eller regel for lang";
        header("Location: administrator.php?nyregel");
    }
    $filpath = "generert/regler.html";
    // Bruker unlink() function for å slette filen regler.html
    unlink($filpath);
}


if(isset($_POST['slettHandling'])) {
    $slettHandlingQ = "";

    if($_POST['handling'] == "misbruk") {
        $slettHandlingQ = "delete from misbruk where idmisbruk = :id";

    } else if($_POST['handling'] == "advarsel") {
        $slettHandlingQ = "delete from advarsel where idadvarsel = :id";

    } else if($_POST['handling'] == "eksklusjon") {
        $slettHandlingQ = "delete from eksklusjon where ideksklusjon = :id";

    } else {
        $_SESSION['admin_melding'] = "Kunne ikke finne handlingen";
        header("Location: administrator.php?bruker=" . $_GET['bruker']);
    }

    $slettHandlingSTMT = $db -> prepare($slettHandlingQ);
    $slettHandlingSTMT -> bindparam(":id", $_POST['slettHandling']);
    $slettHandlingSTMT -> execute();
    $antSlettet = $slettHandlingSTMT -> rowCount();

    if($antSlettet > 0) {
        header("Location: administrator.php?bruker=" . $_GET['bruker']);
    } else {
        $_SESSION['admin_melding'] = "Kunne ikke slette handlingen";
        header("Location: administrator.php?bruker=" . $_GET['bruker']);
    }
}

if(isset($_POST['endreBrukertype'])) {
    if($_POST['endreBrukertype'] >= 1 && $_POST['endreBrukertype'] <= 3) {
        // Endrer brukertypen til valgt bruker
        $oppdaterBTypeQ = "update bruker set brukertype = :type where idbruker = :bruker";
        $oppdaterBTypeSTMT = $db -> prepare($oppdaterBTypeQ);
        $oppdaterBTypeSTMT -> bindparam(":type", $_POST['endreBrukertype']);
        $oppdaterBTypeSTMT -> bindparam(":bruker", $_GET['bruker']);
        $oppdaterBTypeSTMT -> execute();
        header("Location: administrator.php?bruker=" . $_GET['bruker']);
    }
}

// Tabindex, starter på 27 da dette er det maksimale antallet med elementer før første bruker
$tabindex = 27;

?>
<!DOCTYPE html>
<html lang="no">

    <head>
        <!-- Setter riktig charset -->
        <meta charset="UTF-8">
        <!-- Legger til viewport -->
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Setter tittelen på prosjektet -->
        <title>Adminpanel</title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="admin_body" onclick="lukkMelding('mldFEIL_boks')" onload="adminTabbing()">
        <?php include("inkluderes/navmeny.php") ?>

        <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
        <!-- Kan ikke legge denne direkte i body -->
        <header id="admin_header" onclick="lukkHamburgerMeny()">
            <!-- Overskrift på siden -->
            <h1 id="admin_overskrift">Adminpanel</h1>
            <img id="admin_hovedmeny_ikon" src="bilder/pilNIkon.png" alt="Ikon for pil ned" onclick="admHovedmeny()">
        </header>
        <main id="admin_main" onclick="lukkHamburgerMeny()">

            <form method="GET" id="admin_form" action="administrator.php">
            </form>
            <form method="GET" id="rapport_form" action="administrator.php">
            </form>

            <section id="admin_hovedmeny">
                <button name="oversikt" tabindex="15" form="admin_form">Oversikt</button>
                <button id="admin_adm_knapp" tabindex="16" onclick="admMeny()">Administrering</button>
                <section id="admin_adm_delmeny" style="display: none;">
                    <button name="administrering" tabindex="17" form="admin_form" value="Alle brukere">Alle brukere</button>
                    <button name="administrering" tabindex="18" form="admin_form" value="Misbruk">Misbruk</button>
                    <button name="administrering" tabindex="19" form="admin_form" value="Administratorer">Administratorer</button>
                </section>
                <img src="bilder/rapportIkon.png" id="admin_rap_ikon" alt="Ikon for pil ned">
                <button id="admin_rap_knapp" tabindex="20" onclick="rapMeny()">Rapporter</button>
                <section id="admin_rap_delmeny" style="display: none;">
                    <button name="rapporter" tabindex="21" form="rapport_form" value="Alle brukere">Alle brukere</button>
                    <button name="rapporter" tabindex="22" form="rapport_form" value="Eksklusjoner">Eksklusjoner</button>
                    <button name="rapporter" tabindex="23" form="rapport_form" value="Advarsler">Advarsler</button>
                </section>
                <button name="nybruker" tabindex="24" form="admin_form">Opprett ny bruker</button>
                <button name="nyregel" tabindex="25" form="admin_form">Opprett ny regel</button>
            </section>

            <?php 
            if(isset($_GET['administrering'])) { 
                // Administrering ?>
                <h2 id="admin_underskrift"><?php echo($_GET['administrering']); ?></h2>
            
                <form method="GET" id="bruker_form" action="administrator.php">
                    <input type="hidden" id="bruker_form_verdi" name="bruker" value="">
                </form>

                <input type="text" id="admin_sok" tabindex="26" onkeyup="adminpanelSok()" placeholder="Søk etter navn..">

                <?php if($_GET['administrering'] == "Alle brukere") {
                    $hentBrukereQ = "select idbruker, brukernavn, fnavn, enavn, epost, brukertype.brukertypenavn as brukertypenavn from bruker, brukertype where bruker.brukertype = brukertype.idbrukertype order by brukernavn";
                    $hentBrukereSTMT = $db->prepare($hentBrukereQ);
                    $hentBrukereSTMT -> execute();
                    $brukere = $hentBrukereSTMT -> fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <table id="admin_allebrukere_table">
                        <thead>
                            <tr>
                                <th id="admin_allebrukere_bruker">BRUKERNAVN</th>
                                <th id="admin_allebrukere_info">INFO</th>
                                <th id="admin_allebrukere_type">TYPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < count($brukere); $i++) { 
                                if($i < 8) { ?>
                                    <tr class="admin_allebrukere_rad" title="Vis denne brukeren" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($brukere[$i]['idbruker']) ?>)">
                                        <td class="admin_allebrukere_allebruker"><?php echo($brukere[$i]['brukernavn'])?></td>
                                        <td class="admin_allebrukere_allenavn">Navn: <?php if(isset($brukere[$i]['fnavn'])) {echo($brukere[$i]['fnavn'] . " "); if(isset($brukere[$i]['enavn'])) {echo($brukere[$i]['enavn']);}} else {echo("Ikke oppgitt");} ?></td>
                                        <td class="admin_allebrukere_alleepost">Epost: <?php if(isset($brukere[$i]['epost'])) {echo($brukere[$i]['epost']);} else {echo("Ikke oppgitt");}?></td>
                                        <td class="admin_allebrukere_alletype"><?php if(isset($brukere[$i]['brukertypenavn'])) {echo($brukere[$i]['brukertypenavn']);}?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr class="admin_allebrukere_rad" style="display: none" title="Vis denne brukeren" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($brukere[$i]['idbruker']) ?>)">
                                        <td class="admin_allebrukere_allebruker"><?php echo($brukere[$i]['brukernavn'])?></td>
                                        <td class="admin_allebrukere_allenavn">Navn: <?php if(isset($brukere[$i]['fnavn'])) {echo($brukere[$i]['fnavn'] . " "); if(isset($brukere[$i]['enavn'])) {echo($brukere[$i]['enavn']);}} else {echo("Ikke oppgitt");} ?></td>
                                        <td class="admin_allebrukere_alleepost">Epost: <?php if(isset($brukere[$i]['epost'])) {echo($brukere[$i]['epost']);} else {echo("Ikke oppgitt");}?></td>
                                        <td class="admin_allebrukere_alletype"><?php if(isset($brukere[$i]['brukertypenavn'])) {echo($brukere[$i]['brukertypenavn']);}?></td>
                                    </tr>
                                <?php }
                            $tabindex++; 
                            }
                            if($i > 8) { ?>
                                <button id="admin_allebrukere_knapp" onclick="visFlereBrukere()">Vis flere</button>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else if($_GET['administrering'] == "Misbruk") {
                    $hentMisbrukQ = "select tekst, bruker, brukernavn from misbruk, bruker where bruker = idbruker order by idmisbruk desc";
                    $hentMisbrukSTMT = $db->prepare($hentMisbrukQ);
                    $hentMisbrukSTMT -> execute();
                    $misbruk = $hentMisbrukSTMT -> fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <table id="admin_allebrukere_table">
                        <thead>
                            <tr>
                                <th id="admin_allebrukere_misbruk">OPPDAGET MISBRUK</th>
                                <th id="admin_allebrukere_misbruknavn">BRUKERNAVN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < count($misbruk); $i++) { 
                                if($i < 8) { ?>
                                    <tr class="admin_allebrukere_rad" title="Vis denne brukeren" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($misbruk[$i]['bruker']) ?>)">
                                        <td class="admin_allebrukere_allemisbruk"><?php echo($misbruk[$i]['tekst']) ?></td>
                                        <td class="admin_allebrukere_allemisbruknavn"><?php echo($misbruk[$i]['brukernavn']) ?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr class="admin_allebrukere_rad" style="display: none" title="Vis denne brukeren" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($misbruk[$i]['bruker']) ?>)">
                                        <td class="admin_allebrukere_allemisbruk"><?php echo($misbruk[$i]['tekst']) ?></td>
                                        <td class="admin_allebrukere_allemisbruknavn"><?php echo($misbruk[$i]['brukernavn']) ?></td>
                                    </tr>
                                <?php }
                            $tabindex++; 
                            } 
                            if($i > 8) { ?>
                                <button id="admin_allebrukere_knapp" onclick="visFlereBrukere()">Vis flere</button>
                            <?php } ?>
                        </tbody>
                    </table>

                <?php } else if($_GET['administrering'] == "Administratorer") {
                    $hentAdministratorerQ = "select idbruker, brukernavn, fnavn, enavn, epost, telefonnummer, brukertype.brukertypenavn as brukertypenavn from bruker, brukertype where bruker.brukertype = brukertype.idbrukertype and bruker.brukertype = 1 order by brukernavn";
                    $hentAdministratorerSTMT = $db->prepare($hentAdministratorerQ);
                    $hentAdministratorerSTMT -> execute();
                    $administratorer = $hentAdministratorerSTMT -> fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <table id="admin_allebrukere_table">
                        <thead>
                            <tr>
                                <th id="admin_allebrukere_bruker">BRUKERNAVN</th>
                                <th id="admin_allebrukere_info">INFO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < count($administratorer); $i++) { 
                                if($i < 8) { ?>
                                    <tr class="admin_allebrukere_rad" title="Vis denne administratoren" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($administratorer[$i]['idbruker']) ?>)">
                                        <td class="admin_allebrukere_allebruker"><?php echo($administratorer[$i]['brukernavn'])?></td>
                                        <td class="admin_allebrukere_allenavn">Navn: <?php if(isset($administratorer[$i]['fnavn'])) {echo($administratorer[$i]['fnavn'] . " "); if(isset($administratorer[$i]['enavn'])) {echo($administratorer[$i]['enavn']);}} else {echo("Ikke oppgitt");} ?></td>
                                        <td class="admin_allebrukere_alleepost">Epost: <?php if(isset($administratorer[$i]['epost'])) {echo($administratorer[$i]['epost']);} else {echo("Ikke oppgitt");}?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr class="admin_allebrukere_rad" style="display: none" title="Vis denne administratorem" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($administratorer[$i]['idbruker']) ?>)">
                                        <td class="admin_allebrukere_allebruker"><?php echo($administratorer[$i]['brukernavn'])?></td>
                                        <td class="admin_allebrukere_allenavn">Navn: <?php if(isset($administratorer[$i]['fnavn'])) {echo($administratorer[$i]['fnavn'] . " "); if(isset($administratorer[$i]['enavn'])) {echo($administratorer[$i]['enavn']);}} else {echo("Ikke oppgitt");} ?></td>
                                        <td class="admin_allebrukere_alleepost">Epost: <?php if(isset($administratorer[$i]['epost'])) {echo($administratorer[$i]['epost']);} else {echo("Ikke oppgitt");}?></td>
                                    </tr>
                                <?php }
                            $tabindex++; 
                            } 
                            if($i > 8) { ?>
                                <button id="admin_allebrukere_knapp" onclick="visFlereBrukere()">Vis flere</button>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
                <button id="admin_administrering_tiloversikt" name="oversikt" form="admin_form">Til oversikten</button>
            <?php } else if(isset($_GET['nybruker'])) { 
                // Ny bruker ?>
                <h2 id="admin_underskrift">Opprett en bruker</h2>
                <form method="POST" action="administrator.php?nybruker" id="admin_nybruker_form">
                    <section class="admin_input_boks">
                        <img class="admin_input_ikon" src="bilder/brukerIkon.png" alt="Brukerikon">
                        <input type="text" class="RegInnFelt" name="brukernavn" value="<?php echo($input_brukernavn) ?>" placeholder="Skriv inn brukernavn" required title="Skriv inn ett brukernavn" autofocus>
                    </section>
                    <section class="admin_input_boks">
                        <img class="admin_input_ikon" src="bilder/emailIkon.png" alt="Epostikon">
                        <input type="email" class="RegInnFelt" name="epost" value="<?php echo($input_epost) ?>" placeholder="Skriv inn e-postadresse" required title="Skriv inn en gyldig epostadresse">
                    </section>
                    <section class="admin_input_boks">
                        <img class="admin_input_ikon" src="bilder/pwIkon.png" alt="Passordikon">
                        <input type="password" class="RegInnFeltPW" name="passord" value="" placeholder="Skriv inn passord" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Minimum 8 tegn, 1 liten og 1 stor bokstav">
                    </section>
                    <section class="admin_input_boks">
                        <img class="admin_input_ikon" src="bilder/pwIkon.png" alt="Passordikon">
                        <input type="password" class="RegInnFeltPW" name="passord2" value="" placeholder="Bekreft passord" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Minimum 8 tegn, 1 liten og 1 stor bokstav">
                    </section>
                    <section>
                        <select id="brukertypeValg" name="brukertype">
                            <option value="3">Ordinær bruker</option>
                            <option value="2">Redaktør</option>
                            <option value="1">Administrator</option>
                        </select>
                    </section>
                    <input id="visPassordLbl" style="margin-bottom: 1em; margin-top: 1em;" type="checkbox" onclick="visPassordReg()">
                    <label for="visPassordLbl">Vis passord</label>
                <input type="submit" name="subRegistrering" class="RegInnFelt_knappRegistrer" value="Legg til brukeren">
            </form>
            <button id="admin_nybruker_tiloversikt" name="oversikt" form="admin_form">Til oversikten</button>                     

            
            <?php } else if(isset($_GET['nyregel'])) {
                // Ny regel ?>
                <h2 id="admin_underskrift">Opprett en regel</h2>
                <form method="POST" action="administrator.php?nyregel" id="admin_nyregel_form">
                    <textarea name="regTekst" id="admin_nyregel_tekst" placeholder="Skriv inn regelen" required autofocus maxlength="255"></textarea>
                    <input type="submit" name="regRegistrering" id="admin_nyregel_knapp" value="Legg til">
                </form>
                <button id="admin_nyregel_tiloversikt" name="oversikt" form="admin_form">Til oversikten</button>
            <?php } else if(isset($_GET['bruker'])) {
                // Visning av bruker 
                $hentBrukerinfoQ = "select brukernavn, fnavn, enavn, epost, telefonnummer, brukertype from bruker where idbruker = :bruker";
                $hentBrukerinfoSTMT = $db -> prepare($hentBrukerinfoQ);
                $hentBrukerinfoSTMT -> bindparam(":bruker", $_GET['bruker']);
                $hentBrukerinfoSTMT -> execute();
                $brukerinfo = $hentBrukerinfoSTMT -> fetch(PDO::FETCH_ASSOC); 

                $harFornavn = false;
                $harEtternavn = false;
                $harEpost = false;
                $harTlf = false;

                if(isset($brukerinfo['fnavn']) && preg_match("/\S/", $brukerinfo['fnavn']) == 1) {
                    $harFornavn = true;
                }
                if(isset($brukerinfo['enavn']) && preg_match("/\S/", $brukerinfo['enavn']) == 1) {
                    $harEtternavn = true;
                }
                if(isset($brukerinfo['epost']) && preg_match("/\S/", $brukerinfo['epost']) == 1) {
                    $harEpost = true;
                }
                if(isset($brukerinfo['telefonnummer']) && preg_match("/\S/", $brukerinfo['telefonnummer']) == 1) {
                    $harTlf = true;
                }

                // Henter misbruk
                $hentMisbrukQ = "select idmisbruk, tekst from misbruk where bruker = :bruker order by idmisbruk desc";
                $hentMisbrukSTMT = $db -> prepare($hentMisbrukQ);
                $hentMisbrukSTMT -> bindparam(":bruker", $_GET['bruker']);
                $hentMisbrukSTMT -> execute();
                $misbruk = $hentMisbrukSTMT -> fetchAll(PDO::FETCH_ASSOC);

                // Henter advarsler
                $hentAdvarslerQ = "select idadvarsel, advarseltekst, brukernavn from advarsel, bruker where bruker = :bruker and advarsel.administrator = bruker.idbruker order by idadvarsel desc";
                $hentAdvarslerSTMT = $db -> prepare($hentAdvarslerQ);
                $hentAdvarslerSTMT -> bindparam(":bruker", $_GET['bruker']);
                $hentAdvarslerSTMT -> execute();
                $advarsler = $hentAdvarslerSTMT -> fetchAll(PDO::FETCH_ASSOC);

                // Henter eksklusjoner
                $hentEksklusjonerQ = "select ideksklusjon, grunnlag, brukernavn, datofra, datotil from eksklusjon, bruker where bruker = :bruker and eksklusjon.administrator = bruker.idbruker order by ideksklusjon desc";
                $hentEksklusjonerSTMT = $db -> prepare($hentEksklusjonerQ);
                $hentEksklusjonerSTMT -> bindparam(":bruker", $_GET['bruker']);
                $hentEksklusjonerSTMT -> execute();
                $eksklusjoner = $hentEksklusjonerSTMT -> fetchAll(PDO::FETCH_ASSOC);

                if(isset($brukerinfo)) { ?>
                    <section id="admin_brukerinfo">
                        <figure>
                            <?php 
                            $hentBrukerbildeQ = "select hvor from bilder, brukerbilde where bilder.idbilder = brukerbilde.bilde and brukerbilde.bruker = :bruker";
                            $hentBrukerbildeSTMT = $db -> prepare($hentBrukerbildeQ);
                            $hentBrukerbildeSTMT -> bindparam(":bruker", $_GET['bruker']);
                            $hentBrukerbildeSTMT -> execute();
                            $brukerbilde = $hentBrukerbildeSTMT -> fetch(PDO::FETCH_ASSOC);

                            if ($brukerbilde) {
                                $testPaa = $brukerbilde['hvor'];
                                // Tester på om filen faktisk finnes
                                if(file_exists("$lagringsplass/$testPaa")) {
                                    // Profilbilde som resultat av spørring
                                    if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {
                                        // Hvis vi finner et miniatyrbilde bruker vi det ?>
                                        <img id="admin_brukerbilde" src="bilder/opplastet/thumb_<?php echo($brukerbilde['hvor']) ?>" alt="Profilbilde til <?php echo($brukerinfo['brukernavn']) ?>">
                                    <?php } else { ?>
                                        <img id="admin_brukerbilde" src="bilder/opplastet/<?php echo($brukerbilde['hvor']) ?>" alt="Profilbilde til <?php echo($brukerinfo['brukernavn']) ?>">
                                    <?php } ?>
                                <?php } else { ?>
                                    <img id="admin_brukerbilde" src="bilder/profil.png" alt="Standard profilbilde">
                                <?php } ?>
                            <?php } else { ?>
                                <img id="admin_brukerbilde" src="bilder/profil.png" alt="Standard profilbilde">
                            <?php } ?>
                        </figure>
                        <p id="admin_brukernavn"><?php echo($brukerinfo['brukernavn']) ?></p>
                        <?php if($harFornavn) {echo("<p>Navn: " . $brukerinfo['fnavn']);} if($harEtternavn) {echo(" " . $brukerinfo['enavn']);} if(!$harFornavn && !$harEtternavn) {echo("<p id='admin_ikkeoppgitt'>Navn: Ikke oppgitt");} ?></p>
                        <?php if($harEpost) {echo("<p>Epost: " . $brukerinfo['epost']);} else {echo("<p id='admin_ikkeoppgitt'>Epost: Ikke oppgitt");} ?></p>
                        <?php if($harTlf) {echo("<p>Telefon: " . $brukerinfo['telefonnummer']);} else {echo("<p id='admin_ikkeoppgitt'>Telefon: Ikke oppgitt");} ?></p>
                        <form method="POST" action="administrator.php?bruker=<?php echo($_GET['bruker'])?>">
                            <select name="endreBrukertype" tabindex="27" id="admin_select_brukertype" onchange="this.form.submit()">
                                <?php 
                                // Henter brukertypenavn som bruker allerede har
                                $hentAktivtNavnQ = "select idbrukertype, brukertypenavn from brukertype where idbrukertype = :brukertype";
                                $hentAktivtNavnSTMT = $db->prepare($hentAktivtNavnQ);
                                $hentAktivtNavnSTMT -> bindparam(":brukertype", $brukerinfo['brukertype']);
                                $hentAktivtNavnSTMT->execute();
                                $aktivBType = $hentAktivtNavnSTMT->fetch(PDO::FETCH_ASSOC); ?>
                                <option value="<?php echo($aktivBType['idbrukertype']) ?>"><?php echo($aktivBType['brukertypenavn'])?></option>
                                
                                <?php
                                // Henter brukertypenavn fra database
                                $hentbNavnQ = "select idbrukertype, brukertypenavn from brukertype where idbrukertype != 4 and idbrukertype != :brukertype order by brukertypenavn ASC";
                                $hentbNavnSTMT = $db->prepare($hentbNavnQ);
                                $hentbNavnSTMT -> bindparam(":brukertype", $brukerinfo['brukertype']);
                                $hentbNavnSTMT->execute();
                                $liste = $hentbNavnSTMT->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($liste as $brukertype) { ?>
                                    <option value="<?php echo($brukertype['idbrukertype'])?>"><?php echo($brukertype['brukertypenavn'])?></option>
                                <?php } ?>
                            </select>
                        </form>
                    </section>
                    <section id="admin_handlinger">
                        <p class="admin_handlingvalg" tabindex="28" id="admin_aktivhandling" onclick="byttHandling('Advar')">Advar</p>
                        <p class="admin_handlingvalg" tabindex="29" onclick="byttHandling('Ekskluder')">Ekskluder</p>
                        <form id="admin_handling_form" method="POST" action="administrator.php?bruker=<?php echo($_GET['bruker']) ?>">
                            <p id="admin_handling">Advar bruker</p>
                            <input id="admin_handling_bruker" type="hidden" name="advartbruker" value="<?php echo($_GET['bruker']) ?>">
                            <textarea id="admin_handling_tekst" name="advaring" placeholder="Skriv inn grunnlaget" title="Hva brukeren har gjort feil" autofocus required></textarea>
                            <p id="admin_handling_lengde">Lengde, la være for permanent</p>
                            <input id="admin_handling_dato" type="date" name="datotil">
                            <input onclick="sjekkAdminHandling()" id="admin_handling_submit" type="button" value="Advar bruker">
                        </form>
                    </section>
                    <form method="POST" id="misbruk_form" action="administrator.php?bruker=<?php echo($_GET['bruker']) ?>">
                        <input type="hidden" name="handling" value="misbruk">
                    </form>
                    <section id="admin_allemisbruk">
                        <p id="admin_allemisbruk_tittel">Misbruk<p>
                        <table id="admin_misbruk_table">
                            <?php if(count($misbruk) != 0) { ?>
                                <thead>
                                    <tr>
                                        <th id="admin_misbruk_grunnlag">GRUNNLAG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for($i = 0; $i < count($misbruk); $i++) { ?>
                                        <tr class="admin_misbruk_rad">
                                            <td class="admin_misbruk_allegrunnlag"><?php echo($misbruk[$i]['tekst'])?></td>
                                            <td><button class="admin_bruker_slett_knapp" name="slettHandling" form="misbruk_form" value="<?php echo($misbruk[$i]['idmisbruk'])?>">Slett</button></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            <?php } else { ?>
                                <p id="admin_misbruk_ikkeregistrert">Ikke noe misbruk registrert</p>
                            <?php } ?>
                        </table>
                    </section>
                    <form method="POST" id="advarsel_form" action="administrator.php?bruker=<?php echo($_GET['bruker']) ?>">
                        <input type="hidden" name="handling" value="advarsel">
                    </form>
                    <section id="admin_alleadvarsler">
                        <p id="admin_alleadvarsler_tittel">Advarsler<p>
                        <table id="admin_alleadvarsler_table">
                            <?php if(count($advarsler) != 0) { ?>
                                <thead>
                                    <tr>
                                        <th id="admin_alleadvarsler_grunnlag">GRUNNLAG</th>
                                        <th id="admin_alleadvarsler_administrator">ADVART AV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for($i = 0; $i < count($advarsler); $i++) { ?>
                                        <tr class="admin_alleadvarsler_rad">
                                            <td class="admin_alleadvarsler_allegrunnlag"><?php echo($advarsler[$i]['advarseltekst'])?></td>
                                            <td class="admin_alleadvarsler_alleadmin"><?php echo($advarsler[$i]['brukernavn'])?></td>
                                            <td><button class="admin_bruker_slett_knapp" name="slettHandling" form="advarsel_form" value="<?php echo($advarsler[$i]['idadvarsel'])?>">Slett</button></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            <?php } else { ?>
                                <p id="admin_alleadvarsler_ikkeregistrert">Ikke noen advarsler registrert</p>
                            <?php } ?>
                        </table>
                    </section>
                    <form method="POST" id="eksklusjon_form" action="administrator.php?bruker=<?php echo($_GET['bruker']) ?>">
                        <input type="hidden" name="handling" value="eksklusjon">
                    </form>
                    <section id="admin_alleeksklusjoner">
                        <p id="admin_alleeksklusjoner_tittel">Eksklusjoner<p>
                        <table id="admin_alleeksklusjoner_table">
                            <?php if(count($eksklusjoner) != 0) { ?>
                                <thead>
                                    <tr>
                                        <th id="admin_alleeksklusjoner_grunnlag">GRUNNLAG</th>
                                        <th id="admin_alleeksklusjoner_administrator">EKSKLUDERT AV</th>
                                        <th id="admin_alleeksklusjoner_datofra">DATO FRA</th>
                                        <th id="admin_alleeksklusjoner_datotil">DATO TIL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for($i = 0; $i < count($eksklusjoner); $i++) { ?>
                                        <tr class="admin_alleeksklusjoner_rad">
                                            <td class="admin_alleeksklusjoner_allegrunnlag"><?php echo($eksklusjoner[$i]['grunnlag'])?></td>
                                            <td class="admin_alleeksklusjoner_alleadmin"><?php echo($eksklusjoner[$i]['brukernavn'])?></td>
                                            <td class="admin_alleeksklusjoner_alledatofra"><?php echo(date_format(date_create($eksklusjoner[$i]['datofra']), "j M H:i")) ?></td>
                                            <td class="admin_alleeksklusjoner_alledatotil"><?php if(isset($eksklusjoner[$i]['datotil'])) { echo(date_format(date_create($eksklusjoner[$i]['datotil']), "j M H:i")); } else {echo("Permanent"); } ?></td>
                                            <td><button class="admin_bruker_slett_knapp" name="slettHandling" form="eksklusjon_form" value="<?php echo($eksklusjoner[$i]['ideksklusjon'])?>">Slett</button></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            <?php } else { ?>
                                <p id="admin_alleeksklusjoner_ikkeregistrert">Ikke noen eksklusjoner registrert</p>
                            <?php } ?>
                        </table>
                    </section>
                <?php } else { ?>
                    <h2 id="admin_underskrift">Kunne ikke vise brukeren</h2>
                <?php } ?>
            <?php } else if(isset($_GET['rapporter'])) { // Administrering ?>
                <h2 id="admin_underskrift"><?php echo($_GET['rapporter']); ?></h2>
                
                <form method="GET" id="bruker_form" action="administrator.php">
                    <input type="hidden" id="bruker_form_verdi" name="bruker" value="">
                </form>
                <input type="text" id="admin_sok" tabindex="26" onkeyup="adminpanelSok()" placeholder="Søk etter navn..">

                <?php if($_GET['rapporter']) {
                    // Alle rapporterte brukere
                    if($_GET['rapporter'] == "Alle brukere") {
                        $hentBrukereQ = "SELECT brukerrapport.tekst, brukerrapport.dato, rapportertbruker.idbruker, rapportertbruker.brukernavn as brukerNavn, rapportertav.brukernavn as rapporterer 
                        FROM brukerrapport 
                        LEFT OUTER JOIN bruker rapportertbruker ON brukerrapport.rapportertbruker = rapportertbruker.idbruker 
                        LEFT OUTER JOIN bruker rapportertav ON brukerrapport.rapportertav = rapportertav.idbruker 
                        ORDER BY dato DESC";
                        $hentBrukereSTMT = $db->prepare($hentBrukereQ);
                        $hentBrukereSTMT -> execute();
                        $rapporterteBrukere = $hentBrukereSTMT -> fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <table id="admin_allebrukere_table">
                            <thead>
                                <tr>
                                    <th id="rapport_allebrukere_bruker">BRUKER</th>
                                    <th id="rapport_allebrukere_tekst">RAPPORT</th>
                                    <th id="rapport_allebrukere_rapportertav">FRA</th>
                                    <th id="rapport_allebrukere_dato">DATO</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php for($i = 0; $i < count($rapporterteBrukere); $i++) { 
                                if($i < 8) { ?>
                                    <tr class="admin_allebrukere_rad" title="Vis denne brukeren" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($rapporterteBrukere[$i]['idbruker']) ?>)">
                                        <td class="rapport_allebrukere_bruker"><?php echo($rapporterteBrukere[$i]['brukerNavn'])?></td>
                                        <td class="rapport_allebrukere_tekst"><?php echo($rapporterteBrukere[$i]['tekst'])?></td>
                                        <td class="rapport_allebrukere_rapportertav"><?php echo($rapporterteBrukere[$i]['rapporterer'])?></td>
                                        <td class="rapport_allebrukere_dato"><?php echo($rapporterteBrukere[$i]['dato'])?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr class="admin_allebrukere_rad" title="Vis denne brukeren" tabindex="<?php echo($tabindex) ?>" onclick="aapneBruker(<?php echo($rapporterteBrukere[$i]['idbruker']) ?>)">
                                        <td class="rapport_allebrukere_bruker"><?php echo($rapporterteBrukere[$i]['brukerNavn'])?></td>
                                        <td class="rapport_allebrukere_tekst"><?php echo($rapporterteBrukere[$i]['tekst'])?></td>
                                        <td class="rapport_allebrukere_rapportertav"><?php echo($rapporterteBrukere[$i]['rapporterer'])?></td>
                                        <td class="rapport_allebrukere_dato"><?php echo($rapporterteBrukere[$i]['dato'])?></td>
                                    </tr> 
                                <?php }
                                $tabindex++; 
                                } 
                                if($i > 8) { ?>
                                    <button id="admin_allebrukere_knapp" onclick="visFlereBrukere()">Vis flere</button>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } else if($_GET['rapporter'] == "Eksklusjoner") {
                        $hentBrukereQ = "SELECT eksklusjon.ideksklusjon, eksklusjon.bruker, eksklusjon.grunnlag, eksklusjon.datofra, eksklusjon.datotil, bruker.brukernavn as brukerNavn, administrator.brukernavn as administratorNavn FROM eksklusjon LEFT OUTER JOIN bruker bruker ON eksklusjon.bruker = bruker.idbruker LEFT OUTER JOIN bruker administrator ON eksklusjon.administrator = administrator.idbruker ORDER BY datofra DESC";
                        $hentBrukereSTMT = $db->prepare($hentBrukereQ);
                        $hentBrukereSTMT -> execute();
                        $brukere = $hentBrukereSTMT -> fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <table id="admin_allebrukere_table">
                            <thead>
                                <tr>
                                    <th id="rapport_allebrukere_eks_bruker">BRUKERNAVN</th>
                                    <th id="rapport_allebrukere_grunnlag">GRUNNLAG</th>
                                    <th id="rapport_allebrukere_eks_admin">AV ADMINISTRATOR</th>
                                    <th id="rapport_allebrukere_dato_fra">DATO FRA</th>
                                    <th id="rapport_allebrukere_dato_til">DATO TIL</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php for($i = 0; $i < count($brukere); $i++) { 
                                if($i < 8) { ?>
                                    <tr class="admin_allebrukere_rad" title="Vis denne brukeren" onclick="aapneBruker(<?php echo($brukere[$i]['bruker']) ?>)">
                                        <td class="rapport_allebrukere_allebrukerid"><?php echo($brukere[$i]['brukerNavn'])?></td>
                                        <td class="rapport_allebrukere_grunnlag"><?php echo ($brukere[$i]['grunnlag']) ?></td>
                                        <td class="rapport_allebrukere_administrator"><?php echo($brukere[$i]['administratorNavn'])?></td>
                                        <td class="rapport_allebrukere_datofra"><?php echo($brukere[$i]['datofra'])?></td>
                                        <td class="rapport_allebrukere_datotil"><?php echo($brukere[$i]['datotil'])?></td>
                                    </tr>
                                <?php } else { ?>
                                    <tr class="admin_allebrukere_rad" style="display: none" title="Vis denne brukeren" onclick="aapneBruker(<?php echo($brukere[$i]['bruker']) ?>)">
                                        <td class="rapport_allebrukere_allebrukerid"><?php echo($brukere[$i]['brukerNavn'])?></td>
                                        <td class="rapport_allebrukere_grunnlag"><?php echo ($brukere[$i]['grunnlag']) ?></td>
                                        <td class="rapport_allebrukere_administrator"><?php echo($brukere[$i]['administratorNavn'])?></td>
                                        <td class="rapport_allebrukere_datofra"><?php echo($brukere[$i]['datofra'])?></td>
                                        <td class="rapport_allebrukere_datotil"><?php echo($brukere[$i]['datotil'])?></td>
                                    </tr>
                                <?php }
                                } 
                                if($i > 8) { ?>
                                    <button id="admin_allebrukere_knapp" onclick="visFlereBrukere()">Vis flere</button>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } else if($_GET['rapporter'] == "Advarsler") {
                        $hentBrukereQ = "SELECT advarsel.idadvarsel, advarsel.advarseltekst, bruker.idbruker, bruker.brukernavn as brukerNavn, administrator.brukernavn as administratorNavn 
                        FROM advarsel 
                        LEFT OUTER JOIN bruker bruker ON advarsel.bruker = bruker.idbruker 
                        LEFT OUTER JOIN bruker administrator ON advarsel.administrator = administrator.idbruker 
                        ORDER BY brukerNavn";
                        $hentBrukereSTMT = $db->prepare($hentBrukereQ);
                        $hentBrukereSTMT -> execute();
                        $brukere = $hentBrukereSTMT -> fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <table id="admin_allebrukere_table">
                            <thead>
                                <tr>
                                    <th id="rapport_allebrukere_adv_bruker">BRUKERNAVN</th>
                                    <th id="rapport_allebrukere_advarsel">ADVARSEL</th>
                                    <th id="rapport_allebrukere_adv_admin">AV ADMINISTRATOR</th>
                                </tr>
                            </thead>
                        <tbody>
                        <?php for($i = 0; $i < count($brukere); $i++) { 
                            if($i < 8) { ?>
                                <tr class="admin_allebrukere_rad" title="Vis denne brukeren" onclick="aapneBruker(<?php echo($brukere[$i]['idbruker']) ?>)">
                                    <td class="rapport_allebrukere_advarsel_bruker"><?php echo($brukere[$i]['brukerNavn'])?></td>
                                    <td class="rapport_allebrukere_advarseltekst"><?php echo($brukere[$i]['advarseltekst'])?></td>
                                    <td class="rapport_allebrukere_advarsel_admin"><?php echo($brukere[$i]['administratorNavn'])?></td>
                                </tr>
                            <?php } else { ?>
                                <tr class="admin_allebrukere_rad" style="display: none" title="Vis denne brukeren" onclick="aapneBruker(<?php echo($brukere[$i]['idbruker']) ?>)">
                                    <td class="rapport_allebrukere_advarsel_bruker"><?php echo($brukere[$i]['brukerNavn'])?></td>
                                    <td class="rapport_allebrukere_advarseltekst"><?php echo($brukere[$i]['advarseltekst'])?></td>
                                    <td class="rapport_allebrukere_advarsel_admin"><?php echo($brukere[$i]['administratorNavn'])?></td>
                                </tr>
                            <?php }
                            } 
                            if($i > 8) { ?>
                                <button id="admin_allebrukere_knapp" onclick="visFlereBrukere()">Vis flere</button>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } 
                }
            } else {
                // Selve oversikten, default view ?>
                <h2 id="admin_underskrift">Oversikten</h2>

                <form method="GET" id="admin_form_advarsel" action="administrator.php">
                    <input type="hidden"  name="administrering" value="Advarsler">
                    <section id="admin_advarsler">
                        <p id="admin_advarsler_tittel">Advarsler</p>
                        <?php 
                        $hentAntallQ = "select count(idadvarsel) as antall from advarsel";
                        $hentAntallSTMT = $db -> prepare($hentAntallQ);
                        $hentAntallSTMT->execute();
                        $antalladvarsler = $hentAntallSTMT->fetch(PDO::FETCH_ASSOC); 
                        ?>
                        <p id="admin_advarsler_antall"><?php echo($antalladvarsler['antall']) ?></p>
                    </section>
                </form>
                <form method="GET" id="admin_form_misbruk" action="administrator.php">
                    <input type="hidden"  name="administrering" value="Misbruk">
                    <section id="admin_misbruk">
                        <p id="admin_misbruk_tittel">Misbruk</p>
                        <?php 
                        $hentAntallQ = "select count(idmisbruk) as antall from misbruk";
                        $hentAntallSTMT = $db -> prepare($hentAntallQ);
                        $hentAntallSTMT->execute();
                        $antallmisbruk = $hentAntallSTMT->fetch(PDO::FETCH_ASSOC); 
                        ?>
                        <p id="admin_misbruk_antall"><?php echo($antallmisbruk['antall']) ?></p>
                    </section>
                </form>
                <form method="GET" id="admin_form_eksklusjoner" action="administrator.php">
                    <input type="hidden"  name="administrering" value="Eksklusjoner">
                    <section id="admin_eksklusjoner">
                        <p id="admin_eksklusjoner_tittel">Eksklusjoner</p>
                        <?php 
                        $hentAntallQ = "select count(ideksklusjon) as antall from eksklusjon";
                        $hentAntallSTMT = $db -> prepare($hentAntallQ);
                        $hentAntallSTMT->execute();
                        $antalleksklusjoner = $hentAntallSTMT->fetch(PDO::FETCH_ASSOC); 
                        ?>
                        <p id="admin_eksklusjoner_antall"><?php echo($antalleksklusjoner['antall']) ?></p>
                    </section>
                </form>
                <section id="admin_brukere">
                    <p id="admin_brukere_tittel">Antall brukere</p>
                    <?php 
                    $hentAntallQ = "select count(idbruker) as antall from bruker";
                    $hentAntallSTMT = $db -> prepare($hentAntallQ);
                    $hentAntallSTMT->execute();
                    $antallbrukere = $hentAntallSTMT->fetch(PDO::FETCH_ASSOC); 
                    ?>
                    <p id="admin_brukere_antall"><?php echo($antallbrukere['antall'])?></p>
                </section>
                <button id="admin_regler_knapp" onclick="regMeny()">Reglement</button>
                <?php 
                $hentReglerQ = "select idregel, regeltekst, brukernavn from regel, bruker where regel.idbruker = bruker.idbruker";
                $hentReglerSTMT = $db->prepare($hentReglerQ);
                $hentReglerSTMT -> execute();
                $regler = $hentReglerSTMT -> fetchAll(PDO::FETCH_ASSOC);
                ?>
                <table id="admin_regler_table">
                    <thead>
                        <tr>
                            <th id="admin_regler_regel">Regel</th>
                            <th id="admin_regler_oppr">Opprettet av</th>
                            <th id="admin_regler_slett"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for($i = 0; $i < count($regler); $i++) {
                            if(isset($regler[$i]['regeltekst'])) { ?>
                                <tr>
                                    <td><?php echo($regler[$i]['regeltekst'])?></td>
                                    <td><?php echo($regler[$i]['brukernavn'])?></td>
                                    <td><button class="admin_regler_slett_knapp" name="slettregel" form="admin_form" value="<?php echo($regler[$i]['idregel'])?>">Slett</button></td>
                                </tr>
                            <?php }
                        } ?>
                    </tbody>
                </table>
            <?php } ?>

            <!-- Håndtering av feilmeldinger -->

            <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($admin_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                <section id="mldFEIL_innhold">
                    <p id="mldFEIL"><?php echo($admin_melding) ?></p>  
                    <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                    <button id="mldFEIL_knapp" autofocus>Lukk</button>
                </section>  
            </section>
            <?php 
            if(isset($_GET['vellykket']) && $_GET['vellykket'] == 1) { ?>
                <p id="mldOK">Brukeren er opprettet</p>
            <?php } ?>
        </main>
        <?php include("inkluderes/footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Robin Kleppang og Petter Fiskvik, siste gang endret 03.06.2020 -->
<!-- Denne siden er kontrollert av Robin Kleppang, siste gang 04.06.2020 -->
</html>