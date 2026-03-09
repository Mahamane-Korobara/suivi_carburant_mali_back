# INTRODUCTION GÉNÉRALE

## 1.1 Contexte et Problématique

### Contexte

Le Mali, situé en Afrique de l'Ouest, dépend fortement de la distribution régulière de carburants (essence, gazoil, gaz) pour son économie et ses services essentiels. Cependant, le secteur pétrolier du pays fait face à des défis majeurs : **dispersion géographique** des stations-service, **volatilité des approvisionnements** conduisant à des ruptures de stock fréquentes, et surtout, **absence totale de visibilité centralisée** sur l'état réel des disponibilités. 

Actuellement, les informations sur la disponibilité des carburants circulent de façon informelle (orale, par rumeur), sans structure ni traçabilité. Les administrations et les citoyens manquent de moyens fiables pour connaître où trouver du carburant, tandis que les stations-service n'ont aucune plateforme pour signaler leurs statuts et changements.

### Problèmes Identifiés

1. **Manque de transparence et d'accessibilité** : Les citoyens ne savent pas où trouver du carburant disponible ; les administrations manquent de visibilité sur les ruptures réelles.

2. **Processus d'inscription non structuré** : L'intégration de nouvelles stations manque de rigueur, sans validation d'identité ni traçabilité.

3. **Absence de traçabilité** : Impossible de retracer les changements de statut ou de disposer d'un audit pour les décisions administratives.

4. **Absence de retours structurés** : Aucun canal pour que le public signale des problèmes ou erreurs.

5. **Manque de données exploitables** : Pas de statistiques pour analyser les hotspots, identifier les stations problématiques ou planifier les approvisionnements.



## 1.2 Objectifs du Projet (SMART)

### Objectif Principal

**Créer une plateforme centralisée, sécurisée et performante pour le suivi en temps réel de la disponibilité des carburants au Mali, permettant aux stations-service de signaler leurs statuts, aux administrateurs de les valider et gérer, et au public de consulter les informations.**

### Objectifs Spécifiques

| Objectif | Spécification SMART |
|----------|-------------------|
| **1. Centraliser la disponibilité** | 100% des stations approuvées avec statuts à jour ; données fraîches ≤30 min |
| **2. Accréditer les stations** | Workflow: pending → approved/rejected ; approbation ≤2 jours ; audit trail complet |
| **3. API publique accessible** | ≥5 endpoints sans auth ; temps réponse <500ms ; filtres: commune, carburant, statut |
| **4. Gestion stations autonome** | Stations peuvent mettre à jour leurs statuts ; historique 7 jours ; changements tracés |
| **5. Outils administrateur** | ≥10 filtres avancés ; approvals, rejets, désactivations ; exports Excel ; statistiques |
| **6. Signalements & notifications** | Canal pour usagers ; trigger auto à 5 signalements ; rappels 48h sans MAJ |
| **7. Sécurité & authentification** | 2 guards Sanctum (admin/station) ; tokens Bearer ; 0 fuite données sensibles |
| **8. Performance & caching** | Cache 3600s public, 300s admin ; hit rate ≥80% ; latence p95 <500ms |



## 1.3 Portée et Délimitation du Projet

### Inclus dans ce Projet

✅ **Backend API REST complet** (30+ endpoints)  
✅ **Authentification multi-guard** (Admin + Station via Sanctum)  
✅ **Base de données relationnelle** (18 migrations, 10 modèles)  
✅ **Gestion statuts carburant** avec historique d'audit  
✅ **Workflow d'approbation des stations**  
✅ **Exports Excel multi-feuilles**  
✅ **Système de notifications automatiques**  
✅ **Caching stratégique** (Redis/File)  
✅ **Validation et sécurité de base**  

### Exclus de ce Projet

❌ **Frontend/UI** (responsabilité d'un projet séparé)  
❌ **Sécurité avancée** (2FA, OAuth, rate-limiting)  
❌ **Fonctionnalités métier avancées** (paiements, IA, SMS)  
❌ **Infrastructure** (Docker, CI/CD, monitoring)  
❌ **Données géographiques** (calculs de distance, maps)  
❌ **Tests automatisés** (à implémenter ultérieurement)  

### Dépendances Externes

Le projet s'intègre avec:
- **Frontend** (application Web/Mobile séparée consommant l'API)
- **Serveur Mail** (SMTP pour notifications)
- **Base de Données** (MySQL/SQLite)
- **Cache Store** (Redis ou File system)
- **Infrastructure serveur** (PHP 8.2+, Node.js)

### Échelle Initiale

- **Stations** : 50-100 enregistrées, +10-20/mois
- **Utilisateurs publics** : 500-2000/jour
- **Admins** : 1-5
- **Requêtes API** : 10,000-50,000/jour
- **Uptime cible** : 99%



## 1.4 Méthodologie Adoptée

### Architecture et Design

L'architecture du projet suit le **pattern REST API en couches**:

- **Couche Présentation** : Routes API (routes/api.php) suivant conventions RESTful
- **Couche Application** : Controllers (app/Http/Controllers) gérant requêtes et réponses
- **Couche Métier** : Models Eloquent (app/Models) et Helpers encapsulant logique
- **Couche Données** : ORM (Eloquent) et Migrations versionnées (database/migrations)
- **Couche Infrastructure** : Cache (Redis/File), Base de données, Authentification (Sanctum)

### Authentification et Sécurité

Le projet utilise **Laravel Sanctum** pour gérer deux types d'authentification:

1. **Guard "api"** : Authentifie les administrateurs via tokens Bearer
2. **Guard "station"** : Authentifie les stations via tokens Bearer
3. **Public** : Endpoints sans authentification pour consultation citoyenne

Chaque requête authentifiée doit inclure l'header `Authorization: Bearer {token}`. Les mots de passe sont hachés avec bcrypt.

### Gestion des Données et Performance

- **Base de données** : Modèle relationnel SQL avec 18 migrations versionnées
- **Caching** : Stratégie multi-niveaux (3600s pour public, 300s pour admin)
- **Invalidation** : Automatique lors de modifications (bustStationCaches)
- **Eager loading** : Requêtes optimisées via `with()` pour éviter N+1 queries

### Workflow de Stations

Le cycle de vie d'une station suit un workflow structuré:

```
Enregistrement → Validation → Approbation → Opérationnel
   (pending)      (pending)     (approved)     (actif)
                                    ↓
                               (ou rejection)
```

Chaque transition est loggée et traçable.

### Gestion des Statuts de Carburant

Deux tables complémentaires:
- **StationStatus** : Statut ACTUEL de chaque carburant (une ligne par station/fuel)
- **StationStatusHistory** : Historique COMPLET des changements (audit trail)

### Notifications et Automatisations

Deux systèmes de notifications:
- **AdminNotification** : Alertes pour administrateurs (ex: 5 signalements)
- **StationNotification** : Rappels pour stations (ex: 48h sans MAJ)
- **Scheduler** : Cron job quotidien (08:00) pour envoyer rappels

### Stack Technologique

| Couche | Technologie | Justification |
|--------|-------------|--------------|
| Framework | Laravel 12 | Conventions, ecosystem, ORM |
| API Auth | Sanctum | Multi-guard, tokens, simple |
| ORM | Eloquent | Relations expressives, migrations |
| Cache | Redis/File | Performance, expiration native |
| Frontend | Vite + TailwindCSS | Build rapide, modern tooling |
| Exports | Maatwebsite/Excel | Multi-feuilles XLSX natif |
| Versioning | Git + GitFlow | Branches feature/release/hotfix |
| Testing | PHPUnit (TODO) | Coverage des endpoints |

### Phases de Développement

**Phase 1 (Fondations)** : Migrations, models, authentification  
**Phase 2 (Core)** : Endpoints publics, admin, station  
**Phase 3 (Avancé)** : Exports, notifications, caching  
**Phase 4 (Polish)** : Tests, documentation, déploiement

