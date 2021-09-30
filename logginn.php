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

// Setter tidssonen, dette er for at One.com domenet skal fungere, brukes i sjekk mot innloggingsforsøk
date_default_timezone_set("Europe/Oslo");

// Enkel test som gjør det mulig å beholde brukerinput etter siden er lastet på nytt (Form submit)
$input_brukernavn = "";
if (isset($_SESSION['input_brukernavn'])) {
    $input_brukernavn = $_SESSION['input_brukernavn'];
    unset($_SESSION['input_brukernavn']);
}

$logginn_melding = "";
if(isset($_SESSION['logginn_melding'])) {
    $logginn_melding = $_SESSION['logginn_melding'];
    unset($_SESSION['logginn_melding']);
}


if (isset($_POST['submit'])) {
    $_SESSION['input_brukernavn'] = $_POST['brukernavn'];
    // Ventetiden når en bruker er lukket ute
    $ventetid = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . "- 180 seconds"));

    // Hvis feiltelleren ikke eksisterer har ikke bruker forsøkt å logge inn hittils, oppretter da disse med standardverdier
    if (!isset($_SESSION['feilteller'])) {
        $_SESSION['feilteller'] = 0;
        $_SESSION['sistFeilet'] = date("Y-m-d H:i:s");
    }

    // Hvis det har gått 3 minutter mellom siste gang bruker feiler innlogging, tøm telleren
    if ($_SESSION['sistFeilet'] <= $ventetid) {
        $_SESSION['feilteller'] = 0;
    }

    // Sjekker først om bruker har feilet innlogging for mange ganger
    if ($_SESSION['feilteller'] < 5) {

        $br = $_POST['brukernavn'];
        $lbr = strtolower($_POST['brukernavn']);
        $pw = $_POST['passord'];
        $kombinert = $salt . $pw;

        // Krypterer det saltede passordet
        $spw = sha1($kombinert);

        $hentBrukerInfoQ = "select * from bruker where lower(brukernavn) = :brukernavn and passord = :passord";
        $hentBrukerInfoSTMT = $db -> prepare($hentBrukerInfoQ);
        $hentBrukerInfoSTMT -> bindparam(":brukernavn", $lbr);
        $hentBrukerInfoSTMT -> bindparam(":passord", $spw);
        $hentBrukerInfoSTMT->execute();
        $resultat = $hentBrukerInfoSTMT->fetch(PDO::FETCH_ASSOC);

        if($resultat) {
            // Sjekker om bruker har tidligere avregistrert seg
            if ($resultat['brukertype'] == 4) {
                $oppdaterBrukertypeQ = "update bruker set brukertype = 3 where idbruker = " . $resultat['idbruker'];
                $oppdaterBrukertypeSTMT = $db->prepare($oppdaterBrukertypeQ);
                $oppdaterBrukertypeSTMT->execute();
                
                $antallEndret = $oppdaterBrukertypeSTMT->rowCount();

                if ($antallEndret == 0) {
                    $_SESSION['logginn_melding'] = "Kunne ikke logge inn, vennligst forsøk på nytt";
                    header("Location: logginn.php");
                } else {
                    $resultat['brukertype'] = 3;
                }
            }

            // Sjekker om bruker har en aktiv utestengelse
            $hentEksklusjonQ = "select grunnlag, datotil from eksklusjon where bruker = :bruker and (datotil is null or datotil > NOW())";
            $hentEksklusjonSTMT = $db -> prepare($hentEksklusjonQ);
            $hentEksklusjonSTMT -> bindparam(":bruker", $resultat['idbruker']);
            $hentEksklusjonSTMT -> execute();

            $eksklusjon = $hentEksklusjonSTMT -> fetch(PDO::FETCH_ASSOC); 

            if($eksklusjon) {
                if($eksklusjon['datotil'] == null) {
                    $dato = "er permanent";
                } else {
                    $dato = "varer til: " . $eksklusjon['datotil'];
                }
                $_SESSION['logginn_melding'] = "Du har blitt utestengt for '" . $eksklusjon['grunnlag'] . "', utestengelsen " . $dato;
                header("Location: logginn.php");
            } else {
                $_SESSION['idbruker'] = $resultat['idbruker'];
                $_SESSION['brukernavn'] = $resultat['brukernavn'];
                $_SESSION['fornavn'] = $resultat['fnavn'];
                $_SESSION['etternavn'] = $resultat['enavn'];
                $_SESSION['epost'] = $resultat['epost'];
                $_SESSION['telefonnummer'] = $resultat['telefonnummer'];
                $_SESSION['brukertype'] = $resultat['brukertype'];
                
                $_SESSION['feilteller'] = 0;
    
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
    
                // Fjerner session variable for brukerinput om ingen feil oppstår
                unset($_SESSION['input_brukernavn']);
    
                header("Location: backend.php");
            }
        } else {    
            // Øker teller for feilet innlogging med 1
            $_SESSION['feilteller']++;
            $_SESSION['sistFeilet'] = date("Y-m-d H:i:s");
            
            $_SESSION['logginn_melding'] = "Kunne ikke logge inn, vennligst forsøk på nytt";
            header("Location: logginn.php");
        }
    } else {
        // Bruker har feilet for mange ganger, gir tilbakemelding til bruker
        $_SESSION['logginn_melding'] = "Du har feilet innlogging for mange ganger, vennligst vent";
        header("Location: logginn.php");
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
        <title>Innlogging</title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="logginn_body" onclick="lukkMelding('mldFEIL_boks')">
        <?php include("inkluderes/navmeny.php") ?>
        
        <main id="toppMain" onclick="lukkHamburgerMeny()">
            <!-- Form brukes til autentisering av bruker, bruker type="password" for å ikke vise innholdet brukeren skriver -->
            <form method="POST" action="logginn.php" class="innloggForm">
                <section class="inputBoks">
                    <img class="icon" src="bilder/brukerIkon.png" alt="Brukerikon"> <!-- Ikonet for bruker -->
                    <input type="text" class="RegInnFelt" name="brukernavn" value="<?php echo($input_brukernavn) ?>" placeholder="Skriv inn brukernavn" required autofocus>
                </section>
                <section class="inputBoks">
                    <img class="icon" src="bilder/pwIkon.png" alt="Passordikon"> <!-- Ikonet for passord -->
                    <input type="password" class="RegInnFeltPW" name="passord" value="" placeholder="Skriv inn passord" required>
                </section>
                <input id="visPassordLbl" style="margin-bottom: 1em;" type="checkbox" onclick="visPassordReg()">
                <label for="visPassordLbl">Vis passord</label>

                <?php if(isset($_GET['vellykket']) && $_GET['vellykket'] == 1){ ?>
                    <p id="mldOK">Bruker opprettet, vennligst logg inn</p>    
                
                <?php } else if(isset($_GET['vellykket']) && $_GET['vellykket'] == 2){ ?>
                    <p id="mldOK">Passord endret</p>
                <?php } ?>

                <input type="submit" name="submit" class="RegInnFelt_knappLogginn" value="Logg inn">   
            </form>
                
            <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($logginn_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                <section id="mldFEIL_innhold">
                    <p id="mldFEIL"><?php echo($logginn_melding) ?></p>  
                    <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                    <button id="mldFEIL_knapp" autofocus>Lukk</button>
                </section>  
            </section>

            <!-- Sender brukeren tilbake til forsiden -->
            <button onClick="location.href='glemt_passord.php'" class="lenke_knapp">Glemt passord?</button>
            <button onClick="location.href='default.php'" class="lenke_knapp">Tilbake til forside</button>

        </main>
        <?php include("inkluderes/footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Aron Snekkestad, Robin Kleppang, siste gang endret 05.05.2020 -->
<!-- Denne siden er kontrollert av Glenn Petter Pettersen siste gang 04.06.2020 -->
</html>