# Husso Taxi: betalingen, ritvolger en Hostinger Mail

## Live wijzigingen — 15–17 november 2026

- De zwarte en gele website gebruikt de aangeleverde Schipholfoto. De slideshow speelt automatisch af. De zwevende WhatsApp-knop opent `wa.me/31686463268`.
- Na iDEAL opent de privévolgpagina van dezelfde rit. Mislukte, afgebroken en verlopen betalingen tonen rood: **Betaling niet gelukt**. De ritbon en live volglink staan in de e-mail.
- Nieuwe boekingsmails sturen een kopie aan `info@husso.nl`. De aparte wachtrij `booking-mail` heeft een eigen worker. De Hostinger-taak om Laravel elke minuut te starten staat ingesteld.

## Hostinger Mail — gecontroleerd op 17 november 2026

- De eigenaar heeft het Hostinger-appwachtwoord zelf ingevoerd bij de verborgen SSH-vraag. Het wachtwoord staat niet in deze documentatie, patches of Git.
- De eerste authenticatiepoging mislukte en liet bestaande instellingen ongemoeid. De volgende poging slaagde.
- De daadwerkelijke fout was een lege `MAIL_URL`-regel in de productie-`.env`. Laravel behandelde die lege waarde als een URL en verwijderde daardoor de SMTP-transportkeuze. Na een privéback-up met bestandsmodus `600` is alleen de lege regel verwijderd en de Laravel-configuratiecache opnieuw opgebouwd.
- Laravel heeft daarna via de ingestelde Hostinger SMTP-verbinding geauthenticeerd. Eén herkenbare testmail is naar `info@husso.nl` aangeboden en door de SMTP-server geaccepteerd. De ontvangst in de mailbox is niet onafhankelijk bevestigd; controleer zo nodig ook de spammap.
- Boekingsmails die vóór deze reparatie mislukten staan nog als mislukt geregistreerd en worden niet automatisch opnieuw verstuurd. Controleer die boekingen afzonderlijk voordat je berichten opnieuw inplant.
- De 17 oude meldingen in wachtrij `notifications` zijn ongemoeid gelaten. De nieuwe worker verwerkt alleen `booking-mail`.

Laat `MAIL_URL` weg wanneer de mailinstellingen via `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME` en `MAIL_PASSWORD` zijn ingesteld. Voeg geen lege regel `MAIL_URL=` toe. Deel het appwachtwoord niet in chat, documentatie, patches of GitHub.

## Hostinger-instellingen beheren

De verborgen Hostinger-helper blijft beschikbaar voor een latere wachtwoordrotatie:

```bash
bash ~/husso-design.row02McH/configure-hostinger-mail.sh
```

De helper controleert de SMTP-login, maakt een privéback-up en verstuurt geen mail. Voor een afzonderlijke technische testmail wordt de privécontrole-helper gebruikt; die maakt geen boeking aan en start geen betaling.

De bestaande Hostinger-taak start de Laravel-planner iedere minuut:

```bash
/usr/bin/php "$HOME/domains/taxi.husso.nl/artisan" schedule:run
```

## Broncode en uitrol

De volledige bron staat op GitHub in branch `codex/husso-complete-20261115`. De productie-`.env`, mailboxwachtwoord, databases, logs en back-ups horen buiten Git te blijven. De productieconfiguratie is bijgewerkt zonder secrets of databasebestanden te publiceren.

De bron bevat geautomatiseerde tests voor betalingen en e-mail. PHPUnit ontbreekt in de volledige projectkopie, dus die tests konden hier niet draaien. De 65 PHP-bestanden zijn eerder syntactisch gecontroleerd. Tijdens de mailcontrole zijn geen echte boekingen of betalingen gestart.
