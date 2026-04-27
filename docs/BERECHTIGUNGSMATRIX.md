# Berechtigungsmatrix für Benutzergruppen

## Übersicht der Rollen

| Rolle | Beschreibung |
|-------|--------------|
| **super_admin** | Vollzugriff auf alle Funktionen |
| **admin** | Vollzugriff außer Rollenverwaltung |
| **editor** | Bearbeitungsrechte ohne Löschfunktion |
| **author** | Eingeschränkte Erstellungsrechte für Listicles |
| **attractions-author** | Eingeschränkte Erstellungsrechte nur für Attractions |

## Detaillierte Berechtigungsmatrix

### Content Management

| Berechtigung | super_admin | admin | editor | author | attractions-author |
|--------------|-------------|-------|--------|--------|-------------------|
| **Listicles** |
| view listicles | ✅ | ✅ | ✅ | ✅ | ❌ |
| create listicles | ✅ | ✅ | ✅ | ✅ | ❌ |
| edit listicles | ✅ | ✅ | ✅ | ✅ (nur eigene) | ❌ |
| delete listicles | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Posts** |
| view posts | ✅ | ✅ | ✅ | ✅ | ❌ |
| create posts | ✅ | ✅ | ✅ | ✅ | ❌ |
| edit posts | ✅ | ✅ | ✅ | ✅ | ❌ |
| delete posts | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Pages** |
| view pages | ✅ | ✅ | ✅ | ✅ | ❌ |
| create pages | ✅ | ✅ | ✅ | ✅ | ❌ |
| edit pages | ✅ | ✅ | ✅ | ✅ | ❌ |
| delete pages | ✅ | ✅ | ❌ | ❌ | ❌ |

### Places Management

| Berechtigung | super_admin | admin | editor | author | attractions-author |
|--------------|-------------|-------|--------|--------|-------------------|
| **Attractions** |
| view attractions | ✅ | ✅ | ✅ | ❌ | ✅ |
| create attractions | ✅ | ✅ | ✅ | ❌ | ✅ |
| edit attractions | ✅ | ✅ | ✅ | ❌ | ❌ |
| edit own attractions | ✅ | ✅ | ✅ | ❌ | ✅ |
| delete attractions | ✅ | ✅ | ❌ | ❌ | ❌ |
| DataForSEO Update | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Cities** |
| view cities | ✅ | ✅ | ✅ | ✅ | ✅ |
| create cities | ✅ | ✅ | ✅ | ❌ | ❌ |
| edit cities | ✅ | ✅ | ✅ | ❌ | ❌ |
| delete cities | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Countries** |
| view countries | ✅ | ✅ | ✅ | ✅ | ✅ |
| create countries | ✅ | ✅ | ✅ | ❌ | ❌ |
| edit countries | ✅ | ✅ | ✅ | ❌ | ❌ |
| delete countries | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Accessibility Attributes** |
| view accessibility_attributes | ✅ | ✅ | ✅ | ✅ | ✅ |
| create accessibility_attributes | ✅ | ✅ | ✅ | ❌ | ❌ |
| edit accessibility_attributes | ✅ | ✅ | ✅ | ❌ | ❌ |
| delete accessibility_attributes | ✅ | ✅ | ❌ | ❌ | ❌ |

### Keywords & Changes

| Berechtigung | super_admin | admin | editor | author | attractions-author |
|--------------|-------------|-------|--------|--------|-------------------|
| view keywords | ✅ | ✅ | ✅ | ❌ | ❌ |
| manage keywords | ✅ | ✅ | ✅ | ❌ | ❌ |
| view changes | ✅ | ✅ | ✅ | ❌ | ❌ |
| manage changes | ✅ | ✅ | ✅ | ❌ | ❌ |

### Settings

| Berechtigung | super_admin | admin | editor | author | attractions-author |
|--------------|-------------|-------|--------|--------|-------------------|
| **General Settings** |
| view settings | ✅ | ✅ | ❌ | ❌ | ❌ |
| edit settings | ✅ | ✅ | ❌ | ❌ | ❌ |
| **AI Settings** |
| view ai_settings | ✅ | ✅ | ✅ | ❌ | ❌ |
| edit ai_settings | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Backups** |
| view backups | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage backups | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Users** |
| view users | ✅ | ✅ | ❌ | ❌ | ❌ |
| create users | ✅ | ✅ | ❌ | ❌ | ❌ |
| edit users | ✅ | ✅ | ❌ | ❌ | ❌ |
| delete users | ✅ | ✅ | ❌ | ❌ | ❌ |
| manage users | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Roles** |
| view roles | ✅ | ❌ | ❌ | ❌ | ❌ |
| create roles | ✅ | ❌ | ❌ | ❌ | ❌ |
| edit roles | ✅ | ❌ | ❌ | ❌ | ❌ |
| delete roles | ✅ | ❌ | ❌ | ❌ | ❌ |
| manage roles | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Permissions** |
| view permissions | ✅ | ❌ | ❌ | ❌ | ❌ |
| create permissions | ✅ | ❌ | ❌ | ❌ | ❌ |
| edit permissions | ✅ | ❌ | ❌ | ❌ | ❌ |
| delete permissions | ✅ | ❌ | ❌ | ❌ | ❌ |

## Legende

- ✅ = Berechtigung vorhanden
- ❌ = Keine Berechtigung
- ✅ (nur eigene) = Nur für eigene Inhalte

## Wichtige Hinweise

1. **Super Admin**: Hat automatisch alle Berechtigungen, auch wenn neue hinzugefügt werden
2. **Editor**: Kann Inhalte bearbeiten, aber keine Löschfunktionen verwenden
3. **Author**: Kann nur eigene Listicles erstellen und bearbeiten, keine Attractions
4. **Attractions Author**: Kann nur Attractions erstellen und bearbeiten (nur eigene), keine Listicles
5. **Eigene Inhalte**: Die Prüfung auf "nur eigene" Inhalte funktioniert aktuell nur für Listicles (mit `user_id` Feld). Für Attractions wird die Prüfung auf eigene Attractions implementiert, sobald ein `user_id` Feld hinzugefügt wird. Aktuell haben Attractions-Autoren mit der Berechtigung "edit own attractions" Zugriff auf alle Attractions.

