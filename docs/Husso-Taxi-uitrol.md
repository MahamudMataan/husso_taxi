# Husso Taxi uitrollen

## Live sinds 14 november 2026

Hostinger draait release `0e4654e` met het ontwerp, favicon en de nieuwe tarieven. Op 15 november zijn de bijgewerkte boekings-, betaal- en ritvolgerbestanden gepubliceerd. Op de live site zijn de openbare pagina’s, bestanden, inlogvereisten en één prijsindicatie gecontroleerd. De iDEAL-flow, betalen achteraf, ritbon en ingelogde beheerpagina’s zijn vooraf getest op Hostinger met een tijdelijke database en nagebootste diensten. Tijdens de uitrol zijn geen echte boekingen of betalingen gestart.

De uitrol heeft 46 bestanden vervangen of toegevoegd. De bestaande bestanden zijn privé geback-upt. De productie-`.env` en `public_html/index.php` zijn geverifieerd en er zijn geen databasemigraties uitgevoerd.

## In deze versie

- Het mobiele en desktopontwerp vernieuwt de boeking, contactpagina, tracking, login, het ritbeheer en het chauffeursdashboard. De Schipholfoto is van [Christo, CC0](https://commons.wikimedia.org/wiki/File:Amsterdam,_Airport,_Schiphol,_25.jpg). De fontlicenties staan bij de fonts in `public/fonts/`.
- Het favicon gebruikt het schild van het Husso-logo. De ICO, SVG en Apple-touch-icon staan in `public/`. Openbare pagina’s en medewerkerspagina’s gebruiken dezelfde Blade-component. Het favicon is al uitgerold op Hostinger.
- De instap is één keer €4 per rit. De rijtijd kost €0,52 per minuut. Tot en met 55 km geldt €3,17/km. Boven 55 km geldt €2,50/km voor de hele rit. De exacte routeafstand bepaalt de tariefgroep. De ritprijs gebruikt de afstand op één decimaal en de rijtijd afgerond op hele minuten, zoals de boeking die bewaart. Daardoor kan een rit net boven 55 km goedkoper zijn. Eerdere boekingen behouden hun opgeslagen bedragen en tarieven.
- Betaalstatussen en de privéritvolger zijn bijgewerkt; foutieve betalingen zijn rood. Boekingsmails gebruiken de eigen `booking-mail`-wachtrij en bevatten een ritbon en volglink. De live Hostinger-mailtaak verwerkt alleen die wachtrij.
- De Hostinger-appwachtwoordstap is nog niet uitgevoerd. Geen geslaagde e-mail is bevestigd. Zie [betalingen en Hostinger Mail](Husso-Taxi-betaling-en-Hostinger-mail.md) voor de veilige activering. De productie-`.env` en mailgeheimen staan niet in deze repository.

## GitHub en patches

De patchreeks begint bij commit `2b8a215`. Gebruik een schone checkout van die basis als je de patches wilt toepassen. De reeks bevat ontwerp en boekingsflow, favicon, tarieven, deze handleiding en het uitrolverslag, in die volgorde. De losse patchbestanden staan lokaal in `docs/patches/`. Breng de wijzigingen via een aparte tak en pull request naar `main`.

```sh
git am /pad/naar/patches/000*.patch
git diff --check 2b8a215 HEAD
```

Controleer bij publicatie het bestandsoverzicht. Configuratievoorbeelden kunnen in Git. Bewaar de echte `.env`, databases, logbestanden, back-ups en sleutels buiten Git. Gebruik de gepubliceerde branch direct als de commits daar al op staan.

## Afgeschermde testomgeving

Gebruik een aparte database en testaccounts. Installeer met `composer install` en `npm ci` en bouw met `npm run build`. Gebruik uitsluitend een Mollie-testsleutel. Een optioneel testbedrag geldt alleen met zo’n sleutel. Stuur mail en sms naar een testvoorziening.

1. Test de homepage in beide talen, contact, inloggen, rittracking en ritbon. Controleer alle drie de faviconbestanden.
2. Controleer een korte rit, precies 55 km en een rit net boven 55 km. Vergelijk de schatting met de bewaarde prijs, iDEAL in testmodus en betalen achteraf.
3. Controleer het ritbeheer, live overzicht, ritdetails en chauffeursdashboard met testboekingen. Controleer dat bestaande boekingen hun bedragen behouden.

## Hostinger via SSH

1. Gebruik de Hostinger-hostsleutel uit hPanel en plaats een eventuele Git-checkout in een privémappad buiten de webroot. Bouw uit een gecontroleerde commit. Vergelijk de productieversie met die commit voordat je bestanden vervangt.
2. Maak een privéback-up van de bestanden die je vervangt, inclusief het Vite-manifest, en controleer dat de back-up leesbaar is. Bewaar de productie-`.env`, database, uploads en `public_html/index.php`. Deze patchreeks bevat geen databasemigraties. Controleer bij een afwijkende productieversie eerst `php artisan migrate:status`.
3. Plaats de gewijzigde PHP-bronbestanden en views in de app boven `public_html`. Plaats alleen publieke assets onder `public_html`. Kopieer nieuwe Vite-bestanden eerst naar de locatie waar de live app ze zoekt en vervang daarna het bijbehorende manifest. Laat de vorige assets beschikbaar voor terugdraaien. Controleer vóór uitrol hoe deze installatie `public_path()` en het Vite-manifest oplost, want `public/` en `public_html/` zijn aparte mappen. Verwijder uitsluitend verouderde viewcache met `php artisan view:clear` nadat je hebt vastgesteld dat Laravel die privé opslaat.
4. Controleer de openbare pagina’s, levering van de assets en de beheerpagina’s met een bestaand account. Gebruik de afgeschermde testomgeving voor boekingen en betalingen. Bij een mislukte uitrol zet je de vervangen bestanden en het vorige Vite-manifest terug. De eerdere faviconrelease heeft een eigen rollback in de privé-releasefolder op Hostinger.

De oude Windows-log- en viewcachemappen zijn buiten de webroot gearchiveerd. Bewaar ze daar ook bij een rollback. De laatste controle meldde dat Laravel-debugmodus in productie aan stond. Controleer en corrigeer die instelling via Hostinger, zonder de overige productiewaarden te wijzigen.

[Hostinger SSH](https://www.hostinger.com/support/1583245-how-to-connect-to-a-hosting-plan-via-ssh-in-hostinger/) · [Laravel 12 deployment](https://laravel.com/framework/docs/12.x/deployment) · [GitHub pull requests](https://docs.github.com/en/pull-requests/how-tos/create-pull-requests/creating-a-pull-request)
