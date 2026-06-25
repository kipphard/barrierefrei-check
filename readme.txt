=== Barrierefrei – BFSG & WCAG Accessibility Check ===
Contributors: kipphard
Tags: accessibility, barrierefreiheit, bfsg, wcag, audit
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ehrliches WCAG 2.2 / BFSG Audit-Plugin: prüft dein gerendertes HTML, zeigt priorisierte Fehler mit Lösungshinweisen und erzeugt eine Barrierefreiheitserklärung.

== Description ==

**Barrierefrei – BFSG & WCAG Accessibility Check** ist ein ehrliches Audit-Werkzeug für WordPress-Websites. Es analysiert das tatsächlich gerenderte HTML deiner Seiten und meldet Barrierefreiheitsprobleme nach WCAG 2.2 und dem deutschen Barrierefreiheitsstärkungsgesetz (BFSG) – mit klaren Lösungshinweisen, Schweregrad-Priorisierung und einem Zugänglichkeits-Score von 0 bis 100.

**Was dieses Plugin tut:**

* Scannt die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten und Beiträge auf Barrierefreiheitsprobleme
* 12 automatisierte Prüfregeln nach WCAG 2.2 (fehlende Alt-Texte, fehlende Seitensprache, Formulare ohne Labels, leere Links und Schaltflächen, Überschriftenhierarchie, Skip-Links, Landmarks, doppelte IDs u. v. m.)
* Jedes Problem zeigt: Schweregrad (Kritisch / Schwerwiegend / Mittel / Gering), WCAG-Kriterium, Beschreibung, konkreten Lösungshinweis und einen Kontext-Snippet aus dem HTML
* Automatisch generierte Barrierefreiheitserklärung (BFSG-konforme Vorlage) als WordPress-Seite mit dem Shortcode `[barrierefreiheitserklaerung]`
* Kein Tracking, keine externen Requests, keine Abhängigkeiten – alles läuft lokal auf deinem Server

**Was dieses Plugin NICHT tut:**

Dieses Plugin ist *kein Overlay* und behauptet *keine sofortige oder vollständige Konformität*. Automatisierte Tests können nur einen Teil der WCAG-Kriterien prüfen. Anforderungen wie Farbkontraste (1.4.3), vollständige Tastaturbedienung, kognitive Zugänglichkeit und die meisten WCAG-Kriterien der Stufe AAA erfordern eine manuelle Prüfung durch Expertinnen und Experten oder Nutzerinnen und Nutzer assistiver Technologien.

Overlay-Widgets, die "Ein-Klick-Barrierefreiheit" versprechen, sind keine valide Lösung und werden von der Accessibility-Community abgelehnt. Dieses Plugin ist das Gegenteil davon: transparentes Reporting, ehrliche Kommunikation.

*This plugin is an honest audit tool for German-speaking WordPress sites. It checks your real rendered HTML and reports WCAG 2.2 findings with fix guidance. It also generates a legally required "Barrierefreiheitserklärung" (accessibility statement) per the BFSG.*

== Installation ==

1. Lade das Plugin-Verzeichnis `barrierefrei-check` in das `/wp-content/plugins/`-Verzeichnis hoch.
2. Aktiviere das Plugin unter "Plugins" in der WordPress-Administration.
3. Navigiere zu **Barrierefreiheit → Dashboard** und klicke auf "Scan starten".
4. Fülle unter **Barrierefreiheit → Erklärung** deine Kontaktdaten und den Konformitätsstatus aus, um die Barrierefreiheitserklärung zu generieren.

== Frequently Asked Questions ==

= Macht dieses Plugin meine Seite automatisch barrierefrei? =

Nein. Dieses Plugin ist ein Audit-Werkzeug, kein Overlay. Es zeigt dir, wo Barrierefreiheitsprobleme vorliegen, und gibt dir konkrete Hinweise, wie du sie beheben kannst. Die eigentliche Arbeit – Bilder mit Alt-Text versehen, Formulare korrekt beschriften, eine sinnvolle Überschriftenhierarchie erstellen – musst du oder dein Entwicklungsteam selbst leisten.

Kein automatisches Werkzeug kann vollständige WCAG-Konformität garantieren oder herstellen. Wer das behauptet, lügt.

= Welche WCAG-Version wird geprüft? =

Das Plugin orientiert sich an WCAG 2.2 Stufe AA, wie vom BFSG gefordert. Die aktuell implementierten Regeln decken die häufigsten und am einfachsten automatisch erkennbaren Probleme ab.

= Wie viele Seiten werden gescannt? =

Die kostenlose Version scannt die Startseite sowie bis zu 4 zuletzt veröffentlichte Seiten und Beiträge. Geplante Scans größerer Websites sind für die Pro-Version vorgesehen.

= Werden meine Daten an externe Server übertragen? =

Nein. Alle Scans laufen lokal auf deinem eigenen Server. Es werden keine Daten an Dritte übertragen.

= Der Scan liefert keine Ergebnisse oder schlägt fehl. Was kann ich tun? =

Stelle sicher, dass deine Website von sich selbst erreichbar ist (kein Passwortschutz, kein "Coming soon"-Modus). Das Plugin ruft deine eigenen URLs intern per HTTP ab. Prüfe auch, ob PHP-Extensions `dom` und `libxml` verfügbar sind.

= Was ist die Barrierefreiheitserklärung (Barrierefreiheitserklärung)? =

Das BFSG (Barrierefreiheitsstärkungsgesetz) verpflichtet ab dem 28. Juni 2025 bestimmte Anbieter digitaler Produkte und Dienstleistungen, eine öffentliche Erklärung zur Barrierefreiheit zu veröffentlichen. Das Plugin erzeugt eine Vorlage dieser Erklärung, die du an deine Situation anpassen und von einer Rechtsanwältin oder einem Rechtsanwalt prüfen lassen solltest.

== Changelog ==

= 0.1.0 =
* Erstveröffentlichung
* 12 automatisierte WCAG 2.2 / BFSG-Prüfregeln
* Priorisiertes Dashboard mit Score, Schweregrad-Badges und Lösungshinweisen
* Shortcode `[barrierefreiheitserklaerung]` für die Barrierefreiheitserklärung
* Formular zur Verwaltung der Erklärungsfelder (Kontakt, Konformitätsstatus, Adresse, Durchsetzungsstelle)
* SSRF-Schutz: nur URLs des eigenen Hosts werden gescannt
* Keine externen Abhängigkeiten, kein Tracking
