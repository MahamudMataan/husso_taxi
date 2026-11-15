# Husso Taxi slim boeken

## Technische beoordeling

De bestaande Laravel-app heeft Google adresaanvulling en routeprijzen, Mollie iDEAL, betalen na de rit, rittracking en een ritbon. Het dashboard bewaart ritten, diensten en meldingen. Het bevestigen van een rit en het ontvangen van een betaling zijn aparte gebeurtenissen. Voorheen ontving alleen de klant de boekingsmelding via de achtergrondwachtrij.

## Nieuwe werkstroom

- Eén Nederlandse werkplek opent na inloggen. Onder Vandaag staan de dienst, pauzes, actieve of eerstvolgende rit en het overzicht van ontvangen en openstaande bedragen. Een verstreken rit die nog openstaat, krijgt het label ‘Rit wacht op actie’. Het beheerdersaccount beheert ook boekingen, betaalstatus, mails en de minimumritprijs.
- De Nederlandse en Engelse homepage gebruiken drie beeldvullende slides. De twee voertuigbeelden zijn gegenereerde sfeerillustraties. Ze tonen geen specifiek voertuig uit de vloot. De [Schipholfoto is van Christo en is beschikbaar onder CC0](https://commons.wikimedia.org/wiki/File:Amsterdam,_Airport,_Schiphol,_25.jpg). Manrope en DM Sans worden lokaal geleverd. De licenties staan bij de bestanden in `public/fonts/`.
- Online boeken is mogelijk voor één tot vijf passagiers. Bij een grotere groep vraagt de site de klant contact op te nemen voor een extra taxi. De betaalwijzen zijn iDEAL, PIN en contant in de taxi. De WhatsApp-knop voor +31 6 8646 3268 verwijst naar `wa.me/31686463268` en is ook afzonderlijk uitgerold op de bestaande website.
- De boeking gebruikt de ingestelde minimumritprijs, standaard €20. Een aanvulling verschijnt apart als uitrijkosten. Eindhoven of Den Bosch → Schiphol Terminal kost €199 buiten de spits en €219 bij een ophaaltijd op maandag tot en met vrijdag van 07.00 tot 09.00 of van 16.00 tot 18.00. De eindtijden tellen niet mee. De server herberekent de prijs vóór opslag. Bestaande boekingen behouden hun opgeslagen prijs.
- De boekingsmail bevat de ritbon zelf en de knoppen ‘Open ritbon’ en ‘Volg mijn rit’. De klant ziet statuswijzigingen automatisch op de persoonlijke volgpagina en kan de live kaart openen via OpenStreetMap. De chauffeur kan tijdens een actieve rit GPS delen door ‘Deel mijn locatie’ aan te zetten en de pagina open te houden. De volger toont de laatst ontvangen positie maximaal 75 seconden en verbergt die als de chauffeur stopt met delen of de rit afsluit. Ritbon en volgpagina moeten privé blijven. De kaartdienst ontvangt de coördinaten zodra de klant de kaart opent.
- `info@husso.nl` krijgt de aanvraag met een link naar afgeschermd ritbeheer. Mislukte mails zijn zichtbaar en opnieuw in te plannen via de boeking. Een gecontroleerde testmail bevestigt of een bericht ook in de inbox aankomt.
- De aanvraag kan tegen een tweede verzending worden beschermd met een unieke sessiesleutel per aanvraag. De server bewaart de prijs bij indiening en gebruikt de geboekte gegevens voor iDEAL.

## Nog af te spreken

Retourreserveringen en terugwegprijzen lopen voor deze versie via contact. Een factuurwens en factuurgegevens worden opgeslagen. Voor factuurnummers, btw, uitgifte en verzending van een PDF zijn de bevestigde bedrijfsgegevens en factuurregels nodig. Voor het afwijzen of annuleren van prepaid ritten handelt het bedrijf een eventuele terugbetaling via Mollie af.

## Voor productie

1. Test eerst in een omgeving met een eigen database, nagebootste mail en een Mollie-testsleutel. Bewaar de bestaande `.env`, uploads en productiedatabase. Maak een herstelbare privéback-up van de database en te vervangen bestanden. Voer de toevoegende migraties uit vóór het omschakelen van de applicatiecode.
2. Bouw de browserbestanden opnieuw. Publiceer de statische bestanden uit `public/` via de webroot en zorg dat het Vite-manifest in zowel `public/build` als `public_html/build` overeenkomt.
3. De productieverbinding met uitgaande mailserver Brevo wees de aanmelding af. Werk `MAIL_USERNAME` en `MAIL_PASSWORD` privé bij met de SMTP-inloggegevens uit Brevo en controleer de aanmelding. Configureer na die controle één taak in hPanel die vanuit de Laravel-projectmap elke minuut `php artisan schedule:run` uitvoert. Het schema verwerkt uitsluitend de nieuwe wachtrij `booking-mail`. Laat oude berichten in `notifications` staan. [Brevo SMTP-handleiding](https://help.brevo.com/hc/en-us/articles/115000188150-Troubleshooting-Issues-with-Brevo-SMTP), [Hostinger-taken](https://support.hostinger.com/en/articles/1583465-how-to-set-up-a-cron-job-at-hostinger).
4. Controleer in testmodus de ritbon en volgpagina, zet GPS aan en uit op de chauffeurs­telefoon, herhaal dezelfde aanvraag, test de spitsgrenzen en doorloop een chauffeursdienst. Controleer bij productie daarna de prijsindicatie en publieke pagina’s. Gebruik een afzonderlijke testmail naar de beheerinbox om aflevering te bevestigen.
5. Zet bij een probleem de vorige PHP-bestanden, views en het vorige manifest terug. Laat nieuwe databasetabellen en kolommen staan zolang ze nieuwe boekingen of diensten bevatten.
