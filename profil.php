<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//------------------------------- //
include("inkluderes/innstillinger.php");

// Browser må validere cache med server før cached kopi kan benyttes
// Dette gjør at man kan gå frem og tilbake i profil uten at man får ERR_CACHE_MISS
header("Cache-Control: no cache");

//------------------------------//
// Test om man ser egen profil  //
//------------------------------//

if (isset($_SESSION['idbruker']) && $_SESSION['idbruker'] == $_GET['bruker']) {
    $egen = true;
} else {
    $egen = false;
}

if (!isset($_GET['bruker'])) {
    if (!isset($_SESSION['idbruker'])) {
        header("location: sok.php?melding=1");
    } else {
        header("location: profil.php?bruker=" . $_SESSION['idbruker']);
    }
}

// Sjekker om brukeren er utestengt, sender til egen profil om dette skjer
$sjekkUtestengelseQ = "select bruker from eksklusjon where bruker = :bruker and (datotil is null or datotil > NOW())";
$sjekkUtestengelseSTMT = $db -> prepare($sjekkUtestengelseQ);
$sjekkUtestengelseSTMT -> bindParam(":bruker", $_GET['bruker']);
$sjekkUtestengelseSTMT -> execute();
$utestengt = $sjekkUtestengelseSTMT -> fetch(PDO::FETCH_ASSOC);

if($utestengt) {
    header("Location: profil.php?bruker=" . $_SESSION['idbruker']);
}

// Sjekker om brukeren har blitt avregistrert, sender til egen profil om dette skjer
$sjekkAvregistreringQ = "select brukertype from bruker where idbruker = :bruker and brukertype = 4";
$sjekkAvregistreringSTMT = $db -> prepare($sjekkAvregistreringQ);
$sjekkAvregistreringSTMT -> bindParam(":bruker", $_GET['bruker']);
$sjekkAvregistreringSTMT -> execute();
$avregistrert = $sjekkAvregistreringSTMT -> fetch(PDO::FETCH_ASSOC);

if($avregistrert) {
    header("Location: profil.php?bruker=" . $_SESSION['idbruker']);
}


// -------------------- //
// Oppdater profilbilde //
// -------------------- //
if (isset($_POST['endreBilde'])) {
    // Del for filopplastning
    if (is_uploaded_file($_FILES['bilde']['tmp_name'])) {
        // Kombinerer bruker med idbruker
        $navn = "bruker" . $_SESSION['idbruker'];
        $navnMini = "thumb_" . $navn;
        // Henter filtypen
        $filtype = "." . substr($_FILES['bilde']['type'], 6, 4);
        // Kombinerer navnet med filtypen
        $bildenavn = $navn . $filtype;
        
        // Selve prosessen som flytter bildet til bestemt lagringsplass
        // Test om det finnes en fil med samme navn
        // Opprett navn med de 3 ulike filtypene
        $navnjpg = $navn . ".jpg";
        $navnjpeg = $navn . ".jpeg";
        $navnpng = $navn . ".png";
        // Test på om bildet finnes
        if(file_exists("$lagringsplass/$navnjpg")) {
            // Dropp i så fall
            unlink("$lagringsplass/$navnjpg");

        } elseif(file_exists("$lagringsplass/$navnjpeg")) {
            // Dropp i så fall
            unlink("$lagringsplass/$navnjpeg");

        } elseif (file_exists("$lagringsplass/$navnpng")) {
            // Dropp i så fall
            unlink("$lagringsplass/$navnpng");
        }
        // Flytt bildet fra temp til opplastet
        move_uploaded_file($_FILES['bilde']['tmp_name'], "$lagringsplass/$bildenavn");

        
        $navnMinijpg = "thumb_" . $navnjpg;
        $navnMinijpeg = "thumb_" . $navnjpeg;
        $navnMinipng = "thumb_" . $navnpng;
        // Test på om miniatyrbildet finnes
        if(file_exists("$lagringsplass/$navnMinijpg")) {
            // Dropp i så fall
            unlink("$lagringsplass/$navnMinijpg");

        } elseif(file_exists("$lagringsplass/$navnMinijpeg")) {
            // Dropp i så fall
            unlink("$lagringsplass/$navnMinijpeg");

        } elseif (file_exists("$lagringsplass/$navnMinipng")) {
            // Dropp i så fall
            unlink("$lagringsplass/$navnMinipng");
        }

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
        
        // Test om brukeren har et bilde fra før
        $hentBilde = "select idbilder, hvor from bilder, brukerbilde where brukerbilde.bruker = " . $_SESSION['idbruker'] . " and brukerbilde.bilde = bilder.idbilder";
        $stmtBilde = $db->prepare($hentBilde);
        $stmtBilde->execute();
        $bilde = $stmtBilde->fetch(PDO::FETCH_ASSOC);
        $antallBilderFunnet = $stmtBilde->rowCount();
        // rowCount() returnerer antall resultater fra database, er dette null finnes det ikke noe bilde i databasen
        if ($antallBilderFunnet != 0) {
            // Legger til bildet i databasen
            $nyttBildeQ = "update bilder set hvor = '" . $bildenavn . "' where idbilder = " . $bilde['idbilder'];
            $nyttBildeSTMT = $db->prepare($nyttBildeQ);
            $nyttBildeSTMT->execute();
        } else {
            // Hvis brukeren ikke har et tilknyttet bilde:
            // Legger til bildet i databasen
            $nyttBildeQ = "insert into bilder(hvor) values('" . $bildenavn . "')";
            $nyttBildeSTMT = $db->prepare($nyttBildeQ);
            $nyttBildeSTMT->execute();
            // Returnerer siste bildeid
            $bildeid = $db->lastInsertId();
            // Lag en kobling mellom bilder og brukerbilde
            $nyKoblingQ = "insert into brukerbilde(bruker, bilde) values('" . $_SESSION['idbruker'] . "', '" . $bildeid . "')";
            $nyKoblingSTMT = $db->prepare($nyKoblingQ);
            $nyKoblingSTMT->execute();
        }
    }
}

//----------------------//
// Oppdater preferanser //
//----------------------//
if ($egen) {
    if(isset($_POST['oppdaterPreferanser'])) {
        if(isset($_POST['fnavnToggle'])) {
            $visfnavnNy = "1";
        } else $visfnavnNy = "0";
        if(isset($_POST['enavnToggle'])) {
            $visenavnNy = "1";
        } else $visenavnNy = "0";
        if(isset($_POST['epostToggle'])) {
            $visepostNy = "1";
        } else $visepostNy = "0";
        if(isset($_POST['tlfToggle'])) {
            $vistelefonnummerNy = "1";
        } else $vistelefonnummerNy = "0";
        if(isset($_POST['beskrivelseToggle'])) {
            $visBeskrivelseNy = "1";
        } else $visBeskrivelseNy = "0";
        if(isset($_POST['interesserToggle'])) {
            $visInteresserNy = "1";
        } else $visInteresserNy = "0";
        $brukerNy = $_SESSION['idbruker'];

        // Slett gamle preferanser
        $slettPreferanse = "delete from preferanse where bruker = '" . $brukerNy . "'";
        $stmtSlettPreferanse = $db->prepare($slettPreferanse);
        $stmtSlettPreferanse->execute();

        // Oppdater
        $oppdaterPreferanse = "insert into preferanse(visfnavn, visenavn, visepost, vistelefonnummer, visbeskrivelse, visinteresser, bruker) 
                               values('" . $visfnavnNy . "', '" . $visenavnNy . "', '" . $visepostNy . "', '" . $vistelefonnummerNy . "', 
                               '" . $visBeskrivelseNy . "', '" . $visInteresserNy . "', '" . $brukerNy . "')";
                               $stmtOppdaterPreferanse = $db->prepare($oppdaterPreferanse);
                               $stmtOppdaterPreferanse->execute();
    }
}

 //-----------------------------//
 // Oppdaterer egen beskrivelse //
 //-----------------------------//
 // Litt usikker på om vi trenger den første if-testen (Muligheten skal uansett bare være tilstede for å oppdatere når $egen er sann), 
 // men heller for mye integritet enn for lite
 if ($egen) {
    if (isset($_POST['beskrivelse'])) {
        $oppdaterBeskrivelse = "update bruker set beskrivelse = :beskrivelse where idbruker = " . $_SESSION['idbruker'];
        $stmtOppdaterBeskrivelse = $db->prepare($oppdaterBeskrivelse);
        
        $stmtOppdaterBeskrivelse->bindParam(':beskrivelse', $_POST['beskrivelse']);

        $stmtOppdaterBeskrivelse->execute();
    }
 }

//-----------------------------------------------------//
// Oppdater brukerinteresse fra forhåndsdefinert liste //
//-----------------------------------------------------//
if ($egen) {
    if (isset($_POST['interesse'])) {
        $brukerPlaceholder = $_SESSION['idbruker'];
        $interessePlaceholder = $_POST['interesse'];
        $oppdaterBrukerinteresse = "insert into brukerinteresse(bruker, interesse) values(?, ?)";
        $stmtOppdaterBrukerinteresse = $db->prepare($oppdaterBrukerinteresse);
        $stmtOppdaterBrukerinteresse->execute([$brukerPlaceholder, $interessePlaceholder]);
    }
}
//-----------------------------------------------//
// Oppdater interesse med egendefinert interesse //
//-----------------------------------------------//
if ($egen) {
    if (isset($_POST['interesseEgendefinert'])) {
        if(preg_match("/\S/", $_POST['interesseEgendefinert'])) {
        // Kontroller at interessen også er unik sammenlignet i lower case
        // Hent alle navnene fra interesse
        $sammenligning = "select lower(interessenavn) as interessenavn from interesse";
        $stmtSammenligning = $db->prepare($sammenligning);
        $stmtSammenligning->execute();
        $interesseSammenlign = $stmtSammenligning->fetchAll(PDO::FETCH_ASSOC);

        // Lower case egendefinert interesse til sammenligning, trim whitespaces
        $egendefinertLower = trim(strtolower($_POST['interesseEgendefinert']));

        // Sammenlign hvert navn
        foreach($interesseSammenlign as $e) {
            foreach($e as $navn) {
                if($egendefinertLower == trim($navn)) {
                    // Opprett en variabel som tilsiser en match
                    $funnet = true;
                }
            }
        } // Slutt, foreach

        // Oppdater database hvis ingen match ble funnet
        if(!isset($funnet)) {
            // Oppdater interesse
            // ucwords endrer første bokstav (i hvert ord, evt.) til upper case
            $interessePlaceholder = ucwords($egendefinertLower);
            $brukerPlaceholder = $_SESSION['idbruker'];
            $oppdaterInteresse = "insert into interesse(interessenavn) values(?)";
            $stmtOppdaterInteresse = $db->prepare($oppdaterInteresse);
            $stmtOppdaterInteresse->execute([$interessePlaceholder]);

            // Hent id til ny interesse fra interesse
            $hentIdInteresse = "select idinteresse from interesse where interessenavn=?";
            $stmtHentIdInteresse = $db->prepare($hentIdInteresse);
            $stmtHentIdInteresse->execute([$_POST['interesseEgendefinert']]);
            $idInteresse = $stmtHentIdInteresse->fetch(PDO::FETCH_ASSOC);

            // Oppdater så brukerinteresse med denne verdien
            $brukerPlaceholder = $_SESSION['idbruker'];
            $interessePlaceholder = $idInteresse['idinteresse'];
            $oppdaterBrukerinteresse = "insert into brukerinteresse(bruker, interesse) values(?, ?)";
            $stmtOppdaterBrukerinteresse = $db->prepare($oppdaterBrukerinteresse);
            $stmtOppdaterBrukerinteresse->execute([$brukerPlaceholder, $interessePlaceholder]);
        }
     } else {
            // Ellers viser vi en feilmelding
            header('Location: profil.php?bruker=' . $_SESSION['idbruker'] . '&error=1');
        }
    }
}

//-----------------//
// Slett Interesse //
//-----------------//
if ($egen) {
    if (isset($_POST['interesseTilSletting'])) {
        $hentetInteresse = substr($_POST['interesseTilSletting'], 6);
        // Hent tilsvarende ID
        $hentIdInteresse = "select idinteresse from interesse where interessenavn= '" . $hentetInteresse . "'";
        $stmtHentIdInteresse = $db->prepare($hentIdInteresse);
        $stmtHentIdInteresse->execute();
        $idInteresse = $stmtHentIdInteresse->fetch(PDO::FETCH_ASSOC);

        // Slett interessen
        $slettInteresse = "delete from brukerinteresse 
                           where bruker=?
                           and interesse=?";
        $stmtSlettInteresse = $db->prepare($slettInteresse);
        $stmtSlettInteresse->execute([$_SESSION['idbruker'], $idInteresse['idinteresse']]);

    }
}

//------------------------------//
//------------------------------//
// Henting av data på bruker    //
//------------------------------//
//------------------------------//
//-----------------------//
// Henting av brukernavn //
//-----------------------//
$hentBrukernavnProfil = "select brukernavn from bruker where idbruker = " . $_GET['bruker'];
$stmtBrukernavnProfil = $db->prepare($hentBrukernavnProfil);
$stmtBrukernavnProfil->execute();
$brukernavnProfil = $stmtBrukernavnProfil->fetch(PDO::FETCH_ASSOC);

//---------------------------------------------------------------//
// Henting av navn/tlf/mail, avhengig av brukerens innstillinger //
//---------------------------------------------------------------//
$hentPersonaliaProfil = "Select fnavn, enavn, epost, telefonnummer from bruker where idbruker = " . $_GET['bruker'];
$stmtPersonaliaProfil = $db->prepare($hentPersonaliaProfil);
$stmtPersonaliaProfil->execute();
$personaliaProfil = $stmtPersonaliaProfil->fetch(PDO::FETCH_ASSOC);

//----------------------------------//
// Henting av brukerens preferanser //
//----------------------------------//
$hentPreferanser = "Select * from preferanse where bruker = " . $_GET['bruker'];
$stmtPreferanser = $db->prepare($hentPreferanser);
$stmtPreferanser->execute();
$preferanser = $stmtPreferanser->fetch(PDO::FETCH_ASSOC);

if($preferanser != false) {
    if($preferanser['visfnavn'] == "1") $visFnavn = true;
    if($preferanser['visenavn'] == "1") $visEnavn = true;
    if($preferanser['visepost'] == "1") $visEpost = true;
    if($preferanser['vistelefonnummer'] == "1") $visTlf = true;
    if($preferanser['visbeskrivelse'] == "1") $visBeskrivelse = true;
    if($preferanser['visinteresser'] == "1") $visInteresser = true;
}

//-----------------------//
// Henting av interesser //
//-----------------------//
$hentInteresseProfil = "select interessenavn from interesse, brukerinteresse where brukerinteresse.bruker = " . $_GET['bruker'] . " and brukerinteresse.interesse=interesse.idinteresse order by interessenavn";
$stmtInteresseProfil = $db->prepare($hentInteresseProfil);
$stmtInteresseProfil->execute();
$tellingInteresse = $stmtInteresseProfil->rowcount();

// Test på resultat
if ($tellingInteresse > 0) {
    // Hvis sant, har vi bekreftet at vi har noe å vise med echo
    // Resulterer i et 2D array
    $interesseProfil = $stmtInteresseProfil->fetchAll(PDO::FETCH_ASSOC);
// settes ellers til null, for øyeblikket
} else $interesseProfil = "Brukeren har ikke oppgitt noen interesser";

//----------------------------------------------//
// Hent alle interesser fra db, til en <select> //
//----------------------------------------------//
$hentInteresse = "select idinteresse, interessenavn from interesse order by interessenavn";
$stmtHentInteresse = $db->prepare($hentInteresse);
$stmtHentInteresse->execute();
$interesse = $stmtHentInteresse->fetchAll(PDO::FETCH_ASSOC);

//------------------------//
// Henting av beskrivelse //
//------------------------//
$hentBeskrivelseProfil = "select beskrivelse from bruker where idbruker = " . $_GET['bruker'];
$stmtBeskrivelseProfil = $db->prepare($hentBeskrivelseProfil);
$stmtBeskrivelseProfil->execute();
$tellingBeskrivelse = $stmtBeskrivelseProfil->rowcount();

// Test på resultatet
if ($tellingBeskrivelse > 0) {
    $beskrivelseProfil = $stmtBeskrivelseProfil->fetch(PDO::FETCH_ASSOC);
} else $beskrivelseProfil = null;

//---------------------//
// Henting av artikler //
//---------------------//
$hentArtikkelProfil = "select artnavn from artikkel where bruker = " . $_GET['bruker'];
$stmtArtikkelProfil = $db->prepare($hentArtikkelProfil);
$stmtArtikkelProfil->execute();
$tellingArtikkel = $stmtArtikkelProfil->rowcount();

// Test på resultat
if ($tellingArtikkel > 0) {
    $artikkelProfil = $stmtArtikkelProfil->fetchAll(PDO::FETCH_ASSOC);
} else $artikkelProfil = null;

//--------------------------//
// Henting av arrangementer //
//--------------------------//
$hentArrangementProfil = "select eventnavn from event where idbruker = " . $_GET['bruker'];
$stmtArrangementProfil = $db->prepare($hentArrangementProfil);
$stmtArrangementProfil->execute();
$tellingArrangement = $stmtArrangementProfil->rowcount();

// test på resultatet
if ($tellingArrangement > 0) {
    $arrangementProfil = $stmtArrangementProfil->fetchAll(PDO::FETCH_ASSOC);
} else $arrangementProfil = null;

//------------------------------------------------//
// Rapportering av brukere: Innsetting av rapport //
//------------------------------------------------//

// Enkel test som gjør det mulig å beholde brukerinput etter siden er lastet på nytt
$input_rapBeskrivelse = "";

if (isset($_SESSION['input_rapBeskrivelse'])) {
    // Legger innhold i variable som leses senere på siden
    $input_rapBeskrivelse = $_SESSION['input_rapBeskrivelse'];
    // Sletter innholdet så dette ikke eksisterer utenfor denne siden
    unset($_SESSION['input_rapBeskrivelse']);
}

if (isset($_POST['subRapportering'])) {
    $_SESSION['input_rapBeskrivelse'] = $_POST['rapBeskrivelse'];

    if ($_POST['rapBeskrivelse'] != "") {

        // Sanitiserer innholdet før det blir lagt i databasen
        $rapBeskrivelse = filter_var($_POST['rapBeskrivelse'], FILTER_SANITIZE_STRING);

        // Spørringen som oppretter arrangementet
        $nyRapporteringQ = "insert into brukerrapport(tekst, rapportertbruker, rapportertav, dato) values('" . $rapBeskrivelse . "', '" . $_GET['bruker'] . "', '" . 
                            $_SESSION['idbruker'] . "', NOW())";
        $nyRapporteringSTMT = $db->prepare($nyRapporteringQ);
        $nyRapporteringSTMT->execute();

        $antRap = $nyRapporteringSTMT -> rowCount();

        if($antRap > 0) {
            // OK, vi laster inn siden på nytt
            unset($_SESSION['input_rapBeskrivelse']);
            header("Location: profil.php?bruker=" . $_GET['bruker']);
        }
    }
}
// tabindex som skal brukes til å bestemme startpunkt på visningen av interessene
$tabindex = 17;

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
            <?php if(isset($_POST['innstillinger'])) { ?>
                Rediger profil
            <?php } else { 
                echo("Profil | " . $brukernavnProfil['brukernavn']);
            } ?>
        </title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="stoppScroll" onload="profilTabbing()"> 
        <article class="innhold">
            <?php include("inkluderes/navmeny.php") ?>

            <!-----------------------
            Del for brukerinformasjon
            ------------------------>
            <!-- ---------------------------------------------------- -->
            <!-- Tester på om rediger brukerinnstilinger er påklikket -->
            <!-- ---------------------------------------------------- -->
            <?php if(isset($_POST['innstillinger']) && $egen) { ?>
                <header class="profil_header" onclick="lukkHamburgerMeny()">
                </header>
                
                <main class="profil_main2">
                    <!-- -------------------------------------------------------------------------------------------------------------- -->
                    <!-- Del for visning av personalia -->
                    <section class="skjul_grid">
                        <?php if($egen) { ?>
                        <h2>Personalia</h2>
                        <section class="profil_persInf">
                            <!-- Et skjema for å oppdatere preferanser -->
                            <form id="profilForm" name="oppdaterPreferanser" method="POST" action="profil.php?bruker=<?php echo $_SESSION['idbruker'] ?>">
                                <input type="hidden" name="oppdaterPreferanser" value="oppdaterPreferanser" />   
                            <!-- Linje for fornavn -->
                                <p class="personalia">Fornavn</p>
                                    <label class="switch">
                                        <?php if(isset($visFnavn)) { ?>
                                        <input type="checkbox" name="fnavnToggle" value="visFnavn" tabindex="10" checked />
                                        <?php } else { ?> <input type="checkbox" name="fnavnToggle" value="visFnavn" tabindex="10" />
                                        <?php } ?>
                                        <span class="slider round"></span>
                                    </label>
                                <!-- Linje for etternavn -->
                                <p class="personalia">Etternavn</p>
                                    <label class="switch">
                                    <?php if(isset($visEnavn)) { ?>
                                        <input type="checkbox" name="enavnToggle" value="visEnavn" tabindex="11" checked />
                                        <?php } else { ?> <input type="checkbox" name="enavnToggle" value="visEnavn" tabindex="11" />
                                        <?php } ?>
                                        <span class="slider round"></span>
                                    </label>
                                <!-- Linje for epostadresse -->
                                <p class="personalia">E-Post Adresse</p>
                                    <label class="switch">
                                    <?php if(isset($visEpost)) { ?>
                                        <input type="checkbox" name="epostToggle" value="visEpost" tabindex="12" checked />
                                        <?php } else { ?> <input type="checkbox" name="epostToggle" value="visEpost" tabindex="12" />
                                        <?php } ?>
                                        <span class="slider round"></span>
                                    </label>
                                <!-- Linje for telefonnummer -->
                                <p class="personalia">Telefonnummer</p>
                                    <label class="switch">
                                    <?php if(isset($visTlf)) { ?>
                                        <input type="checkbox" name="tlfToggle" value="visTlf" tabindex="13" checked />
                                        <?php } else { ?> <input type="checkbox" name="tlfToggle" value="visTlf" tabindex="13" />
                                        <?php } ?>
                                        <span class="slider round"></span>
                                    </label>
                                <!-- Linje for beskrivelse -->
                                <p class="personalia">Beskrivelse</p>
                                    <label class="switch">
                                    <?php if(isset($visBeskrivelse)) { ?>
                                        <input type="checkbox" name="beskrivelseToggle" value="visBeskrivelse" tabindex="14" checked />
                                        <?php } else { ?> <input type="checkbox" name="beskrivelseToggle" value="visBeskrivelse" tabindex="14" />
                                        <?php } ?>
                                        <span class="slider round"></span>
                                    </label>
                                <!-- Linje for interesser -->
                                <p class="personalia">Interesser</p>
                                    <label class="switch">
                                    <?php if(isset($visInteresser)) { ?>
                                        <input type="checkbox" name="interesserToggle" value="visInteresser" tabindex="15" checked />
                                        <?php } else { ?> <input type="checkbox" name="interesserToggle" value="visInteresser" tabindex="15" />
                                        <?php } ?>
                                        <span class="slider round"></span>
                                    </label>
                            </form>
                        </section>
                    <?php } ?>
                </section>
                
                    <!-- -------------------------------------------------------------------------------------------------------------- -->
                    <!-- Del for å oppdatere brukerbeskrivelse -->
                    <section class="bsk_grid">
                        <?php if($egen) { ?>
                            <h2>Endre beskrivelse</h2>
                            <section class="profil_beskrivelse" >
                                <textarea form="profilForm" name="beskrivelse" maxlength="1024" placeholder="Skriv litt om deg selv" tabindex="9"><?php echo $beskrivelseProfil['beskrivelse'] ?></textarea>
                            </section>
                        <?php } ?>
                    </section>

                    <!-- Oppdater-knapp -->
                    <?php if($egen) { ?>
                        <button class="oppdater_profil_knapp" onclick="lastOppProfil()" tabindex="105">Oppdater beskrivelse og personalia</button>
                        <input type="button" id="profil_endreKnapp" onclick="bekreftMelding('profil_endreBilde')" value="Endre profilbilde" tabindex="16">
                        
                        <!-- pop-up vindu -->
                        <section id="profil_endreBilde" style="display: none;">
                            <section id="profil_endreBildeInnhold">
                                <h2>Endre profilbilde</h2>
                                <form class="profil_velgBilde" method="POST" enctype="multipart/form-data" action="profil.php?bruker=<?php echo $_SESSION['idbruker'] ?>">
                                    <h3>Velg et bilde</h3>
                                    <input type="file" name="bilde" id="bildeK" accept=".jpg, .jpeg, .png" tabindex="7">
                                    <input class="profil_knapp" type="submit" name="endreBilde" value="Last opp" tabindex="8">
                                </form>
                                
                                <button id="arrangement_inviterAvbrytKnapp" onclick="bekreftMelding('profil_endreBilde')">Avbryt</button>
                            </section>
                        </section>

                        
                    <?php } ?>

                    <section class="int2_grid">
                        <!-- Viser interesser -->
                        <h2 class="OverskriftInter">Interesser</h2>
                        <!-- Nøstet foreach -->
                        <!-- Ytre løkke -->
                        <section class="interesserSection">
                            <section class="interesserTags">
                                <?php if ($tellingInteresse != null) {
                                    foreach ($interesseProfil as $rad) {    
                                        foreach ($rad as $kolonne) { ?> 
                                            <!-- Test om bruker er i slettemodus -->
                                            <?php if (isset($_POST['slettemodus'])) { ?> 
                                                <input id="innholdAaSlette<?php echo($kolonne)?>" class="slett" form="slettemodus" name="interesseTilSletting" type="submit" onmouseenter="visSlett('innholdAaSlette<?php echo($kolonne)?>')" onmouseout="visSlett('innholdAaSlette<?php echo($kolonne)?>')" value="<?php echo($kolonne) ?>" tabindex = <?php echo($tabindex); $tabindex++; ?>></input>
                                                <!-- Ellers normal visning -->
                                            <?php } else { ?> 
                                                <p class="proInt"onClick="location.href='sok.php?brukernavn=&epost=&interesse=<?php echo($kolonne) ?>'" tabindex = <?php echo($tabindex); $tabindex++;?>><?php echo($kolonne); ?></p>
                                            <?php } // Slutt, else løkke    
                                        } // Slutt, indre løkke
                                    } // Slutt, ytre løkke
                                } ?> <!-- Slutt, IF-test -->
                            </section>
                            <!-- Del for å legge til interesser -->
                            <!-- dropdown med forhåndsdefinerte interesser, for egen profil -->

                            <!-- Slettemodus -->
                            <?php if (isset($_POST['innstillinger']) && $egen) { ?>
                            <form id="slettemodus" class="slett_interesse" method="POST" action="profil.php?bruker=<?php echo $_SESSION['idbruker'] ?>">
                                <input type="hidden" name="innstillinger" value="innstillinger">
                                <?php if(!isset($_POST['slettemodus'])) { ?>
                                    <input class="profil_knapp3" type="submit" name="slettemodus" value="Slett interesse" tabindex="100">
                                <?php } else { ?> 
                                    <input class="profil_knapp2" type="submit" name="avbryt" value="Avbryt" tabindex="100"> 
                                <?php } ?>
                            </form>
                            <?php } ?>
                        <?php if($egen) { ?>
                            <form class="profil_interesse" method="POST" action="profil.php?bruker=<?php echo $_SESSION['idbruker'] ?>">
                                <input type="hidden" name="innstillinger" value="innstillinger">
                                <select class="profil_input" name="interesse" tabindex="101">
                                    <?php $index=1 ?>
                                    <?php foreach($interesse as $rad) { ?>
                                        <option value="<?php echo($rad['idinteresse']) ?>"> <?php echo($rad['interessenavn']) ?> </option>
                                    <?php } ?> <!-- Slutt, ytre løkke -->
                                    
                                </select>
                                <input class="profil_knapp" type="submit" value="Legg til" tabindex="102"></input>
                            </form>

                            <!-- Egendefinert interesse -->
                            <form class="profil_interesse_egendefinert" method ="POST" action="profil.php?bruker=<?php echo $_SESSION['idbruker'] ?>&">
                                <input type="hidden" name="innstillinger" value="innstillinger">
                                <input class="profil_inputTekst" name="interesseEgendefinert" type="text" placeholder="Egendefinert" tabindex="103"></input>
                                <input class="profil_knapp" type="submit" value="Legg til" tabindex="104"></input>
                            </form>
                        </section>
                        <?php } ?> <!-- Slutt, IF-test -->                
                    </section> 
                    
                    <section class="knapp2_grid">
                        <!-- tilbake-knapp -->
                        <?php if($egen) {?>
                                <button onClick="location.href='profil.php?bruker=<?php echo $_SESSION['idbruker'] ?>'" name="redigerkonto" id="profilTilbakeKnapp" tabindex="107">Tilbake</button>
                        <?php }?>
                    </section>

                </main>
            <?php } else { ?>
                <!-- ---------- -->
                <!-- profilside -->
                <!-- ---------- -->
                <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
                <!-- Kan ikke legge denne direkte i body -->
                <header class="profil_header" onclick="lukkHamburgerMeny()">
                    
                </header>

                <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
                <main class="profil_main" onclick="lukkHamburgerMeny()">  
                    <section class="profil_pb">
                        <!-- Bilde av brukeren -->
                        <!-- FLYTT SØK-DELEN AV DENNE BITEN OPP TIL FØR HTML-ERKLÆRING? -->
                        <?php
                        $hentProfilbilde = "select hvor from bilder, brukerbilde where brukerbilde.bruker = " . $_GET['bruker'] . " and brukerbilde.bilde = bilder.idbilder";
                        $stmtProfilbilde = $db->prepare($hentProfilbilde);
                        $stmtProfilbilde->execute();
                        $profilbilde = $stmtProfilbilde->fetch(PDO::FETCH_ASSOC);
                        $antallProfilbilderFunnet = $stmtProfilbilde->rowCount();
                        // rowCount() returnerer antall resultater fra database, er dette null finnes det ikke noe bilde i databasen
                        if ($antallProfilbilderFunnet != 0) { ?>
                            <!-- Hvis vi finner et bilde til brukeren viser vi det -->
                            <section class="bildeKontroll" tabindex="-1">
                                <?php // Tester på om filen faktisk finnes
                                $testPaa = $profilbilde['hvor'];
                                if(file_exists("$lagringsplass/$testPaa")) {  ?> 
                                    <img src="bilder/opplastet/<?php echo($profilbilde['hvor'])?>" alt="Profilbilde" class="profil_bilde">
                                <?php } else { ?>
                                    <img src="bilder/profil.png" alt="Profilbilde" class="profil_bilde">
                                <?php } ?>
                                <h1 class="velkomst"> <?php echo $brukernavnProfil['brukernavn'] ?> </h1>
                            </section>
 
                        <?php } else { ?>
                            <!-- Hvis brukeren ikke har noe profilbilde, bruk standard profilbilde -->
                            <section class="bildeKontroll" tabindex="3">
                                <img src="bilder/profil.png" alt="Profilbilde" class="profil_bilde">
                                <!-- Vis brukernavn -->
                                <h1 class="velkomst"> <?php echo $brukernavnProfil['brukernavn'] ?> </h1>
                            </section>
                        <?php } ?>
                        
                        <!-- RAPPORTERING AV BRUKERE -->
                        <?php if(isset($_SESSION['brukertype']) && ($_SESSION['brukertype'] == 2 || $_SESSION['brukertype'] == 1 || $_SESSION['brukertype'] == 3)) { ?>
                            <?php if ($_SESSION['idbruker'] != $_GET['bruker']) { ?>
                            
                            <!-- Viser ikonet/knappen for rapportering -->
                            <input type="image" class="profil_rapporterFlaggIkon" src="bilder/rapporterflaggIkon2_rv2.png" alt="Ikon for rapportering" onclick="bekreftMelding('profil_rapporterBruker')">
                            <button onclick="bekreftMelding('profil_rapporterBruker')" name="submit" class="profil_rapporterKnapp">Rapporter</button>
                            <!-- pop-up vindu -->
                            <section id="profil_rapporterBruker" style="display: none;">
                                <section id="profil_rapporterBrukerInnhold">
                                    <?php { ?>
                                    
                                    <!-- Viser brukernavnet som skal rapporteres -->
                                    <?php } ?>
                                    <h2>Rapporter <?php echo($brukernavnProfil['brukernavn'])?></h2>
                                    
                                    <!-- Presenterer innholdet -->
                                    <section class="profil_rapporterInnhold">
                                        <form method="POST" action="profil.php?bruker=<?php echo($_GET['bruker'])?>">
                                            <textarea id="profil_inputRapportering" name="rapBeskrivelse" maxlength="1024" placeholder="Skriv hvorfor du ønsker å rapportere brukeren" required><?php echo($input_rapBeskrivelse) ?></textarea>
                                            <p>Din rapportering blir registrert med brukernavnet ditt og vedkommende det gjelder. Rapporteringen blir behandlet av en administrator.</p>
                                            <!-- Knapp for å rapportere bruker -->
                                            <input type="submit" name="subRapportering" class="profil_rapporterKnappVindu" value="Rapporter">
                                        </form>
                                    </section>
                                    
                                    <!-- Knapp for å avbryte rapporteringen -->
                                    <button id="profil_rapporterAvbrytKnapp" onclick="bekreftMelding('profil_rapporterBruker')">Avbryt</button>
                                </section>
                            </section>
                            <?php } else { ?>
                            <!-- Viser ikonet/knappen for rapportering som hidden-->
                            <input type="image" class="profil_rapporterFlaggIkon" src="bilder/rapporterflaggIkon2_rv2.png" alt="Ikon for rapportering" style=" visibility: hidden;">
                            <button onclick="bekreftMelding('profil_rapporterBruker')" name="submit" class="profil_rapporterKnapp" style=" visibility: hidden;">Rapporter</button>
                            <?php } ?>
                        <?php } ?>
                    </section>   
                        
                    <section class="ovs_grid">
                        <!---------------->
                        <!-- BRUKERINFO -->
                        <!---------------->
                        <h2>Oversikt</h2>
                        <section class="profil_persInf">
                            <!-- Fornavn -->
                            <!-- Test først på om det finnes en preferanse -->
                            <!-- Hvis ikke oppgis mangelen -->
                            <p class="personalia">Fornavn:</p> <?php if(!preg_match("/\S/", ($personaliaProfil["fnavn"]))) { ?>
                                <p class="ikkeOppgitt"> <?php echo("Ikke oppgitt"); ?> </p>
                                <!-- Test så på om info er skjult -->
                                <!-- Dette resulterer også i at brukere må velge å vise info til andre -->
                                <!-- Dette virker hensiktsmessig ihht. personvern -->
                                <?php } elseif(!isset($visFnavn)) { ?>
                                    <p class="ikkeOppgitt"> <?php echo("Skjult"); ?> </p>
                                <!-- Ellers vises den som vanlig -->
                                <?php } else { ?> <p> <?php echo($personaliaProfil["fnavn"]) ?> </p> <?php } ?>
                                
                                <p class="personalia">Etternavn:</p> <?php if(!preg_match("/\S/", ($personaliaProfil["enavn"]))) { ?>
                                <p class="ikkeOppgitt"> <?php echo("Ikke oppgitt"); ?> </p>
                                <?php } elseif(!isset($visEnavn)) { ?>
                                    <p class="ikkeOppgitt"> <?php echo("Skjult"); ?> </p>
                                <?php } else { ?> <p> <?php echo($personaliaProfil["enavn"]) ?> </p> <?php } ?>

                                <p class="personalia">E-Post Adresse:</p> <?php if(!preg_match("/\S/", ($personaliaProfil["epost"]))) { ?>
                                <p class="ikkeOppgitt"> <?php echo("Ikke oppgitt"); ?> </p>
                                <?php } elseif(!isset($visEpost)) { ?>
                                    <p class="ikkeOppgitt"> <?php echo("Skjult"); ?> </p>
                                <?php } else { ?> <p> <?php echo($personaliaProfil["epost"]) ?> </p> <?php } ?>

                                <p class="personalia">Telefonnummer:</p> <?php if(!preg_match("/\S/", ($personaliaProfil["telefonnummer"]))) { ?>
                                <p class="ikkeOppgitt"> <?php echo("Ikke oppgitt"); ?> </p>
                                <?php } elseif(!isset($visTlf)) { ?>
                                    <p class="ikkeOppgitt"> <?php echo("Skjult"); ?> </p>
                                <?php } else { ?> <p> <?php echo($personaliaProfil["telefonnummer"]) ?> </p> <?php } ?>
                        </section>
                    </section>    
                    
                    <!-- BESKRIVELSE -->
                    <section class="brukerBeskrivelse">
                    <h2>Beskrivelse</h2>
                    <?php if(!preg_match("/\S/", ($beskrivelseProfil["beskrivelse"]))) { ?>
                        <p class="ikkeOppgitt"> <?php echo("Ikke oppgitt"); ?> </p>
                        <?php } elseif(!isset($visBeskrivelse)) { ?>
                            <p class="ikkeOppgitt"> <?php echo("Skjult"); ?> </p>
                        <?php } else { ?> <p> <?php echo($beskrivelseProfil["beskrivelse"]) ?> </p> <?php } ?>
                    </section>

                    <section class="int_grid">
                        <!-- INTERESSER -->
                        <h2>Interesser</h2>
                        <!-- Nøstet foreach -->
                        <!-- Ytre løkke -->
                        <section class="interesserTags">
                        <?php if ($tellingInteresse != null && isset($visInteresser)) {
                            // Test på om bruker vil vise mer //
                            if(isset($_POST["visMer"])) {
                                // Sett i så fall $ //
                                // IT'S OVER 9000! // 
                                $max = 9999;
                            } else $max = 11;
                            // Teller for å ikke vise for mange interesser umiddelbart
                            $teller = 0;
                            foreach ($interesseProfil as $rad) {    
                                foreach ($rad as $kolonne) { ?>
                                    <!-- Oppdater teller -->
                                    <?php $teller++; ?>
                                    <!-- break; hvis vi har vist mange nok -->
                                    <?php if($teller > $max) { ?>
                                        <!-- POST en variabel som brukes til å angi max -->
                                        <form method="POST" action="profil.php?bruker=<?php echo $_SESSION['idbruker'] ?>">
                                            <input class="proInt" name="visMer" type="submit" value="..." tabindex = <?php echo($tabindex); $tabindex++;?> > </p>
                                        </form>
                                        <!-- break 2; bryter ut av begge løkkene -->
                                        <?php break 2;
                                    } ?>
                                    <!-- Test om bruker er i slettemodus -->
                                    <?php if (isset($_POST['slettemodus'])) { ?> 
                                        <input class="slett" form="slettemodus" name="interesseTilSletting" type="submit" value="<?php echo($kolonne) ?>" tabindex = <?php echo($tabindex); $tabindex++; ?>></input>
                                    <!-- Ellers normal visning -->
                                    <?php } else { ?> 
                                        <p class="proInt" onClick="location.href='sok.php?brukernavn=&epost=&interesse=<?php echo($kolonne) ?>'" tabindex = <?php echo($tabindex); $tabindex++;?>> <?php echo($kolonne); ?> </p>
                                    <?php } // Slutt, else løkke    
                                } // Slutt, indre løkke
                            } // Slutt, ytre løkke
                            // slutt, if-test. elseif-test
                        } elseif(!isset($visInteresser)) { ?> <p class="ikkeOppgitt"> <?php echo("Skjult") ?> </p>
                        <?php } ?> <!-- slutt, elseif -->
                        </section>
                    </section>
                    <section class="knapp_grid">
                    <?php if($egen) {?>
                        <button onclick="innstillinger(<?php echo $_GET['bruker'] ?>)" name="redigerkonto" class="rediger_profil_knapp" tabindex=30>Rediger informasjon</button>
                    <?php } ?>
                    </section>
                </main>
            <?php } ?> <!-- Test på om brukeren har klikket på rediger -->
            <?php include("inkluderes/footer.php") ?>
        </article>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Robin Kleppang, Petter Fiskvik, Aron Snekkestad, Ajdin Bajorvic siste gang endret 03.06.2020 -->
<!-- Denne siden er kontrollert av Petter Fiskvik, siste gang 04.06.2020 -->
</html>