<?php

try {
    include("../inkluderes/innstillinger.php");
    $db = new mysqlPDO();
} 
catch (Exception $ex) {
    // Disse feilmeldingene leder til samme tilbakemelding for bruker, dette kan ønskes å utvide i senere tid, så beholder alle for nå.
    if ($ex->getCode() == 1049) {
        // 1049, Fikk koblet til men databasen finnes ikke
        header('location: default.php?error=3');
    }
    if ($ex->getCode() == 2002) {
        // 2002, Kunne ikke koble til server
        header('location: default.php?error=3');
    }
    if ($ex->getCode() == 1045) {
        // 1045, Bruker har ikke tilgang
        header('location: default.php?error=3');
    }
}

// Setter så PDO kaster ut feilmelding og stopper funksjonen ved database-feil (PDOException)
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "select * from bruker";
$stmt = $db->prepare($sql);
$stmt->execute();
$resultat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hentBilde = "select * from bilder";
$stmtBilde = $db->prepare($hentBilde);
$stmtBilde->execute();
$bilde = $stmtBilde->fetchAll(PDO::FETCH_ASSOC);

$hentBildeA = "select * from artikkelbilde";
$stmtBildeA = $db->prepare($hentBildeA);
$stmtBildeA->execute();
$bildeA = $stmtBildeA->fetchAll(PDO::FETCH_ASSOC);

$hentPaameldteQ = "select * from påmelding";
$hentPaameldteSTMT = $db->prepare($hentPaameldteQ);
$hentPaameldteSTMT->execute();
$paameldte = $hentPaameldteSTMT->fetchAll(PDO::FETCH_ASSOC);

$hentArrQ = "select * from event";
$hentArrSTMT = $db->prepare($hentArrQ);
$hentArrSTMT->execute();
$arrangement = $hentArrSTMT->fetchAll(PDO::FETCH_ASSOC);

$hentKommQ = "select * from kommentar";
$hentKommSTMT = $db->prepare($hentKommQ);
$hentKommSTMT->execute();
$kommentarer = $hentKommSTMT->fetchAll(PDO::FETCH_ASSOC);

echo("Alle brukere");
foreach ($resultat as $res) {
    echo "<br>";
    echo "<br>";
    echo "<br>";
    echo($res['idbruker']);
    echo "<br>";
    echo($res['brukernavn']);
    echo "<br>";
    echo($res['passord']);
    echo "<br>";
    echo($res['fnavn']);
    echo "<br>";
    echo($res['enavn']);
    echo "<br>";
    echo($res['epost']);
    echo "<br>";
}
echo "<br>";
echo "<br>";
echo("Alle bilder");

foreach ($bilde as $res) {
    echo "<br>";
    echo "<br>";
    echo($res['idbilder']);
    echo "<br>";
    echo($res['hvor']);
    echo "<br>";
    echo($res['bilde']);
    echo "<br>";
    echo "<br>";
}
echo "<br>";
echo "<br>";
echo("Alle artikkelbilder");

foreach ($bildeA as $res) {
    echo "<br>";
    echo "<br>";
    var_dump($res);
    echo "<br>";
    echo($res['idartikkel'] . " med id ");
    echo($res['idbilde']);
    echo "<br>";
}

echo "<br>";
echo "<br>";
echo("Alle påmeldte brukere");

foreach ($paameldte as $res) {
    echo "<br>";
    echo "<br>";
    var_dump($res);
    echo "<br>";
    echo($res['event_id'] . " med id ");
    echo($res['bruker_id']);
    echo($res['interessert']);
    echo "<br>";
}

echo "<br>";
echo "<br>";
echo("Alle arrangement");

foreach ($arrangement as $res) {
    echo "<br>";
    echo "<br>";
    var_dump($res);
    echo "<br>";
    echo($res['idevent']);
    echo($res['eventnavn']);
    echo($res['eventtekst']);
    echo "<br>";
}



echo "<br>";
echo "<br>";
echo("Alle kommentarer");

foreach ($kommentarer as $res) {
    echo "<br>";
    echo "<br>";
    var_dump($res);
    echo "<br>";
    echo($res['idkommentar']);
    echo($res['ingress']);
    echo($res['tekst']);
    echo($res['tid']);
    echo($res['artikkel']);
    echo($res['bruker']);
    echo "<br>";
}
?>