# 🏦 BanqueApp v2 — Gestion & Audit avec Rôles
## Stack : PHP + PostgreSQL + Sessions + Triggers

---

## 📁 Structure du projet

```
bank_v2/
├── config.php        → Connexion PostgreSQL
├── index.php         → Point d'entrée (redirige selon le rôle)
├── login.php         → Page de connexion
├── logout.php        → Déconnexion
├── admin.php         → Dashboard Admin (lecture seule : audit)
├── user.php          → Dashboard User (CRUD ses propres comptes)
├── generate_hash.php → Générateur de hash bcrypt (à supprimer après usage)
├── schema.sql        → Tables + Triggers PostgreSQL
└── README.md
```

---

## 🚀 Installation

### 1. Générer le vrai hash du mot de passe

```bash
php -r "echo password_hash('password', PASSWORD_BCRYPT);"
```

Copiez le hash obtenu et remplacez `'$2y$10$...'` dans `schema.sql`.

### 2. Créer la base et exécuter le schéma

```bash
psql -U postgres
CREATE DATABASE bank_db;
\c bank_db
\i schema.sql
```

### 3. Configurer config.php

```php
define('DB_PASS', 'votre_vrai_mot_de_passe');
```

### 4. Lancer

```bash
php -S localhost:8000
```

Accéder via : **http://localhost:8000** → redirige vers login.php

---

## 👥 Comptes de test

| Username | Mot de passe | Rôle  |
|----------|-------------|-------|
| admin    | password    | Admin |
| alice    | password    | User  |
| bob      | password    | User  |
| clara    | password    | User  |

---

## 🔐 Règles d'accès

| Action | Admin | User |
|--------|-------|------|
| Voir l'audit global | ✅ | ❌ |
| Filtrer l'audit par user/date | ✅ | ❌ |
| Créer un compte | ❌ | ✅ |
| Modifier ses comptes | ❌ | ✅ (les siens uniquement) |
| Supprimer ses comptes | ❌ | ✅ (les siens uniquement) |
| Voir les comptes des autres | ❌ | ❌ |

---

## 🔧 Triggers PostgreSQL

| Trigger | Événement | Enregistre dans audit_compte |
|---------|-----------|------------------------------|
| `trig_after_insert` | INSERT | type='INSERTION', solde_nouv |
| `trig_after_update` | UPDATE | type='MODIFICATION', solde_ancien + solde_nouv |
| `trig_after_delete` | DELETE | type='SUPPRESSION', solde_ancien |

Le champ `utilisateur` dans l'audit est rempli avec le **username de l'application** (et non `CURRENT_USER` PostgreSQL), ce qui permet à l'admin de savoir exactement qui a fait quoi.
