<?php
session_start();

//-------------------------------//
// Innstillinger, faste variable //
//-------------------------------//
include("../inkluderes/innstillinger.php");

// Oppdaterer KlimateBruker til Redaktør
$nyttArrangementQ = "update bruker set brukertype = 2 where idbruker = 83";
$nyttArrangementSTMT = $db->prepare($nyttArrangementQ);
$nyttArrangementSTMT->execute();
// Oppdaterer KlimateAdmin til Administrator
$nyttArrangementQ = "update bruker set brukertype = 1 where idbruker = 22";
$nyttArrangementSTMT = $db->prepare($nyttArrangementQ);
$nyttArrangementSTMT->execute();
/*

insert into artikkel(artnavn, artingress, arttekst, bruker) values ('Groundhopping: Reisehobby for fotballfrelste', 'Groundhopping har blitt en populær hobby for både norske og engelske fotballinteresserte. Det handler om å reise på kryss og tvers og å se fotballkamper på de seks øverste nivåene.', 'Det er ikke nødvendigvis selve fotballkampen som er hovedmålet. Det er planleggingen, selve reisen til en ukjent plass og alt det som skjer rundt kampen som kan være attraksjonen. Her er mine sju anbefalte anlegg å groundhoppe til etter å ha drevet med dette i 35 år og besøkt 120 baner. – White ball! Den dresskledde mannen et par rader bak deg på tribunen reiser seg og roper engasjert ut. Det er favorittlaget i hvit trøye og svart bukse han heier på. Det er en midtuke-kveld og han har en stresskoffert mellom beina - og har åpenbart ikke rukket hjem fra jobben i Londons finansdistrikt for å skifte til et mer passende antrekk. For dette er Fulham FC og vi er i en av Londons finere bydeler, og det er reflekteres litt på vei i noen av hjemmesupporternes klær og språkbruk.Craven Cottage har en unik karakter. Den ene langsiden ved Stevenage Road kalles Johnny Haynes Stand etter en tidligere klubblegende, og har verneverdig status.', 4);
insert into artikkel(artnavn, artingress, arttekst, bruker) values ('Vintermagiske innsjøer på instagramtoppen', 'Dette er noen av verdens mest instagrammede innsjøer – slik de ser ut om vinteren.', 'Omgitt av snø og is blir landskapet gjerne enda mer fotogent enn om sommeren. Vi har funnet vinterbilder av noen de innsjøene som er aller mest delt på Instagram. De viser at is, snø og blek vintersol gir stedene sin egen magi. Lake District, Storbritannia Lake Distrikt er riktig nok ikke én innsjø, men en nasjonalpark med mange innsjøer og fjell som står på UNESCOS verdensarvliste. Søker du #lakedistrict på Instagram får du over 2,5 millioner treff. De fleste bildene er tatt sommer eller høst, men innsjøene her i det nordvestre hjørnet av England er også fascinerende om vinteren. Michigansjøen, USA. Ordet Michigan betyr stort vann på ojibwe-språket. Vannet er 58.000 kvadratkilometer, og grenser til både Michigan, Wisconsin, Illinois og Indiana. Søker du #lakemichigan på Instagram får du i overkant av 2,1 millioner treff. Her finnes det også flere fantastiske vintermotiver.', 5);
insert into artikkel(artnavn, artingress, arttekst, bruker) values ('Internasjonalt reisemagasin roser Trondheim', 'Victoriafallene, Kuala Lumpur, Patagonia og Trondheim!', 'Dette er steder som er listet opp på fronten av anerkjente Travel+Leisure Magazines januarutgave, som er viet hvor man bør reise nå i 2020. Inne i magasinet – med nærmere fem millioner lesere verden over – er det en tre siders reportasje om trønderbyen. Reportasjen ble også publisert digitalt på Travel+Leisures nettside denne uken, med tittelen «Hvordan tilbringe en perfekt langhelg i Trondheim, Norge» Reiselivsdirektør Tanja Holmen i Trondheim forteller at hun føler seg nærmest startstruck over omtalen i det anerkjente internasjonale reisemagasinet, og er takknemlig for hvert eneste ord som er skrevet. – Jeg fikk vite det da direktøren for Britannia kom og la et eksemplar av det helt ferske reisemagasinet på pulten min. Det er jo helt fantastisk. Det er alt man kan drømme om som reiselivssjef, sier Tanja Holmen til VG', 6);
*/

?>

<!DOCTYPE html>
<html lang="no">

</html>