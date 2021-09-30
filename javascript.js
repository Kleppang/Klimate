/* Funksjonen som åpner og lukker gardinmenyen, bruker height for at den skal gå over hele siden */
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/
var hamburgerMenyGjort = false;
var hentetBurger = document.getElementsByClassName("menytab");

function hamburgerMeny() {
  if (hamburgerMenyGjort == false) {
    var tabStart = 7;
    /* Når hamburgermenyen er åpen får menyinnholdet tabIndex for å kunne gå igjennom dette uten mus */
    document.getElementById("navMeny").style.height = "100%";
    hamburgerMenyGjort = true;
    // Bruker for løkke for å gå igjennom elementene mottat 
    for (i = 0; i < hentetBurger.length; i++) {
      hentetBurger[i].tabIndex = tabStart;
      tabStart++;
    }
  } else {
    /* Når hamburgermenyen er lukket setter vi tabIndex for innholdet til -1 for å ikke tabbe inn i denne */
    document.getElementById("navMeny").style.height = "0%";
    hamburgerMenyGjort = false;
    // Bruker for løkke for å gå igjennom elementene mottat 
    for (i = 0; i < hentetBurger.length; i++) {
      hentetBurger[i].tabIndex = "-1";
    }
  }
}
/* funksjon for å lukke hamburger-meny'en om man trykker utenfor dropdown'en */
/* Når hamburgermenyen er lukket setter vi tabIndex for innholdet til -1 for å ikke tabbe inn i denne */
function lukkHamburgerMeny() {
  if (hamburgerMenyGjort == true) {
    document.getElementById("navMeny").style.height = "0%";
    hamburgerMenyGjort = false;
    // Bruker for løkke for å gå igjennom elementene mottat 
    for (i = 0; i < hentetBurger.length; i++) {
      hentetBurger[i].tabIndex = "-1";
    }
  }
}
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/



/* Funksjon for å teste på touch */
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/
function kanTouchBrukes() {
  var msTouchOK = window.navigator.msMaxTouchPoints;
  var generalTouchOK = "ontouchstart" in document.createElement("div");
  if (msTouchOK || generalTouchOK) {
    return true;
  } else {
    return false;
  }
}

/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/


/* Del for å vise "Tilbake til topp" knapp */
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/
window.onscroll = function() { skrollFunksjon() };

/* Når vinduet scroller, kjør denne */
function skrollFunksjon() {
  var knappen = document.getElementById("toppKnapp");
  if (document.body.scrollTop > 250 || document.documentElement.scrollTop > 250) {
    /* Hvis vinduet er mer enn 20 piksler fra toppen, vis tilbake til topp knappen */
    knappen.style.display = "block";
  } else {
    /* Hvis vinduet er mindre enn 20 piksler fra toppen, fjern tilbake til topp knappen */
    knappen.style.display = "none";
  }
}
/* Når bruker trykker på knappen for å gå til topp, kjør denne */
function tilbakeTilTopp() {
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
}
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/




/* Denne blir kjørt når konto_rediger.php blir lastet inn, legger til en eventlistener */
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/
function kontoRullegardin() {
  // Funksjonen åpner og lukker rullegardinen innenfor passord endring ved klikk
  // Henter alle elementer som har class kontoRullegardin og KontoredigeringFeltPW
  var element = document.getElementsByClassName("kontoRullegardin");
  var elementPW = document.getElementsByClassName("KontoredigeringFeltPW");

  // Henter 
  var bredde = window.innerWidth;

  // Boolean for å vite om "Endre passord" delen er åpen eller ikke
  var aapnet = false;

  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet
  for (var i = 0; i < element.length; i++) {
      // Legger på en eventlistener som ser etter et klikk på alle elementer med class navn kontoRullegardin
      element[i].addEventListener("click", function() {
          // nextElementSibling returnerer det neste elementet som følger gjeldende element (this)
          var innholdRullegardin = this.nextElementSibling;
          if (aapnet == false) {
            // Når vinduet er åpnet, vis "Avbryt" 
            document.getElementById("kontoRullegardin").innerHTML = "Avbryt";
            aapnet = true;
          } else {
            // Når vinduet er lukket, vis "Endre passord" 
            document.getElementById("kontoRullegardin").innerHTML = "Endre passord";
            // FOR løkke som tømmer input feltene da bruker ikke lenger ønsker å oppdatere passord
            for (i = 0; i < elementPW.length; i++) {
              elementPW[i].value = "";
            }
            aapnet = false;
          }
          if (innholdRullegardin.style.display == "block") {
              // Med none vises ikke innholdet i rullegardinen (Gammelt pw, nytt pw og bekreft nytt pw)
              innholdRullegardin.style.display = "none";
          } else {
              // Nå vises innholdet
              innholdRullegardin.style.display = "block";
          }
      });
  }
}

// For å sikre riktig visning når bruker endrer størrelsen på vinduet
function fiksRullegardin() {
  // Henter bredde 
  var bredde = window.innerWidth;

  if (bredde >= 720) {
    document.getElementById("konto_rediger_pw").style.display = "block";
  } else if (document.getElementById("kontoRullegardin").innerHTML == "Endre passord") {
    document.getElementById("konto_rediger_pw").style.display = "none";
  } else if (document.getElementById("kontoRullegardin").innerHTML == "Avbryt") {
    document.getElementById("konto_rediger_pw").style.display = "block";
  }
}

// Åpner en bekreftelses-boks for å avregistrere og slette arrangement / artikler
function bekreftMelding(element) {
  var knapp = document.getElementById(element);
  var scroll = document.getElementById('stoppScroll');
  

  if (knapp.style.display == 'none') {
    knapp.style.display = 'block';
    if(scroll != null) {
      scroll.style.overflow = 'hidden';
    }

  } else {
    knapp.style.display = 'none';
    if(scroll != null) {
      scroll.style.overflow = 'visible';
    }
  }
  
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
}

// Lukker vindu til bruker
function lukkMelding(element) {
  var knapp = document.getElementById(element);
  //var scroll = document.getElementsByTagName('body');
  
  if (knapp.style.display != 'none') {

    knapp.style.display = 'none';
    //scroll.style.overflow = 'visible';

    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
  }
}

/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/


/* Denne blir kjørt når sok.php blir lastet inn, legger til en eventlistener */
/*---------------------------------------------------------------------------*/
/*---------------------------------------------------------------------------*/
function sokRullegardin() {
  // Funksjonen åpner og lukker rullegardinen for søk, lukker de andre søkemetodene 

  // Henter alle elementer som brukes
  var elementBr = document.getElementsByClassName("brukerRullegardin");
  var elementArt = document.getElementsByClassName("artikkelRullegardin");
  var elementArr = document.getElementsByClassName("arrangementRullegardin");
  var elementInp = document.getElementsByClassName("sokBrukerFelt");

  // Boolean for å vite om en av søkemetodene er åpnet
  var aapnet = false;
  
  var tabStart = 15;
  // FOR løkke som legger til tabIndex på valgene i menyen
  for (i = 0; i < elementInp.length; i++) {
    elementInp[i].tabIndex = tabStart;
    tabStart++;
  }

  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet
  for (var i = 0; i < elementBr.length; i++) {
      // Legger på en eventlistener som ser etter et klikk på alle elementer med class navn brukerRullegardin
      elementBr[i].addEventListener("click", function() {
          // previousElementSibling returnerer det forrige elementet, altså innholdet i menyen
          var innholdRullegardin = this.previousElementSibling;
          if (aapnet == false) {
            // Når vinduet er åpnet, vis "Avbryt" 
            document.getElementById("brukerRullegardin").innerHTML = "Avbryt";

            // Bytt fargen på knappen til rød for å enkelt visuelt vise hvordan man kan avbryte
            document.getElementById("brukerRullegardin").style.backgroundColor = "darkred";
            document.getElementById("brukerRullegardin").style.color = "white";


            // Gjemmer de to andre søkemetodene så lenge en er åpen
            document.getElementById("artikkelRullegardin").style.display = "none";
            document.getElementById("arrangementRullegardin").style.display = "none";


            // Sett aapnet til true, søkemenyen er åpen
            aapnet = true;
          } else {
            // Når vinduet er lukket, vis "Søk etter bruker" 
            document.getElementById("brukerRullegardin").innerHTML = "Søk etter bruker";
            
            // Setter farge tilbake til farge før åpning
            document.getElementById("brukerRullegardin").style.backgroundColor = "rgb(211, 211, 211)";
            document.getElementById("brukerRullegardin").style.color = "initial";

            // Bruker har lukket valgt søkemetode, viser de to andre søkene igjen
            document.getElementById("artikkelRullegardin").style.display = "block";
            document.getElementById("arrangementRullegardin").style.display = "block";

            // FOR løkke som tømmer input feltene og setter tabIndex til -1 (Tabber ikke inn i lukket meny)
            for (i = 0; i < elementInp.length; i++) {
              if (!elementInp[i].value == "Søk på bruker") {
                elementInp[i].value = "";
              }
              elementInp[i].tabIndex = "-1";
            }
            
            // Sett aapnet til false, søkemenyen er lukket
            aapnet = false;
          }
        
          // Enkel test på om valgene skal vises
          if (innholdRullegardin.style.display == "block") {
              // Viser ikke valgene til søket
              innholdRullegardin.style.display = "none";
          } else {
              // Viser valgene til søket
              innholdRullegardin.style.display = "block";
          }
      });
  }
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet
  for (var i = 0; i < elementArt.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med class navn artikkelRullegardin
    elementArt[i].addEventListener("click", function() {
        // previousElementSibling returnerer det forrige elementet, altså innholdet i menyen
        var innholdRullegardin = this.previousElementSibling;
        if (aapnet == false) {
          // Når vinduet er åpnet, vis "Avbryt" 
          document.getElementById("artikkelRullegardin").innerHTML = "Avbryt";

          // Bytt fargen på knappen til rød for å enkelt visuelt vise hvordan man kan avbryte
          document.getElementById("artikkelRullegardin").style.backgroundColor = "darkred";
          document.getElementById("artikkelRullegardin").style.color = "white";

          // Gjemmer de to andre søkemetodene så lenge en er åpen
          document.getElementById("brukerRullegardin").style.display = "none";
          document.getElementById("arrangementRullegardin").style.display = "none";


          // Sett aapnet til true, søkemenyen er åpen
          aapnet = true;
        } else {
          // Når vinduet er lukket, vis "Søk etter artikkel" 
          document.getElementById("artikkelRullegardin").innerHTML = "Søk etter artikkel";

          // Setter farge tilbake til farge før åpning
          document.getElementById("artikkelRullegardin").style.backgroundColor = "rgb(211, 211, 211)";
          document.getElementById("artikkelRullegardin").style.color = "initial";

          // Bruker har lukket valgt søkemetode, viser de to andre søkene igjen
          document.getElementById("brukerRullegardin").style.display = "block";
          document.getElementById("arrangementRullegardin").style.display = "block";

          // FOR løkke som tømmer input feltene og setter tabIndex til -1 (Tabber ikke inn i lukket meny)
          for (i = 0; i < elementInp.length; i++) {
            if (!elementInp[i].value == "Søk på artikkel") {
              elementInp[i].value = "";
            }
            elementInp[i].tabIndex = "-1";
          }
          
          // Sett aapnet til false, søkemenyen er lukket
          aapnet = false;
        }

        // Enkel test på om valgene skal vises
        if (innholdRullegardin.style.display == "block") {
            // Viser ikke valgene til søket
            innholdRullegardin.style.display = "none";
        } else {
            // Viser valgene til søket
            innholdRullegardin.style.display = "block";
        }
    });
  }
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet
  for (var i = 0; i < elementArr.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med class navn arrangementRullegardin
    elementArr[i].addEventListener("click", function() {
        // previousElementSibling returnerer det forrige elementet, altså innholdet i menyen
        var innholdRullegardin = this.previousElementSibling;
        if (aapnet == false) {
          // Når vinduet er åpnet, vis "Avbryt" 
          document.getElementById("arrangementRullegardin").innerHTML = "Avbryt";
          
          // Bytt fargen på knappen til rød for å enkelt visuelt vise hvordan man kan avbryte
          document.getElementById("arrangementRullegardin").style.backgroundColor = "darkred";
          document.getElementById("arrangementRullegardin").style.color = "white";

          // Gjemmer de to andre søkemetodene så lenge en er åpen
          document.getElementById("brukerRullegardin").style.display = "none";
          document.getElementById("artikkelRullegardin").style.display = "none";



          // Sett aapnet til true, søkemenyen er åpen
          aapnet = true;
        } else {
          // Når vinduet er lukket, vis "Søk etter arrangement" 
          document.getElementById("arrangementRullegardin").innerHTML = "Søk etter arrangement";

          // Setter farge tilbake til farge før åpning
          document.getElementById("arrangementRullegardin").style.backgroundColor = "rgb(211, 211, 211)";
          document.getElementById("arrangementRullegardin").style.color = "initial";

          // Bruker har lukket valgt søkemetode, viser de to andre søkene igjen
          document.getElementById("brukerRullegardin").style.display = "block";
          document.getElementById("artikkelRullegardin").style.display = "block";

          // FOR løkke som tømmer input feltene og setter tabIndex til -1 (Tabber ikke inn i lukket meny)
          for (i = 0; i < elementInp.length; i++) {
            if (!elementInp[i].value == "Søk på arrangement") {
              elementInp[i].value = "";
            }
            elementInp[i].tabIndex = "-1";
          }

          // Sett aapnet til false, søkemenyen er lukket
          aapnet = false;
        }
        
        // Enkel test på om valgene skal vises
        if (innholdRullegardin.style.display == "block") {
            // Viser ikke valgene til søket
            innholdRullegardin.style.display = "none";
        } else {
            // Viser valgene til søket
            innholdRullegardin.style.display = "block";
        }
    });
  }
}

/* Viser innhold på flere sider */
var forelopigSide = 0; // Starter med å sette side vi er på

// Funksjonene tar imot navn på elementene så vi kan bruke samme kode til forskjellige sider
function hentSide(side, tilbake, neste/*, res*/) {
  var vindu = window.innerWidth;
  // henter alle elementer med navn mottatt i variabel side
  var sideDel = document.getElementsByClassName(side);

  // Sjekker om element eksisterer
  if (typeof sideDel[forelopigSide] != 'undefined') {
    // Hvis ikke viser vi første side av søket
    sideDel[forelopigSide].style.display = "grid";

    if (sideDel.length > 1) {
      // Er det mer enn 1 side, vis neste knapp
      document.getElementById(neste).style.display = "inline-block";
    } 
    if (forelopigSide > 0) {
      // Er vi på side 2 eller høyere, vis tilbake knapp
      document.getElementById(tilbake).style.display = "inline-block";
    } else {
      // Hvis vi er på side 1, gjem tilbake knapp
      document.getElementById(tilbake).style.display = "none";
    }
    if (sideDel.length <= (forelopigSide + 1)) {
      // Om vi er på siste side, gjem neste knapp
      document.getElementById(neste).style.display = "none";
    }
  }
}

function visNesteSide(side, tilbake, neste) {
  // Hent alle elementer med navn side_sok
  var sideDel = document.getElementsByClassName(side);

  // Gjem denne siden, neste side hentes i hentSide()
  sideDel[forelopigSide].style.display = "none";
  
  // Øk teller med 1, dette er siden vi kommer til
  forelopigSide++;

  // Scroll til topp på ny side
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;

  // Hent den nye siden
  hentSide(side, tilbake, neste);
}

function visForrigeSide(side, tilbake, neste) {
  // Hent alle elementer med navn side_sok
  var sideDel = document.getElementsByClassName(side);
  
  // Gjem denne siden, neste side hentes i hentSide()
  sideDel[forelopigSide].style.display = "none";

  // Reduser teller med 1, dette er siden vi kommer til
  forelopigSide--;
  
  // Scroll til topp på ny side
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
  
  // Hent den nye siden
  hentSide(side, tilbake, neste);
}

// Funksjon for å trykke på et resulat med enter for søk
function sokTabbing() {
  var artikkel = document.getElementsByClassName("artRes_sok");
  var bruker = document.getElementsByClassName("brukerRes_sok");
  var arrangement = document.getElementsByClassName("arrRes_sok");
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet
  for (var i = 0; i < artikkel.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    artikkel[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
  for (var i = 0; i < bruker.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    bruker[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
  for (var i = 0; i < arrangement.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    arrangement[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
}

function backendTabbing() {
  var backendTab1 = document.getElementById("bTab1");
  var backendTab2 = document.getElementById("bTab2");

  backendTab1.addEventListener("keyup", function(event) {
    
    var gaaTil = this;

    if (event.keyCode === 13) {
      gaaTil.click();
    }
  });

  backendTab2.addEventListener("keyup", function(event) {
    
    var gaaTil = this;

    if (event.keyCode === 13) {
      gaaTil.click();
    }
  });
}

// Funksjon for å trykke på et resulat med enter for arrangement
function arrTabbing() {
  var arrangement = document.getElementsByClassName("arrangement_ressection");
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet arrangement_ressection
  for (var i = 0; i < arrangement.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    arrangement[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
}

// Funksjon for å trykke på et resulat med enter for artikler
function artTabbing() {
  var artikkel = document.getElementsByClassName("res_artikkel");
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet res_artikkel
  for (var i = 0; i < artikkel.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    artikkel[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
}

// Funksjon for å trykke på en interesse på profil
function profilTabbing() {
  var interesse = document.getElementsByClassName("proInt");
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet proInt
  for (var i = 0; i < interesse.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    interesse[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
}


// Funksjon for å trykke på en bruker i adminpanelet
function adminTabbing() {
  var brukere = document.getElementsByClassName("admin_allebrukere_rad");
  var handlinger = document.getElementsByClassName("admin_handlingvalg");
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet
  for (var i = 0; i < brukere.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    brukere[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
  for (var i = 0; i < handlinger.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    handlinger[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
}

/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/



/* Vis passord ved registrering og innlogging*/
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/
function visPassordReg() {
  // Henter alle elementer med class navn RegInnFeltPW og kaller funksjonen visPassord
  var hentetReg = document.getElementsByClassName("RegInnFeltPW");
  visPassord(hentetReg);
}

/* Vis passord ved brukerinstillinger */
function visPassordInst() {
  // Henter alle elementer med class navn KontoredigeringFeltPW og kaller funksjonen visPassord
  var hentetInst = document.getElementsByClassName("KontoredigeringFeltPW");
  visPassord(hentetInst);
}

/* Funksjonalitet bak det å vise et passord */
function visPassord(hentet) {
  // Bruker for løkke for å gå igjennom elementene mottat 
  for (i = 0; i < hentet.length; i++) {
    if (hentet[i].type == "password") {
      // Hvis elementet er av type passord, sett dette til tekst (Bruker har valgt å vise passord)
      hentet[i].type = "text";
    } else {
      // Hvis elementet er av type text, sett dette til passord (Bruker har valgt å skjule passord)
      hentet[i].type = "password";
    }
  }
}
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/


/* Del for arrangement*/
/*-------------------------------------------------------------------------------------------------------*/
/*-------------------------------------------------------------------------------------------------------*/
function visAvmeld(valg) {
  if(valg == "Avmeld") {
    document.getElementById("arrangement_paameldt").innerHTML = "Avmeld";
  } else if(valg == "Skal") {
    document.getElementById("arrangement_paameldt").innerHTML = "Skal";
  }
  else if(valg == "Kanskje") {
    document.getElementById("arrangement_paameldt").innerHTML = "Kanskje";
  } 
  else if(valg == "KanIkke") {
    document.getElementById("arrangement_paameldt").innerHTML = "Kan ikke";
  } 
  else if(valg == "Paameld") {
    document.getElementById("arrangement_paameld").innerHTML = "Påmeld";
  }
  else if(valg == "Invitert") {
    document.getElementById("arrangement_paameld").innerHTML = "Invitert";
  }
}

function visSlett(sletteid) {
  var slettverdi = document.getElementById(sletteid);
  var testPaa = slettverdi.value.substring(0, 5);
  if (testPaa != "Slett") {
    forVerdi = slettverdi.value;
    slettverdi.value = "Slett " + slettverdi.value;
  } else {
    slettverdi.value = forVerdi;
  }
}



/*-------------------*/
/*-------------------*/
/* Del for meldinger */
/*-------------------*/
/*-------------------*/

function aapneSamtale(valgtSamtale) {
  document.getElementById("meldinger_innboks_valgt").value = valgtSamtale;
  document.getElementById("meldinger_form_innboks").submit();
}

function slettSamtale(valgtSamtale) {
  document.getElementById("meldinger_innboks_soppel_valgt").value = valgtSamtale;
  document.getElementById("meldinger_innboks_soppel").submit();
}

function gjenopprettSamtale(valgtSamtale) {
  document.getElementById("meldinger_innboks_restore_valgt").value = valgtSamtale;
  document.getElementById("meldinger_innboks_restore").submit();
}

function aapneUtboks() {
  document.getElementById("meldinger_form_utboks").submit();
}



// Funksjon for å trykke på en melding eller ikon for papirkurv / gjenoppretting
function meldingTabbing() {
  var melding = document.getElementsByClassName("meldinger_innboks_samtale");
  var ikon = document.getElementsByClassName("meldinger_innboks_restore");
  if(ikon.length == 0) {
    var ikon = document.getElementsByClassName("meldinger_innboks_soppel");
  }
  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet meldinger_innboks_samtale
  for (var i = 0; i < melding.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    melding[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
  for (var i = 0; i < ikon.length; i++) {
    // Legger på en eventlistener som ser etter et klikk på alle elementer med mottat class navn
    ikon[i].addEventListener("keyup", function(event) {
      // Henter dette elementet
      var gaaTil = this;
      // 13 er Enter tasten
      if (event.keyCode === 13) {
        // Trykk på resultatet
        gaaTil.click();
      }
    });
  }
}

/*-------------------*/
/*-------------------*/
/* Del for kommentarer */
/*-------------------*/
/*-------------------*/
function VisSkjulKommentarer(divId) {
  if(document.getElementById(divId).style.display == 'none') {
  
    document.getElementById(divId).style.display='block';
    document.getElementById("leskommentarer").innerHTML = 'Skjul kommentarer';
  }
  else {
    document.getElementById(divId).style.display = 'none';
    document.getElementById("leskommentarer").innerHTML = 'Vis kommentarer';
    
  }
}


function visKommentar() {
  
  var knappLes = document.getElementsByClassName("kommentar_lesknapp");

  // Går igjennom alle elementene fra tidligere, element.length er antall elementer med class navnet kommentar_lesknapp
  for (var i = 0; i < knappLes.length; i++) {
    
    knappLes[i].addEventListener("click", function() {
      // Siden en kommentar har en tekst, har den også to elementer for ingress og tekst
      var innholdTekst = this.previousElementSibling;
      var innholdIngress = innholdTekst.previousElementSibling;
      var knappLes = this;

      // Tester på style som er nå, hvis ingressen vises, skjul ingress og vis tekst
      if (innholdIngress.style.display == "inline-block") {
        innholdTekst.style.display = "inline-block";
        innholdIngress.style.display = "none";
        knappLes.innerHTML = "Les mindre";
      } else {
        innholdTekst.style.display = "none";
        innholdIngress.style.display = "inline-block";
        knappLes.innerHTML = "Les mer";
      }
    });
  }
}

// Funksjon for å laste opp oppdateringer til profil (beskrivelse og preferanser)
function lastOppProfil() {
  document.getElementById("profilForm").submit();
}


/*----------------------*/
/*----------------------*/
/* Del for adminpanelet */
/*----------------------*/
/*----------------------*/

function admHovedmeny() {
  var meny = document.getElementById("admin_hovedmeny");

  if(meny.style.display == "none" || meny.style.display == "") {
    meny.style.display = "inline";
    document.getElementById("admin_hovedmeny_ikon").src = "bilder/pilOIkon.png";
  } else if(meny.style.display == "inline") {
    meny.style.display = "none";
    document.getElementById("admin_hovedmeny_ikon").src = "bilder/pilNIkon.png";
  }
}

function admMeny() {
  var meny = document.getElementById("admin_adm_delmeny");

  if(meny.style.display == "none") {
    meny.style.display = "inline";
    meny.previousElementSibling.style.backgroundImage = "url('bilder/pilOIkon.png')";
  } else {
    meny.style.display = "none";
    meny.previousElementSibling.style.backgroundImage = "url('bilder/pilNIkon.png')";
  }
}

function rapMeny() {
  var meny = document.getElementById("admin_rap_delmeny");

  if(meny.style.display == "none") {
    meny.style.display = "inline";
    meny.previousElementSibling.style.backgroundImage = "url('bilder/pilOIkon.png')";
  } else {
    meny.style.display = "none";
    meny.previousElementSibling.style.backgroundImage = "url('bilder/pilNIkon.png')";
  }
}

function regMeny() {
  var table = document.getElementById("admin_regler_table");

  if(table.style.display == "none" || table.style.display == "") {
    table.style.display = "inline-block";
    table.nextElementSibling.style.display = "inline-block";
    table.previousElementSibling.style.backgroundImage = "url('bilder/pilOIkon.png')";
  } else {
    table.style.display = "none";
    table.nextElementSibling.style.display = "none";
    table.previousElementSibling.style.backgroundImage = "url('bilder/pilNIkon.png')";
  }
}


function aapneBruker(valgtDel) {
  document.getElementById("bruker_form_verdi").value = valgtDel;
  document.getElementById("bruker_form").submit();
}

function adminpanelSok() {
  // Oppretter variabler
  var skrevet, filteret, tabellen, rad, data, i, innhold;

  skrevet = document.getElementById("admin_sok");
  filteret = skrevet.value.toUpperCase();
  tabellen = document.getElementById("admin_allebrukere_table");
  rad = tabellen.getElementsByTagName("tr");

  // Går igjennom alle radene og gjemmer de som ikke passer med det bruker har skrevet
  for (i = 0; i < rad.length; i++) {
    data = rad[i].getElementsByTagName("td")[0];
    if (data) {
      innhold = data.textContent || data.innerText;
      if (innhold.toUpperCase().indexOf(filteret) > -1) {
        rad[i].style.display = "";
      } else {
        rad[i].style.display = "none";
      }
    }
  }
}

/* Funksjonalitet for å bytte mellom advarsel og ekskludering */
function byttHandling(handling) {
  var knappene =  document.getElementsByClassName("admin_handlingvalg");

  if(handling == "Advar") {
    /* Admin ønsker å advare */
    knappene[1].removeAttribute("id");
    knappene[0].setAttribute("id", "admin_aktivhandling");

    document.getElementById("admin_handling_tekst").setAttribute("name", "advaring");
    document.getElementById("admin_handling_bruker").setAttribute("name", "advartbruker");

    document.getElementById("admin_handling").innerHTML = "Advar bruker";

    document.getElementById("admin_handling_submit").style.backgroundColor = "dodgerblue";
    document.getElementById("admin_handling_submit").value = "Advar bruker";

    document.getElementById("admin_handling_lengde").style.display = "none";
    document.getElementById("admin_handling_dato").style.display = "none";

  } else if(handling == "Ekskluder") {
    /* Admin ønsker å ekskludere */
    knappene[0].removeAttribute("id");
    knappene[1].setAttribute("id", "admin_aktivhandling");

    document.getElementById("admin_handling_tekst").setAttribute("name", "ekskludering");
    document.getElementById("admin_handling_bruker").setAttribute("name", "ekskludertbruker");

    document.getElementById("admin_handling").innerHTML = "Ekskluder bruker";
    
    document.getElementById("admin_handling_submit").style.backgroundColor = "red";
    document.getElementById("admin_handling_submit").value = "Ekskluder bruker";

    document.getElementById("admin_handling_lengde").style.display = "block";
    document.getElementById("admin_handling_dato").style.display = "block";

  }
}

function visFlereBrukere() {
  var tabell = document.getElementsByClassName("admin_allebrukere_rad");
  var knapp = document.getElementById("admin_allebrukere_knapp");

  if(tabell.length > 8) {
  
    if(knapp.innerHTML == "Vis flere") {
      for(i = 8; i < tabell.length; i++) {
        tabell[i].style.display = "";
      }
      knapp.innerHTML = "Vis mindre";
    } else {
      for(i = 8; i < tabell.length; i++) {
        tabell[i].style.display = "none";
      }
      knapp.innerHTML = "Vis flere";
    }
  }
}

function sjekkAdminHandling() {
  var bruker = document.getElementById("admin_handling_bruker");
  var grunnlag = document.getElementById("admin_handling_tekst");

  var boks = document.getElementById("mldFEIL_boks");
  var melding = document.getElementById("mldFEIL");

  if(bruker.value != "") {
    if(grunnlag.value != "") {
      document.getElementById("admin_handling_form").submit();
    } else {
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
      boks.style.display = "block";
      melding.innerHTML = "Oppgi et grunnlag";
    }
  } else {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
    boks.style.display = "block";
    melding.innerHTML = "Feil oppsto, ingen bruker oppgitt";
  }
}


/* Funksjonalitet for å gi synlighet for default navmeny */
function byttFargeNavbar() {
  if (document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
    document.getElementsByClassName('default_navTop')[0].style.backgroundColor = "rgb(200,218,211)";
    } else {
    document.getElementsByClassName('default_navTop')[0].style.backgroundColor = "transparent";
  }
}

/* Innsending av skjema fra profil om innstillinger */
/* Med hjelp fra: https://stackoverflow.com/questions/24459984/php-hide-url-get-parameters (per 29.03.2020) */
function innstillinger(bruker) {
  /* Opprett skjema */
  var form = document.createElement("form");
  /* Definer metode */
  form.setAttribute("method", "post");
  /* Definer handling */
  form.setAttribute("action", "profil.php?bruker=" + bruker)

  /* Opprett et input-felt i form */
  var input = document.createElement("input");
  /* Definer type */
  input.setAttribute("type", "hidden");
  /* Definer navn */
  input.setAttribute("name", "innstillinger")
  /* Definer verdi */
  input.setAttribute("value", "innstillinger")
  /* Legg til i skjema */
  form.appendChild(input);
  
  /* Legg til skjema i dokument */
  document.body.appendChild(form);
  /* Send skjema */
  form.submit();
}

function aapneRegler() {
  var boksen = document.getElementById('mldREGLER_boks');
  var scroll = document.getElementsByTagName("BODY")[0];
  
  scroll.style.overflow = 'hidden';

  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
  
  boksen.style.display = "block";
}

function lukkRegler() {
  var boksen = document.getElementById('mldREGLER_boks');

  if(boksen.style.display == "block") {
    var scroll = document.getElementsByTagName("BODY")[0];
    scroll.style.overflow = '';
    boksen.style.display = "none";
  }
}

/* Denne siden er utviklet av Robin Kleppang, Ajdin Bajrovic, Aron Snekkestad, Glenn Petter Pettersen, Petter Fiskvik sist endret 04.06.2020 */
/* Denne siden er kontrollert av Aron Snekkestad, Robin Kleppang, siste gang 04.06.2020 */