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

$glemtpassord_melding = "";
if(isset($_SESSION['glemtpassord_melding'])) {
    $glemtpassord_melding = $_SESSION['glemtpassord_melding'];
    unset($_SESSION['glemtpassord_melding']);
}

// Hoveddelen for glemt passord
if (isset($_POST['glemtPassord'])) {
    // Tester på om passordene er like
    if ($_POST['passord'] == $_POST['passord2']) {
        // Tester på om brukernavnet er fyllt ut
        if ($_POST['brukernavn'] != "") {
            $br = $_POST['brukernavn'];
            $pw = $_POST['passord'];

            // Validering av passordstyrke
            $storebokstaver = preg_match('@[A-Z]@', $pw);
            $smaabokstaver = preg_match('@[a-z]@', $pw);
            $nummer = preg_match('@[0-9]@', $pw);

            if ($pw == "") {
                // Ikke noe passord skrevet
                $_SESSION['glemtpassord_melding'] = "Du har ikke oppgitt et passord";
                header("Location: glemt_passord.php");
            } else if (!$storebokstaver || !$smaabokstaver || !$nummer || strlen($pw) < 8) {
                // Ikke tilstrekkelig passord skrevet
                $_SESSION['glemtpassord_melding'] = "Du har ikke oppgitt et tilstrekkelig passord";
                header("Location: glemt_passord.php");
            } else {
                // OK, vi salter passord for eksiterende bruker
                $kombinert = $salt . $pw;
                // Krypterer passorder med salting 
                $spw = sha1($kombinert);
                $lbr = strtolower($_POST['brukernavn']);
                $sql = "update bruker set passord='" . $spw . "' where lower(brukernavn)='". $lbr . "'";

                // Prepared statement for å beskytte mot SQL injection
                $stmt = $db->prepare($sql);

                $stmt->execute();

                // Ved update blir antall rader endret returnert, vi kan utnytte dette til å teste om noen endringer faktisk skjedde
                $antall = $stmt->rowCount();

                if ($antall > 0) {
                    // Alt gikk OK, sender til logginn med melding til bruker
                    header("location: logginn.php?vellykket=2");
                } else {
                    //Ikke ok, ber bruker om å oppgi brukernavn på nytt
                    $_SESSION['glemtpassord_melding'] = "Feil oppstod ved endring av passord";
                    header("Location: glemt_passord.php");
                }
            }
        } else {
            // Feilmelding 5, bruker har ikke fylt ut felt
            $_SESSION['glemtpassord_melding'] = "Ett eller flere felt er ikke fylt inn";
            header("Location: glemt_passord.php");
        }
    } else {
        // Feilmelding 2 = passord ikke like
        $_SESSION['glemtpassord_melding'] = "Passordene er ikke like";
        header("Location: glemt_passord.php");
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
        <title>Glemt Passord</title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="glemtpassord_body" onclick="lukkMelding('mldFEIL_boks')">
        <?php include("inkluderes/navmeny.php") ?>

        <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
        <!-- Kan ikke legge denne direkte i body -->
        <header onclick="lukkHamburgerMeny()">
            <!-- Logoen midten øverst på siden, med tittel -->
            <img src="bilder/klimate.png" alt="Klimate logo" class="Logo_forside">
        </header>

        <main onclick="lukkHamburgerMeny()">
            <!-- Form brukes til autentisering av bruker, bruker type="password" for å ikke vise innholdet brukeren skriver -->
            <form method="POST" action="glemt_passord.php" class="innloggForm">
                <section class="inputBoks">
                    <img class="icon" src="bilder/brukerIkon.png" alt="Brukerikon"> <!-- Ikonet for bruker -->
                    <input type="text" class="RegInnFelt" name="brukernavn" value="" placeholder="Skriv inn ditt brukernavn" required autofocus>
                </section>
                <section class="inputBoks">
                    <img class="icon" src="bilder/pwIkon.png" alt="Passordikon"> <!-- Ikonet for passord -->
                    <input type="password" class="RegInnFelt" name="passord" value="" placeholder="Skriv inn nytt passord" required>
                </section>
                <section class="inputBoks">
                    <img class="icon" src="bilder/pwIkon.png" alt="Passordikon"> <!-- Ikonet for passord -->
                    <input type="password" class="RegInnFelt" name="passord2" value="" placeholder="Gjenta passord" required>
                </section>
                <input type="submit" name="glemtPassord" class="RegInnFelt_knappLogginn" value="Endre passord"> 

                <!-- Håndtering av feilmeldinger -->

                <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($glemtpassord_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                    <section id="mldFEIL_innhold">
                        <p id="mldFEIL"><?php echo($glemtpassord_melding) ?></p>  
                        <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                        <button id="mldFEIL_knapp" autofocus>Lukk</button>
                    </section>  
                </section>
            </form>

            <!-- Sender brukeren tilbake til forsiden -->
            <button onClick="location.href='logginn.php'" class="lenke_knapp">Tilbake til logg inn</button>

        </main>
        <?php include("inkluderes/footer.php") ?>
    </body>

<!-- Denne siden er utviklet av Aron Snekkestad og Robin Kleppang, siste gang endret 21.02.2020 -->
<!-- Denne siden er kontrollert av Glenn Petter Pettersen, siste gang 04.06.2020 -->
</html>