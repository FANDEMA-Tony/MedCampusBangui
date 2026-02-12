markdown# 🚀 Guide d'Installation - MedCampus Bangui

Guide complet pour installer et configurer le système MedCampus Bangui sur votre machine locale.

---

## 📋 Table des matières

- [Prérequis](#prérequis)
- [Installation pas à pas](#installation-pas-à-pas)
- [Configuration](#configuration)
- [Vérification](#vérification)
- [Dépannage](#dépannage)
- [Scripts utiles](#scripts-utiles)

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé :

### Obligatoires

| Logiciel | Version minimale | Téléchargement |
|----------|------------------|----------------|
| **PHP** | 8.2+ | [php.net](https://www.php.net/downloads) |
| **Composer** | 2.0+ | [getcomposer.org](https://getcomposer.org) |
| **MySQL** | 8.0+ | [mysql.com](https://dev.mysql.com/downloads/) |
| **XAMPP/WAMP/MAMP** | Dernière version | [apachefriends.org](https://www.apachefriends.org) |

### Extensions PHP requises

Vérifiez que ces extensions sont activées dans `php.ini` :
```ini
extension=openssl
extension=pdo_mysql
extension=mbstring
extension=tokenizer
extension=xml
extension=ctype
extension=json
extension=fileinfo
```

**Vérification :** Tapez dans le terminal :
```bash
php -m
```

---

## Installation pas à pas

### Étape 1 : Cloner ou télécharger le projet

**Option A : Avec Git**
```bash
git clone https://github.com/votre-username/medcampus-bangui.git
cd medcampus-bangui/backend
```

**Option B : Sans Git**
1. Téléchargez le ZIP du projet
2. Extrayez dans un dossier (ex: `C:\xampp\htdocs\MedCampusBangui\backend`)
3. Ouvrez le terminal dans ce dossier

---

### Étape 2 : Installer les dépendances PHP
```bash
composer install
```

**Résultat attendu :**
```
Generating optimized autoload files
> Illuminate\Foundation\ComposerScripts::postAutoloadDump
> @php artisan package:discover --ansi
Discovered Package: ...
```

**En cas d'erreur :**
```bash
composer update
composer install
```

---

### Étape 3 : Configurer l'environnement

**1. Copier le fichier d'exemple**
```bash
# Windows
copy .env.example .env

# Mac/Linux
cp .env.example .env
```

**2. Générer la clé d'application**
```bash
php artisan key:generate
```

**Résultat attendu :**
```
Application key set successfully.
```

---

### Étape 4 : Configurer la base de données

**1. Créer la base de données**

**Option A : Via phpMyAdmin** (recommandé pour débutants)
1. Ouvrez `http://localhost/phpmyadmin`
2. Cliquez sur "Nouvelle base de données"
3. Nom : `medcampus_bangui`
4. Interclassement : `utf8mb4_unicode_ci`
5. Cliquez "Créer"

**Option B : Via ligne de commande MySQL**
```bash
mysql -u root -p
```

Puis dans MySQL :
```sql
CREATE DATABASE medcampus_bangui CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

**2. Configurer le fichier `.env`**

Ouvrez le fichier `.env` et modifiez ces lignes :
``env
APP_NAME="MedCampus Bangui"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medcampus_bangui
DB_USERNAME=root
DB_PASSWORD=

# Si vous avez un mot de passe MySQL, mettez-le ici :
# DB_PASSWORD=votre_mot_de_passe
```

---

### Étape 5 : Configurer JWT

Générez le secret JWT :
```bash
php artisan jwt:secret
```

**Résultat attendu :**
```
jwt-auth secret [xxxxx] set successfully.
```

Cela ajoute automatiquement `JWT_SECRET=...` dans votre `.env`.

---

### Étape 6 : Exécuter les migrations

Créez toutes les tables dans la base de données :
```bash
php artisan migrate
```

**Résultat attendu :**
```
Migration table created successfully.
Migrating: 2024_01_xx_create_utilisateurs_table
Migrated:  2024_01_xx_create_utilisateurs_table (50.23ms)
Migrating: 2024_01_xx_create_etudiants_table
Migrated:  2024_01_xx_create_etudiants_table (45.67ms)
...
```

**En cas d'erreur** "Access denied" :
- Vérifiez que MySQL est démarré (via XAMPP/WAMP)
- Vérifiez les identifiants dans `.env`

---

### Étape 7 : Configurer le stockage des fichiers

**1. Créer le lien symbolique**
```bash
php artisan storage:link
```

**Résultat attendu :**
```
The [public/storage] link has been connected to [storage/app/public].
The links have been created.
```

**2. Créer le dossier pour les ressources**
```bash
# Windows
mkdir storage\app\public\ressources

# Mac/Linux
mkdir -p storage/app/public/ressources
```

**3. Vérifier les permissions** (Mac/Linux uniquement)
```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

### Étape 8 : Lancer le serveur
```bash
php artisan serve
```

**Résultat attendu :**
```
Starting Laravel development server: http://127.0.0.1:8000
```

🎉 **Votre API est maintenant accessible sur `http://127.0.0.1:8000` !**

---

## Configuration

### Configuration JWT (optionnel)

Si vous souhaitez modifier la durée de validité des tokens, éditez `config/jwt.php` :
```php
'ttl' => env('JWT_TTL', 60), // Durée en minutes (défaut: 1h)
'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 semaines
```

Ou dans `.env` :
```env
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

### Configuration du stockage

Par défaut, les fichiers sont stockés localement dans `storage/app/public/`.

Pour modifier, éditez `config/filesystems.php`.

---

## Vérification

### 1. Vérifier que l'API fonctionne

Ouvrez votre navigateur et allez sur :
```
http://127.0.0.1:8000/api/
```

Vous devriez voir une erreur 404 ou une page blanche (c'est normal, il n'y a pas de route `/api/`).

### 2. Tester l'inscription

**Avec Postman/Insomnia :**
```http
POST http://127.0.0.1:8000/api/register
Content-Type: application/json

{
  "nom": "Test",
  "prenom": "Utilisateur",
  "email": "test@medcampus.cf",
  "mot_de_passe": "secret123",
  "role": "admin"
}
```

**Résultat attendu :** Code 201 avec un `access_token`.

### 3. Vérifier la base de données

Dans phpMyAdmin, vous devriez voir **9 tables** :
- `utilisateurs`
- `etudiants`
- `enseignants`
- `cours`
- `notes`
- `ressources_medicales`
- `donnees_sanitaires`
- `messages`
- `migrations`

---

## Dépannage

### Problème : "Access denied for user 'root'@'localhost'"

**Solution :**
1. Vérifiez que MySQL est démarré dans XAMPP/WAMP
2. Vérifiez le mot de passe dans `.env` (souvent vide par défaut)
3. Testez la connexion :
```bash
   mysql -u root -p
```

---

### Problème : "Class 'Tymon\JWTAuth\...' not found"

**Solution :**
```bash
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

---

### Problème : "The stream or file could not be opened"

**Solution :** Problème de permissions sur le dossier `storage`.

**Windows :**
```bash
icacls storage /grant Everyone:(OI)(CI)F /T
icacls bootstrap/cache /grant Everyone:(OI)(CI)F /T
```

**Mac/Linux :**
```bash
chmod -R 775 storage bootstrap/cache
```

---

### Problème : "404 Not Found" sur `/api/register`

**Solution :**
1. Vérifiez que le serveur Laravel tourne (`php artisan serve`)
2. Vérifiez l'URL : `http://127.0.0.1:8000/api/register`
3. Nettoyez le cache :
```bash
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
```

---

### Problème : Upload de fichiers ne fonctionne pas

**Solution :**
1. Vérifiez que le lien symbolique existe :
```bash
   php artisan storage:link
```
2. Vérifiez que le dossier `storage/app/public/ressources` existe
3. Vérifiez `php.ini` :
```ini
   upload_max_filesize = 100M
   post_max_size = 100M
```

---

## Scripts utiles

### Réinitialiser complètement la base de données

⚠️ **ATTENTION : Cela supprime toutes les données !**
```bash
php artisan migrate:fresh
```

### Nettoyer le cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Voir toutes les routes
```bash
php artisan route:list
```

### Créer un utilisateur admin rapidement

Utilisez Postman/Insomnia :
```http
POST http://127.0.0.1:8000/api/register

{
  "nom": "Admin",
  "prenom": "Principal",
  "email": "admin@medcampus.cf",
  "mot_de_passe": "admin123",
  "role": "admin"
}
```

---

## Résumé des commandes

Voici toutes les commandes dans l'ordre :
```bash
# 1. Installer les dépendances
composer install

# 2. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 3. Créer la base de données (via phpMyAdmin ou MySQL)

# 4. Configurer JWT
php artisan jwt:secret

# 5. Créer les tables
php artisan migrate

# 6. Configurer le stockage
php artisan storage:link
mkdir storage/app/public/ressources

# 7. Lancer le serveur
php artisan serve
```

---

## Prochaines étapes

Après l'installation :

1. ✅ Testez l'inscription et la connexion
2. ✅ Créez un utilisateur admin
3. ✅ Importez la collection Postman (voir documentation)
4. ✅ Consultez la documentation API : `API_DOCUMENTATION.md`

---

**🎉 Installation terminée ! Votre backend est opérationnel !**

**Support :** En cas de problème, consultez les logs Laravel dans `storage/logs/laravel.log`