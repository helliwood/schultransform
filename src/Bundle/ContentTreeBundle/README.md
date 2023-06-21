ContentTreeBundle
===============

Ideen und Merkliste
-------------------
  - Benötigte Ids für z.B. "for"-Tags werden durchnummeriert (for1...forn). Muss man nun for umsortieren dann kann man die Id explizit angeben
  - Es gibt Platzhalter für andere Snippets (wie früher Parts) und explizite includes für Snippet in Snippet including
  - Wir brauchen ein Tag-Element mit dem man dem parent ein Attribut hinzufügen/appenden/prependen kann, inkl. suffix "content_" und prefix und pointer z.b. foreach.loop
  - Element Handler muss wissen ob backend/nicht backend, form oder rendermodus etc.
  - Snippets haben Versionen (eventuell mit 2 Stellen 1.2): Man kann Snippet ändern z.B. Layout und die Version lassen -> gleich update oder neue Version: wird erst beim neuen Anlegen von Snippets verwendet
    - beim editierne kann man auf neues Snippet wechseln
  - Alle Werte stehen in Key/Value Table mit Snippet Id+Version, SiteId+Version
  - Extra Suchindex, eventuell in Memory 
  - Elemente als "syntaktisch" definieren um z.B. bei der allowed parent überprüfung, if oder foreach auszublenden
  - Snippet-Vorschau: Redakteur kann für alle Snippets Dummy-Vorschau-Daten hinterlegen
  - Snippet-Vorschau: Bei der Snippetauswahl wird das "Part" wo das Snippet hinzugefügt wird, mit allen vorhanden Snippets gerendet und eine Vorschau des Snippets angezeigt
  - Snippets: Liegen in einem Ordner mit beliebiger Struktur, werden geparst und automatisch in die DB geschrieben
    - Name und Group stehen im template-Tag im xml
  
  - Beim Speichern TemplateCode in SiteContent speichern und wenn SiteContentData verändert, neue Version mit TemplateCode und JSON(Data) speichern (zum wiederherstellen)