# Husso Taxi op Hostinger

Op 15 november 2026 gepubliceerd op [taxi.husso.nl](https://taxi.husso.nl/). De homepage heeft een fotocarrousel, nieuwe typografie en mobiele navigatie. De beheeromgeving gebruikt dezelfde stijl en het chauffeursscherm toont dienst, actieve rit en ritbedrag in aparte panelen.

## Bronbestanden

De ontwerppatch begint bij Git-versie `20f210e` met `Husso-Taxi-WhatsApp-live.patch` toegepast. Gebruik `git apply --check Husso-Taxi-ontwerp-Hostinger.patch` en pas daarna de patch toe. Bouw de CSS en JavaScript met `npm run build`. De code gebruikt de bestaande boekings- en betaalroutes.

Op Hostinger staan de gebundelde browserbestanden in beide buildmappen. De nieuwe afbeeldingen en lettertypen staan in `public_html`. De Blade-templates staan in `resources/views`.

## Controle en herstel

Alle 49 Laravel-tests slaagden. Chrome bevestigde de Nederlandstalige en Engelstalige homepage, de carrousel, de mobiele navigatie, de bestaande boekingsknoppen, contact en login op de live site. Het chauffeursscherm is in Chrome gecontroleerd met een fictieve rit in de testomgeving.

De back-up staat in de privéstagingmap op Hostinger. Draai in SSH het volgende om de gecontroleerde bestanden uit de vorige versie terug te zetten.

```bash
bash ~/husso-design.wuLH1CCc/release.sh rollback
```

Het herstelscript vergelijkt de releasebestanden eerst met hun opgeslagen controlesommen. Daarna zet het de back-up terug en bouwt het de viewcache opnieuw op.
