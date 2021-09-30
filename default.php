<?php
session_start();

$default_melding = "";
if(isset($_SESSION['default_melding'])) {
    $default_melding = $_SESSION['default_melding'];
    unset($_SESSION['default_melding']);
}

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("inkluderes/innstillinger.php");


// Om vi har en systemfeil og bruker er innlogget så logger vi ut den brukeren
if(substr($default_melding, 0, 10) == "Systemfeil" && isset($_SESSION['idbruker'])) {
    session_destroy();
}


// Utlogging av bruker
if (isset($_POST['loggUt'])) { 
    session_destroy();
    header("Location: default.php?utlogget=1");
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
        <title>Klimate</title>
        <!-- Henter inn ekstern stylesheet -->
        <link rel="stylesheet" type="text/css" href="stylesheet.css">
        <!-- Henter inn favicon, bildet som dukker opp i fanene i nettleseren -->
        <link rel='icon' href='bilder/favicon.png' type='image/x-icon'>
        <!-- Henter inn JavaScript -->
        <script language="JavaScript" src="javascript.js"> </script>
    </head>

    <body id="default_body" onscroll="byttFargeNavbar()" onclick="lukkMelding('mldFEIL_boks')">
        <?php include("inkluderes/default_navmeny.php") ?>

        <!-- For å kunne lukke hamburgermenyen ved å kun trykke på et sted i vinduet må lukkHamburgerMeny() funksjonen ligge i deler av HTML-koden -->
        <!-- Kan ikke legge denne direkte i body -->
        <header onclick="lukkHamburgerMeny()">
            
            <!-- Logoen midten øverst på siden, med tittel -->
            <img src="bilder/klimate.png" alt="Klimate logo" class="Logo_forside">
            <h1 style="display: none">Bilde av Klimate logoen.</h1>    
 
            <!-- Meldinger til bruker -->
            <?php if(isset($_GET['utlogget']) && $_GET['utlogget'] == 1){ ?>
                <p id="mldOK">Du har logget ut</p>    

            <?php } else if(isset($_GET['avregistrert']) && $_GET['avregistrert'] == "true"){ ?>
                <p id="mldFEIL">Du har blitt avregistrert, du kan logge inn igjen ved senere tid om ønskelig</p>  

            <?php } ?>

            <section id="mldFEIL_boks" onclick="lukkMelding('mldFEIL_boks')" <?php if($default_melding != "") { ?> style="display: block" <?php } else { ?> style="display: none" <?php } ?>>
                <section id="mldFEIL_innhold">
                    <p id="mldFEIL"><?php echo($default_melding) ?></p>  
                    <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                    <button id="mldFEIL_knapp" autofocus>Lukk</button>
                </section>  
            </section>

            <p id="default_beskrivelse">Klimate er en nettside hvor du kan diskutere klimasaker med likesinnede personer!</p>
        </header>
        
        <!-- Funksjon for å lukke hamburgermeny når man trykker på en del i Main -->
        <main id="default_main" onclick="lukkHamburgerMeny()">   
            <section id="default_section">
                <?php if(substr($default_melding, 0, 10) != "Systemfeil") { ?>
                    <!-- IDene brukes til å splitte opp kolonnene i queries -->
                   <article>
                        <section class="default_overskriftSeksjon">
                            <h2>Populære artikler</h2>   
                        </section>
                        <!-- Dette vil da være resultat av en spørring mot database, bruk av echo for å vise -->
                        <section id="default_innholdSeksjonArtikkel">
                            <?php 
                                //-----------------------------------------------//
                                // Henter mest kommenterte/populære fra database //
                                //----------------------------------------------//

                                // Henter artikler fra database, sorterer på høyeste antall og viser denne
                                $mestKommenterteQ = "select count(idkommentar) as antall, idartikkel, artnavn, artingress from kommentar, artikkel
                                                    where kommentar.artikkel = artikkel.idartikkel
                                                    group by idartikkel
                                                    order by antall DESC limit 5";
                                $mestKommenterteSTMT = $db->prepare($mestKommenterteQ);
                                $mestKommenterteSTMT->execute();
                                $mestKommenterte = $mestKommenterteSTMT->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php for($i = 0; $i < count($mestKommenterte); $i++) { ?>
                                    <?php
                                    $hentArtBilde = "select hvor from bilder, artikkelbilde where artikkelbilde.idartikkel = " . $mestKommenterte[$i]['idartikkel'] . " and artikkelbilde.idbilde = bilder.idbilder";
                                    $stmtArtBilde = $db->prepare($hentArtBilde);
                                    $stmtArtBilde->execute();
                                    $resBilde = $stmtArtBilde->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <section class="default_artikkelBildeFelt">
                                        <?php
                                        if (!$resBilde) { ?>
                                            <!-- Standard artikkelbilde om arrangør ikke har lastet opp noe enda -->
                                            <img class="default_art_BildeBoks" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                        <?php } else {
                                            // Tester på om filen faktisk finnes
                                            $testPaa = $resBilde['hvor'];
                                            if(file_exists("$lagringsplass/$testPaa")) {  
                                                //Artikkelbilde som resultat av spørring
                                                if(file_exists("$lagringsplass/" . "thumb_" . $testPaa)) {  ?> 
                                                    <!-- Hvis vi finner et miniatyrbilde bruker vi det -->
                                                    <img class="default_art_BildeBoks" src="bilder/opplastet/thumb_<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($mestKommenterte[$i]['artnavn'])?>">
                                                <?php } else { ?>
                                                    <img class="default_art_BildeBoks" src="bilder/opplastet/<?php echo($resBilde['hvor'])?>" alt="Bilde for <?php echo($mestKommenterte[$i]['artnavn'])?>">
                                                <?php } ?>
                                            <?php } else { ?>
                                                <img class="default_art_BildeBoks" src="bilder/stockevent.jpg" alt="Bilde av Oleg Magni fra Pexels">
                                            <?php }
                                        } ?>
                                    </section>

                                    <section class="default_artikkelFelt">
                                        <h3 class="PopArtiklerOverskrift"><?php echo $mestKommenterte[$i]['artnavn'] ?> </h3>
                                        <p class="PopArtiklerIngress"><?php echo $mestKommenterte[$i]['artingress'] ?> </p>
                                        
                                        <img class="default_antallKommentarerIkon" src="bilder/meldingIkon.png" alt="Ikon for kommentarer">
                                        <?php
                                            $hentAntallKommentarer = "select count(idkommentar) as antall from kommentar where kommentar.artikkel = " . $mestKommenterte[$i]['idartikkel'];
                                            $hentAntallKommentarerSTMT = $db -> prepare($hentAntallKommentarer);
                                            $hentAntallKommentarerSTMT->execute();
                                            $antallkommentarer = $hentAntallKommentarerSTMT->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <p class="default_antallKommentarer"><?php echo $antallkommentarer['antall'] ?></p>
                                        <a href="artikkel.php?artikkel=<?php echo($mestKommenterte[$i]['idartikkel'])?>">Les videre</a>
                                                                                  
                                    </section>
                                <?php } ?>
                        </section>
                    </article>
                    <article>
                        <section class="default_overskriftSeksjon">
                            <h2>Kommende arrangementer</h2>  
                        </section>
                        
                        <section id="default_innholdSeksjonArrangement">
                            <?php 
                                //------------------------------------//
                                // Henter arrangementer fra database //
                                //-----------------------------------//

                                // Denne sorterer top 5 nyeste arrangementer
                                $hentArrangement = "select idevent, eventnavn, eventtekst, tidspunkt from event where tidspunkt is not null and tidspunkt > NOW() order by tidspunkt ASC LIMIT 5";
                                $hentArrangementSTMT = $db->prepare($hentArrangement);
                                $hentArrangementSTMT->execute();
                                $arrangementer = $hentArrangementSTMT->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                
                                <?php for($i = 0; $i < count($arrangementer); $i++) { ?>
                                        <section class="default_arrangementFelt">
                                            <h3 class="KommendeArrangementOverskrift"><?php echo $arrangementer[$i]['eventnavn'] ?> </h3>
                                            <img class="KommendeArrangement_datobilde" src="bilder/datoIkon.png" alt="Ikon for kalender">
                                            <p class="KommendeArrangementTidspunkt">
                                                <?php 
                                                    $dato = date_create($arrangementer[$i]['tidspunkt']);
                                                    echo(date_format($dato,"d/m/Y"));
                                                ?>
                                            </p>
                                            <?php
                                            if(strlen($arrangementer[$i]['eventtekst']) >= 150) { ?>
                                                <p class="KommendeArrangementTekst"><?php echo(substr($arrangementer[$i]['eventtekst'], 0, 150)) ?>...</p>
                                            <?php } else { ?>
                                                <p class="KommendeArrangementTekst"><?php echo($arrangementer[$i]['eventtekst']) ?></p>
                                            <?php } ?>                           
                                            
                                            <a href="arrangement.php?arrangement=<?php echo($arrangementer[$i]['idevent'])?>">Les videre</a>
                                        </section>
                                <?php } ?>
                        </section>
                    </article>
                <?php } ?>
            </section>
        </main>
        <?php include("inkluderes/default_footer.php") ?>
    </body>
    <?php include("inkluderes/lagFil_regler.php"); ?>

<!-- Denne siden er utviklet av Ajdin Bajrovic & Robin Kleppang, siste gang endret 04.06.2020 -->
<!-- Denne siden er kontrollert av Aron Snekkestad, siste gang 04.06.2020 -->
</html>