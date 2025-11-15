# Posts und Pages

## Übersicht

**Posts** und **Pages** sind WordPress-Inhalte, die aus WordPress über die WordPress REST API importiert werden. Beide werden in Laravel als Models verwaltet und können über Filament administriert werden.

## Posts

### Beschreibung
Posts sind WordPress-Posts, die aus WordPress über die WordPress REST API (`/wp-json/wp/v2/posts`) importiert werden.

### Datenstruktur
Die `posts`-Tabelle speichert die vollständigen WordPress-Post-Daten:
- `post_id` - WordPress Post ID
- `post_author` - Autor des Posts
- `post_date` / `post_date_gmt` - Veröffentlichungsdatum
- `post_content` - Vollständiger Inhalt des Posts
- `post_title` - Titel des Posts
- `post_excerpt` - Kurzbeschreibung
- `post_status` - Status (z.B. 'publish', 'draft')
- `comment_status` - Kommentarstatus
- `ping_status` - Ping-Status
- `post_name` - Slug/URL-Name
- `post_modified` / `post_modified_gmt` - Änderungsdatum
- `post_content_filtered` - Gefilterter Inhalt
- `guid` - Globally Unique Identifier
- `post_type` - Post-Typ (standardmäßig 'post')
- `website_id` - Verknüpfung zur Website

### Model
- **Klasse**: `App\Models\Post`
- **Beziehungen**:
  - `belongsTo(Website::class)` - Gehört zu einer Website
  - `hasMany(Change::class)` - Hat mehrere Changes (für Tracking)

### Import
Posts werden über eine Route in `routes/web.php` importiert:
```php
Route::get('/posts', function () {
    // Importiert Posts von WordPress REST API
    // Endpoint: /wp-json/wp/v2/posts
});
```

### Permissions
- `view posts` - Posts anzeigen
- `create posts` - Posts erstellen
- `edit posts` - Posts bearbeiten
- `delete posts` - Posts löschen

## Pages

### Beschreibung
Pages sind WordPress-Pages, die ebenfalls aus WordPress importiert werden.

### Datenstruktur
Die `pages`-Tabelle speichert aktuell eine vereinfachte Struktur:
- `id` - Interne ID
- `website_id` - Verknüpfung zur Website
- `url` - URL der WordPress-Page
- `created_at` / `updated_at` - Zeitstempel

### Model
- **Klasse**: `App\Models\Page`
- **Beziehungen**:
  - `belongsTo(Website::class)` - Gehört zu einer Website
  - `hasMany(Change::class)` - Hat mehrere Changes (für Tracking)

### Filament Resource
- **Resource**: `App\Filament\Resources\PageResource`
- **Navigation**: Nicht in der Navigation sichtbar (`shouldRegisterNavigation()` gibt `false` zurück)
- **Verfügbare Aktionen**: Erstellen, Bearbeiten, Löschen (abhängig von Berechtigungen)

### Permissions
- `view pages` - Pages anzeigen
- `create pages` - Pages erstellen
- `edit pages` - Pages bearbeiten
- `delete pages` - Pages löschen

## Gemeinsamkeiten

### Beziehungen
Beide Models haben:
- Eine Beziehung zu `Website` (gehören zu einer Website)
- Eine Beziehung zu `Change` (für Change-Tracking/Monitoring)

### Berechtigungen
Beide haben identische Permission-Strukturen in der Berechtigungsmatrix:
- Super Admin und Admin haben volle Rechte
- Editor kann erstellen und bearbeiten, aber nicht löschen
- Author kann erstellen und bearbeiten
- Attractions-Author hat keine Rechte auf Posts/Pages

### WordPress-Integration
Beide werden aus WordPress importiert und sind Teil des Content-Management-Systems, das mit WordPress synchronisiert wird.

## Verwendung in Filament

### Posts
- Aktuell gibt es keine `PostResource` in Filament (nur das Model existiert)
- Posts werden über die Import-Route synchronisiert

### Pages
- Verfügbar über `PageResource` in Filament
- Kann über die Filament-Admin-Oberfläche verwaltet werden
- Nicht in der Navigation sichtbar, aber über direkten Zugriff erreichbar

## Technische Details

### Migrationen
- **Posts**: `database/migrations/2023_11_14_211036_create_posts_table.php`
- **Pages**: `database/migrations/2021_05_30_070749_create_pages_table.php`

### Models
- `app/Models/Post.php`
- `app/Models/Page.php`

### Filament Resources
- `app/Filament/Resources/PageResource.php` (für Pages)
- Keine `PostResource` vorhanden (nur Model)

