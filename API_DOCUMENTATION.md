markdown# 📚 API Documentation - MedCampus Bangui

Documentation complète de l'API REST du système MedCampus Bangui.

---

## 📋 Table des matières

- [Base URL](#base-url)
- [Authentification](#authentification)
- [Codes de réponse](#codes-de-réponse)
- [Modules](#modules)
  - [1. Authentification](#1-authentification)
  - [2. Étudiants](#2-étudiants)
  - [3. Enseignants](#3-enseignants)
  - [4. Cours](#4-cours)
  - [5. Notes](#5-notes)
  - [6. Ressources Médicales](#6-ressources-médicales)
  - [7. Données Sanitaires](#7-données-sanitaires)
  - [8. Messages](#8-messages)

---

## Base URL
```
http://127.0.0.1:8000/api
```

**Production :** Remplacer par votre URL de production.

---

## Authentification

L'API utilise **JWT (JSON Web Tokens)** pour l'authentification.

### Obtenir un token

Après connexion via `/login`, vous recevez un `access_token` à inclure dans toutes les requêtes protégées.

### Format du header
```http
Authorization: Bearer {votre_access_token}
```

### Expiration

- **Token d'accès** : 60 minutes
- **Token de rafraîchissement** : 2 semaines

---

## Codes de réponse

| Code | Signification | Description |
|------|---------------|-------------|
| **200** | OK | Requête réussie |
| **201** | Created | Ressource créée avec succès |
| **204** | No Content | Suppression réussie |
| **400** | Bad Request | Requête mal formée |
| **401** | Unauthorized | Token manquant ou invalide |
| **403** | Forbidden | Accès refusé (permissions) |
| **404** | Not Found | Ressource introuvable |
| **422** | Unprocessable Entity | Erreur de validation |
| **500** | Internal Server Error | Erreur serveur |

---

## Modules

---

## 1. Authentification

### 1.1 Inscription

**Endpoint :** `POST /register`

**Accès :** Public

**Body (JSON) :**
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean.dupont@medcampus.cf",
  "mot_de_passe": "secret123",
  "role": "etudiant",
  "filiere": "Médecine",
  "date_naissance": "2000-01-15"
}
```

**Champs obligatoires :**
- `nom`, `prenom`, `email`, `mot_de_passe`, `role`

**Champs spécifiques :**
- **Étudiant :** `filiere`, `date_naissance`
- **Enseignant :** `specialite`, `date_naissance`

**Réponse (201) :**
```json
{
  "success": true,
  "message": "Inscription réussie",
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "utilisateur": {
    "id_utilisateur": 1,
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean.dupont@medcampus.cf",
    "role": "etudiant"
  }
}
```

---

### 1.2 Connexion

**Endpoint :** `POST /login`

**Accès :** Public

**Body (JSON) :**
```json
{
  "email": "jean.dupont@medcampus.cf",
  "mot_de_passe": "secret123"
}
```

**Réponse (200) :**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

### 1.3 Déconnexion

**Endpoint :** `POST /logout`

**Accès :** Authentifié

**Headers :**
```http
Authorization: Bearer {token}
```

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

---

### 1.4 Informations utilisateur

**Endpoint :** `GET /me`

**Accès :** Authentifié

**Headers :**
```http
Authorization: Bearer {token}
```

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "id_utilisateur": 1,
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean.dupont@medcampus.cf",
    "role": "etudiant"
  }
}
```

---

## 2. Étudiants

### 2.1 Liste des étudiants

**Endpoint :** `GET /etudiants`

**Accès :** Admin uniquement

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Liste des étudiants récupérée avec succès",
  "data": [
    {
      "id_etudiant": 1,
      "nom": "Martin",
      "prenom": "Sophie",
      "email": "sophie.martin@medcampus.cf",
      "matricule": "ETU19700520",
      "filiere": "Médecine",
      "statut": "actif"
    }
  ],
  "current_page": 1,
  "total": 50
}
```

---

### 2.2 Créer un étudiant

**Endpoint :** `POST /etudiants`

**Accès :** Admin uniquement

**Body (JSON) :**
```json
{
  "nom": "Martin",
  "prenom": "Sophie",
  "email": "sophie.martin@medcampus.cf",
  "date_naissance": "2000-03-20",
  "filiere": "Médecine",
  "statut": "actif"
}
```

**Réponse (201) :**
```json
{
  "success": true,
  "message": "Étudiant créé avec succès",
  "data": {
    "id_etudiant": 1,
    "matricule": "ETU19700520",
    "nom": "Martin",
    "prenom": "Sophie"
  }
}
```

---

### 2.3 Notes d'un étudiant

**Endpoint :** `GET /etudiants/{id}/notes`

**Accès :** Admin uniquement

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Notes de l'étudiant récupérées avec succès",
  "data": {
    "etudiant": {
      "id": 1,
      "nom": "Martin",
      "prenom": "Sophie",
      "matricule": "ETU19700520"
    },
    "notes": [
      {
        "id_note": 1,
        "valeur": 15.5,
        "cours": {
          "code": "MED101",
          "titre": "Anatomie générale"
        }
      }
    ]
  }
}
```

---

## 3. Enseignants

### 3.1 Liste des enseignants

**Endpoint :** `GET /enseignants`

**Accès :** Admin uniquement

---

### 3.2 Cours d'un enseignant

**Endpoint :** `GET /enseignants/{id}/cours`

**Accès :** Admin uniquement

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Cours de l'enseignant récupérés avec succès",
  "data": {
    "enseignant": {
      "id": 1,
      "nom": "Dupont",
      "prenom": "Jean-Pierre",
      "matricule": "ENS19750815",
      "specialite": "Cardiologie"
    },
    "cours": [
      {
        "id_cours": 1,
        "code": "MED101",
        "titre": "Anatomie générale"
      }
    ]
  }
}
```

---

## 4. Cours

### 4.1 Liste des cours

**Endpoint :** `GET /cours`

**Accès :** Admin, Enseignant

---

### 4.2 Créer un cours

**Endpoint :** `POST /cours`

**Accès :** Admin, Enseignant

**Body (JSON) :**
```json
{
  "code": "MED101",
  "titre": "Anatomie générale",
  "description": "Introduction à l'anatomie",
  "id_enseignant": 1
}
```

---

### 4.3 Notes d'un cours

**Endpoint :** `GET /cours/{id}/notes`

**Accès :** Admin, Enseignant

---

## 5. Notes

### 5.1 Créer une note

**Endpoint :** `POST /notes`

**Accès :** Enseignant uniquement

**Body (JSON) :**
```json
{
  "id_etudiant": 1,
  "id_cours": 1,
  "valeur": 15.5,
  "date_evaluation": "2026-02-10"
}
```

**Validation :**
- `valeur` : 0 à 20

---

## 6. Ressources Médicales

### 6.1 Liste des ressources

**Endpoint :** `GET /ressources`

**Accès :** Tous les utilisateurs authentifiés

**Query Parameters :**
- `type` : cours, livre, video, article, autre
- `categorie` : Anatomie, Physiologie, etc.
- `niveau` : L1, L2, L3, M1, M2, doctorat
- `recherche` : Texte libre

**Exemple :**
```
GET /ressources?type=cours&categorie=Anatomie&recherche=système nerveux
```

---

### 6.2 Créer une ressource

**Endpoint :** `POST /ressources`

**Accès :** Admin, Enseignant

**Content-Type :** `multipart/form-data`

**Body (form-data) :**
```
titre: Anatomie du système nerveux
description: Cours complet sur l'anatomie du système nerveux
auteur: Dr. Martin
type: cours
categorie: Anatomie
niveau: L2
est_public: true
fichier: [Fichier PDF/Vidéo]
```

---

### 6.3 Télécharger une ressource

**Endpoint :** `GET /ressources/{id}/telecharger`

**Accès :** Tous les utilisateurs authentifiés

**Réponse :** Téléchargement direct du fichier

---

## 7. Données Sanitaires

### 7.1 Liste des données

**Endpoint :** `GET /donnees-sanitaires`

**Accès :** Tous les utilisateurs authentifiés

**Query Parameters :**
- `pathologie` : Nom de la pathologie
- `ville` : Bangui
- `commune` : Nom de la commune
- `gravite` : leger, modere, grave, critique
- `tranche_age` : 0-5, 6-12, 13-18, 19-35, 36-60, 60+
- `sexe` : M, F, Autre
- `date_debut` : Date de début (format: YYYY-MM-DD)
- `date_fin` : Date de fin

---

### 7.2 Créer une donnée sanitaire

**Endpoint :** `POST /donnees-sanitaires`

**Accès :** Tous les utilisateurs authentifiés

**Body (JSON) :**
```json
{
  "sexe": "M",
  "age": 35,
  "quartier": "PK5",
  "commune": "3ème Arrondissement",
  "ville": "Bangui",
  "pathologie": "Paludisme",
  "symptomes": "Fièvre, frissons, maux de tête",
  "gravite": "modere",
  "date_consultation": "2026-02-10",
  "diagnostic": "Paludisme à Plasmodium falciparum",
  "traitement_prescrit": "Artemether + Lumefantrine"
}
```

**Note :** Le `code_patient` est généré automatiquement.

---

### 7.3 Statistiques

**Endpoint :** `GET /donnees-sanitaires/statistiques`

**Accès :** Tous les utilisateurs authentifiés

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Statistiques récupérées avec succès",
  "data": {
    "total_cas": 150,
    "cas_en_cours": 45,
    "cas_gueris": 100,
    "cas_graves": 5,
    "par_gravite": [
      { "gravite": "leger", "total": 50 },
      { "gravite": "modere", "total": 80 },
      { "gravite": "grave", "total": 15 },
      { "gravite": "critique", "total": 5 }
    ],
    "top_pathologies": [
      { "pathologie": "Paludisme", "total": 60 },
      { "pathologie": "Diarrhée", "total": 30 }
    ]
  }
}
```

---

## 8. Messages

### 8.1 Boîte de réception

**Endpoint :** `GET /messages/boite-reception`

**Accès :** Tous les utilisateurs authentifiés

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Boîte de réception récupérée avec succès",
  "data": [
    {
      "id_message": 1,
      "sujet": "Question sur le cours",
      "contenu": "Bonjour...",
      "est_lu": false,
      "created_at": "2026-02-10T10:30:00.000000Z",
      "expediteur": {
        "nom": "Martin",
        "prenom": "Sophie"
      }
    }
  ],
  "non_lus": 3
}
```

---

### 8.2 Envoyer un message

**Endpoint :** `POST /messages`

**Accès :** Tous les utilisateurs authentifiés

**Body (JSON) :**
```json
{
  "destinataire_id": 5,
  "sujet": "Question sur le cours",
  "contenu": "Bonjour Professeur, j'ai une question..."
}
```

---

### 8.3 Conversation

**Endpoint :** `GET /messages/conversation/{utilisateurId}`

**Accès :** Tous les utilisateurs authentifiés

**Description :** Affiche tous les messages échangés avec un utilisateur spécifique.

**Note :** Les messages non lus sont automatiquement marqués comme lus.

---

### 8.4 Compteur de messages non lus

**Endpoint :** `GET /messages/non-lus`

**Accès :** Tous les utilisateurs authentifiés

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Nombre de messages non lus récupéré",
  "data": {
    "non_lus": 3
  }
}
```

---

## Gestion des erreurs

### Format des erreurs
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "email": [
      "Le champ email est obligatoire."
    ]
  }
}
```

---

## Notes importantes

1. **Tous les endpoints protégés nécessitent un token JWT valide**
2. **Les données sanitaires sont automatiquement anonymisées**
3. **Les matricules sont générés automatiquement (ETU/ENS + date)**
4. **Les fichiers uploadés sont limités à 100 Mo**
5. **La pagination est activée sur toutes les listes (20 éléments/page)**

---

**🚀 API complète et prête à l'emploi !**