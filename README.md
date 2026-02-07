# Decode Headless Connector

Plugin WordPress permettant de communiquer avec un CMS headless via API REST.

## Fonctionnalites

- **Connexion securisee a l'API** : formulaire avec login, mot de passe et secret key (optionnel)
- **Requetes asynchrones** : connexion en AJAX avec affichage des messages d'erreur/succes
- **Token de securite** : stocke dans WordPress et affiche en lecture seule (masque)
- **Deconnexion** : bouton pour se deconnecter et supprimer le token
- **3 shortcodes** avec parametres pour afficher le contenu du CMS
- **Consultation du contenu** : tableau listant les contenus recus depuis l'API
- **Edition du contenu** (bonus) : modification des contenus directement depuis WordPress
- **Cache API** (bonus) : mise en cache des reponses avec duree configurable et vidage manuel

## Installation

1. Copier le dossier `decode-headless-connector` dans `wp-content/plugins/`
2. Activer le plugin dans l'administration WordPress
3. Aller dans le menu **Headless Connector** pour configurer la connexion

## Configuration

1. Renseigner l'URL de base de l'API (ex: `http://host.docker.internal:9080`)
2. Entrer le login et le mot de passe
3. Optionnel : renseigner la secret key
4. Cliquer sur **Se connecter**

## Endpoints API attendus

Le plugin communique avec les endpoints suivants :

| Methode | Endpoint            | Description              |
| ------- | ------------------- | ------------------------ |
| POST    | `/api/login`        | Authentification         |
| GET     | `/api/content`      | Liste des contenus       |
| GET     | `/api/content/{id}` | Detail d'un contenu      |
| PUT     | `/api/content/{id}` | Mise a jour d'un contenu |

## Shortcodes

### Liste de contenus

```
[dhc_content_list limit="5"]
```

Parametres :

- `limit` : nombre de contenus a afficher (defaut : 5)

### Contenu unique

```
[dhc_content id="123"]
```

Parametres :

- `id` : identifiant du contenu (affiche titre + contenu)

### Champ specifique

```
[dhc_content_field id="123" field="title"]
```

Parametres :

- `id` : identifiant du contenu
- `field` : nom du champ a afficher (title, content, excerpt, etc.)

## Structure du code

```
decode-headless-connector/
├── decode-headless-connector.php   # Point d'entree du plugin
├── README.md
├── assets/
│   ├── admin.css                   # Styles de la page admin
│   └── admin.js                    # Scripts JS (AJAX)
└── includes/
    ├── dhc-api.php                 # Client API + cache (transients)
    ├── dhc-admin.php               # Page d'administration et handlers AJAX
    └── dhc-shortcodes.php          # Shortcodes
```

## Standards

- Code organise en programmation orientee objet (POO) : 3 classes (DHC_Api, DHC_Admin, DHC_Shortcodes)
- Respect des standards WordPress (nonces, capabilities, sanitization, escaping)
- Securite : verification des droits, echappement des sorties, validation des entrees
