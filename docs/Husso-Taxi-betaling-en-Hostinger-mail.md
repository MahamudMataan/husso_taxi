# Husso Taxi: betalingen, ritvolger en Hostinger Mail

## Live wijzigingen — 15 november 2026

- De zwarte en gele website gebruikt de aangeleverde Schipholfoto. De slideshow speelt automatisch af. De zwevende WhatsApp-knop gebruikt de aangeleverde afbeelding en opent `wa.me/31686463268`.
- Na iDEAL opent de privévolgpagina van dezelfde rit. Mislukte, afgebroken en verlopen betalingen tonen rood: **Betaling niet gelukt**. Een nog onbevestigde betaling toont een waarschuwing. De ritbon en live volglink staan in de e-mail.
- Nieuwe boekingsmails sturen een kopie aan `info@husso.nl`. De aparte wachtrij `booking-mail` heeft een eigen worker. De Hostinger-taak om Laravel elke minuut te starten staat al ingesteld.

De vernieuwde website, privéritvolger en rode foutstatus voor mislukte betalingen staan live. Nieuwe boekingen horen een bevestiging naar de klant en een aparte melding naar `info@husso.nl` te sturen. De appwachtwoordcontrole en een echte testmail zijn nog niet uitgevoerd.

## Mail: wat nog openstaat

De SMTP-login van Brevo faalde met fout 535. Hostinger bevestigt dat `info@husso.nl` actief is en de beveiligde SMTP-server bereikbaar is. De app-login is nog niet gecontroleerd, dus er is nog geen geslaagde Hostinger-mail of testmail bevestigd.

Er stonden 17 oude meldingen in de wachtrij `notifications`. Die zijn ongemoeid gelaten. De nieuwe worker verwerkt uitsluitend `booking-mail`, zodat oude ritmails niet onverwacht opnieuw verstuurd worden.

## Hostinger Mail veilig activeren

1. Maak zelf in hPanel bij **E-mails → husso.nl → Mailboxes → App passwords** een appwachtwoord aan voor `info@husso.nl`.
2. Meld via SSH aan op de Hostinger-server en voer uit:

   ```bash
   bash ~/husso-design.row02McH/configure-hostinger-mail.sh
   ```

3. Typ het appwachtwoord bij de verborgen terminalvraag en druk op Enter. Het wachtwoord verschijnt niet op het scherm. De helper test eerst de SMTP-login; daarna maakt hij een privéback-up van `.env` en past uitsluitend de mailinstellingen aan. Hij verstuurt geen e-mail.
4. Laat het wachtwoord in Hostinger en `.env`; plak het niet in een chat, document, patch of GitHub.

De bestaande Hostinger-taak start de Laravel-planner iedere minuut. Een aparte taak toevoegen is niet nodig. De bijbehorende opdracht is:

```bash
/usr/bin/php "$HOME/domains/taxi.husso.nl/artisan" schedule:run
```

Na de verborgen appwachtwoordstap kan SMTP worden gecontroleerd en kan één duidelijk herkenbare testmail naar `info@husso.nl` worden gestuurd. Dat maakt geen boeking aan en start geen betaling. Totdat die stap door de eigenaar is uitgevoerd, mag de mailfunctie niet als werkend worden beschouwd.

## Broncode en uitrol

De bronpatches staan in `docs/patches/`; de belangrijkste aanvullende patches zijn `Husso-Taxi-betaling-en-tracking.patch` en `Husso-Taxi-Hostinger-mail.patch`. Pas een patch alleen toe op de vermelde basisversie en controleer die vooraf met `git apply --check`.

De bron bevat geautomatiseerde tests voor betalingen en e-mail. In de volledige projectkopie ontbreekt PHPUnit, dus die tests konden hier niet draaien; de 65 PHP-bestanden zijn wel syntactisch gecontroleerd. Een echte Hostinger-mail is nog niet bevestigd. Op de live site zijn geen echte boekingen of betalingen gestart.
