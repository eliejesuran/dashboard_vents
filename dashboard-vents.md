# Dashboard Vents — Bruxelles

> Auteur : Elie JESURAN · Créé : 2026-04-22 · Mis à jour : 2026-05-11  
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
| Grand Place  | `grandplace`  | `…/vTm9wYDlwkAEO8mH1746783018793.m3u8` |
| De Brouckère | `debrouckere` | `…/fDdnnEmqOn6Kyy3E1701416388577.m3u8` |

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
| 12 (device)   | Rafale max (m/s → km/h) — `WIND_PEAK_GUST` | device_id |
| 15 (device)   | Vitesse moy. (m/s → km/h) — affiché en meta | device_id |
| 14 (device)   | Direction (degrés) | device_id |
| 1  (master)   | Pression atm. (×0.1 hPa) | master_id |

### Affichage carte

- **`anem-speed`** : rafale (channel 12, m/s → km/h)
- **Meta "Moy."** : vitesse moyenne (channel 15)
- Les seuils d'alerte s'appliquent à la rafale réelle

Seuils d'alerte rafale :

| Rafale | Classe CSS | Couleur |
|--------|-----------|---------|
| ≥ 35 km/h | `gust-warning` | Ambre `#f59e0b` |
| > 50 km/h | `gust-alert`   | Rouge `--accent2` |

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

Endpoint : `reporters/{masterId}/sensors/{deviceId}/channels/12/measurements`  
Paramètres : `start_time`, `end_time`, `page=0`, `page_size=10000`  
Refresh : toutes les 30 secondes

### Fenêtres temporelles disponibles

```
15 min · 30 min · 1 h · 2 h (défaut) · 3 h · 6 h · 9 h · 12 h
18 h · 24 h · 36 h · 48 h · 60 h · 72 h · 84 h
```

L'axe X s'adapte automatiquement (unité minute / heure, stepSize variable).

### Datasets Chart.js

| Label | Couleur bordure | `borderDash` | `tension` |
|-------|----------------|-------------|-----------|
| BIP          | `rgba(0,112,204,1)`  | `[]` (pleine) | `0.1` |
| CONTINENTAL  | `rgba(229,62,62,1)`  | `[2,1]`       | `0.1` |
| PALAIS 5     | `rgba(124,58,237,1)` | `[1,1]`       | `0.1` |
| PATINOIRE    | `rgba(5,150,105,1)`  | `[3,1]`       | `0.1` |

### ⚠️ Améliorations prévues — layout Panel 04

**Option A — Chips de sélection temporelle**  
Remplacer les boutons `◂ / ▸` par une rangée de pills cliquables listant toutes les fenêtres disponibles. La fenêtre active est mise en évidence.

**Option B — Graphique enrichi**  
- Ligne de référence horizontale en pointillés ambrés au seuil 35 km/h
- Tooltip enrichi : 4 valeurs + mini-classement instantané (station la plus ventée en tête)

**Option C — Split layout**  
Diviser le panel : graphique à gauche (75%) + mini-tableau récapitulatif à droite (25%) affichant pour chaque station la vitesse min / moy / max sur la fenêtre sélectionnée.

---

## Performance — Optimisation des appels Worker

La limite Cloudflare Workers gratuit est de **100 000 requêtes/jour** (navigateur → Worker).

### Cache côté Worker (Cloudflare Cache API)

Le Worker met en cache la réponse Crodeon dans la **Cache API Cloudflare** (gratuite, sans KV). Son intérêt principal est de **mutualiser les appels entre utilisateurs simultanés** : sans cache, 10 utilisateurs = ×10 les appels vers Crodeon ; avec cache, ça reste 1 appel/TTL quelle que soit la charge.

> Un cache côté navigateur (anti re-render) n'a pas été retenu : les valeurs d'anémomètre changent à chaque poll de 10s, le taux de cache hit serait négligeable et la complexité ajoutée injustifiée.

#### TTL appliqués

| Endpoint | TTL cache Worker |
|----------|-----------------|
| `measurements/latest` (Panel 03) | 10 secondes |
| `measurements` historique (Panel 04) | 30 secondes |

#### Gain estimé (appels Worker → Crodeon, par utilisateur)

| Source | Sans cache | Avec cache Worker |
|--------|-----------|-------------------|
| Panel 03 — 4 stations × 6/min | 34 560 /24h | **8 640 /24h** |
| Panel 04 — 4 stations × 2/min | 11 520 /24h | **≤ 11 520 /24h** |

#### Implémentation (`worker.js`)

```js
export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    url.hostname = 'api.crodeon.com';

    const cacheKey = url.toString();
    const cache = caches.default;

    const cached = await cache.match(cacheKey);
    if (cached) return cached;

    const response = await fetch(url, {
      headers: {
        ...Object.fromEntries(request.headers),
        'X-API-KEY': env.CRODEON_API_KEY
      }
    });

    if (!response.ok) {
      const headers = new Headers(response.headers);
      headers.set('Access-Control-Allow-Origin', '*');
      return new Response(response.body, { status: response.status, headers });
    }

    const ttl = url.pathname.includes('latest') ? 10 : 30;
    const headers = new Headers(response.headers);
    headers.set('Access-Control-Allow-Origin', '*');
    headers.set('Cache-Control', `public, max-age=${ttl}`);

    const cachedResponse = new Response(response.body, { status: response.status, headers });
    cache.put(cacheKey, cachedResponse.clone());
    return cachedResponse;
  }
};
```

**Statut** : déployé — validation en cours (résultats attendus le 2026-05-12).

---

## Sécurité

### ✅ Réalisé

| Risque | Solution mise en place |
|--------|----------------------|
| Clé API exposée | Proxy Cloudflare Worker — clé stockée en secret, absente du code client. Ancienne clé révoquée, nouveau token en place. |

### ⚠️ Points restants

| Risque | Description | Action recommandée |
|--------|-------------|-------------------|
| Streams HLS non authentifiés | URLs `.m3u8` en clair dans le HTML | Acceptable (streams publics) |
| Pas de CSP | Aucune Content-Security-Policy déclarée | Ajouter header CSP via Cloudflare Pages |
| Dépendances CDN sans SRI | Chart.js, HLS.js depuis jsdelivr | Ajouter attributs `integrity` (Subresource Integrity) |

---

## Infrastructure

| Service | Usage |
|---------|-------|
| Cloudflare Worker `rough-block-b4fe.e-jesuran.workers.dev` | Proxy API Crodeon — injecte la clé côté serveur, cache les réponses (TTL 10 s / 30 s), expose les endpoints sans authentification client |

## Fichiers associés

| Fichier | Usage |
|---------|-------|
| `vents.html` | Application principale |
| `worker.js` | Proxy Cloudflare Worker avec cache |
| `logo.png` | Logo topbar (thème bright) |
| `logo_white.png` | Logo topbar (thème dark) |
| `manifest.json` | PWA manifest |

---

## Backlog des améliorations

| Numéro | Priorité | Tâche | Panel | Comm |
|--------|----------|-------|-------|------|
| I1 | ✅ Fait | Sécuriser la clé API (Cloudflare Worker proxy) | — |------|
| I2 | ✅ Fait | Révoquer l'ancienne clé Crodeon + nouveau token | — |------|
| U1 | ✅ Fait | Inverser l'ordre Grand Place / De Brouckère | 01 |------|
| U2 | ✅ Fait | Refresh automatique des streams HLS (~55 min) | 01 |------|
| U3 | ✅ Fait | Afficher le statut opérationnel de chaque anémomètre | 03 |------|
| U4 | ✅ Fait | Courbes moins arrondies (`tension: 0.1`) + `borderDash` par station | 04 |------|
| I3 | ✅ Fait | Cache Worker (Cache API Cloudflare, TTL 10 s / 30 s) | — |------|
| U5 | 🟡 Moyenne | Refonte layout Panel 04 (chips, ligne seuil 35 km/h, split stats) | 04 |------|
| U6 | ✅ Fait | Connecter l'API rafales réelles (channel 12) — estimation supprimée | 03/04 | anem-speed = rafale réelle, Moy. en meta, graphique sur channel 12 |
| U7 | ✅ Fait | Bascule TOP / AVERAGE dans le graphique (channel 12 ↔ 15) | 04 | Bouton toggle dans les contrôles du panel |
| I4 | 🟢 Basse | Ajouter CSP + SRI sur les dépendances CDN | — | |
