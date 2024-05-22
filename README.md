---
Plugin Name: AMB-DidO Plugin 
Description: Erstellt Metadaten gemäß AMB-Standard im JSON-Format für didaktische und Organisationsressourcen auf beliebigen Wordpress-Seiten.
Latest Release: 0.5
Author: Justus Henke / HoF
---
# AMB-DidO Plugin 


 
Dies ist ein Wordpress-Plugin zur Erstellung von Metadaten für didaktische und Organisationsressourcen angelehnt an das Allgemeines Metadatenprofil für Bildungsressourcen (AMB).

Weitere Informationen zum AMB: https://dini-ag-kim.github.io/amb/latest/

Kontakt und Feedback sowie Feature-Requests: opendata@hof.uni-halle.de

## Status 
Aktuell noch in Entwicklung, aber bereits funktionsfähig zum Testen.

## Integrierte Features
- Editor: Auswählen von Metadaten (Werte und offene Felder)
- Quellcode: Erstellen des JSON-LD Skripts im Quellcode
- Optimierung: Import öffentlicher Wertelisten/Vokabulare (AMB, LRMI, schema.org) 
- Optionen: Einstellen der Seitentypen, in denen Metadaten aktiviert sind
- Optionen: Einstellen von Standard-Werten und ausblenden dieser Felder im Editor
- Optionen: Ausgabe von Metadaten im Frontend (unterhalb Content)
- Optionen: Einstellen welche Metadaten im Frontend angezeigt werden sollen
- Frontend: Hook-Function und Shortcode für einbau beliebiger Metadaten im Frontend
- Optimierung: Integration eigener veröffentlichter Wertelisten/Vokabulare
- Optionen: bestimmte Felder deaktivieren statt Standardwert festzulegen.

## Features in Arbeit
- Editor/Minor: Creator-Feld als Wordpress-Taxonomie integrieren statt Freifeld.
- Frontend: Ausgabe von Keywords, Autoren und Beschreibung im Frontend.
- Optimierung: Integration von Mehrebenen-Vokabularen in die Standardfunktion zur Generierung der Metadatenfelder

## Features in Planung
- Optimierung: Integration der Metadaten in die Wordpress Suchfunktion
- Editor: Tooltips mit Beschreibungen der Werte
- Optionen: Anleitung zur Nutzung des Plugins und pflegen von Metadaten 
- Optionen: Standard-Wordpress-Felder für Keywords (tags) und Beschreibung (excerpt) nutzen statt Plugin-spezifischer
- Optionen: Anpassung der Darstellung von Metadaten im Frontend
- Optionen: Einstellung der Sprache der Wertelabels
- Optionen: Zusätzliche selbstgewählte Wertelisten aus Archiven abrufen und integrieren. 
