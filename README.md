# 🏥 MedCampus Bangui - Système de Gestion Académique et Sanitaire

> Application complète de gestion pour la Faculté de Médecine de Bangui (République Centrafricaine)

[![Laravel](https://img.shields.io/badge/Laravel-12.5-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://mysql.com)
[![JWT](https://img.shields.io/badge/JWT-Auth-green.svg)](https://jwt-auth.readthedocs.io)

---

## 📋 Table des matières

- [À propos](#-à-propos)
- [Fonctionnalités](#-fonctionnalités)
- [Technologies](#-technologies)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [API Documentation](#-api-documentation)
- [Tests](#-tests)
- [Sécurité](#-sécurité)
- [Auteur](#-auteur)

---

## 📖 À propos

**MedCampus Bangui** est une application backend complète développée avec Laravel pour faciliter la gestion académique et sanitaire de la Faculté de Médecine de Bangui.

Le système permet de :
- Gérer les étudiants, enseignants, cours et notes
- Partager des ressources pédagogiques (PDF, vidéos)
- Collecter et analyser des données sanitaires anonymisées
- Communiquer via un système de messagerie intégré

---

## ✨ Fonctionnalités

### 🔐 **Authentification & Autorisation**
- Inscription et connexion avec JWT
- 3 rôles : Admin, Enseignant, Étudiant
- Permissions fines avec Laravel Policies
- Middleware personnalisés

### 👨‍🎓 **Gestion Académique**
- CRUD complet pour Étudiants, Enseignants, Cours, Notes
- Génération automatique de matricules uniques
- Relations entre modules
- Consultation des notes par étudiant
- Consultation des cours par enseignant

### 📚 **Bibliothèque Médicale**
- Upload de fichiers (PDF, vidéos, documents)
- Catégorisation par type, catégorie, niveau
- Recherche avancée (titre, auteur, description)
- Téléchargement sécurisé avec compteur
- Filtres multiples

### 🏥 **Suivi Sanitaire**
- Collecte de données sanitaires anonymisées
- Génération automatique de codes patients
- Statistiques complètes (pathologies, gravité, démographie)
- Filtres avancés (pathologie, période, zone géographique)
- Export potentiel des données

### 💬 **Messagerie**
- Messages privés entre utilisateurs
- Boîte de réception et d'envoi
- Conversations groupées
- Compteur de messages non lus
- Marquage automatique comme lu

---

## 🛠️ Technologies

### Backend
- **Framework** : Laravel 12.5 (PHP 8.2+)
- **Base de données** : MySQL 8.0+
- **Authentification** : JWT (tymon/jwt-auth)
- **Stockage** : Laravel Storage (fichiers locaux)

### Architecture
- **Design Pattern** : MVC (Model-View-Controller)
- **API** : RESTful
- **Autorisation** : Laravel Policies
- **Validation** : Form Requests personnalisés

---

## 📦 Installation

### Prérequis
```bash
- PHP >= 8.2
- Composer
- MySQL >= 8.0
- XAMPP / WAMP / MAMP (ou serveur web)
```

### Étapes d'installation

1. **Cloner le dépôt**
```bash
git clone https://github.com/FANDEMA-Tony/medcampus-bangui.git
cd medcampus-bangui/backend
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Copier le fichier d'environnement**
```bash
cp .env.example .env
```

4. **Générer la clé d'application**
```bash
php artisan key:generate
```

5. **Configurer la base de données**

Modifier le fichier `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medcampus_bangui
DB_USERNAME=root
DB_PASSWORD=
```

6. **Créer la base de données**
```sql
CREATE DATABASE medcampus_bangui CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

7. **Exécuter les migrations**
```bash
php artisan migrate
```

8. **Générer le secret JWT**
```bash
php artisan jwt:secret
```

9. **Créer le lien symbolique pour le stockage**
```bash
php artisan storage:link
```

10. **Lancer le serveur**
```bash
php artisan serve
```

L'API sera accessible sur : `http://127.0.0.1:8000`

---

## ⚙️ Configuration

### JWT Configuration

Le fichier `config/jwt.php` contient la configuration JWT. Par défaut :
- **TTL** : 60 minutes
- **Refresh TTL** : 20160 minutes (2 semaines)

### Storage Configuration

Les fichiers uploadés sont stockés dans `storage/app/public/ressources/`

---

## 📚 API Documentation

### Base URL
```
http://127.0.0.1:8000/api
```

### Authentification

#### Inscription
```http
POST /register
Content-Type: application/json

{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean.dupont@medcampus.cf",
  "mot_de_passe": "secret123",
  "role": "enseignant"
}
```

#### Connexion
```http
POST /login
Content-Type: application/json

{
  "email": "jean.dupont@medcampus.cf",
  "mot_de_passe": "secret123"
}
```

**Réponse :**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### Endpoints Principaux

| Module | Méthode | Endpoint | Description | Rôle requis |
|--------|---------|----------|-------------|-------------|
| **Étudiants** | GET | `/etudiants` | Liste | Admin |
| | POST | `/etudiants` | Créer | Admin |
| | GET | `/etudiants/{id}` | Détails | Admin |
| | PUT | `/etudiants/{id}` | Modifier | Admin |
| | DELETE | `/etudiants/{id}` | Supprimer | Admin |
| | GET | `/etudiants/{id}/notes` | Notes d'un étudiant | Admin |
| **Enseignants** | GET | `/enseignants` | Liste | Admin |
| | GET | `/enseignants/{id}/cours` | Cours d'un enseignant | Admin |
| **Cours** | GET | `/cours` | Liste | Admin, Enseignant |
| | POST | `/cours` | Créer | Admin, Enseignant |
| | GET | `/cours/{id}/notes` | Notes d'un cours | Admin, Enseignant |
| **Notes** | POST | `/notes` | Créer | Enseignant |
| **Ressources** | GET | `/ressources` | Liste | Tous |
| | POST | `/ressources` | Upload | Admin, Enseignant |
| | GET | `/ressources/{id}/telecharger` | Télécharger | Tous |
| **Données sanitaires** | GET | `/donnees-sanitaires` | Liste | Tous |
| | POST | `/donnees-sanitaires` | Créer | Tous |
| | GET | `/donnees-sanitaires/statistiques` | Stats | Tous |
| **Messages** | GET | `/messages/boite-reception` | Messages reçus | Tous |
| | POST | `/messages` | Envoyer | Tous |
| | GET | `/messages/conversation/{id}` | Conversation | Tous |

**Note :** Tous les endpoints nécessitent un token JWT dans le header :
```
Authorization: Bearer {votre_token}
```

📄 **Documentation complète** : Voir `API_DOCUMENTATION.md`

---

## 🧪 Tests

### Tests manuels avec Postman

1. Importer la collection Postman : `postman/MedCampus_Collection.json`
2. Configurer l'environnement avec votre token JWT
3. Exécuter les tests dans l'ordre

### Résultats des tests

✅ **68 tests validés** couvrant :
- Authentification (3 tests)
- Module académique (21 tests)
- Bibliothèque médicale (12 tests)
- Suivi sanitaire (13 tests)
- Messagerie (10 tests)
- Permissions et sécurité (9 tests)

---

## 🔒 Sécurité

### Mesures de sécurité implémentées

- ✅ **Authentification JWT** avec expiration de token
- ✅ **Hachage des mots de passe** (bcrypt)
- ✅ **Validation stricte** des entrées utilisateur
- ✅ **Policies Laravel** pour les permissions
- ✅ **Middleware de rôles** personnalisés
- ✅ **Anonymisation** des données sanitaires
- ✅ **CSRF Protection** sur les formulaires
- ✅ **Rate limiting** sur les routes sensibles

### Bonnes pratiques

- Pas de données sensibles dans les logs
- Génération de codes patients anonymes
- Validation des types de fichiers uploadés
- Nettoyage des inputs utilisateur

---

## 📊 Structure du Projet
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── EtudiantController.php
│   │   │   ├── EnseignantController.php
│   │   │   ├── CoursController.php
│   │   │   ├── NoteController.php
│   │   │   ├── RessourceMedicaleController.php
│   │   │   ├── DonneeSanitaireController.php
│   │   │   └── MessageController.php
│   │   └── Middleware/
│   │       ├── JwtMiddleware.php
│   │       └── RoleMiddleware.php
│   ├── Models/
│   │   ├── Utilisateur.php
│   │   ├── Etudiant.php
│   │   ├── Enseignant.php
│   │   ├── Cours.php
│   │   ├── Note.php
│   │   ├── RessourceMedicale.php
│   │   ├── DonneeSanitaire.php
│   │   └── Message.php
│   ├── Observers/
│   │   ├── EtudiantObserver.php
│   │   └── EnseignantObserver.php
│   └── Policies/
│       ├── EtudiantPolicy.php
│       ├── EnseignantPolicy.php
│       ├── CoursPolicy.php
│       ├── NotePolicy.php
│       ├── RessourceMedicalePolicy.php
│       ├── DonneeSanitairePolicy.php
│       └── MessagePolicy.php
├── database/
│   └── migrations/
├── routes/
│   └── api.php
└── storage/
    └── app/public/ressources/    

## 📊 Structure de la Base de Données

Le projet utilise **9 tables principales** :

1. `utilisateurs` - Comptes (admin, enseignant, étudiant)
2. `etudiants` - Profils étudiants avec matricule auto
3. `enseignants` - Profils enseignants avec matricule auto
4. `cours` - Cours avec code unique
5. `notes` - Notes des étudiants
6. `ressources_medicales` - Fichiers pédagogiques
7. `donnees_sanitaires` - Données anonymisées
8. `messages` - Messagerie interne

📄 **Schéma complet** : Voir `database/schema.png`

---

## 📄 Licence

Ce projet a été développé dans le cadre d'un projet académique pour la Faculté de Médecine de Bangui.

---

## 👨‍💻 Auteur

Développé avec ❤️ pour améliorer la gestion académique et sanitaire en République Centrafricaine.

**Contact** : tonybienheureuxfandema@.Com

---

## 🙏 Remerciements

- Laravel Framework
- Tymon JWT Auth
- Communauté PHP
- Faculté de Médecine de Bangui

---

## 📝 Notes de version

### Version 1.0.0 (Février 2026)
- ✅ Module académique complet
- ✅ Bibliothèque médicale avec upload
- ✅ Suivi sanitaire avec statistiques
- ✅ Messagerie intégrée
- ✅ Authentification JWT
- ✅ 68 tests validés

---

**🚀 Projet prêt pour la production !**