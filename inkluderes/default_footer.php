<?php
?> 
<!-- Knapp som vises når du har scrollet i vinduet, tar deg tilbake til toppen -->
        <button onclick="tilbakeTilTopp()" id="toppKnapp" title="Toppen"><img src="bilder/pilopp.png" alt="Tilbake til toppen"></button>

        <!-- Footer, epost er for øyeblikket på en catch-all, videresendes til RK -->
        <footer id="default_footer">
            <p class=footer_beskrivelse>&copy; Klimate <?php echo date("Y");?> | <a href="mailto:kontakt@klimate.no">Kontakt oss</a>
                <!-- Om brukeren ikke er administrator eller redaktør, vis link for søknad til å bli redaktør -->
                <?php if (isset($_SESSION['idbruker']) and $_SESSION['brukertype'] == "3") { ?> | <a href="soknad.php">Søknad om å bli redaktør</a><?php } ?>
                <?php if (isset($_SESSION['idbruker'])) { ?> | <a id="regler_a" href="javascript:void(0)" onclick="aapneRegler()">Regler</a> <?php } ?>
            </p>
            <?php if (isset($_SESSION['idbruker'])) { ?>
                <section id="mldREGLER_boks" onclick="lukkRegler()">
                    <section id="mldREGLER_innhold">
                        <p id="mldREGLER"><?php include('generert/regler.html'); ?></p>  
                        <!-- Denne gjør ikke noe, men er ikke utelukkende åpenbart at man kan trykke hvor som helst -->
                        <button id="mldREGLER_knapp" autofocus>Lukk</button>
                    </section>  
                </section>
            <?php } ?>
        </footer>
<?php
// Denne siden er utviklet av Robin Kleppang, siste gang endret 01.06.2020
// Denne siden er kontrollert av Ajdin Bajrovic, siste gang 04.06.2020
?>