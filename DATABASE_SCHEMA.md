# 🗄️ Schéma de Base de Données - MedCampus Bangui

Documentation complète de la structure de la base de données.

---

## 📋 Vue d'ensemble

Le système utilise **9 tables principales** avec relations complètes.

### Liste des tables

1. `utilisateurs` - Comptes utilisateurs (authentification)
2. `etudiants` - Profils étudiants
3. `enseignants` - Profils enseignants
4. `cours` - Cours académiques
5. `notes` - Notes des étudiants
6. `ressources_medicales` - Fichiers pédagogiques
7. `donnees_sanitaires` - Données sanitaires anonymisées
8. `messages` - Messagerie interne
9. `migrations` - Historique migrations Laravel

---

## 📊 Diagramme des relations
```
utilisateurs (1) ----< (N) etudiants
utilisateurs (1) ----< (N) enseignants
utilisateurs (1) ----< (N) messages (expéditeur)
utilisateurs (1) ----< (N) messages (destinataire)
utilisateurs (1) ----< (N) ressources_medicales
utilisateurs (1) ----< (N) donnees_sanitaires

enseignants (1) ----< (N) cours
cours (1) ----< (N) notes
etudiants (1) ----< (N) notes
```

---

## 📋 Description détaillée des tables

---

### 1. `utilisateurs`

**Description :** Table principale pour l'authentification et les rôles.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_utilisateur` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `nom` | VARCHAR(255) | NOT NULL | Nom de famille |
| `prenom` | VARCHAR(255) | NOT NULL | Prénom |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Email (identifiant) |
| `mot_de_passe` | VARCHAR(255) | NOT NULL | Mot de passe haché (bcrypt) |
| `role` | ENUM | NOT NULL | admin, enseignant, etudiant, invite |
| `statut` | ENUM | DEFAULT 'actif' | actif, suspendu, inactif |
| `created_at` | TIMESTAMP | | Date de création |
| `updated_at` | TIMESTAMP | | Date de modification |

**Index :**
- PRIMARY KEY (`id_utilisateur`)
- UNIQUE (`email`)
- INDEX (`role`)
- INDEX (`statut`)

---

### 2. `etudiants`

**Description :** Profils détaillés des étudiants avec matricule auto-généré.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_etudiant` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `matricule` | VARCHAR(255) | UNIQUE, NOT NULL | Généré automatiquement |
| `nom` | VARCHAR(255) | NOT NULL | Nom de famille |
| `prenom` | VARCHAR(255) | NOT NULL | Prénom |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Email |
| `date_naissance` | DATE | NOT NULL | Date de naissance |
| `filiere` | VARCHAR(255) | NOT NULL | Médecine, Pharmacie, etc. |
| `statut` | ENUM | DEFAULT 'actif' | actif, suspendu, diplome |
| `created_at` | TIMESTAMP | | Date de création |
| `updated_at` | TIMESTAMP | | Date de modification |

**Format matricule :** `[NOM3][PRENOM3][FILIERE3][YYYYMMDD]`  
**Exemple :** `MARSOPMED20000320`

**Index :**
- PRIMARY KEY (`id_etudiant`)
- UNIQUE (`matricule`)
- UNIQUE (`email`)

---

### 3. `enseignants`

**Description :** Profils détaillés des enseignants avec matricule auto-généré.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_enseignant` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `matricule` | VARCHAR(255) | UNIQUE, NOT NULL | Généré automatiquement |
| `nom` | VARCHAR(255) | NOT NULL | Nom de famille |
| `prenom` | VARCHAR(255) | NOT NULL | Prénom |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL | Email |
| `date_naissance` | DATE | NOT NULL | Date de naissance |
| `specialite` | VARCHAR(255) | NOT NULL | Spécialité médicale |
| `grade` | VARCHAR(255) | NULLABLE | Grade académique |
| `created_at` | TIMESTAMP | | Date de création |
| `updated_at` | TIMESTAMP | | Date de modification |

**Format matricule :** `[NOM3][PRENOM3][SPECIALITE3][YYYYMMDD]`  
**Exemple :** `DUPJEACAR19800515`

**Index :**
- PRIMARY KEY (`id_enseignant`)
- UNIQUE (`matricule`)
- UNIQUE (`email`)

---

### 4. `cours`

**Description :** Cours académiques dispensés par les enseignants.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_cours` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `code` | VARCHAR(50) | UNIQUE, NOT NULL | Code du cours (MED101) |
| `titre` | VARCHAR(255) | NOT NULL | Titre du cours |
| `description` | TEXT | NULLABLE | Description détaillée |
| `id_enseignant` | BIGINT UNSIGNED | FK, NOT NULL | Enseignant responsable |
| `created_at` | TIMESTAMP | | Date de création |
| `updated_at` | TIMESTAMP | | Date de modification |

**Relations :**
- FK `id_enseignant` → `enseignants(id_enseignant)` ON DELETE CASCADE

**Index :**
- PRIMARY KEY (`id_cours`)
- UNIQUE (`code`)
- INDEX (`id_enseignant`)

---

### 5. `notes`

**Description :** Notes des étudiants pour chaque cours.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_note` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `id_etudiant` | BIGINT UNSIGNED | FK, NOT NULL | Étudiant noté |
| `id_cours` | BIGINT UNSIGNED | FK, NOT NULL | Cours évalué |
| `valeur` | DECIMAL(5,2) | NOT NULL | Note sur 20 |
| `date_evaluation` | DATE | NOT NULL | Date de l'évaluation |
| `created_at` | TIMESTAMP | | Date de création |
| `updated_at` | TIMESTAMP | | Date de modification |

**Relations :**
- FK `id_etudiant` → `etudiants(id_etudiant)` ON DELETE CASCADE
- FK `id_cours` → `cours(id_cours)` ON DELETE CASCADE

**Validation :**
- `valeur` : 0.00 à 20.00

**Index :**
- PRIMARY KEY (`id_note`)
- INDEX (`id_etudiant`)
- INDEX (`id_cours`)

---

### 6. `ressources_medicales`

**Description :** Fichiers pédagogiques (PDF, vidéos) partagés.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_ressource` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `titre` | VARCHAR(255) | NOT NULL | Titre de la ressource |
| `description` | TEXT | NULLABLE | Description |
| `auteur` | VARCHAR(255) | NULLABLE | Auteur |
| `type` | ENUM | NOT NULL | cours, livre, video, article, autre |
| `categorie` | VARCHAR(255) | NULLABLE | Catégorie/matière |
| `niveau` | ENUM | NULLABLE | L1, L2, L3, M1, M2, doctorat, formation_continue |
| `nom_fichier` | VARCHAR(255) | NOT NULL | Nom original du fichier |
| `chemin_fichier` | VARCHAR(255) | NOT NULL | Chemin de stockage |
| `type_fichier` | VARCHAR(255) | NOT NULL | Extension (pdf, mp4) |
| `taille_fichier` | BIGINT UNSIGNED | NOT NULL | Taille en octets |
| `nombre_telechargements` | INT | DEFAULT 0 | Compteur de téléchargements |
| `est_public` | BOOLEAN | DEFAULT TRUE | Ressource publique ou privée |
| `ajoute_par` | BIGINT UNSIGNED | FK, NULLABLE | Utilisateur qui a ajouté |
| `created_at` | TIMESTAMP | | Date de création |
| `updated_at` | TIMESTAMP | | Date de modification |

**Relations :**
- FK `ajoute_par` → `utilisateurs(id_utilisateur)` ON DELETE SET NULL

**Index :**
- PRIMARY KEY (`id_ressource`)
- INDEX (`type`)
- INDEX (`categorie`)
- INDEX (`niveau`)
- INDEX (`est_public`)

---

### 7. `donnees_sanitaires`

**Description :** Données sanitaires anonymisées pour recherche épidémiologique.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_donnee` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `code_patient` | VARCHAR(255) | UNIQUE, NOT NULL | Code anonyme (PAT-XXXXXXX) |
| `sexe` | ENUM | NULLABLE | M, F, Autre |
| `age` | INT | NULLABLE | Âge du patient |
| `tranche_age` | VARCHAR(255) | NULLABLE | 0-5, 6-12, 13-18, 19-35, 36-60, 60+ |
| `quartier` | VARCHAR(255) | NULLABLE | Quartier de résidence |
| `commune` | VARCHAR(255) | NULLABLE | Commune |
| `ville` | VARCHAR(255) | DEFAULT 'Bangui' | Ville |
| `coordonnees_gps` | VARCHAR(255) | NULLABLE | Latitude, longitude |
| `pathologie` | VARCHAR(255) | NOT NULL | Maladie/symptôme principal |
| `symptomes` | TEXT | NULLABLE | Liste des symptômes |
| `gravite` | ENUM | DEFAULT 'modere' | leger, modere, grave, critique |
| `date_debut_symptomes` | DATE | NULLABLE | Début des symptômes |
| `date_consultation` | DATE | NOT NULL | Date de consultation |
| `diagnostic` | TEXT | NULLABLE | Diagnostic médical |
| `traitement_prescrit` | TEXT | NULLABLE | Traitement |
| `statut` | ENUM | DEFAULT 'en_cours' | en_cours, guerison, decede, suivi_perdu |
| `antecedents_medicaux` | BOOLEAN | DEFAULT FALSE | Antécédents médicaux |
| `antecedents_details` | TEXT | NULLABLE | Détails antécédents |
| `vaccination_a_jour` | BOOLEAN | NULLABLE | Vaccination à jour |
| `notes` | TEXT | NULLABLE | Observations |
| `est_anonyme` | BOOLEAN | DEFAULT TRUE | Données anonymisées |
| `collecte_par` | BIGINT UNSIGNED | FK, NOT NULL | Collecteur |
| `created_at` | TIMESTAMP | | Date de création |
| `updated_at` | TIMESTAMP | | Date de modification |

**Relations :**
- FK `collecte_par` → `utilisateurs(id_utilisateur)` ON DELETE CASCADE

**Index :**
- PRIMARY KEY (`id_donnee`)
- UNIQUE (`code_patient`)
- INDEX (`pathologie`)
- INDEX (`ville`)
- INDEX (`commune`)
- INDEX (`tranche_age`)
- INDEX (`sexe`)
- INDEX (`gravite`)
- INDEX (`date_consultation`)

---

### 8. `messages`

**Description :** Messagerie interne entre utilisateurs.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id_message` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Identifiant unique |
| `expediteur_id` | BIGINT UNSIGNED | FK, NOT NULL | Utilisateur expéditeur |
| `destinataire_id` | BIGINT UNSIGNED | FK, NOT NULL | Utilisateur destinataire |
| `sujet` | VARCHAR(255) | NULLABLE | Sujet du message |
| `contenu` | TEXT | NOT NULL | Contenu du message |
| `est_lu` | BOOLEAN | DEFAULT FALSE | Message lu ou non |
| `lu_a` | TIMESTAMP | NULLABLE | Date/heure de lecture |
| `created_at` | TIMESTAMP | | Date d'envoi |
| `updated_at` | TIMESTAMP | | Date de modification |

**Relations :**
- FK `expediteur_id` → `utilisateurs(id_utilisateur)` ON DELETE CASCADE
- FK `destinataire_id` → `utilisateurs(id_utilisateur)` ON DELETE CASCADE

**Index :**
- PRIMARY KEY (`id_message`)
- INDEX (`expediteur_id`)
- INDEX (`destinataire_id`)
- INDEX (`est_lu`)
- INDEX (`created_at`)

---

## 🔗 Diagramme relationnel détaillé
```
┌─────────────────┐
│  utilisateurs   │
│ (authentif)     │
└────────┬────────┘
         │
         ├──────────────┐
         │              │
         ▼              ▼
┌──────────────┐  ┌──────────────┐
│  etudiants   │  │ enseignants  │
└──────┬───────┘  └──────┬───────┘
       │                  │
       │                  ▼
       │          ┌──────────────┐
       │          │    cours     │
       │          └──────┬───────┘
       │                 │
       └────────┬────────┘
                ▼
         ┌──────────────┐
         │    notes     │
         └──────────────┘

┌─────────────────┐
│  utilisateurs   │
└────────┬────────┘
         │
         ├───────┬───────┬───────┐
         ▼       ▼       ▼       ▼
     messages ressources donnees
              medicales sanitaires
```

---

## 📊 Statistiques

- **Total tables :** 9
- **Total FK :** 8
- **Total index :** 25+
- **Champs AUTO_INCREMENT :** 9
- **Champs UNIQUE :** 7

---

## 🔒 Sécurité

1. **Mots de passe :** Hachés avec bcrypt (60 caractères)
2. **Données sanitaires :** Anonymisation automatique
3. **Relations :** Clés étrangères avec CASCADE
4. **Validation :** Contraintes au niveau BDD + application

---

**🚀 Base de données optimisée et prête pour la production !**