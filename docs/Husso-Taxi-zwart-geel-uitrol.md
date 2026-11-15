# Husso Taxi zwart en geel

De site gebruikt het Husso-geel op een zwarte basis. Instrument Serif wordt gebruikt voor grote koppen en Instrument Sans voor navigatie, bediening en lopende tekst. Beide lettertypen worden lokaal gehost. De eerste slide gebruikt de aangeleverde Schipholfoto. De slideshow start automatisch en heeft pijlen aan de buitenranden, een pauzeknop en bediening met toetsen en veegbewegingen.

Het boekingsformulier valt over de onderzijde van de hero. De diensten openen een venster met uitleg en een knop die het juiste rittype selecteert. Het totaalbedrag verschijnt bij de laatste boekingsstap. Ritbon en volgpagina tonen ook het totaalbedrag. De ronde WhatsApp-knop linksonder opent `wa.me/31686463268`.

Ritbeheer zet actieve ritten en open boekingen vooraan. Op de boekingspagina verschijnt ‘Chauffeur is online’ zodra de chauffeur een dienst start. Bij uitklokken verdwijnt het lampje. De pagina controleert de status elke 20 seconden zolang ze zichtbaar is.

## Patches

Pas na de eerdere patches `Husso-Taxi-WhatsApp-live.patch` en `Husso-Taxi-ontwerp-Hostinger.patch` eerst `Husso-Taxi-zwart-geel.patch` toe en daarna `Husso-Taxi-chauffeur-online.patch`. Gebruik voor elke patch eerst `git apply --check`. Bouw daarna de browserbestanden opnieuw.

## Herstel op Hostinger

De terugzetcommando’s gebruiken de privéback-ups van beide releases. Draai voor een volledig herstel de laatste release als eerste terug.

```bash
bash ~/husso-design.JtkqZ0lE/release.sh rollback
bash ~/husso-design.Mnu1HQps/release.sh rollback
```

## Mailstatus

Bij de controle op 15 november 2026 wees Brevo de productieaanmelding af met SMTP-fout 535. Controleer de SMTP-login en werk de SMTP-sleutel op Hostinger bij. Daarna kan de bezorging worden getest. [Brevo SMTP-handleiding](https://help.brevo.com/hc/en-us/articles/115000188150-Troubleshooting-Issues-with-Brevo-SMTP).
