<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");


// Sjekker om bruker er i en gyldig session, sender tilbake til hovedsiden hvis så
if (isset($_SESSION['idbruker'])) {
    $_SESSION['default_melding'] = "Du kan ikke se denne siden";
    header("Location: default.php");
}

// Enkel test som gjør det mulig å beholde brukerinput etter siden er lastet på nytt (Form submit)
$input_brukernavn = "";
$input_epost = "";
if (isset($_SESSION['input_brukernavn'])) {
    $input_brukernavn = $_SESSION['input_brukernavn'];
    $input_epost = $_SESSION['input_epost'];
    unset($_SESSION['input_brukernavn']);
    unset($_SESSION['input_epost']);
}

$registrer_melding = "";
if(isset($_SESSION['registrer_melding'])) {
    $registrer_melding = $_SESSION['registrer_melding'];
    unset($_SESSION['registrer_melding']);
}

if (isset($_POST['subRegistrering'])) {
    $_SESSION['input_brukernavn'] = $_POST['brukernavn'];
    $_SESSION['input_epost'] = $_POST['epost'];
    // Tester på om passordene er like
    if ($_POST['passord'] == $_POST['passord2']) {
        // Tester på om bruker har fyllt ut alle de obligatoriske feltene
        if ($_POST['brukernavn'] != "" && $_POST['epost'] != "") {
            // Tester på om en gyldig epost ("NAVN@NAVN.DOMENE") er oppgitt
            if (filter_var($_POST["epost"], FILTER_VALIDATE_EMAIL)) {
                try {

                    $br = filter_var($_POST['brukernavn'], FILTER_SANITIZE_STRING);

                    // Hvis ikke bruker har forsøkt å skrive inn HTML kode i brukernavn-feltet så fortsetter vi
                    if($br == $_POST['brukernavn']) {
                        $pw = $_POST['passord'];
    
                        // Validering av passordstyrke, server validering
                        $storebokstaver = preg_match('@[A-Z]@', $pw);
                        $smaabokstaver = preg_match('@[a-z]@', $pw);
                        $nummer = preg_match('@[0-9]@', $pw);
                        // Denne er for spesielle symboler, ikke i bruk for øyeblikket
                        // $spesielleB = preg_match('@[^\w]@', $pw);
    
                        if ($pw == "") {
                            // Ikke noe passord skrevet
                            $_SESSION['registrer_melding'] = "Du har ikke oppgitt et passord";
                            header("Location: registrer.php");
                        } else if (!$storebokstaver || !$smaabokstaver || !$nummer /*|| !$spesielleB*/ || strlen($pw) < 8) {
                            // Ikke tilstrekkelig passord skrevet
                            $_SESSION['registrer_melding'] = "Du har ikke oppgitt et tilstrekkelig passord";
                            header("Location: registrer.php");
                        } else {
                            // Sjekker om brukernavn er opptatt (Brukes så lenge brukernavn ikke er satt til UNIQUE i db)
                            $lbr = strtolower($_POST['brukernavn']);
                            $sjekkbnavn = "select lower(brukernavn) as brukernavn from bruker where lower(brukernavn)='" . $lbr . "'";
                            $sjekket = $db->prepare($sjekkbnavn);
                            $sjekket->execute();
                            $testbnavn = $sjekket->fetch(PDO::FETCH_ASSOC);
    
                            // Hvis resultatet over er likt det bruker har oppgitt som brukernavn stopper vi og advarer bruker om at brukernavnet er allerede tatt
                            if ($testbnavn['brukernavn'] != $lbr) {
                                // OK, vi forsøker å registrere bruker
                                $epost = $_POST['epost'];
    
                                // Salter passorder
                                $kombinert = $salt . $pw;
                                // Krypterer saltet passord
                                $spw = sha1($kombinert);
                                $sql = "insert into bruker(brukernavn, passord, epost, brukertype) VALUES('" . $br . "', '" . $spw . "', '" . $epost . "', 3)";
    
    
                                // Prepared statement for å beskytte mot SQL injection
                                $stmt = $db->prepare($sql);
    
                                $vellykket = $stmt->execute(); 
    
                                // Vi har lagt til ny bruker, henter IDen
                                $nyBrukerID = $db -> lastInsertId();
                                
                                // Alt gikk OK, sender til logginn med melding til bruker
                                if ($vellykket) {
                                    // Fjerner session variable for brukerinput om ingen feil oppstår
                                    unset($_SESSION['input_brukernavn']);
                                    unset($_SESSION['input_epost']);
    
                                    // Sjekker på om bruker har registrert preferanser
                                    $sjekkPrefQ = "select idpreferanse from preferanse where bruker = " . $nyBrukerID;
                                    $sjekkPrefSTMT = $db->prepare($sjekkPrefQ);
                                    $sjekkPrefSTMT->execute();
                                    $resPref = $sjekkPrefSTMT->fetch(PDO::FETCH_ASSOC); 
    
                                    // Bruker har ikke preferanser, oppretter de
                                    // Variabelen $personvern kommer fra innstillinger
                                    if(!$resPref) {
                                        $opprettPrefQ = "insert into preferanse(visfnavn, visenavn, visepost, visinteresser, visbeskrivelse, vistelefonnummer, bruker) values('" . 
                                                            $personvern[0] . "', '" . $personvern[1] . "', '" . $personvern[2] . "', '" . $personvern[3] . "', '" . $personvern[4] . "', '" . $personvern[5] . "', " .
                                                                $nyBrukerID . ")";
    
                                        $opprettPrefSTMT = $db->prepare($opprettPrefQ);
                                        $opprettPrefSTMT->execute();
                                    }
                                    
                                    // Vi logger inn den nye brukeren
                                    $_SESSION['idbruker'] = $nyBrukerID;
                                    $_SESSION['brukernavn'] = $br;
                                    $_SESSION['fornavn'] = null;
                                    $_SESSION['etternavn'] = null;
                                    $_SESSION['epost'] = $epost;
                                    $_SESSION['telefonnummer'] = null;
                                    $_SESSION['brukertype'] = 3;
    
                                    header("Location: backend.php");
                                }
                            } else {
                                // Brukernavnet er tatt
                                $_SESSION['registrer_melding'] = "Brukernavnet er opptatt";
                                header("Location: registrer.php");
                            }
                        }
                    } else {
                        // Kan ikke registrere dette navnet
                        $_SESSION['registrer_melding'] = "Brukernavnet er ugyldig";
                        header("Location: registrer.php");
                    }
                }
                catch (PDOException $ex) {
                    if ($ex->getCode() == 23000) {
                        // 23000, Duplikat, tenkes brukt til brukernavn da det ønskes å være satt UNIQUE i db
                        $_SESSION['registrer_melding'] = "Brukernavnet er opptatt";
                        header("Location: registrer.php");
                    } else if ($ex->getCode() == '42S22') {
                        // 42S22, Kolonne eksisterer ikke
                        $_SESSION['registrer_melding'] = "Feil oppstod ved registrering";
                        header("Location: registrer.php");
                    }
                } 
            } else {
                // Feilmelding 7, bruker har oppgitt en ugyldig epost
                $_SESSION['registrer_melding'] = "Du har oppgitt en ugyldig epost";
                header("Location: registrer.php");
            }
        } else {
            // Feilmelding 6, bruker har ikke skrevet noe i ett av de obligatoriske feltene
            $_SESSION['registrer_melding'] = "Et eller flere felt ikke fylt inn";
            header("Location: registrer.php");
        }
    } else {
        // Feilmelding 2 = passord ikke like
        $_SESSION['registrer_melding'] = "Passordene er ikke like";
        header("Location: registrer.php");
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
        <title>Registrering</title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="registrer_body" onclick="lukkMelding('mldFEIL_boks')">
        <?php include("inkluderes/navmeny.php") ?>

        <main id="toppMain" onclick="lukkHamburgerMeny()">
            <!-- Formen som bruker til registrering av bruker, mulighet for å vise passord til bruker om de er usikre -->
            <form method="POST" action="registrer.php" class="innloggForm">
                <section class="inputBoks">
                    <img class="icon" src="bilder/brukerIkon.png" alt="Brukerikon"> <!-- Ikonet for bruker -->
                    <input type="text" class="RegInnFelt" name="brukernavn" value="<?php echo($input_brukernavn) ?>" placeholder="Skriv inn brukernavn" required title="Skriv inn ett brukernavn" autofocus>
                </section>
                <section class="inputBoks">
                    <img class="icon" src="bilder/emailIkon.png" alt="Epostikon"> <!-- Ikonet for epostadresse -->
                    <input type="email" class="RegInnFelt" name="epost" value="<?php echo($input_epost) ?>" placeholder="Skriv inn e-postadresse" required title="Skriv inn en gyldig epostadresse">
                </section>
                <section class="inputBoks">
                    <img class="icon" src="bilder/pwIkon.png" alt="Passordikon"> <!-- Ikonet for passord -->
                    <input type="password" class="RegInnFeltPW" name="passord" value="" placeholder="Skriv inn passord" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Minimum 8 tegn, 1 liten og 1 stor bokstav">
                </section>
                <section class="inputBoks">
                    <img class="icon" src="bilder/pwIkon.png" alt="Passordikon"> <!-- Ikonet for passord -->
                    <input type="password" class="RegInnFeltPW" name="passord2" value="" placeholder="Bekreft passord" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Minimum 8 tegn, 1 liten og 1 stor bokstav">
                </section>
                <input id="visPassordLbl" style="margin-bottom: 1em;" type="checkbox" onclick="visPassordReg()">
                <label for="visPassordLbl">Vis passord</label>
                
                <!-- Håndtering av feilmeldinger -->

                <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($registrer_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                    <section id="mldFEIL_innhold">
                        <p id="mldFEIL"><?php echo($registrer_melding) ?></p>  
                        <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                        <button id="mldFEIL_knapp" autofocus>Lukk</button>
                    </section>  
                </section>

                <input type="submit" name="subRegistrering" class="RegInnFelt_knappRegistrer" value="Registrer ny bruker">
            </form>

            <!-- Sender brukeren tilbake til forsiden -->
            <button onClick="location.href='default.php'" name="submit" class="lenke_knapp">Tilbake til forside</button>
            
        </main>
        <?php include("inkluderes/footer.php") ?>
    </body>

<!-- Denne siden er utviklet av Robin Kleppang, siste gang endret 01.06.2020 -->
<!-- Denne siden er kontrollert av Glenn Petter Pettersen, siste gang 04.06.2020 -->
</html>