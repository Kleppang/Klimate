<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");

// Browser må validere cache med server før cached kopi kan benyttes
// Dette gjør at man kan gå frem og tilbake i profil uten at man får ERR_CACHE_MISS
header("Cache-Control: no cache");

// Enkel test som gjør det mulig å beholde brukerinput etter siden er lastet på nytt (Form submit)
$input_tittel = "";
$input_ingress = "";
$input_innhold = "";

if (isset($_SESSION['input_tittel'])) {
    // Legger innhold i variable som leses senere på siden
    $input_tittel = $_SESSION['input_tittel'];
    $input_ingress = $_SESSION['input_ingress'];
    $input_innhold = $_SESSION['input_innhold'];
    // Sletter innholdet så dette ikke eksisterer utenfor denne siden
    unset($_SESSION['input_tittel']);
    unset($_SESSION['input_ingress']);
    unset($_SESSION['input_innhold']);
}

$artikkel_melding = "";
if(isset($_SESSION['artikkel_melding'])) {
    $artikkel_melding = $_SESSION['artikkel_melding'];
    unset($_SESSION['artikkel_melding']);
}

if (isset($_POST['publiserArtikkel'])) {

    $_SESSION['input_tittel'] = $_POST['tittel'];
    $_SESSION['input_ingress'] = $_POST['ingress'];
    $_SESSION['input_innhold'] = $_POST['innhold'];

    if (strlen($_POST['tittel']) <= 45 && strlen($_POST['tittel']) > 0) {
        if (strlen($_POST['ingress']) <= 255 && strlen($_POST['ingress']) > 0) {
            if (strlen($_POST['innhold'] <= 1000) && strlen($_POST['tittel']) > 0) {
                // Tar utgangspunkt i at bruker ikke har lastet opp bilde
                $harBilde = false;

                // Sanitiserer innholdet før det blir lagt i databasen
                $tittel = filter_var($_POST['tittel'], FILTER_SANITIZE_STRING);
                $ingress = filter_var($_POST['ingress'], FILTER_SANITIZE_STRING);
                $innhold = filter_var($_POST['innhold'], FILTER_SANITIZE_STRING);
                
                // Spørringen som oppretter artikkelen
                $nyArtikkelQ = "insert into artikkel(artnavn, artingress, arttekst, bruker, tid) values('" . $tittel . "', '" . $ingress . "', '" . $innhold . "', '" . $_SESSION['idbruker'] . "', NOW())";
                $nyArtikkelSTMT = $db->prepare($nyArtikkelQ);
                $nyArtikkelSTMT->execute();
                $artikkelid = $db->lastInsertId();
                
                // Del for filopplastning
                if (is_uploaded_file($_FILES['bilde']['tmp_name'])) {
                    // Kombinerer artikkel med den siste artikkelid'en
                    $navn = "artikkel" . $artikkelid;
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

                    // Spørringen som lager koblingen mellom bilder og artikkel
                    $nyKoblingQ = "insert into artikkelbilde(idartikkel, idbilde) values('" . $artikkelid . "', '" . $bildeid . "')";
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
                unset($_SESSION['input_ingress']);
                unset($_SESSION['input_innhold']);

                header('Location: artikkel.php?artikkel=' . $artikkelid);

            } else { $_SESSION['artikkel_melding'] = "Du har enten ikke oppgitt noe innhold, eller innholdet er for langt"; header("Location: artikkel.php?nyartikkel"); } // Innhold tomt / for langt
        } else { $_SESSION['artikkel_melding'] = "Du har enten ikke oppgitt noen ingress, eller ingressen er for langt"; header("Location: artikkel.php?nyartikkel"); } // Ingress tomt / for langt
    } else { $_SESSION['artikkel_melding'] = "Du har enten ikke oppgitt noen tittel, eller tittelen er for langt"; header("Location: artikkel.php?nyartikkel"); } // Tittel tomt / for langt
}


if (isset($_POST['slettDenne'])) {
    // Sjekker om vi fortsatt er på riktig side (Og at bruker ikke har endret IDen vi ønsker å slette selv)
    if ($_POST['slettDenne'] == $_GET['artikkel']) {

        // Henter henvisningen til bildet fra databasen.
        $slettBildeFQ = "select hvor from artikkelbilde, bilder where artikkelbilde.idbilde = bilder.idbilder and artikkelbilde.idartikkel = " . $_POST['slettDenne'];
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

        // Begynner med å slette referansen til bildet artikkelen har
        $slettBildeQ = "delete from artikkelbilde where idartikkel = " . $_POST['slettDenne'];
        $slettBildeSTMT = $db->prepare($slettBildeQ);
        $slettBildeSTMT->execute();

        // Sletter så alle kommentarene for en artikkel
        $slettKommentarerQ = "delete from kommentar where artikkel = " . $_POST['slettDenne'];
        $slettKommentarerSTMT = $db -> prepare($slettKommentarerQ);
        $slettKommentarerSTMT -> execute();

        // Sletter så artikkelen
        $slettingQ = "delete from artikkel where idartikkel = " . $_POST['slettDenne'];
        $slettingSTMT= $db->prepare($slettingQ);
        $slettingSTMT->execute();

        $antallSlettet = $slettingSTMT->rowCount();

        if ($antallSlettet > 0) {
            header('location: artikkel.php?slettingok');
        } else {
            header('location: artikkel.php?slettingfeil');
        }
    }
}

$input_kommentar = "";
if (isset($_SESSION['input_kommentar'])) {
    // Legger innhold i variable som leses senere på siden
    $input_kommentar = $_SESSION['input_kommentar'];
    // Sletter innholdet så dette ikke eksisterer utenfor denne siden
    unset($_SESSION['input_kommentar']);
}

// Del for å legge til en ny kommentar
if(isset($_POST['sendKommentar'])) {

    $_SESSION['input_kommentar'] = $_POST['tekst'];

    $ingress = "";
    $tekst = "";

    if (strlen($_POST['tekst']) >= 255) {
        $ingress = substr($_POST['tekst'], 0, 255); 
    } else {
        $ingress = $_POST['tekst'];
    }
    
    if(strlen($_POST['tekst']) > 255 && strlen($_POST['tekst']) <= 1000) {
        $tekst = $_POST['tekst'];
    }
    
    if(strlen($_POST['tekst']) >= 1000) {
        $_SESSION['artikkel_melding'] = "Kommentaren er for lang"; 
        header("Location: artikkel.php?artikkel=" . $_GET['artikkel']);
    } else {
        // Legger til en ny kommentar
        $nyKommentarQ = "insert into kommentar(ingress, tekst, tid, bruker, artikkel) values(:ingress, :tekst, NOW(), " . $_SESSION['idbruker'] . ", " . $_GET['artikkel'] . ")";
        $nyKommentarSTMT = $db->prepare($nyKommentarQ);
        
        $nyKommentarSTMT->bindParam(':ingress', filter_var($ingress, FILTER_SANITIZE_STRING));
        $nyKommentarSTMT->bindParam(':tekst', filter_var($tekst, FILTER_SANITIZE_STRING));
    
        $nyKommentarSTMT->execute();
        $sendt = $nyKommentarSTMT->rowCount();

        unset($_SESSION['input_kommentar']);
        
        header("Location: artikkel.php?artikkel=" . $_GET['artikkel']);
    }
    
    
}

// Del for å slette en kommentar
if(isset($_POST['slettKommentar'])) {
    // Bare tillate at innlogget bruker kan slette sine egne kommentarer
    $sjekkPaaQ = "select idkommentar from kommentar, bruker where idkommentar = " . $_POST['idkommentar'] . " and bruker = " . $_SESSION['idbruker'];
    $sjekkPaaSTMT = $db->prepare($sjekkPaaQ);
    $sjekkPaaSTMT->execute();
    $funnetKommentar = $sjekkPaaSTMT->rowCount();

    // Hvis kommentaren som er funnet er større enn 0, eller innlogget brukertype er 1 (Admin) -> slett
    if($funnetKommentar > 0 or $_SESSION['brukertype'] == 1) {
        // Begynner med å slette kommentaren
        $slettKommentarQ = "delete from kommentar where idkommentar = " . $_POST['idkommentar'];
        $slettKommentarSTMT = $db->prepare($slettKommentarQ);
        $slettKommentarSTMT->execute();
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
                if(isset($_GET['nyartikkel'])) { ?>
                    Ny artikkel
                <?php } else if (isset($_GET['artikkel'])) {
                    $hentTittelQ = "select artnavn from artikkel where idartikkel = " . $_GET['artikkel'] ." and 
                        bruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                    $hentTittelSTMT = $db -> prepare($hentTittelQ);
                    $hentTittelSTMT->execute();
                    $artikkel_title = $hentTittelSTMT->fetch(PDO::FETCH_ASSOC);
                    if($artikkel_title) {
                        echo($artikkel_title['artnavn']);
                    } else {
                        echo("Artikkel ikke funnet");
                    }
                } else { ?>
                    Artikler
            <?php } ?> 
        </title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="artikkel_body" onclick="lukkMelding('mldFEIL_boks')" onload="visKommentar(), hentSide('side_artikkel', 'artikkel_tilbKnapp', 'artikkel_nesteKnapp'), artTabbing()" onresize="hentSide('side_artikkel', 'artikkel_tilbKnapp', 'artikkel_nesteKnapp')">
        <?php include("inkluderes/navmeny.php") ?>

        <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
        
            <?php if(isset($_GET['artikkel'])){
                // Henter artikkelen bruker ønsker å se
                $hent = "select * from artikkel, bruker where bruker=idbruker and idartikkel = " . $_GET['artikkel'] . " and 
                    artikkel.bruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) ";
                $stmt = $db->prepare($hent);
                $stmt->execute();
                $artikkel = $stmt->fetch(PDO::FETCH_ASSOC);
                $antallArtikkel = $stmt->rowCount();
                // rowCount() returnerer antall resultater fra database, er dette null finnes det ikke noe artikkel med denne artikkelid i databasen
                ?>
                <main id="artikkel_main" style="margin-top: 6em;"onclick="lukkHamburgerMeny()">
                <?php
                if ($antallArtikkel == 0) { ?>
                    <!-- Del for å vise feilmelding til bruker om at artikkel ikke eksisterer -->
                    <h1 id="artikkel_ikkefunnet">Artikkel ikke funnet</h1>
                <?php } else { 
                    // ------------------------------ artikler som er klikket på -----------------------------
                    // Del for å vise en spesifik artikkel
                    // Henter bilde fra database utifra artikkelid
                    $hentBilde = "select hvor from bilder, artikkelbilde where artikkelbilde.idartikkel = " . $_GET['artikkel'] . " and artikkelbilde.idbilde = bilder.idbilder";
                    $stmtBilde = $db->prepare($hentBilde);
                    $stmtBilde->execute();
                    $bilde = $stmtBilde->fetch(PDO::FETCH_ASSOC);
                    $antallBilderFunnet = $stmtBilde->rowCount();
                    ?>
                    <!--  -->
                    <!-- Innholdet i påklikket artikkel -->
                    <!-- rowCount() returnerer antall resultater fra database, er dette null finnes det ikke noe bilde i databasen -->
                    <?php if ($antallBilderFunnet != 0) {
                        if($artikkel['brukertype'] != 4) { ?>
                            <!-- Hvis vi finner et bilde til artikkelen viser vi det -->
                            <section class="bildeArtikkelSeksjon">
                                <?php // Tester på om filen faktisk finnes
                                $testPaa = $bilde['hvor'];
                                if(file_exists("$lagringsplass/$testPaa")) {  ?>  
                                    <img class="bildeArtikkel" src="bilder/opplastet/<?php echo($bilde["hvor"]) ?>" alt="Bilde av artikkel">  
                                <?php } ?>
                            </section>
                        <?php }
                    } ?>
                        <section class="Artikkel_innhold">
                            <h1 class="artikkel_overskrift"><?php echo($artikkel['artnavn'])?></h1>
                            <p><?php echo($artikkel['artingress'])?></p>
                            <p><?php echo($artikkel['arttekst'])?></p>
                        </section>
                        <section class="ForfatterInfo">
                            
                            <p id="ForfatterSkrevetAv">Skrevet av </p>
                            <?php
                            // Sjekker om bruker er avregistrert
                            if($artikkel['brukertype'] != 4) {
                                // Henter personvern
                                $personvernQ = "select visfnavn, visenavn from preferanse where bruker = " . $artikkel['bruker'];
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
                                    if(preg_match("/\S/", $artikkel['fnavn']) == 1) {
                                        $navn = $artikkel['fnavn'];  
                                    } else {
                                        $navn = $artikkel['brukernavn'];
                                    }
                                } else if($kanViseFornavn == false && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $artikkel['enavn']) == 1) {
                                        $navn = $artikkel['enavn'];  
                                    } else {
                                        $navn = $artikkel['brukernavn'];
                                    }
                                } else if($kanViseFornavn == true && $kanViseEtternavn == true) {
                                    if(preg_match("/\S/", $artikkel['enavn']) == 1) {
                                        $navn = $artikkel['fnavn'] . " " . $artikkel['enavn'];  
                                    } else {
                                        $navn = $artikkel['brukernavn'];
                                    }
                                } else {
                                    $navn = $artikkel['brukernavn'];
                                } ?>
                                <a id="ForfatterTilBruker" onClick="location.href='profil.php?bruker=<?php echo($artikkel['bruker'])?>'"><?php echo($navn)?></a>
                            <?php } else { ?>
                                <a id="ForfatterTilBruker" style="font-style: italic;">Avregistrert bruker</a>
                            <?php } ?>

                            
                            <?php setlocale(LC_ALL, "no, NO"); 
                            $dato=($artikkel['tid']);
                            ?>

                            
                            <p class="artikkelTid"><?php echo(date_format(date_create($dato), "j M H:i")) ?></p>
                        </section>
                    <?php

                    /*------------------------------------*/
                    /*------------------------------------*/
                    /*--Del for å kommentere en artikkel--*/
                    /*------------------------------------*/
                    /*------------------------------------*/ 
                    ?> 
                        <!-- Sjekker på brukernavnet for å se om bruker er innlogget og viser input kommentar feltet, hvis ikke ikke vis -->
                        <!-- Antall kommentarer av artikler --> 
                        <?php if (isset($_SESSION["idbruker"])) { ?>                         
                        <section id="artikkel_kommentarOversikt"> 
                            <img class="artikkel_antallKommentarerIkon" src="bilder/meldingIkon.png">
                            <?php
                                $hentAntallKommentarer = "select count(idkommentar) as antall from kommentar where kommentar.artikkel = " . $_GET['artikkel'] ." and 
                                    bruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW()))";
                                $hentAntallKommentarerSTMT = $db -> prepare($hentAntallKommentarer);
                                $hentAntallKommentarerSTMT->execute();
                                $antallkommentarer = $hentAntallKommentarerSTMT->fetch(PDO::FETCH_ASSOC);
                            ?>
                                <p id="artikkel_antallKommentarer"><?php echo $antallkommentarer['antall'] ?> kommentar(er)</p>
                        </section>      
                        
                        <!-- Skjuler/viser kommentarer og kommenteringen -->
                        <section id="skjulkommentarer" style="display: none;">
                            <section id="artikkel_kommentarSeksjon">
                                <!-- input kommentering felt -->
                                <form method="POST" id="kommentar_form" action="artikkel.php?artikkel=<?php echo($_GET['artikkel']) ?>">
                                    <textarea id="artikkel_nyKommentar" type="textbox" name="tekst" placeholder="Skriv din mening..." required><?php echo($input_kommentar) ?></textarea>
                                    <input id="artikkel_nyKommentar_knapp" type="submit" name="sendKommentar" value="Publiser kommentar">
                                </form>
                                
                                <!-- Henter kommentarer -->
                                <?php
                                    $hentKommentar = "select idkommentar, ingress, tekst, tid, brukernavn, bruker, brukertype from kommentar, bruker
                                                where kommentar.bruker = bruker.idbruker and kommentar.artikkel = ". $_GET['artikkel'] . " and 
                                                    bruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) 
                                                        order by tid DESC";
                                    $hentKommentarSTMT = $db->prepare($hentKommentar);
                                    $hentKommentarSTMT->execute();
                                    $kommentarer = $hentKommentarSTMT->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <?php for($i = 0; $i < count($kommentarer); $i++) { ?>
                                        <section id="artikkel_kommentarBoks">                
                                            <!-- Henter profilbilde, brukernavn, tid, og tekst for kommentaren-->
                                            <?php
                                            $brukerbildeQ = "select bruker, hvor from brukerbilde, bilder where brukerbilde.bilde = bilder.idbilder and brukerbilde.bruker= " . $kommentarer[$i]["bruker"];
                                            $brukerbildeSTMT = $db -> prepare($brukerbildeQ);
                                            $brukerbildeSTMT -> execute();
                                            $brukerbilde = $brukerbildeSTMT->fetch(PDO::FETCH_ASSOC);


                                            if($kommentarer[$i]['brukertype'] != 4) {
                                                if($brukerbilde) {
                                                    $testPaa = $brukerbilde['hvor'];
                                                    if(file_exists("$lagringsplass/$testPaa")) {  
                                                        if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) { ?> 
                                                            <img class="kommentar_profilBilde" src="bilder/opplastet/thumb_<?php echo($brukerbilde["hvor"])?>" alt="Profilbilde">   
                                                        <?php } else { ?>
                                                            <img class="kommentar_profilBilde" src="bilder/opplastet/<?php echo($brukerbilde["hvor"])?>" alt="Profilbilde">  
                                                        <?php }
                                                    } else { ?>
                                                        <img class="kommentar_profilBilde" src="bilder/profil.png" alt="Profilbilde">
                                                    <?php }
                                                } else { ?>
                                                    <img class="kommentar_profilBilde" src="bilder/profil.png" alt="Profilbilde">
                                                <?php } ?>
                                                <p class="kommentarBrukernavn"><?php echo $kommentarer[$i]['brukernavn'] ?> </p> 
                                            <?php } else { ?>
                                                <img class="kommentar_profilBilde" src="bilder/profil.png" alt="Profilbilde">
                                                <p class="kommentarBrukernavn" style="font-style: italic;">Avregistrert bruker</p> 
                                            <?php } ?>
                                            <p class="kommentarTid"><?php echo $kommentarer[$i]['tid'] ?> </p> 

                                            <p class="kommentarIngress" style="display: inline-block"><?php echo $kommentarer[$i]['ingress'] ?>
                                            <?php if($kommentarer[$i]['tekst'] != "") { ?>...</p>
                                                <p class="kommentarTekst" style="display: none"><?php echo $kommentarer[$i]['tekst'] ?></p>
                                                <p class="kommentar_lesknapp">Les mer</p>
                                            <?php } else { ?>
                                                </p>
                                            <?php } ?>

                                            <!-- Henter slette knapp for kommentarer basert på bruker -->
                                            <?php

                                            if ($kommentarer[$i]['bruker'] == $_SESSION['idbruker'] || $_SESSION['brukertype'] == 1) { ?>
                                                <form method="POST" id="artikkel_kommentar_slett" action="artikkel.php?artikkel=<?php echo($_GET['artikkel'])?>">
                                                    <input type="submit" id="artikkel_slettKommentar_knapp" name="slettKommentar" value="Slett kommentar">
                                                    <input type="hidden" id="artikkel_slettKommentar_valgt" name="idkommentar" value="<?php echo($kommentarer[$i]["idkommentar"])?>">
                                                </form>
                                                
                                            <?php } ?>
                                                                                
                                        </section>
                                    <?php } ?>    
                            </section> 
                            
                            <?php } else { ?>
                            <section id="artikkel_kommentarSeksjon">
                                <!-- Knapp for å sende brukeren til logg inn -->
                                <button onClick="location.href='logginn.php'" name="submit" class="artikkel_tilLogginn_knapp">Logg inn for å kommentere</button>
                                
                                <!-- Henter kommentarer -->
                                <?php
                                    $hentKommentar = "select idkommentar, ingress, tekst, tid, brukernavn, bruker from kommentar, bruker
                                                where kommentar.bruker = bruker.idbruker and kommentar.artikkel = ". $_GET['artikkel'] . " and 
                                                    bruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) 
                                                        order by tid DESC";
                                    $hentKommentarSTMT = $db->prepare($hentKommentar);
                                    $hentKommentarSTMT->execute();
                                    $kommentarer = $hentKommentarSTMT->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    
                                    <?php for($i = 0; $i < count($kommentarer); $i++) {

                                        // Sjekker om bruker er utestengt
                                        $hentEksklusjonQ = "select grunnlag, datotil from eksklusjon where bruker = :bruker and (datotil is null or datotil > NOW())";
                                        $hentEksklusjonSTMT = $db -> prepare($hentEksklusjonQ);
                                        $hentEksklusjonSTMT -> bindparam(":bruker", $kommentarer[$i]["bruker"]);
                                        $hentEksklusjonSTMT -> execute();

                                        $antEks = $hentEksklusjonSTMT -> rowCount();

                                        if($antEks == 0) { ?>
                                            <section id="artikkel_kommentarBoks">
                                                <?php
                                                $brukerbildeQ = "select bruker, hvor from brukerbilde, bilder where brukerbilde.bilde = bilder.idbilder and brukerbilde.bruker= " . $kommentarer[$i]["bruker"];
                                                $brukerbildeSTMT = $db -> prepare($brukerbildeQ);
                                                $brukerbildeSTMT -> execute();
                                                $brukerbilde = $brukerbildeSTMT->fetch(PDO::FETCH_ASSOC);
                                                ?>
                                                <img class="kommentar_profilBilde" src="bilder/opplastet/<?php echo($brukerbilde["hvor"])?>">
                                                <p class="kommentarBrukernavn"><?php echo $kommentarer[$i]['brukernavn'] ?> </p>
                                                <p class="kommentarTid"><?php echo $kommentarer[$i]['tid'] ?> </p> 
                                                
                                                <p class="kommentarIngress" style="display: inline-block"><?php echo $kommentarer[$i]['ingress'] ?>...</p>
                                                <p class="kommentarTekst" style="display: none"><?php echo $kommentarer[$i]['tekst'] ?></p>
                                                <p class="kommentar_lesknapp">Les mer</p>
                                            </section>
                                    <?php }
                                    } ?>    
                            </section> 
                            <?php } ?>

                        </section>
                        <section id="visSkjulKnapp">
                            <button onclick="javascript:VisSkjulKommentarer('skjulkommentarer')" name="submit" id="leskommentarer">Vis kommentarer</button>
                        </section>
                        <!-- Slett og tilbake knapper -->
                        <button id="artikkelValgt_tilbKnapp" onClick="location.href='artikkel.php'">Tilbake</button>
                        <?php 
                        if(isset($_SESSION['idbruker'])) {
                            $hentEierQ = "select bruker from artikkel where bruker = " . $_SESSION['idbruker'] . " and idartikkel = " . $_GET['artikkel'];
                            $hentEierSTMT = $db->prepare($hentEierQ);
                            $hentEierSTMT->execute();
                            $artikkelEier = $hentEierSTMT->fetch(PDO::FETCH_ASSOC);

                            if ($artikkelEier != false || $_SESSION['brukertype'] == 1) { ?>
                                <input type="button" id="artikkel_slettKnapp" onclick="bekreftMelding('artikkel_bekreftSlett')" value="Slett artikkelen">
                                <section id="artikkel_bekreftSlett" style="display: none;">
                                    <section id="artikkel_bekreftSlettInnhold">
                                        <h2>Sletting</h2>
                                        <p>Er du sikker på av du vil slette denne artikkelen?</p>
                                        <form method="POST" action="artikkel.php?artikkel=<?php echo($_GET['artikkel'])?>">
                                            <button id="artikkel_bekreftSlettKnapp" name="slettDenne" value="<?php echo($_GET['artikkel']) ?>">Slett</button>
                                        </form>
                                        <button id="artikkel_avbrytKnapp" onclick="bekreftMelding('artikkel_bekreftSlett')">Avbryt</button>
                                    </section>
                                </section>
                            <?php } ?>
                        <?php } ?>
                    </main>
                <?php } ?>
            <?php  } else if (isset($_GET['nyartikkel']) && ($_SESSION['brukertype'] == 2 || $_SESSION['brukertype'] == 1)) { ?>      
    
                <header class="artikkel_header" onclick="lukkHamburgerMeny()">
                    <h1>Ny artikkel</h1>
                </header>

                <main id="artikkel_mainNy" onclick="lukkHamburgerMeny()">

                <article id="artikkel_articleNy">
                    <form method="POST" action="artikkel.php" enctype="multipart/form-data">
                        <h2>Tittel</h2>
                        <input id="artikkel_inputTittel" type="text" maxlength="45" name="tittel" value="<?php echo($input_tittel) ?>" placeholder="Skriv inn tittel" autofocus required>
                        <h2>Ingress</h2>
                        <textarea id="artikkel_inputIngress" maxlength="255" name="ingress" rows="3" cols="35" placeholder="Skriv inn inngress" required><?php echo($input_ingress) ?></textarea>
                        <h2>Innhold</h2>
                        <textarea id="artikkel_inputInnhold" maxlength="1000" name="innhold" rows="5" cols="35" placeholder="Skriv inn innhold" required><?php echo($input_innhold) ?></textarea>
                        <h2>Bilde</h2>
                        <input type="file" name="bilde" id="bilde" accept=".jpg, .jpeg, .png">

                        <a href="artikkel.php" id="artikkel_lenke_knapp">Tilbake til artikler</a> 
                        <input id="artikkel_submitNy" type="submit" name="publiserArtikkel" value="Opprett artikkel">
                    </form>
                </article>

            <?php } else {
                // -------------------- Artikler som vises på artikkel.php forside----------------
            
                // Del for å vise alle artikler 
                $hentAlleArt = "select idartikkel, artnavn, artingress, arttekst, tid, brukernavn, enavn, fnavn, bruker, brukertype
                                FROM artikkel, bruker
                                WHERE bruker=idbruker  and 
                                    bruker NOT IN (select bruker from eksklusjon where (datotil is null or datotil > NOW())) 
                                order by idartikkel desc";
            
                $stmtArt = $db->prepare($hentAlleArt);
                $stmtArt->execute();
                $resArt = $stmtArt->fetchAll(PDO::FETCH_ASSOC); 
                
                // Variabel som brukes til å fortelle når vi kan avslutte side_sok
                $avsluttTag = 0;
                $antallSider = 0;

                $resAntall = $stmtArt->rowCount(); 
                ?>            
    
                <header class="artikkel_header" onclick="lukkHamburgerMeny()">
                    <h1>Artikler</h1>
                </header>
                <main id="artikkel_main" onclick="lukkHamburgerMeny()">
                    <section id="artikkel_redpanel">
                        <?php if(isset($_SESSION['brukertype']) && ($_SESSION['brukertype'] == 2 || $_SESSION['brukertype'] == 1)) { ?>
                        <a href="artikkel.php?nyartikkel" tabindex="-1"><p>Ny artikkel</p></a>
                        <a href="artikkel.php?nyartikkel" tabindex="7"> <!-- VIKTIG, tabindex -->
                            <img src="bilder/plussIkon.png" alt="Plussikon for å opprette ny artikkel">
                        </a>
                        <?php } ?>
                    </section>
                    <?php if(isset($_GET['slettingok'])) { ?> <p id="mldOK">Du har slettet artikkelen</p> <?php } ?>
                    <?php if(isset($_GET['slettingfeil'])) { ?> <p id="mldFEIL">Kunne ikke slette artikkelen</p> <?php } ?>
                <?php if ($resAntall > 0 ) { ?>
                    <?php for ($j = 0; $j < count($resArt); $j++) {
                        // Hvis rest av $j delt på 8 er 0, start section (Ny side)
                        if ($j % 8 == 0) { ?>
                        <section class="side_artikkel">
                        <?php $antallSider++; } $avsluttTag++; ?>
                        <section class="res_artikkel" onClick="location.href='artikkel.php?artikkel=<?php echo($resArt[$j]['idartikkel']) ?>'" tabindex = <?php echo($tabindex); $tabindex++; ?>>
                            <figure class="infoBoks_artikkel">

                                <?php // Henter bilde til artikkel
                                $hentArtBilde = "select hvor from bilder, artikkelbilde where artikkelbilde.idartikkel = " . $resArt[$j]['idartikkel'] . " and artikkelbilde.idbilde = bilder.idbilder";
                                $stmtArtBilde = $db->prepare($hentArtBilde);
                                $stmtArtBilde->execute();
                                $resBilde = $stmtArtBilde->fetch(PDO::FETCH_ASSOC);
                                
                                if (!$resBilde) { ?>
                                    <!-- Standard atikkelbilde om redaktør ikke har lastet opp noe enda -->
                                    <img class="BildeBoks_artikkel" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                <?php } else {
                                    // Tester på om filen faktisk finnes
                                    $testPaa = $resBilde['hvor'];
                                    if(file_exists("$lagringsplass/$testPaa")) { 
                                        // Artikkeltbilde som resultat av spørring
                                        if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {  ?> 
                                            <!-- Hvis vi finner et miniatyrbilde bruker vi det -->
                                            <img class="BildeBoks_artikkel" src="bilder/opplastet/thumb_<?php echo($resBilde['hvor'])?>" alt="Artikkelbilde for <?php echo($resArt[$j]['artnavn'])?>">
                                        <?php } else { ?>
                                            <img class="BildeBoks_artikkel" src="bilder/opplastet/<?php echo($resBilde['hvor'])?>" alt="Artikkelbilde for <?php echo($resArt[$j]['artnavn'])?>">
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img class="BildeBoks_artikkel" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                <?php }
                                } ?>
                            </figure>
                            <!-- brukerens profilbilde -->
                            <!-- blir hentet fram avhengig av hvilken bruker som har skrevet artikkelen -->
                            <?php
                            if($resArt[$j]['brukertype'] != 4) {
                                $hentPb="select hvor from brukerbilde, bilder where brukerbilde.bilde = bilder.idbilder and brukerbilde.bruker= " . $resArt[$j]['bruker'];
                                $stmtHentPb = $db->prepare($hentPb);
                                $stmtHentPb->execute();
                                $brukerPB = $stmtHentPb->fetch(PDO::FETCH_ASSOC);

                                
                                if($brukerPB) {
                                    $testPaa = $brukerPB['hvor'];
                                    // Tester på om filen faktisk finnes
                                    if(file_exists("$lagringsplass/$testPaa")) {
                                        if(file_exists($lagringsplass . "/thumb_" . $testPaa)) {  ?>
                                            <img class="navn_artikkel_bilde" src="bilder/opplastet/thumb_<?php echo($brukerPB['hvor'])?>" alt="Profilbilde">
                                        <?php } else { ?>
                                            <img class="navn_artikkel_bilde" src="bilder/opplastet/<?php echo($brukerPB['hvor'])?>" alt="Profilbilde">
                                        <?php } ?>
                                    <?php } else { ?>
                                        <img class="navn_artikkel_bilde" src="bilder/brukerIkonS.png" alt="Profilbilde">
                                    <?php } ?>
                                <?php } else { ?>
                                    <img class="navn_artikkel_bilde" src="bilder/brukerIkonS.png" alt="Profilbilde">
                                <?php }
                            } else { ?>
                                <img class="navn_artikkel_bilde" src="bilder/brukerIkonS.png" alt="Profilbilde">
                            <?php }
                            
                            
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
                                <p class="navn_artikkel"><?php echo($navn)?></p>
                            <?php } else { ?>
                                <p class="navn_artikkel" style="font-style: italic">Avregistrert bruker</p>
                            <?php } ?>
                            <p class="tid_artikkel"><?php echo(date_format(date_create($resArt[$j]['tid']), "j M H:i")) ?></p>
                            <img class="tid_artikkel_bilde" src="bilder/datoIkon.png">
                            <h2><?php echo($resArt[$j]['artnavn'])?></h2>
                            <p><?php echo($resArt[$j]['artingress'])?></p>
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
                <section id="artikkel_bunnSection">
                    <?php if ($antallSider > 1) {?>
                        <p id="sok_antSider">Antall sider: <?php echo($antallSider) ?></p>
                        <button type="button" id="artikkel_tilbKnapp" onclick="visForrigeSide('side_artikkel', 'artikkel_tilbKnapp', 'artikkel_nesteKnapp')">Forrige</button>
                        <button type="button" id="artikkel_nesteKnapp" onclick="visNesteSide('side_artikkel', 'artikkel_tilbKnapp', 'artikkel_nesteKnapp')">Neste</button>
                    <?php } ?>
                </section>
            <?php }  ?>
            <!-- Håndtering av feilmeldinger -->

            <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($artikkel_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                <section id="mldFEIL_innhold">
                    <p id="mldFEIL"><?php echo($artikkel_melding) ?></p>  
                    <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                    <button id="mldFEIL_knapp" autofocus>Lukk</button>
                </section>  
            </section>
        </main>
        <?php include("inkluderes/footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Robin Kleppang, Ajdin Bajrovic, Aron Snekkestad siste gang endret 06.03.2020 -->
<!-- Denne siden er kontrollert av Glenn Petter Pettersen, siste gang 04.06.2020 -->
</html>