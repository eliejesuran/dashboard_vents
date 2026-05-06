# Dashboard Vents — Bruxelles

> Auteur : Elie JESURAN · Créé : 2026-04-22 · Mis à jour : 2026-05-06  
> Fichier source : `vents.html` · Langue UI : Français

---

## Vue d'ensemble

Application web monopage (SPA HTML/CSS/JS) de monitoring des vents à Bruxelles. Affichage en temps réel de 4 anémomètres via l'API Crodeon, historique graphique, webcams live et liens vers les outils météo IRM.

**Thèmes** : Bright (défaut) / Dark — persisté dans `localStorage`.  
**Fonts** : Barlow (UI), Share Tech Mono (valeurs/labels).  
**Dépendances externes** :
- [Chart.js](https://cdn.jsdelivr.net/npm/chart.js) + adaptateur date-fns
- [HLS.js](https://cdn.jsdelivr.net/npm/hls.js@latest) (streaming vidéo)
- Google Fonts (Barlow, Share Tech Mono)

---

## Architecture — Grille des 4 panels

```
┌─────────────────────────┬──────────┐
│  Panel 04 · Historique  │          │
│  (Chart.js, ligne)      │  Panel   │
├─────────────────────────┤  03 ·    │
│  Panel 02 · Liens       │  Anémo-  │
│  (Bulletin / INCA)      │  mètres  │
├─────────────────────────┤          │
│  Panel 01 · Live Cams   │          │
└─────────────────────────┴──────────┘
```

Sur mobile (< 900 px), la grille passe en colonne unique dans l'ordre : Panel 03 → 04 → 02 → 01.

---

## Panel 01 · Live Cam Bruxelles

Deux flux HLS diffusés via `hls.js` (fallback natif pour Safari).

| Tuile | ID stream | URL HLS |
|-------|-----------|---------|
| De Brouckère | `debrouckere` | `…/fDdnnEmqOn6Kyy3E1701416388577.m3u8` |
| Grand Place  | `grandplace`  | `…/vTm9wYDlwkAEO8mH1746783018793.m3u8` |

**Ordre d'affichage** : Grand Place en premier, De Brouckère en second.  
**Mobile** : un seul flux visible à la fois, bouton de bascule `↔️`. Stream par défaut : Grand Place.  
**Fallback** : si le flux échoue, lien direct vers le `.m3u8` affiché sur fond sombre.  
**Refresh automatique** : re-initialisation du flux toutes les 55 minutes via `setTimeout` récursif.

---

## Panel 02 · Liens météo

Deux boutons-liens externes :

| Bouton | Destination |
|--------|-------------|
| Bulletin météo | `mymeteo.be` — prévisions texte IRM |
| INCA | `mymeteo.be/incaBe` — carte météo interactive |

---

## Panel 03 · Anémomètres (état actuel)

### API Crodeon

- **Base URL** : `https://rough-block-b4fe.e-jesuran.workers.dev/api/v2/` (proxy Cloudflare Worker)
- **Authentification** : gérée côté Worker via secret `CRODEON_API_KEY` (clé absente du code client)
- **Endpoint utilisé** : `reporters/{masterId}/measurements/latest`
- **Refresh** : toutes les 10 secondes

### Stations

| Nom | Master ID | Device ID | Couleur CSS |
|-----|-----------|-----------|-------------|
| CONTINENTAL | 593494672 | 1181746382 | `--accent2` (rouge) |
| BIP          | 743441032 | 1105200446 | `--accent`  (bleu)  |
| PALAIS 5     | 673186439 | 1258292575 | `--accent3` (violet)|
| PATINOIRE    | 799015652 | 1125124441 | `--accent4` (vert)  |

### Canaux lus par station

| Channel index | Donnée | Source |
|---------------|--------|--------|
| 15 (device)   | Vitesse vent moy. (m/s → km/h) | device_id |
| 14 (device)   | Direction (degrés) | device_id |
| 1  (master)   | Pression atm. (×0.1 hPa) | master_id |

### Calcul de la rafale estimée

```
rafale = vitesse_moy × gustFactor   (arrondi 1 décimale)
```

| Station | gustFactor |
|---------|-----------|
| CONTINENTAL | 1.8 |
| BIP         | 1.7 |
| PALAIS 5    | 1.6 |
| PATINOIRE   | 1.7 |

Seuils d'alerte rafale :

| Rafale | Classe CSS | Couleur |
|--------|-----------|---------|
| ≥ 35 km/h | `gust-warning` | Ambre `#f59e0b` |
| > 50 km/h | `gust-alert`   | Rouge `--accent2` |

### ⚠️ Améliorations prévues
- **Connecter l'API des rafales réelles** dès qu'elle sera disponible (remplacer l'estimation par la valeur directe du channel dédié).

### Statut opérationnel

Endpoint supplémentaire appelé en parallèle : `reporters/{masterId}/sensors`  
Le champ `sensors[].state` (source : `crlink = CONNECTOR_1`) détermine l'état affiché.

| État API | Rendu carte |
|----------|-------------|
| `ONLINE`  | Badge vert discret, carte normale |
| `OFFLINE` / autre | Badge rouge, fond légèrement teinté rouge |

---

## Panel 04 · Historique des vitesses

### Source de données

Endpoint : `reporters/{masterId}/sensors/{deviceId}/channels/15/measurements`  
Paramètres : `start_time`, `end_time`, `page=0`, `page_size=10000`  
Refresh : toutes les 30 secondes

### Fenêtres temporelles disponibles

```
15 min · 30 min · 1 h · 2 h (défaut) · 3 h · 6 h · 9 h · 12 h
18 h · 24 h · 36 h · 48 h · 60 h · 72 h · 84 h
```

L'axe X s'adapte automatiquement (unité minute / heure, stepSize variable).

### Datasets Chart.js

| Label | Couleur bordure |
|-------|----------------|
| BIP          | `rgba(0,112,204,1)`  |
| CONTINENTAL  | `rgba(229,62,62,1)`  |
| PALAIS 5     | `rgba(124,58,237,1)` |
| PATINOIRE    | `rgba(5,150,105,1)`  |

---

## Sécurité

### ✅ Réalisé

| Risque | Solution mise en place |
|--------|----------------------|
| Clé API exposée | Proxy Cloudflare Worker — clé stockée en secret, absente du code client. Ancienne clé révoquée, nouveau token en place. |

### ⚠️ Points restants

| Risque | Description | Action recommandée |
|--------|-------------|-------------------|
| Streams HLS non authentifiés | Les URLs `.m3u8` sont en clair dans le HTML | Acceptable (streams publics) |
| Pas de CSP | Aucune Content-Security-Policy déclarée | Ajouter un header CSP restrictif |
| Dépendances CDN | Chart.js, HLS.js chargés depuis jsdelivr sans SRI | Ajouter des attributs `integrity` (Subresource Integrity) |

---

## Fichiers associés

| Fichier | Usage |
|---------|-------|
| `vents.html` | Application principale |
| `logo.png` | Logo topbar (thème bright) |
| `logo_white.png` | Logo topbar (thème dark) |
| `manifest.json` | PWA manifest |

## Infrastructure

| Service | Usage |
|---------|-------|
| Cloudflare Worker `rough-block-b4fe.e-jesuran.workers.dev` | Proxy API Crodeon — injecte la clé côté serveur, expose les endpoints sans authentification client |

---

## Backlog des améliorations

| Priorité | Tâche | Panel |
|----------|-------|-------|
| ✅ Fait | Sécuriser la clé API (Cloudflare Worker proxy) | — |
| ✅ Fait | Révoquer l'ancienne clé Crodeon + nouveau token | — |
| ✅ Fait | Inverser l'ordre Grand Place / De Brouckère | 01 |
| ✅ Fait | Refresh automatique des streams HLS (~55 min) | 01 |
| ✅ Fait | Afficher le statut opérationnel de chaque anémomètre | 03 |
| 🟢 Basse | Connecter l'API rafales réelles quand disponible | 03 |
| 🟢 Basse | Ajouter CSP + SRI sur les dépendances CDN | — |
