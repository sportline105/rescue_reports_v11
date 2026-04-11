# Detaillierte Einsatzberichte für Feuerwehren und BOS

Entwickelt mithilfe TYPO3 Dev Assist [by in2code]. Weiterentwicklung auf Grundlage der TYPO3-Extension "firefighter".

---

Für einen leichteren Start kann nach der Installation der Upgrade Wizard durchgeführt werden.
Dieser erstellt einen Speicherordner für die Extension und fügt diverse (deutsche) BOS (Behörden und Organisationen mit Sicherheitsaufgaben) sowie typische Fahrzeugtypen hinzu.

---

## Die Extension bietet folgende Datensätze:

- <ins>__Stadtfeuerwehr:__</ins> Hier werden die Einheiten angelegt. Es können zu jeder Stadtfeuerwehr mehrere Ortsfeuerwehren und zu diesen mehrere Fahrzeuge hinzugefügt werden. Zur Sortierung im Backend wird jedem Datensatz eine Priorität zugewiesen. Bei Erstellung kann der Fahrzeugname aus den vordefinierten Fahrzeugtypen generiert oder händisch eingegeben werden. Jedem Fahrzeug kann ein Link zum dazugehörigen Datensatz sowie ein Bild hinzugefügt werden, welche im FE angezeigt werden.

- <ins>__Organisation:__</ins> Wie der Name schon sagt, werden hier die Organisationen angelegt. Jeder Organisation kann ein Kürzel sowie ein Symbol zugewiesen werden (Beispielsweise: Feuerwehr; FF; 🚒)

- <ins>__Fahrzeugtyp:__</ins> Hier werden die gängigen Fahrzeugbezeichnungen sowie die dazugehörige Organisationhinterlegt. Die Fahrzeugtypen können dann im Datensatz Stadtfeuerwehr > Ortsfeuerwehr > Fahrzeug ausgewählt werden. 

- <ins>__Einsatzstichwort:__</ins> neben dem Selbsterklärenden Namen können hier Datensätze zur Auswahl deaktiviert werden. Bestehende Einträge werden weiterhin im FE angezeigt

- <ins>__Einsatz:__</ins> Hier kommen alle Datensätze zusammen:

  - <b>Allgemein</b>:              hier werden Einsatztitel, Einsatzbeginn- und ende, -nummer, -art, -ort sowie der Einsatzbericht eingegeben.
  - Eingesetzte Einheiten:  hier werden die erstellten Stadt- sowie Ortsfeuerwehren angezeigt und ausgewählt.
  - Fahrzeuge:              nach Auswahl der eingesetzten Einheiten und Zwischenspeichern können hier die eingesetzten Fahrzeuge ausgewählt werden.
                             Diese werden zur einfacheren Übersicht sortiert nach Stadtfeuerwehr angezeigt.

---
  
## Frontend-Plugins:

- <b>Rescue Reports: Sidebar:</b>   kleine Einsatzübersicht mit den nötigsten Informationen - perfekt für einen Einsatzticker oder eine Übersicht in der Sidebar. Das Plugin ist standardmäßig auf 5 Einträge begrenzt, eine geringere Anzahl kann über das Flexform ausgewählt werden. Benötigt zur Detailansicht einen "Rescue Reports: Einsätze"-Datensatz

- <b>Rescue Reports: Einsätze:</b>  Vollständige Einsatzübersicht mit Suchfunktion und Detailansicht.

## Templates:
- Standard/Sidebar > Foundation 7 Framework
- NewDesign/NewDesignSidebar > Bootstrap Framework
