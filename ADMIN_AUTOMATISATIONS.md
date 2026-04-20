# 📊 Onglet Automatisations - Interface Admin

## Vue d'ensemble

L'onglet "Automatisations" a été ajouté à l'interface d'administration pour permettre de:
- 📋 Voir la liste de toutes les automatisations disponibles
- ▶️ Lancer les automatisations directement depuis l'interface
- 📸 Consulter les captures d'écran (succès/échec)
- 📝 Afficher les messages d'erreur détaillés
- ⏱️ Voir le temps d'exécution

## Architecture

### Backend (Java)

#### Classes créées:
- `AutomationInfo.java` - Modèles de données pour les automatisations
  - `AutomationInfo` - Représentation d'une automatisation
  - `AutomationStatus` - Énumération des états
  - `AutomationResult` - Résultat d'exécution
  - `AutomationExecutionResponse` - Réponse API

- `AutomationService.java` - Service principal
  - Scanne le dossier `automatisation/` pour découvrir les scripts Python
  - Exécute les scripts Python via ProcessBuilder
  - Gère les logs et captures d'écran
  - Cache les résultats des exécutions

- `AutomationRestController.java` - REST API
  - `GET /api/automations` - Liste toutes les automatisations
  - `GET /api/automations/{id}` - Détails d'une automatisation
  - `POST /api/automations/{id}/execute` - Lance une automatisation
  - `GET /api/automations/{id}/result` - Résultat de la dernière exécution

#### Modifications:
- `AdminController.java` - Injection du service et passage au modèle

### Frontend (HTML/JavaScript)

#### Nouvel onglet "Automatisations"
- Affichage en grille des cartes d'automatisation
- Chaque carte affiche:
  - Catégorie (IES, BAD, FACTURE, etc.)
  - Nom de l'automatisation
  - Description
  - Bouton "Lancer"
  - Résultat de la dernière exécution
  - Capture d'écran si disponible

#### JavaScript
- `loadAutomations()` - Charge la liste depuis l'API
- `executeAutomation()` - Lance une automatisation
- `viewScreenshot()` - Ouvre la capture d'écran
- Affichage en temps réel des résultats

## Structure des fichiers

```
automatisation/
├── ies/
│   ├── login/
│   │   ├── login_automation.py    ← Script Python
│   │   ├── config.ini
│   │   ├── results/
│   │   │   ├── login_success.png
│   │   │   ├── execution_20260420_143022.log
│   │   │   └── ...
│   │   └── ...
│   ├── bad/
│   ├── facture/
│   └── proforma/
└── ...
```

## Flux d'exécution

```
1. Utilisateur accède à /admin?tab=automatisations
   ↓
2. LoadAutomations() appelle GET /api/automations
   ↓
3. AutomationService.getAvailableAutomations()
   - Scanne automatisation/ récursivement
   - Cherche les fichiers *.py
   - Crée AutomationInfo pour chaque script
   ↓
4. Interface affiche les cartes
   ↓
5. Utilisateur clique "Lancer"
   ↓
6. executeAutomation() appelle POST /api/automations/{id}/execute
   ↓
7. AutomationService.executeAutomation()
   - Exécute: python3 path/to/script.py
   - Capture stdout/stderr
   - Cherche les .png générés
   - Enregistre les logs
   ↓
8. Retourne AutomationExecutionResponse avec résultats
   ↓
9. Interface met à jour la carte avec le résultat
```

## Configuration

### application.properties
```properties
# Chemin de base des automatisations (relatif au répertoire de travail)
app.automation.basepath=automatisation

# Timeout d'exécution en secondes
app.automation.timeout=600
```

## Format de réponse API

### GET /api/automations
```json
[
  {
    "id": "ies:login:login_automation.py",
    "name": "login",
    "description": "Automatisation de connexion au site IES",
    "category": "ies",
    "scriptPath": "automatisation/ies/login/login_automation.py",
    "status": "IDLE",
    "lastRun": "2026-04-20T14:30:22",
    "lastRunStatus": "SUCCESS",
    "lastRunError": null,
    "lastRunScreenshot": "/absolute/path/to/login_success.png"
  },
  ...
]
```

### POST /api/automations/{id}/execute
```json
{
  "id": "ies:login:login_automation.py",
  "name": "ies:login:login_automation.py",
  "status": "success",
  "message": "Exécution réussie",
  "error": null,
  "screenshotPath": "/absolute/path/to/login_success.png",
  "logPath": "/absolute/path/to/execution_20260420_143022.log",
  "executionTime": 12345
}
```

## Intégration avec les automatisations Python

Chaque script Python doit:
1. **Accepter l'exécution sans arguments** ou avec des arguments optionnels
2. **Retourner un code de sortie approprié**:
   - `0` = succès
   - `1` ou autre = erreur
3. **Générer des fichiers de résultats** (optionnel):
   - Captures: `login_success.png`, `login_failed.png`
   - Logs: sauvegardés automatiquement
4. **Placer les fichiers dans le dossier `results/`**:
   ```python
   Path("results").mkdir(exist_ok=True)
   # Ou laisser le script générer les fichiers
   # dans le même dossier et les passer en relative
   ```

### Exemple (login_automation.py)

```python
def run(self):
    # ... logique ...
    if success:
        self.take_screenshot("login_success.png")
        return 0  # Succès
    else:
        self.take_screenshot("login_failed.png")
        return 1  # Échec
```

## Stylisation

L'onglet utilise une grille responsive avec cartes de couleur basées sur:
- **Inactif** (gris): Jamais exécuté
- **En cours** (jaune): Exécution en cours
- **Succès** (vert): Dernière exécution réussie
- **Échec** (rouge): Dernière exécution échouée

## Points importants

⚠️ **Sécurité**
- Les scripts sont exécutés avec les droits de l'utilisateur Java
- Assurez-vous que les chemins sont sécurisés
- Validez les entrées utilisateur

⚠️ **Performance**
- Les exécutions longues bloquent le thread
- Utilisez un timeout pour éviter les blocages infinis
- Envisagez un système asynchrone pour les longues tâches

⚠️ **Fichiers générés**
- Les captures d'écran occupent de l'espace disque
- Mettez en place une politique de nettoyage
- Les logs sont conservés indéfiniment

## Extension future

- [ ] Support de l'exécution asynchrone (background job)
- [ ] Planification d'exécutions (cron)
- [ ] Historique des exécutions
- [ ] Paramètres configurables par utilisateur
- [ ] Notifications par email/Slack
- [ ] Graphiques de performance
- [ ] Support de formats de résultats (JSON, XML)

## Dépannage

### Les automatisations ne s'affichent pas
1. Vérifier que le dossier `automatisation/` existe
2. Vérifier les logs de l'application

### Exécution échoue sans message
1. Vérifier que le script Python existe
2. Vérifier les permissions du fichier
3. Vérifier que Python est installé

### Captures d'écran non trouvées
1. Le script doit générer des fichiers `.png`
2. Placer dans le dossier `results/` du script

## Exemples d'utilisation

Voir [automatisation/ies/login/](../../automatisation/ies/login/) pour un exemple complet.
