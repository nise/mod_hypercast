# HyperCast

Das Moodle-Plugin HyperCast stellt den Prototypen für einen kollaborativen Audioplayer zur Verfügung. Das Plugin fügt zu Moodle eine neue Aktivität mit dem Namen `HyperCast` hinzu. Wird eine solche Aktivität dem Kurs hinzugefügt und folgen Lernende dem Verweis, gelangen sie zur Gruppenübersicht und von dort zum Audioplayer (Gruppenplayer).

## Funktionen

HyperCast bietet folgende Grundfunktionen:

### Gruppenverwaltung
- Studierende eines Kurses können sich in kleineren Gruppen von maximal 20 Mitgliedern zusammenschließen, um gemeinsam einen vertonten Kurstext anzuhören und zu kommentieren
- Nur die Person, die die Gruppe erstellt hat, kann den Gruppennamen und die Einstellungen für eine Gruppe editieren, ansonsten sind alle Gruppenmitglieder gleichberechtigt
- Es können private und öffentliche Gruppen gebildet werden. In private Gruppen gelangt man nur über einen Einladungslink, den jedes vorhandene Gruppenmitglied generieren kann
- In der Gruppenübersicht kann man Statistiken über das Hörverhalten der Gruppe einsehen: welche Teile des Kurstextes wurden bereits gehört und wie oft wurden sie gehört
- Man kann beliebig vielen Gruppen beitreten

### Hyperaudio-Player
- Es wird ein Transkript des vertonten Kurstextes angezeigt
- Beim Abspielen der Audiodatei scrollt das Transkript mit der Abspielposition mit und die aktuell abgespielte Stelle wird hervorgehoben
- Zusatzinhalte in Form von Tabellen, Abbildungen und Links sind in das Transkript eingefügt
- An den Stellen, an denen Zusatzinhalte vorhanden sind oder an denen auf sie referenziert wird, werden akustische Hinweise, sogenannte Audio Cues, wiedergegeben
- Die Fortschrittsanzeige des Audioplayers ist eine vertikale Leiste. Zusätzlich zum Füllgrad der Leiste zeigt ein persönlicher Fortschrittsmarker den Hörfortschritt an
- mit dem persönlichen Fortschrittsmarker kann im Audiodokument gesprungen werden, es kann eine Live-Session gestartet werden und wenn das Transkript manuell gescrollt wurde, kann zur aktuellen Stelle zurückgekehrt werden
- Standardfunktionalitäten eines Audioplayers wie Pause, Wiedergabegeschwindigeit, 15 Sekunden vor- und zurückspringen, Lautstärkeregelung sind verfügbar
- Der Hörfortschritt der übrigen Gruppenmitglieder wird ebenfalls durch Fortschrittsmarker auf der Fortschrittsanzeige dargestellt
- Die akustischen Hinweise und die Textgröße sind einstellbar, die Darstellung des eigenen Fortschritts für andere und des Fortschritts der anderen kann optional abgeschaltet werden
- Durch einen Klick auf eine Stelle im Transkript kann an die entsprechende Stelle im Audiodokument gesprungen werden

### Kommentare
- Durch einen Klick auf eine Stelle im Transkript kann ein Kommentar an der Stelle erstellt werden
- Stellen, an denen sich bereits Kommentare befinden, werden im Transkript hervorgehoben
- Ein Kommentar kann mit verschiedenen Kategorien versehen werden, um die Kommentare zu klassifizieren
- Auf einen Kommentar kann geantwortet werden, so dass eine lineare Diskussion ohne weitere Verzweigungen entstehen kann
- eigene Kommentare können bearbeitet und gelöscht werden

### Live-Session
- Über den persönlichen Fortschrittsmarker kann eine Live-Session gestartet werden
- Wenn bereits eine Live-Session in der Gruppe existiert, wird diese durch einen Gruppen-Fortschrittsmarker angezeigt, über den man beitreten kann
- In einer Live-Session steuern alle Teilnehmenden den Audioplayer gemeinsam und der Hörfortschritt wird für alle Teilnehmenden synchronisiert
- Wenn ein Teilnehmender einer Live-Session den Audioplayer pausiert, wird ein Sprach-Chat gestartet, in dem die Teilnehmenden sich über das Gehörte unterhalten können
- Das Mikrofon kann im Sprachchat stummgeschaltet werden
- Wird die Wiedergabe fortgesetzt, wird der Sprach-Chat unterbrochen

### Nutzungsstatistiken
Lehrende erhalten in der Gruppenübersicht zusätzlich einen Button mit dem sie zu einer Auswertung der Nutzung gelangen. Dort stehen bereit:
- Nutzung des Audioplayers
- Nutzung des Voicechats
- Verteilung der Kommentare und Fragen
jeweils im Verlauf der Audiodatei, sowie absolute Zahlen zu Gruppen und Kommentaren.


## Hyperaudio-Quelldateien
 
 Im Ordner 'assets/hyperaudio' finden sich die Audio-Quellen, Bildquellen Marker, SSML und eine aus den Markern generierte VTT. Um Marker-Datein in VTTs umzuwandeln gibt es unter data/tools ein Python-Script. Es ist zu beachten, dass die Marker-Dateien vorab in valide JSON-Dateien umgewandelt werden müssen.
 Für den Prototypen wurde exemplarisch eine Beispieldatei (KE6) eingebunden. Zum Einbinden anderer Dateien müssen die Referenzen auf die jeweiligen Dateien entsprechend geändert werden.

## Verwendete Komponenten

* Vue.js 3.2.45 mit TypeScript
* Composer [Composer][https://github.com/composer/composer]
* PHP Ratchet [Ratchet](https://github.com/ratchetphp/Ratchet)
* WebRTC [WebRTC](https://webrtc.org/)
* nodemon.json - [Nodemon](https://www.npmjs.com/package/nodemon)
* .babelrc - [Babel](https://babeljs.io/)
* webpack.config.js - [Webpack](https://webpack.js.org/)
* package.json; package-lock.json - [Node Package Manager](https://www.npmjs.com/)
* .gitignore - [Gitignore](https://git-scm.com/docs/gitignore)
* verschiiedene npm-Pakete wie vue-toastification, popper.js, hammer.js, vue-multiselect

## Installation des Plugins

### Betrieb in einem Docker-Container

Für das Plugin wurde ein angepasstes Moodle-Docker Projekt erstellt, in dem Beispiel-User und ein Beispiel-Kurs erstellt werden und alle notwendigen Services initialisiert werden.

- Einen lokalen SSH-Key / GPG-Key generieren und in GitLab eintragen, um Zugriff auf die Repositories via SSH zu haben.
- Das Repository für das angepasste Moodle-Docker Projekt klonen: <br />`git@gitlab.pi6.fernuni-hagen.de:ks/fapra/fachpraktikum-2022/alpha/moodle.git`
- Das Repository für dieses Plugin in dasselbe Verzeichnis klonen:<br />`git@gitlab.pi6.fernuni-hagen.de:ks/fapra/fachpraktikum-2022/alpha/hypercast.git`
- Im Moodle Docker die Datei .env anpassen. Dabei vor allem daran denken, dass die GIT_*-Parameter und der Parameter HYPERCAST_PLUGIN geändert werden. Der Parameter HYPERCAST_PLUGIN muss auf das HyperCast-Projekt verweisen.
- Per Git Bash in das Moodle Docker Projekt navigieren und `docker compose up --build` ausführen.

Moodle und das Plugin werden installiert, die Testdaten angelegt und über localhost:8081 kann dann auf Moodle zugegriffen werden.


## JavaScript-Änderungen bzw. Vue-Änderungen übernehmen
Für das Frontend sollten Sie hauptsächlich im Ordner `vue` arbeiten. Dort können Sie JavaScript- bzw. Vue-Dateien anlegen.
Führen Sie den Befehl `npm run build` mithilfe des Terminals in diesen Verzeichnis des Plugins aus, um die Änderungen minifiziert einmalig zu übernehmen. 
Möchten Sie, dass die Änderungen automatisch nach dem Speichern übernommen werden, rufen Sie den Befehl `npm run nodemon` auf.

## Lizenzen
Unser Plugin steht unter GNU GPLv3-Lizenz, die im Repository in der Datei license.md hinterlegt ist. Dies gilt auch für die verwendeten Packages und Dienste. Diese stehen unter MIT-Lizenz oder abweichenden, mit der Weitergabe des Plugins unter MIT-Lizenz kompatiblen, Lizenzen (meist Apache 2.0, BSD-2 oder 3). Dies gilt auch für moodle (GPL 3.0).
Unser Icon steht unter CC-BY-NC.
Die genutzten Soundeffekts stammen von [pixabay](https://pixabay.com/) und stehen unter Royalty Free, die Fonts unter Unlicense und Open Font License.

## Credits: 
Contributors: Frank Langenbrink, Robin Dürhager, Veronika Stirling, Marcel Goldammer, Alexander Henze, Joachim Otto
