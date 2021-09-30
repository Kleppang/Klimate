<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");


// Sjekker om bruker er i en gyldig session, sender tilbake til hovedsiden hvis ikke
if (!isset($_SESSION['idbruker'])) {
    $_SESSION['default_melding'] = "Du kan ikke se denne siden";
    header("Location: default.php");
}

$konto_melding = "";
if(isset($_SESSION['konto_melding'])) {
    $konto_melding = $_SESSION['konto_melding'];
    unset($_SESSION['konto_melding']);
}

if (isset($_POST['avregistrerMeg'])) {

    // Del for oppdatering av passord, sjekker om begge passordene er like, og om bruker faktisk har skrevet noe
    if ($_POST['passord'] != "") {
        if ($_SESSION['brukertype'] != 1) {
            $pw = $_POST['passord'];

            // Salter passordet
            $kombinert = $salt . $pw;

            // Krypterer det saltede passordet
            $spw = sha1($kombinert);

            $sjekkPassordQ = "select idbruker, passord from bruker where idbruker = " . $_SESSION['idbruker'] . " and passord = '" . $spw . "'";
            $sjekkPassordSTMT = $db->prepare($sjekkPassordQ);
            $sjekkPassordSTMT->execute();
            $resultat = $sjekkPassordSTMT->fetch(PDO::FETCH_ASSOC);

            if (($resultat != false) &&$resultat['idbruker'] == $_SESSION['idbruker'] && $resultat['passord'] == $spw) {
                // Passordet er riktig, vi fortsetter med avregistrering
                $avregistreringQ = "update bruker set brukertype = 4 where idbruker = '" . $_SESSION['idbruker'] . "'";
                $avregistreringSTMT = $db->prepare($avregistreringQ);
                $avregistreringSTMT->execute();
                $avregistreringRes = $avregistreringSTMT->fetch(PDO::FETCH_ASSOC);

                $antallEndret = $avregistreringSTMT->rowCount();

                if($antallEndret != 0) {
                    session_destroy();
                    header('Location: default.php?avregistrert=true');
                }
            } else {
                // Feil passord oppgitt
                $_SESSION['konto_melding'] = "Feil passord oppgitt";
                header("Location: konto.php");
            }
        } else {
            // Brukertype er administrator
            $_SESSION['konto_melding'] = "Du kan ikke avregistrere en administrator";
            header("Location: konto.php");
        }
    } else {
        // Ikke noe passord skrevet
        $_SESSION['konto_melding'] = "Ikke noe passord oppgitt";
        header("Location: konto.php");
    }
}



// Enkel test som gjør det mulig å beholde brukerinput etter siden er lastet på nytt (Form submit)
$input_brukernavn = "";
$input_epost = "";
$input_fornavn = "";
$input_etternavn = "";
$input_telefonnummer = "";
if (isset($_SESSION['input_brukernavn'])) {
    // Legger innhold i variable som leses senere på siden
    $input_brukernavn = $_SESSION['input_brukernavn'];
    $input_epost = $_SESSION['input_epost'];
    $input_fornavn = $_SESSION['input_fornavn'];
    $input_etternavn = $_SESSION['input_etternavn'];
    $input_telefonnummer = $_SESSION['input_telefonnummer'];
    // Sletter innholdet så dette ikke eksisterer utenfor denne siden
    unset($_SESSION['input_brukernavn']);
    unset($_SESSION['input_epost']);
    unset($_SESSION['input_fornavn']);
    unset($_SESSION['input_etternavn']);
    unset($_SESSION['input_telefonnummer']);
}

// Hoveddelen for redigering av konto
if (isset($_POST['subEndring'])) {
    // Boolske verdier vi tester på for å vite om noe er endret på
    $oppdatertBr = false;
    $oppdatertPw = false;
    
    try {
        // Del for oppdatering av brukernavn, epost, fornavn og/eller etternavn
        if ($_POST['nyttbrukernavn'] != "" || $_POST['nyepost'] != "" || $_POST['nyttfornavn'] != "" || $_POST['nyttetternavn'] != "" || $_POST['nytttelefonnummer'] != "") {
            $_SESSION['input_brukernavn'] = $_POST['nyttbrukernavn'];
            $_SESSION['input_epost'] = $_POST['nyepost'];
            $_SESSION['input_fornavn'] = $_POST['nyttfornavn'];
            $_SESSION['input_etternavn'] = $_POST['nyttetternavn'];
            $_SESSION['input_telefonnummer'] = $_POST['nytttelefonnummer'];
            
            // Tester på om en epost faktisk er oppgitt (Om bruker endrer input type til text eller hvis browser ikke støtter type epost)
            $epostValidert = false;

            // Test på om brukernavn, fornavn og etternavn er ok
            $brukernavnValidert = false;
            $fornavnValidert = false;
            $etternavnValidert = false;

            if ($_POST['nyepost'] == "") {
                // Bruker har ikke oppgitt en epost, ignorerer dette
                $epostValidert = true;
            } else {
                $epostValidert = filter_var($_POST["nyepost"], FILTER_VALIDATE_EMAIL);
            }

            if ($epostValidert != false) {
                // Da vet vi at bruker vil oppdatere en av verdiene over, sjekker individuelt
                if ($_POST['nyttbrukernavn'] == "") {
                    // Bruker har valgt å ikke oppdatere brukernavn
                    $nyttBrukernavn = $_SESSION['brukernavn'];
                    $brukernavnValidert = true;
                } else {
                    $skrevetBrukernavn = $_POST['nyttbrukernavn'];
                    $konvertertBrukernavn = filter_var($_POST['nyttbrukernavn'], FILTER_SANITIZE_STRING);
                    if($skrevetBrukernavn == $konvertertBrukernavn) {
                        $nyttBrukernavn = $konvertertBrukernavn;
                        $brukernavnValidert = true;
                    } else {
                        $brukernavnValidert = false;
                    }
                }
            
                if ($_POST['nyepost'] == "") {
                    // Bruker har valgt å ikke oppdatere epost
                    $nyEpost = $_SESSION['epost'];
                } else {
                    $nyEpost = $_POST['nyepost'];
                }
            
                if ($_POST['nyttfornavn'] == "") {
                    // Bruker har valgt å ikke oppdatere fornavn
                    $nyttFornavn = $_SESSION['fornavn'];
                    $fornavnValidert = true;
                } else {
                    $skrevetFornavn = $_POST['nyttfornavn'];
                    $konvertertFornavn = filter_var($_POST['nyttfornavn'], FILTER_SANITIZE_STRING);
                    if($skrevetFornavn == $konvertertFornavn) {
                        $nyttFornavn = $konvertertFornavn;
                        $fornavnValidert = true;
                    } else {
                        $fornavnValidert = false;
                    }
                    
                }
            
                if ($_POST['nyttetternavn'] == "") {
                    // Bruker har valgt å ikke oppdatere etternavn
                    $nyttEtternavn = $_SESSION['etternavn'];
                    $etternavnValidert = true;
                } else {
                    $skrevetEtternavn = $_POST['nyttetternavn'];
                    $konvertertEtternavn = filter_var($_POST['nyttetternavn'], FILTER_SANITIZE_STRING);
                    if($skrevetEtternavn == $konvertertEtternavn) {
                        $nyttEtternavn = $konvertertEtternavn;
                        $etternavnValidert = true;
                    } else {
                        $etternavnValidert = false;
                    }
                }
            
                // Sjekker på om bruker har skrevet et telefonnummer, maks 12 tegn (0047) 
                if ($_POST['nytttelefonnummer'] != "") {
                    if(!preg_match('/^[0-9]{0,12}$/', $_POST['nytttelefonnummer'])) {
                        $_SESSION['konto_melding'] = "Telefonnummeret oppfyller ikke kravet";
                        header("Location: konto.php?rediger");
                    } else {
                        $nyttTelefonnummer = $_POST['nytttelefonnummer'];
                    }
                } else {
                    // Bruker har valgt å ikke oppdatere telefonnummer
                    $nyttTelefonnummer = $_SESSION['telefonnummer'];
                }

                if($brukernavnValidert && $fornavnValidert && $etternavnValidert) {
                    // SQL script som oppdaterer info. Med testing over vil ikke informasjon som bruker ikke vil endre faktisk endres
                    $oppdaterBruker = "update bruker set brukernavn = '" . $nyttBrukernavn . "', fnavn = '" . $nyttFornavn . "', enavn = '" . $nyttEtternavn . "', epost = '" . $nyEpost . "', telefonnummer = '" . $nyttTelefonnummer . "'  where idbruker='". $_SESSION['idbruker'] . "'";
                    $stmt = $db->prepare($oppdaterBruker);
                    $stmt->execute();
    
                    // Ved update blir antall rader endret returnert, vi kan utnytte dette til å teste om noen endringer faktisk skjedde
                    $antall = $stmt->rowCount();
    
                    if ($antall > 0) {
                        // Oppdaterer session-info
                        $_SESSION['brukernavn'] = $nyttBrukernavn;
                        $_SESSION['fornavn'] = $nyttFornavn;
                        $_SESSION['etternavn'] = $nyttEtternavn;
                        $_SESSION['epost'] = $nyEpost;
                        $_SESSION['telefonnummer'] = $nyttTelefonnummer;
                        $oppdatertBr = true;
    
                        // Alt gikk ok, fjerner session variable for brukerinput
                        unset($_SESSION['input_brukernavn']);
                        unset($_SESSION['input_epost']);
                        unset($_SESSION['input_fornavn']);
                        unset($_SESSION['input_etternavn']);
                        unset($_SESSION['input_telefonnummer']);
                    }
                } else {
                    // Bruker har forsøkt å oppgi ugyldig info
                    $_SESSION['konto_melding'] = "Et av feltene inneholder ugyldig info";
                    header("Location: konto.php?rediger");
                }
            } else {
                // Error 8, Epost er ikke gyldig
                $_SESSION['konto_melding'] = "Du har oppgitt en ugyldig epost";
                header("Location: konto.php?rediger");
            }
        } 

        // Del for oppdatering av passord, sjekker om begge passordene er like, og om bruker faktisk har skrevet noe
        if ($_POST['nyttpassord'] != "") {
            if ($_POST['nyttpassord'] == $_POST['bekreftnyttpassord']) {

                $lbr = strtolower($_SESSION['brukernavn']);
                $pw = $_POST['gammeltpassord'];
                $kombinert = $salt . $pw;
                // Krypterer det saltede passordet
                $spw = sha1($kombinert);

                $sjekkGammelt = "select * from bruker where lower(brukernavn)='" . $lbr . "' and passord='" . $spw . "'";
                // Prepared statement for å beskytte mot SQL injection
                $stmt = $db->prepare($sjekkGammelt);

                $stmt->execute();

                $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($resultat['brukernavn'] == $_SESSION['brukernavn'] && $resultat['passord'] == $spw) {
                    // Validering av passordstyrke
                    // Kilde: https://www.codexworld.com/how-to/validate-password-strength-in-php/
                    $storebokstaver = preg_match('@[A-Z]@', $_POST['nyttpassord']);
                    $smaabokstaver = preg_match('@[a-z]@', $_POST['nyttpassord']);
                    $nummer = preg_match('@[0-9]@', $_POST['nyttpassord']);

                    if (!$storebokstaver || !$smaabokstaver || !$nummer || strlen($pw) < 8) {
                        // Ikke tilstrekkelig passord skrevet
                        $_SESSION['konto_melding'] = "Passordet oppfyller ikke kravet";
                        header("Location: konto.php?rediger");
                    } else {
                        // Passord er OK, vi fortsetter
                        $kombinert = $salt . $_POST['nyttpassord'];
                        $nyttPassord = sha1($kombinert);
            
                        $oppdaterBruker = "update bruker set passord = '" . $nyttPassord . "'  where brukernavn='". $_SESSION['brukernavn'] . "'";
            
                        $stmt = $db->prepare($oppdaterBruker);
                        $stmt->execute();

                        // Ved update blir antall rader endret returnert, vi kan utnytte dette til å teste om noen endringer faktisk skjedde
                        $antall = $stmt->rowCount();
                
                        if ($antall > 0) {
                            $oppdatertPw = true;
                        }
                    }
                }
            } else {
                // Error 4, Passordene er ikke like
                $_SESSION['konto_melding'] = "Passordene er ikke like";
                header("Location: konto.php?rediger");
            }
        }

        // Hvis vi har oppdatert brukerinfo eller passord, returner bruker til kontosiden, her ser vi oppdatert info direkte
        if ($oppdatertBr == true || $oppdatertPw == true) {
            header("location: konto.php?vellykket=1");
        }
    } 
    catch (PDOException $ex) {
        if ($ex->getCode() == 23000) {
            // 23000, Duplikat brukernavn (Siden brukernavn er UNIQUE)
            $_SESSION['konto_melding'] = "Brukernavnet er allerede tatt";
            header("Location: konto.php?rediger");
        }
    }    
}

if(isset($_POST['slettInfo'])) {
    if (isset($_POST['fnavn']) || isset($_POST['enavn']) || isset($_POST['telefonnummer'])) {
        $slettetFnavn = 0;
        $slettetEnavn = 0;
        $slettetTlfnr = 0;
        
        if (isset($_POST['fnavn'])) {
            $slettfNavnQ = "update bruker set fnavn = null where idbruker = " . $_SESSION['idbruker'];
            $slettfNavnSTMT = $db->prepare($slettfNavnQ);
            $slettfNavnSTMT->execute();

            $slettetFnavn =  $slettfNavnSTMT->rowCount();
    
            $_SESSION['fornavn'] = "";
    
        } if (isset($_POST['enavn'])) {
            $sletteNavnQ = "update bruker set enavn = null where idbruker = " . $_SESSION['idbruker'];
            $sletteNavnSTMT = $db->prepare($sletteNavnQ);
            $sletteNavnSTMT->execute();

            $slettetEnavn =  $sletteNavnSTMT->rowCount();
    
            $_SESSION['etternavn'] = "";
    
        } if (isset($_POST['telefonnummer'])) {
    
            $slettTlfQ = "update bruker set telefonnummer = null where idbruker = " . $_SESSION['idbruker'];
            $sletteTlfSTMT = $db->prepare($slettTlfQ);
            $sletteTlfSTMT->execute();

            $slettetTlfnr =  $sletteTlfSTMT->rowCount();
    
            $_SESSION['telefonnummer'] = "";
    
        }
        
        if($slettetFnavn > 0 || $slettetEnavn > 0 || $slettetTlfnr > 0) {
            header("location: konto.php?vellykket=1");
        } else {
            // Error 10, kunne ikke slette data
            $_SESSION['konto_melding'] = "Feil oppsto ved sletting av data";
            header("Location: konto.php?rediger");
        }
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
            <?php if(isset($_POST['rediger'])) { ?>
                Konto | Rediger
            <?php } else { ?>
                Konto
            <?php } ?>
        </title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"></script>
    </head>

    <body id="konto_body" onload="kontoRullegardin()" onclick="lukkMelding('mldFEIL_boks')" <?php if(isset($_GET['rediger'])) { ?> onresize="fiksRullegardin()"<?php } ?>>
        <?php include("inkluderes/navmeny.php") ?>

        <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
        <!-- Kan ikke legge denne direkte i body -->
        <header class="konto_header" onclick="lukkHamburgerMeny()">
            <h1>Konto</h1>
        </header>

        <?php if(isset($_GET['rediger'])) { ?>
            <main id="konto_rediger_main" onclick="lukkHamburgerMeny()">

                <section class="brukerinformasjon_rediger"> 
                    <!-- Underoverskrift -->
                    <h2 class="redigerbruker_overskrift">Rediger brukeropplysninger</h2>

                    <form id="konto_rediger_formSlett" method="POST" action="konto.php" style="display: none;">
                        <input type="hidden" name="slettInfo">
                    </form>
                    
                    <!-- Felt for brukeropplysning endringer -->
                    <form id="konto_rediger_form" method="POST" action="konto.php" class="konto_rediger_Form">
                        <!-- Brukernavn -->
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Endre brukernavn</p>
                            <input type="text" class="KontoredigeringFelt" name="nyttbrukernavn" value="<?php echo($input_brukernavn) ?>" placeholder="Nytt brukernavn" autofocus>
                        </section>
                        <!-- Epost -->
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Endre epost</p>
                            <input type="email" class="KontoredigeringFelt" name="nyepost" value="<?php echo($input_epost) ?>" placeholder="Ny epost">
                        </section>    
                        <!-- Fornavn -->
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Endre fornavn
                                <input type="submit" form="konto_rediger_formSlett" class="konto_rediger_slettKnapp" name="fnavn" value="(Slett)">
                            </p>
                            <input type="text" class="KontoredigeringFelt" name="nyttfornavn" value="<?php echo($input_fornavn) ?>" placeholder="Nytt fornavn" title="Oppgi et gyldig navn">
                        </section>
                        <!-- Etternavn -->
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Endre etternavn
                                <input type="submit" form="konto_rediger_formSlett" class="konto_rediger_slettKnapp" name="enavn" value="(Slett)">
                            </p>
                            <input type="text" class="KontoredigeringFelt" name="nyttetternavn" value="<?php echo($input_etternavn) ?>" placeholder="Nytt etternavn" title="Oppgi et gyldig navn">
                        </section>
                        <!-- Telefonnummer -->
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Endre telefonnummer
                                <input type="submit" form="konto_rediger_formSlett" class="konto_rediger_slettKnapp" name="telefonnummer" value="(Slett)">
                            </p>
                            <input type="text" class="KontoredigeringFelt" name="nytttelefonnummer" value="<?php echo($input_telefonnummer) ?>" placeholder="Nytt telefonnummer" pattern="[0-9]{8,12}"  title="Oppgi telefonnummer i formatet: 12345678. Oppgi landkode som 0047">
                        </section>
                        
                    </form>

                    <!-- Passord: gammelt, nytt, bekreft (Rullegardin) -->
                    <button type="button" id="kontoRullegardin" class="kontoRullegardin">Endre passord</button>
                    <section id="konto_rediger_pw" class="innholdRullegardin">
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Gammelt passord</p>
                            <input type="password" class="KontoredigeringFeltPW" name="gammeltpassord" value="" placeholder="Gammelt passord" form="konto_rediger_form">
                        </section>
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Nytt passord</p>
                            <input type="password" class="KontoredigeringFeltPW" name="nyttpassord" value="" placeholder="Nytt passord" form="konto_rediger_form" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Minimum 8 tegn, 1 liten og 1 stor bokstav">
                        </section>
                        <section class="konto_rediger_inputBoks">
                            <p class="endre_bruker_overskrift">Bekreft nytt passord</p>
                            <input type="password" class="KontoredigeringFeltPW" name="bekreftnyttpassord" value="" placeholder="Bekreft nytt passord" form="konto_rediger_form" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Minimum 8 tegn, 1 liten og 1 stor bokstav">
                        </section>
                        <input id="visPassordLbl" style="margin-bottom: 1em;" type="checkbox" onclick="visPassordInst()">
                        <label for="visPassordLbl">Vis passord</label>
                    </section>

                    <!-- Knapp for å lagre endringer -->
                    <input type="submit" name="subEndring" class="KontoredigeringFelt_knappLagre" value="Lagre endringer" form="konto_rediger_form">
                    <!-- Sender brukeren tilbake til forsiden -->
                    <button onClick="location.href='konto.php'" name="submit" class="lenke_knapp">Avbryt redigering</button>
                </section>
                <!-- Håndtering av feilmeldinger -->

                <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($konto_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                    <section id="mldFEIL_innhold">
                        <p id="mldFEIL"><?php echo($konto_melding) ?></p>  
                        <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                        <button id="mldFEIL_knapp" autofocus>Lukk</button>
                    </section>  
                </section>
            </main>
        <?php } else { ?>
            <!-- Konto brukeropplysninger -->
            <main id="konto_main" onclick="lukkHamburgerMeny()">
                <!-- Meldinger til bruker -->
                <?php if(isset($_GET['vellykket']) && $_GET['vellykket'] == 1){ ?>
                    <p id="mldOK">Konto oppdatert</p>  
                <?php }

                // Henter personvern
                $personvernQ = "select visfnavn, visenavn, visepost, vistelefonnummer from preferanse where bruker = " . $_SESSION['idbruker'];
                $personvernSTMT = $db->prepare($personvernQ);
                $personvernSTMT->execute();
                $personvernArtikkel = $personvernSTMT->fetch(PDO::FETCH_ASSOC); 

                $kanViseFornavn = false;
                $kanViseEtternavn = false;
                $kanViseEpost = false;
                $kanViseTlf = false;

                if(isset($personvernArtikkel['visfnavn']) && $personvernArtikkel['visfnavn'] == "1") {
                    $kanViseFornavn = true;
                }

                if(isset($personvernArtikkel['visenavn']) && $personvernArtikkel['visenavn'] == "1") {
                    $kanViseEtternavn = true;
                }

                if(isset($personvernArtikkel['visepost']) && $personvernArtikkel['visepost'] == "1") {
                    $kanViseEpost = true;
                }

                if(isset($personvernArtikkel['vistelefonnummer']) && $personvernArtikkel['visepost'] == "1") {
                    $kanViseTlf = true;
                }
                ?>

                <section class="brukerinformasjon">
                    <table class="brukerinformasjon_tabell">
                        <!-- Brukernavn output -->
                        <tr>
                            <th>Brukernavn:</th>
                                <td><?php echo($_SESSION['brukernavn']) ?></td>
                        <!-- Epost output -->
                        <tr>
                            <th>Epost:</th>
                                <?php if(preg_match("/\S/", $_SESSION['epost']) == 1) {echo("<td>" . $_SESSION['epost']); if($kanViseEpost == false) {echo(" (Skjult offentlig)");}} else {echo("<td style='font-style: italic;'>Ikke oppgitt");} ?></td>
                        </tr>  
                        <!-- Fornavn output -->
                        <tr>
                            <th>Fornavn:</th>
                                <?php if(preg_match("/\S/", $_SESSION['fornavn']) == 1) {echo("<td>" . $_SESSION['fornavn']); if($kanViseFornavn == false) {echo(" (Skjult offentlig)");}} else {echo("<td style='font-style: italic;'>Ikke oppgitt");} ?></td>
                        </tr>
                        <!-- Etternavn output -->
                        <tr>
                            <th>Etternavn:</th>
                                <?php if(preg_match("/\S/", $_SESSION['etternavn']) == 1) {echo("<td>" . $_SESSION['etternavn']); if($kanViseEtternavn == false) {echo(" (Skjult offentlig)");}} else {echo("<td style='font-style: italic;'>Ikke oppgitt");} ?></td>
                        </tr>
                        <!-- Telefonnummer output -->
                        <tr>
                            <th>Telefonnummer:</th>
                                <?php if(preg_match("/\S/", $_SESSION['telefonnummer']) == 1) {echo("<td>" . $_SESSION['telefonnummer']); if($kanViseTlf == false) {echo(" (Skjult offentlig)");}} else {echo("<td style='font-style: italic;'>Ikke oppgitt");} ?></td>
                        </tr>
                    
                    </table>
                    <button onClick="location.href='konto.php?rediger'" class="rediger_konto_knapp">Rediger konto</button>
                    
                    <button onclick="bekreftMelding('konto_bekreftAvr')" class="konto_avregistrer" id="konto_avregistrerKnapp">Avregistrering</button>

                    
                    <section id="konto_bekreftAvr" style="display: none;">
                        <section id="konto_bekreftAvrInnhold">
                            <h2>Avregistrering</h2>
                            <p>Er du sikker på av du vil avregistrere?</p>
                            <form method="POST" action="konto.php">
                                <input type="password" id="konto_avregistrerpassord" name="passord" placeholder="Oppgi passord" title="Oppgi passordet ditt for å bekrefte avregistrering" required>
                                <button id="konto_avregistrerMeg" name="avregistrerMeg">Avregistrer</button>
                            </form>
                            <button id="konto_avbrytKnapp" onclick="bekreftMelding('konto_bekreftAvr')">Avbryt</button>
                        </section>
                    </section>
                </section> 
                <!-- Håndtering av feilmeldinger -->

                <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($konto_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                    <section id="mldFEIL_innhold">
                        <p id="mldFEIL"><?php echo($konto_melding) ?></p>  
                        <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                        <button id="mldFEIL_knapp" autofocus>Lukk</button>
                    </section>  
                </section>
            </main>
        <?php } ?>
        <?php include("inkluderes/footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

    
<!-- Denne siden er utviklet av Ajdin Bajrovic, siste gang endret 06.03.2020 -->
<!-- Sist kontrollert av Glenn Petter Pettersen, siste gang 04.06.2020 -->
</html>