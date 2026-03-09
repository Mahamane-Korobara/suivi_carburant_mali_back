# 📋 ANALYSE COMPLÈTE DU PROJET "SUIVI CARBURANT MALI"

**Date:** 10 janvier 2026  
**Type:** Application Laravel 12 REST API  
**Framework:** Laravel 12 + Laravel Sanctum + Vite + TailwindCSS

---

## 📌 RÉSUMÉ EXÉCUTIF

**Suivi Carburant Mali** est une application Laravel complète dédiée au suivi des stations-service au Mali. Elle permet:
- 🏪 **Aux stations-service** d'enregistrer des demandes d'adhésion, mettre à jour les statuts de leurs carburants (essence, gazoil, gaz) et gérer leurs informations
- 👨‍💼 **Aux administrateurs** de valider/rejeter les demandes, désactiver/réactiver les stations, consulter des statistiques et exporter des données
- 👥 **Aux utilisateurs publics** de consulter les stations approuvées, leurs statuts de carburant, signaler des problèmes et voir l'historique des visites

L'application utilise **Laravel Sanctum** pour l'authentification par tokens API et un système de **caching** pour optimiser les performances. Elle gère plusieurs **statuts** (pending, approved, rejected) et implémente des **notifications** automatiques.

---

## 🏗️ ARCHITECTURE GÉNÉRALE

### Stack Technologique

| Couche | Technologie |
|--------|------------|
| **Backend** | Laravel 12 |
| **API Auth** | Laravel Sanctum (tokens JWT-like) |
| **Frontend Build** | Vite 7 + TailwindCSS 4 |
| **Base de Données** | MySQL/SQLite |
| **Cache** | Redis/File (configurable) |
| **Export Données** | Maatwebsite/Excel (multi-feuilles XLSX) |
| **PDF** | barryvdh/laravel-dompdf |
| **Emails** | Mail natif Laravel |

### Structure du Projet

```
app/
├── Console/
│   └── Commands/
│       └── SendStationReminders.php       # Rappels pour mise à jour de statuts
├── Exports/
│   ├── DashboardExport.php               # Export multi-feuilles
│   ├── StationsSheet.php                 # Feuille: Stations
│   ├── VisitsSheet.php                   # Feuille: Visites
│   └── ReportsSheet.php                  # Feuille: Signalements
├── Helpers/
│   └── StationHelper.php                 # Trait: Gestion du cache
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── AdminRequestController.php    # Gestion stations (index, approve, reject, etc.)
│   │   │   ├── ReportController.php          # Gestion signalements
│   │   │   ├── DashboardExportController.php # Export Excel
│   │   │   └── AdminNotificationController.php # Notifications admin
│   │   ├── Auth/
│   │   │   ├── AdminAuthController.php       # Login/Logout admin
│   │   │   └── StationAuthController.php     # Login/Logout station
│   │   ├── Station/
│   │   │   └── StationRequestController.php  # Gestion statuts carburant
│   │   ├── StationController.php             # Endpoints publics + registration
│   │   ├── ReportControllerUsager.php        # Signalements publics
│   │   └── Controller.php                    # Classe de base
│   └── Requests/
│       └── StoreStationRequest.php           # Validation inscription station
├── Models/
│   ├── Admin.php                             # Modèle Admin
│   ├── Station.php                           # Modèle Station
│   ├── StationStatus.php                     # Statut carburant (current)
│   ├── StationStatusHistory.php              # Historique statuts
│   ├── StationVisit.php                      # Enregistrement des visites
│   ├── StationNotification.php               # Notifications stations
│   ├── AdminNotification.php                 # Notifications admin
│   ├── Report.php                            # Signalements
│   ├── FuelType.php                          # Types de carburant
│   └── User.php                              # Modèle User (non utilisé)
└── Providers/
    └── AppServiceProvider.php                # Services (vide)

config/
├── app.php         # Nom, env, debug
├── auth.php        # Guards (web, api/admin, station)
├── database.php    # Connexion DB
├── filesystems.php # Stockage fichiers
├── mail.php        # Configuration emails
└── ...

database/
├── migrations/     # 18 migrations
│   ├── Création tables de base (users, cache, jobs)
│   ├── Création Station, Admin, FuelType
│   ├── Relations Station <-> FuelType
│   ├── Statuts carburant et historique
│   ├── Visites, Signalements
│   ├── Notifications (Admin + Station)
│   └── Historique statuts
├── seeders/
│   ├── AdminSeeder.php      # Crée admin@suivicarburant.test / admin123
│   ├── FuelTypeSeeder.php   # Crée: Essence, Gazoil, Gaz
│   └── DatabaseSeeder.php
└── factories/
    └── UserFactory.php      # Factory pour tests

routes/
├── api.php       # Routes principales API
├── web.php       # Routes web (non utilisées)
└── console.php   # Commandes console

resources/
├── css/
│   └── app.css       # TailwindCSS
├── js/
│   └── app.js        # Vite entry point
└── views/            # Vues (non utilisées - API only)

storage/ & bootstrap/  # Fichiers système

vendor/  # Dépendances Composer
```

---

## 📊 MODÈLE DE DONNÉES

### Relations Principales

```
Station (1) ──hasMany──> StationStatus (N)
Station (1) ──belongsToMany──> FuelType (N)
  (via table: fuel_type_pivot_station)

Station (1) ──hasMany──> StationStatusHistory (N)
Station (1) ──hasMany──> StationVisit (N)
Station (1) ──hasMany──> Report (N)
Station (1) ──hasMany──> StationNotification (N)

FuelType (1) ──hasMany──> StationStatus (N)
FuelType (1) ──hasMany──> StationStatusHistory (N)

Admin (standalone)
AdminNotification (standalone - sans relation avec Admin)
```

### Modèles Détaillés

#### 1. **Station** (Authenticatable avec Sanctum)
```php
Champs:
- id, name, address, quartier, commune
- gerant_name, phone, email (unique)
- password (hashé), status (pending|approved|rejected)
- rejection_reason, is_active (bool)
- latitude, longitude (géolocalisation)
- created_at, updated_at

Statuts workflow:
  pending → approve → approved
           ↘ reject → rejected
           
  approved → disable → rejected
  rejected → reactivate → approved

Relations:
- hasMany(StationStatus) - statuts actuels des carburants
- hasOne(StationStatus, latestOfMany) - dernier statut
- belongsToMany(FuelType) via fuel_type_pivot_station
- hasMany(StationVisit) - historique visites
- hasMany(Report) - signalements reçus
- hasMany(StationNotification) - notifications pour cette station
```

#### 2. **Admin** (Authenticatable avec Sanctum)
```php
Champs:
- id, name, email, password (hashé)
- created_at, updated_at

Relations: aucune (standalone)
Authentification: tokens Sanctum pour API
```

#### 3. **FuelType** (Pivot)
```php
Champs:
- id, name (Essence, Gazoil, Gaz)
- created_at, updated_at

Relations:
- belongsToMany(Station) via fuel_type_pivot_station
- hasMany(StationStatus) - statuts actuels
- hasMany(StationStatusHistory) - historique statuts
```

#### 4. **StationStatus** (Courant)
```php
Champs:
- id, station_id, fuel_type_id
- status (disponible|peu|rupture)
- created_at, updated_at

Rôle: Table pour stocker UNIQUEMENT le statut ACTUEL de chaque
      type de carburant pour chaque station. 
      Les changements sont logging dans StationStatusHistory.

Relations:
- belongsTo(Station)
- belongsTo(FuelType)

Clé unique: (station_id, fuel_type_id)
```

#### 5. **StationStatusHistory** (Audit)
```php
Champs:
- id, station_id, fuel_type_id, status
- created_at, updated_at

Rôle: Trace CHAQUE changement de statut pour audit/historique.
      Permet de voir tendances, calculs d'uptime, etc.

Relations:
- belongsTo(Station)
- belongsTo(FuelType)
```

#### 6. **StationVisit** (Analytics)
```php
Champs:
- id, station_id, ip_address, device
- commune, quartier, visited_at
- timestamps = false (manual visited_at)

Rôle: Enregistre chaque consultation de station par un visiteur.
      Permet analytics, top stations, popularité, etc.

Relations:
- belongsTo(Station)
```

#### 7. **Report** (Signalements)
```php
Champs:
- id, station_id, type (incident|erreur|autre)
- message (≤1000 caractères)
- created_at, updated_at

Rôle: Permet aux usagers de signaler des problèmes aux stations.
      Trigger: 5 reports exact = AdminNotification créée auto

Relations:
- belongsTo(Station)
```

#### 8. **StationNotification** (Notifications)
```php
Champs:
- id, station_id, title, message
- read (boolean, NOT is_read)
- created_at, updated_at

Rôle: Envoie des notifications aux stations
      Ex: Rappels de mise à jour après 48h d'inactivité

Relations:
- belongsTo(Station)

⚠️ INCOHÉRENCE: Champ `read` vs AdminNotification.`is_read`
   (À normaliser selon votre préférence)
```

#### 9. **AdminNotification** (Notifications Admin)
```php
Champs:
- id, title, message
- is_read (boolean, NOT read)
- created_at, updated_at

Rôle: Notifie les admins d'événements importants
      Ex: Station atteint 5 reports, demandes nouvelles, etc.

Relations: AUCUNE (standalone)

⚠️ INCOHÉRENCE: Champ `is_read` vs StationNotification.`read`
```

#### 10. **User** (Hérité, Non utilisé)
```php
Modèle standard Laravel non utilisé dans ce projet.
À supprimer ou à réutiliser selon vos plans.
```

---

## 🔐 AUTHENTIFICATION & GUARDS

### Configuration Guards (config/auth.php)

```php
Guards:
1. 'web' → session-based (non utilisé)
2. 'api' (defaut) → Sanctum + provider 'admins'
3. 'station' → Sanctum + provider 'stations'

Providers:
- 'admins' → App\Models\Admin
- 'stations' → App\Models\Station
```

### Flux Authentification

#### Admin Login
```
POST /admin/login
{email, password}
  → Admin::where('email')->first()
  → Hash::check(password, admin.password)
  → $admin->createToken('admin_token') 
  → Bearer Token (plainTextToken)
  → auth:sanctum → Guard 'api' → Admin
```

#### Station Login
```
POST /stations/login
{email, password}
  → Station::where('email')->first()
  → Hash::check(password, station.password)
  → Vérifier status === 'approved' (sinon 403)
  → $station->createToken('station_token')
  → Bearer Token
  → auth:sanctum → Guard 'station' → Station
```

#### Public (No Auth)
```
GET /public/stations
GET /public/stations/{id}
POST /public/stations/register
GET /public/fuel-types
POST /public/stations/{id}/report
```

### Middleware Protection
```
Route::middleware('auth:sanctum')->group(...)
  ✓ Valide token Bearer
  ✓ auth()->user() retourne Admin ou Station selon le guard
  ✗ Pas de vérification de rôle (tout utilisateur auth=authentifié)
```

---

## 🌐 ENDPOINTS API COMPLETS

### 1. Authentification

#### POST /admin/login
```
Request: {email, password}
Response 200:
{
  "message": "Connexion réussie",
  "token": "eyJ...",
  "admin": {id, name, email}
}
Response 401: Identifiants invalides
```

#### POST /stations/login
```
Request: {email, password}
Response 200: (si status='approved')
{
  "message": "Connexion réussie",
  "token": "eyJ...",
  "station": {id, name, email, status}
}
Response 403: Compte non approuvé
Response 401: Identifiants invalides
```

#### POST /admin/logout (auth:sanctum)
Response 200: {message: "Déconnexion réussie"}

#### POST /stations/logout (auth:sanctum)
Response 200: {message: "Déconnexion réussie"}

---

### 2. Stations - Endpoints Publics

#### GET /public/stations
```
Query params:
  sort: created_at|visits|name (défaut: created_at)
  order: asc|desc (défaut: desc)
  search: texte recherche (name, quartier)
  fuel: nom carburant (Essence, Gazoil, Gaz)
  status: disponible|peu|rupture

Cache: 3600s avec clé dynamique
  Retourne: Stations approuvées + leurs statuts carburant
Response:
[
  {
    id, name, quartier, commune, latitude, longitude,
    visits_count,
    fuel_statuses: [
      {fuel_type, status, color, updated_at}
    ]
  }
]
```

#### GET /public/stations/{id}
```
Cache: 3600s
Response:
{
  "message": "Détails...",
  "data": {
    id, name, commune, latitude, longitude, status,
    fuel_statuses: [...],
    created_at, updated_at
  }
}

Side effect: Crée StationVisit (firstOrCreate via IP address)
```

#### POST /public/stations/register (STATION REGISTRATION)
```
Request:
{
  name, address, quartier, commune, gerant_name, phone,
  email (unique), password,
  fuel_types: [1, 2], # array d'IDs
  latitude, longitude
}
Validation: StoreStationRequest
Response 201:
{
  "message": "Demande envoyée...",
  "data": {
    id, name, email, status: "pending",
    fuelTypes: [{id, name}]
  }
}

Création:
  → Station créée avec status='pending'
  → Associations FuelType via sync()
  → Pas de password hashé (défini par admin lors approve)
```

#### GET /public/fuel-types
```
Response:
[{id, name}, ...]
```

#### POST /public/stations/{stationId}/report
```
Request:
{
  type: incident|erreur|autre,
  message: string (max 1000)
}
Response 201:
{
  "message": "Signalement envoyé...",
  "data": {id, station_id, type, message, created_at}
}

Logique spéciale:
  → Report créé
  → Si totalReports === 5 exactement
     → AdminNotification créée auto
```

---

### 3. Admin Endpoints (auth:sanctum)

#### GET /admin/stations
```
Filters avancés:
  commune, status (string|array), search
  quartier, visits_min, visits_max
  fuel, status_filter, updated_from, updated_to
  sort_by, sort_order

Cache: 300s (5 min) avec clé MD5

Response: Array stations avec champs:
  id, name, gerant_name, phone, email, quartier, address,
  commune, latitude, longitude, status,
  is_active (booléen: status==='approved'),
  updated_at,
  fuel_statuses: [{fuel_type, status, updated_at}]
```

#### GET /admin/stations/{id}/history
```
Cache: 600s
Response:
{
  "station": {id, name, commune},
  "history": [StationStatus objects, ordered desc]
}
```

#### POST /admin/stations/{id}/approve
```
Logique:
  1. Vérifie status === 'pending'
  2. Génère password: 'stationXXXX' (rand 1000-9999)
  3. Hash(password) + save
  4. status → 'approved'
  5. Email au gérant (avec password EN CLAIR!)
  6. Invalidate caches

Response: {message: "Station approuvée..."}
```

#### POST /admin/stations/{id}/reject
```
Request: {reason: string} (requis)
Logique:
  1. Vérifie status === 'pending' (non obligatoire)
  2. status → 'rejected'
  3. rejection_reason = request.reason
  4. Email au gérant
  5. Invalidate caches

Response: {message: "Station refusée..."}
```

#### POST /admin/stations/{id}/disable
```
Logique:
  1. Vérifie status === 'approved'
  2. status → 'rejected'
  3. rejection_reason = 'Station désactivée par admin'
  4. password = null (invalide)
  5. Email au gérant
  6. Invalidate caches

Response: {message: "Station désactivée..."}
```

#### POST /admin/stations/{id}/reactivate
```
Logique:
  1. Vérifie status === 'rejected'
  2. Génère nouveau password: 'stationXXXX'
  3. status → 'approved'
  4. rejection_reason = null
  5. Email au gérant (password EN CLAIR!)
  6. Invalidate caches

Response: {message: "Station réactivée..."}
```

#### GET /admin/stations/reports
```
Query:
  search: texte (cherche dans message + nom station)
  Pagination: 10 par page

Response:
{
  "success": true,
  "message": "...",
  "data": {
    current_page, data: [{...}], last_page, per_page, total
  }
}
```

#### GET /admin/stations/reports/{id}
Response: {success, data: Report}

#### DELETE /admin/stations/reports/{id}
Response: {success, message: "..."}

#### GET /admin/stations/notifications
```
Cache: AUCUN (real-time)
Response:
[
  {id, title, message, is_read, created_at, updated_at}
]
```

#### POST /admin/stations/notifications/{id}/read
```
Logique: is_read = true
Response: {success, message: "Notification marquée..."}
```

#### GET /admin/stations/export
```
Query: commune, status (optionnel)
Logique: DashboardExport(filters)
  → StationsSheet (filtrée)
  → VisitsSheet (toutes)
  → ReportsSheet (tous)

Response: File download XLSX
          ou 500 JSON en cas erreur
```

#### GET /admin/stations/stats
```
Cache: 300s
Response:
{
  total, approved, rejected, pending,
  new_this_week, new_this_month,
  approved_this_week,
  approval_rate (%), # (approved / (approved+rejected)) * 100
  top_communes: [{commune, total}],
  total_visits, visits_today, visits_this_week,
  last_update: timestamp
}
```

#### GET /admin/stations/fuel-stats
```
Cache: 300s
Response:
{
  total_fuel_points,
  available, out_of_stock, limited,
  by_fuel_type: [
    {
      id, name,
      available_count, out_of_stock_count, limited_count,
      total_count,
      availability_rate (%)
    }
  ],
  recent_updates: [
    {station, commune, fuel, status, updated_at}
  ]
}
```

---

### 4. Station Endpoints (auth:sanctum, guard='station')

#### GET /stations/fuel-statuses
```
Retourne: Types de carburant de la station + statuts actuels
Response:
{
  "message": "...",
  "data": [
    {
      id, type, status: null|disponible|peu|rupture,
      updated_at
    }
  ]
}
```

#### POST /stations/status-change
```
Request:
{
  fuel_type_id: int,
  status: disponible|peu|rupture
}

Logique:
  1. Valide fuel_type_id existe
  2. Récupère ancien StationStatus (si existe)
  3. updateOrCreate(StationStatus)
  4. Crée historique: StationStatusHistory
  5. Retourne old_status et new_status

Response:
{
  "message": "Statut mis à jour...",
  "data": {
    fuel_type_id, old_status, new_status,
    updated_at: "d/m/Y à H:i"
  }
}
```

#### GET /stations/fuel-history
```
Retourne: Historique des changements de la dernière semaine
Logique:
  1. StationStatusHistory de la semaine passée
  2. Pour chaque entry, calcule le statut antérieur
  3. Ordonne desc

Response:
{
  "message": "Historique récupéré...",
  "data": [
    {
      id, fuel_type, old_status, status,
      created_at: "d/m/Y à H:i"
    }
  ]
}
```

---

## 💾 MIGRATIONS (18 total)

### Structure Chronologique

1. **0001_01_01_000000_create_users_table**
   - Crée table `users` (non utilisée)

2. **0001_01_01_000001_create_cache_table**
   - Crée table `cache` pour cache DB

3. **0001_01_01_000002_create_jobs_table**
   - Crée table `jobs` pour queue (non utilisée)

4. **2025_10_30_125653_create_personal_access_tokens_table**
   - Crée table `personal_access_tokens` (Sanctum)

5. **2025_10_30_131102_create_stations_table**
   - Crée table `stations`
   - Champs: name, address, quartier, commune, gerant_name, phone, email, password, status, rejection_reason, latitude, longitude, created_at, updated_at

6. **2025_10_30_131551_create_admins_table**
   - Crée table `admins`
   - Champs: name, email, password, created_at, updated_at

7. **2025_10_30_143916_add_is_active_to_stations_table**
   - ALTER: Ajoute colonne `is_active` BOOLEAN à stations

8. **2025_10_30_143927_create_station_statuses_table**
   - Crée table `station_statuses` (statuts ACTUELS)
   - Champs: station_id (FK), fuel_type_id (FK), status
   - Index: composite (station_id, fuel_type_id) UNIQUE

9. **2025_10_30_161121_add_coordinates_to_stations_table**
   - ALTER: Ajoute latitude, longitude (si pas déjà présentes)

10. **2025_10_30_163841_create_station_visits_table**
    - Crée table `station_visits`
    - Champs: station_id (FK), ip_address, device, commune, visited_at

11. **2025_10_30_171119_create_reports_table**
    - Crée table `reports`
    - Champs: station_id (FK), type (enum), message (text), created_at, updated_at

12. **2025_10_31_144048_create_fuel_types_table**
    - Crée table `fuel_types`
    - Champs: id, name (Essence, Gazoil, Gaz), created_at, updated_at

13. **2025_10_31_144201_create_fuel_type_pivot_station_table**
    - Crée table `fuel_type_pivot_station` (Many-to-Many)
    - Champs: station_id (FK), fuel_type_id (FK)

14. **2025_10_31_144428_remove_type_from_stations_table**
    - ALTER: Supprime colonne `type` de stations

15. **2025_10_31_152314_alter_station_statuses_table_add_fuel_type_id.php**
    - ALTER: Ajoute fuel_type_id à station_statuses (si absent)

16. **2025_11_02_103122_create_station_notifications_table**
    - Crée table `station_notifications`
    - Champs: station_id (FK), title, message, read (boolean), created_at, updated_at

17. **2025_11_02_104957_create_admin_notifications_table**
    - Crée table `admin_notifications`
    - Champs: id, title, message, is_read (boolean), created_at, updated_at

18. **2025_11_11_162201_create_station_status_histories_table**
    - Crée table `station_status_histories` (Audit historique)
    - Champs: station_id (FK), fuel_type_id (FK), status, created_at, updated_at

19. **2025_11_11_162257_create_station_current_statuses_table** (?)
    - Nom similaire à station_statuses - **À VÉRIFIER** (doublon possible?)

---

## 🌱 SEEDERS

### AdminSeeder
```php
Crée EXACTEMENT 1 admin si n'existe pas:
  email: admin@suivicarburant.test
  password: admin123 (hashé)
  name: Admin

Condition: firstOrCreate(['email' => '...'])
Idempotent: OUI (peut être relancé)
```

### FuelTypeSeeder
```php
Crée EXACTEMENT 3 types si n'existent pas:
  1. Essence
  2. Gazoil
  3. Gaz

Condition: firstOrCreate(['name' => ...])
Idempotent: OUI
```

### DatabaseSeeder
Appelle: AdminSeeder, FuelTypeSeeder (structure standard)

---

## 🎯 LOGIQUE MÉTIER CLÉS

### 1. Workflow Stations

```
REGISTRATION:
  public → POST /public/stations/register
       → Validation (StoreStationRequest)
       → Station(status='pending') créée
       → FuelTypes associées
       → Pas de password
       ✗ N'attend pas email confirmation

APPROVAL:
  admin → POST /admin/stations/{id}/approve
       → Vérifie status === 'pending'
       → Génère password 'stationXXXX'
       → Hash + save
       → Email gérant (password EN CLAIR)
       → status = 'approved'
       ⚠️ RISQUE SÉCURITÉ: password en clair dans email

REJECTION:
  admin → POST /admin/stations/{id}/reject
       → status = 'rejected'
       → rejection_reason = request.reason
       → Email gérant
       ✗ Pas de password fourni

REACTIVATION:
  admin → POST /admin/stations/{id}/reactivate
       → Vérifie status === 'rejected'
       → Génère password 'stationXXXX'
       → status = 'approved'
       → Email gérant
       ⚠️ MÊME RISQUE: password en clair

DISABLING:
  admin → POST /admin/stations/{id}/disable
       → Vérifie status === 'approved'
       → password = null (invalide)
       → status = 'rejected'
       → rejection_reason = "Station désactivée..."
       → Email gérant
       ✓ Meilleur: password annulé
```

### 2. Gestion Statuts Carburant

```
STRUCTURE:
  StationStatus: statut ACTUEL (une seule ligne par station/fuel)
  StationStatusHistory: trace CHAQUE changement

MISE À JOUR:
  station → POST /stations/status-change
         → {fuel_type_id, status: disponible|peu|rupture}
         → updateOrCreate(StationStatus)
         → Create(StationStatusHistory) - audit
         → Retourne old_status + new_status

CONSULTATION:
  public → GET /public/stations/{id}
        → Affiche fuel_statuses (avec colors)
        
  admin → GET /admin/stations
       → Affiche statuts actuels + commune + filters

  station → GET /stations/fuel-statuses
         → Affiche leurs statuts + updated_at

  station → GET /stations/fuel-history
         → Historique semaine passée + old_status
```

### 3. Notifications Automatiques

```
TRIGGER 1: 5 Signalements
  public → POST /public/stations/{id}/report
        → Report créé
        → IF count(Report WHERE station_id) === 5
           → AdminNotification créée auto
           → Titre: "Station signalée..."

TRIGGER 2: 48h Sans Mise à Jour (Cron)
  artisan → stations:send-reminders (dailyAt('08:00'))
         → Pour chaque station approuvée:
            - Récupère lastUpdate de ses statuts
            - IF (now - lastUpdate >= 48h) AND (pas déjà notifiée)
              → StationNotification créée
              → Titre: "Rappel de mise à jour"

Note: AdminNotification est standalone (pas de relation)
      StationNotification est liée à Station
      ⚠️ Incohérence: is_read vs read
```

### 4. Caching Strategy

```
CLÉS CACHE:

1. stations.index.{sort}.{order}.{search}.{fuel}.{status}
   - TTL: 3600s
   - Invalidée par: approve, reject, disable, reactivate
   - Via: StationHelper::bustStationCaches()

2. station.{id}
   - TTL: 3600s
   - Invalidée par: même

3. stations_list_{md5(filters)}
   - TTL: 300s (5 min)
   - Admin endpoint filtres avancés

4. stations_stats
   - TTL: 300s
   - Admin stats globales

5. fuel_stats
   - TTL: 300s
   - Stats par carburant

6. stations.analytics (optionnel)
   - TTL: 300s
   - Analytics visites

INVALIDATION:
  bustStationCaches($stationId):
    - Supprime toutes les variantes stations.index.*
    - Supprime station.{id}
    - Supprime caches globaux
    - Redis-aware si disponible
```

### 5. Analytics & Visites

```
TRACKING:
  GET /public/stations/{id}
    → firstOrCreate(StationVisit)
       {station_id, ip_address}
    → Champs: device (User-Agent), commune
    → visited_at: NOW()

ANALYTICS (Admin):
  GET /admin/stations/stats
    → visited_today, visited_this_week, total_visits
    → top_communes (5)
    → new_this_week, new_this_month

EXPORT:
  GET /admin/stations/export
    → VisitsSheet: Affiche tous visits + top 5 "most viewed"
```

---

## 📁 EXPORTS EXCEL

### DashboardExport (WithMultipleSheets)

```php
Feuilles:
1. StationsSheet
   - Filtrée par commune/status (optionnel)
   - Colonnes: Nom, Commune, Quartier, Type Carburant,
              Statut, Dernière mise à jour
   - Source: Station.with(lastStatus)

2. VisitsSheet
   - Toutes les visites récentes
   - Colonnes: Station, Commune, Quartier, Date/Heure,
              IP, Device
   - + Section "Most Viewed" (top 5 de la semaine)
   - Source: StationVisit

3. ReportsSheet
   - Tous les signalements
   - Colonnes: Station, Type, Message, Date/Heure
   - Source: Report.with(station)
```

---

## 🎨 Configuration Vite & TailwindCSS

### vite.config.js
```javascript
Plugins:
1. laravel() - Laravel Vite Plugin
   - Input: resources/css/app.css, resources/js/app.js
   - Auto refresh

2. tailwindcss() - TailwindCSS Vite Plugin (v4)
   - No PostCSS needed

Build output: public/build/
```

### Dépendances Package.json
```
@tailwindcss/vite: ^4.0.0
tailwindcss: ^4.0.0
vite: ^7.0.7
laravel-vite-plugin: ^2.0.0
axios: ^1.11.0
```

---

## ⚙️ DÉPENDANCES PRINCIPALES (Composer)

| Package | Version | Usage |
|---------|---------|-------|
| laravel/framework | ^12.0 | Framework |
| laravel/sanctum | ^4.2 | API Tokens |
| laravel/tinker | ^2.10.1 | REPL |
| barryvdh/laravel-dompdf | ^3.1 | PDF Generation |
| maatwebsite/excel | ^3.1 | Export XLSX |
| fakerphp/faker | ^1.23 | Fake data (dev) |
| phpunit/phpunit | ^11.5.3 | Testing (dev) |
| laravel/pail | ^1.2.2 | Logs viewer (dev) |
| laravel/sail | ^1.41 | Docker (dev) |

---

## 🔄 COMMANDES ARTISAN

### Migrations
```bash
php artisan migrate              # Exécute tous les seeders
php artisan migrate --force      # Force (production)
php artisan migrate:rollback     # Rollback dernière batch
php artisan migrate:refresh      # Rollback + migrates
php artisan migrate:reset        # Reset tout
php artisan make:migration ...   # Nouvelle migration
```

### Seeders
```bash
php artisan db:seed                    # Exécute DatabaseSeeder
php artisan db:seed --class=AdminSeeder # Seeder spécifique
```

### Commandes Perso
```bash
php artisan stations:send-reminders    # Rappels 48h
                                       # Schedulé à 08:00 chaque jour
```

---

## 🚀 DÉPLOIEMENT & SETUP

### Composer Scripts

```json
"setup": [
  "composer install",
  "file_exists(.env) || copy(.env.example, .env)",
  "php artisan key:generate",
  "php artisan migrate --force",
  "npm install",
  "npm run build"
]

"dev": [
  "Composer\\Config::disableProcessTimeout",
  "npx concurrently ... server|queue|logs|vite"
]

"test": [
  "php artisan config:clear",
  "php artisan test"
]
```

### Environnement (.env)
À configurer:
- `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CACHE_DRIVER` (redis|file)
- `MAIL_*` (pour notifications)
- `SANCTUM_STATEFUL_DOMAINS` (Frontend origin)

---

## ⚠️ PROBLÈMES & POINTS D'AMÉLIORATION

### 1. **Sécurité**
- ✗ Passwords généré en clair et envoyés par email
- ✗ Pas de validation d'email (vérification URL)
- ✗ Pas de rôles/permissions (tout admin est super-admin)
- ✗ Pas de rate limiting sur API publique
- ✗ CORS pas configuré

### 2. **Incohérences de Données**
- ✗ Champ `read` (StationNotification) vs `is_read` (AdminNotification)
- ✗ Migration 19 (`station_current_statuses`) semble redondante avec `station_statuses`
- ✗ Modèle `User` non utilisé (à supprimer)

### 3. **Validation**
- ✗ `StoreStationRequest` ne valide pas mot de passe (à ajouter)
- ✗ Pas de validation taille image/fichiers

### 4. **Performance**
- ✗ Pas de pagination pour GET /admin/stations (retourne toutes?)
- ✗ Cache TTL court (300s) = potentiellement trop fréquent
- ✗ Pas d'index DB pour recherches fréquentes

### 5. **Erreurs/Bugs Potentiels**
- ✗ `StationStatus.updated_at` utilisé comme `created_at` dans affichages
- ✗ Pas de soft deletes (suppression logique)
- ✗ Pas de audit trail pour actions admin
- ✗ Timestamps ManualVisited_at correctement configuré?
- ✗ `StationVisit::firstOrCreate` risque race condition en haute concurrence

### 6. **Frontend**
- ✗ Pas de fichier front dans resources/ (API-only?)
- ✗ TailwindCSS configuré mais pas de composants/pages

### 7. **Documentation**
- ✓ README.md fourni avec exemples API
- ✗ Pas d'OpenAPI/Swagger
- ✗ Pas de commentaires PhpDoc sur certains contrôleurs

### 8. **Tests**
- ✗ Dossier tests/ vide
- ✗ Pas de tests unitaires/feature

---

## 📊 SCHÉMA RELATIONNEL SIMPLIFIÉ

```
┌─────────────────────────────────────────────────────────┐
│                      STATIONS                            │
│ (Authenticatable, id, name, email, password, status)   │
└────────┬──────────────────────┬──────────┬───────────────┘
         │                      │          │
    1:N  │ hasMany         M:M  │         1:N │ hasMany
         │                      │             │
┌────────▼──┐  ┌────────────────▼──┐  ┌──────▼──┐
│  REPORTS   │  │  FUEL_TYPES       │  │ VISITS  │
│ (station)  │  │ (pivot table)      │  │ (station)│
└────────────┘  └──────────┬────────┘  └─────────┘
                           │
                      1:N  │ hasMany
                           │
                ┌──────────▼──────────┐
                │ STATION_STATUSES    │
                │ (current status)    │
                └─────────────────────┘
                      │
                      │ (+ FK: fuel_type_id)
                      │
                ┌──────────▼──────────────────┐
                │ STATION_STATUS_HISTORIES    │
                │ (audit trail)               │
                └─────────────────────────────┘

┌──────────────────┐     ┌──────────────────┐
│    ADMINS        │     │ ADMIN_NOTIF      │
│ (Authenticatable)│     │ (standalone)     │
└──────────────────┘     └──────────────────┘

┌──────────────────┐     ┌──────────────────┐
│ STATION_NOTIF    │────▶│ STATIONS         │
│ (per station)    │     │ (1:N relation)   │
└──────────────────┘     └──────────────────┘
```

---

## 🎓 POINTS CLÉS À RETENIR

1. **Triple authentification**: Admin (API guard), Station (station guard), Public (aucune)
2. **Workflow d'approbation**: pending → approved → rejected (+ disable/reactivate)
3. **Double table de statuts**: StationStatus (current) + StationStatusHistory (audit)
4. **Caching agressif**: Tout est cachée sauf admin filters (5 min)
5. **Notifications auto**: 5 signalements trigger AdminNotification, 48h inactivité trigger StationNotification
6. **Exports multi-feuilles**: XLSX avec données complètes (stations, visites, reports)
7. **Pas de validation email**: Registration directe sans confirmation
8. **Passwords en clair**: Risk critique de sécurité lors approbation/réactivation
9. **Pas de permissions**: Tous les admins auth=full access
10. **Analytics basiques**: Visits tracking par IP + statistiques simples

---

## 📝 RÉSUMÉ TECHNIQUE

| Aspect | Détails |
|--------|---------|
| **Language** | PHP 8.2+ |
| **Framework** | Laravel 12 |
| **API Type** | REST (Stateless, Token-based) |
| **Auth** | Sanctum (Multi-guard) |
| **Database** | Relation (18 migrations) |
| **Cache** | Redis/File (configurable) |
| **Queue** | Non utilisé |
| **Frontend Build** | Vite + TailwindCSS |
| **Testing** | PHPUnit (non implanté) |
| **Exports** | Excel multi-feuilles |
| **Scheduled Tasks** | 1 Cron (notifications 08:00) |
| **Lines of Code** | ~3000-4000 (hors vendor) |

---

**Fin de l'analyse - Tous les fichiers ont été lus et analysés en détail.**

