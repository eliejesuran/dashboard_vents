# Dashboard Vents — Bruxelles

> Auteur : Elie JESURAN · Mis à jour : 2026-07-08  
> Fichier principal : `index.html` · Langue UI : Français

---

## Vue d'ensemble

SPA HTML/CSS/JS — monitoring temps réel de 4 anémomètres (API Crodeon) + historique graphique + webcams live + liens météo IRM.

⚠️ Le comptage de foule (YOLO/ONNX) a été **retiré le 2026-07-08** — repris par le projet dédié `../bme_GP` (voir son `CLAUDE.md`). Détail des calibrations : historique git de ce repo (≤ commit `43cb028`).

**Thèmes** : Bright (défaut) / Dark — `localStorage`.  
**Fonts** : Barlow (UI), Share Tech Mono (valeurs/labels).  
**CDN** : Chart.js + chartjs-adapter-date-fns · HLS.js · Google Fonts.

---

## Grille

```
┌─────────────────────────┬──────────┐
│  Panel 04 · Historique  │  Panel   │
├─────────────────────────┤  03 ·    │
│  Panel 02 · Liens IRM   │  Anémo-  │
├─────────────────────────┤  mètres  │
│  Panel 01 · Live Cams   │          │
└─────────────────────────┴──────────┘
```

Mobile < 900 px : colonne unique, ordre 03 → 04 → 02 → 01.  
Mobile paysage (`@media (max-width:900px) and (orientation:landscape)`) : affiche `.anem-max30` dans chaque tuile.

Mobile — veille & zoom : verrou d'écran (`navigator.wakeLock`, IIFE `keepScreenAwake`, repris à chaque retour au premier plan et au premier contact) empêche la mise en veille pendant le monitoring ; `touch-action: manipulation` sur `body` supprime le zoom au double-tap (pinch-zoom et scroll conservés). Wake Lock : Chrome/Android OK, iOS Safari ≥ 16.4 uniquement (no-op silencieux en dessous).

---

## Variables CSS

| Variable | Bright | Dark | Rôle |
|----------|--------|------|------|
| `--accent`  | `#0070cc` | `#00c6ff` | BIP (bleu) |
| `--accent2` | `#d97706` | `#fbbf24` | CONTINENTAL (ambre) |
| `--accent3` | `#7c3aed` | `#a78bfa` | PALAIS 5 (violet) |
| `--accent4` | `#0891b2` | `#22d3ee` | PATINOIRE (teal) |
| `--danger`  | `#e53e3e` | `#ff6b6b` | Alertes, offline, erreurs |
| `--ok`      | `#059669` | `#34d399` | Badge ONLINE |

`--danger` et `--ok` sont distincts des couleurs station pour éviter toute connotation sémantique parasite sur CONTINENTAL/PATINOIRE.

---

## Panel 03 · Anémomètres

**Proxy** : `https://rough-block-b4fe.e-jesuran.workers.dev/api/v2/`  
**Refresh** : 6 s

### Stations

| Nom | masterId | deviceId | cssVar |
|-----|----------|----------|--------|
| CONTINENTAL | 593494672 | 1181746382 | `--accent2` |
| BIP         | 743441032 | 1105200446 | `--accent`  |
| PALAIS 5    | 673186439 | 1258292575 | `--accent3` |
| PATINOIRE   | 799015652 | 1125124441 | `--accent4` |

### Canaux

| Channel | Donnée | Source |
|---------|--------|--------|
| 12 (device) | Rafale max — `WIND_PEAK_GUST` (m/s → km/h) | deviceId |
| 15 (device) | Vitesse moy. (m/s → km/h) | deviceId |
| 14 (device) | Direction (degrés) | deviceId |
| 1  (master) | Pression atm. (×0.1 hPa) | masterId |

### Seuils rafale

| Rafale | Classe | Couleur |
|--------|--------|---------|
| ≥ 35 km/h | `gust-warning` | `#f59e0b` |
| > 50 km/h | `gust-alert`   | `var(--danger)` |

### Max 30' mobile paysage

Élément `.anem-max30` dans chaque tuile (caché hors paysage). Calculé depuis `window._p4Cache` (historyCache du Panel 04 exposé en global), canal 12, fenêtre glissante 30 min. Couleur neutre : `var(--text)`.

### Statut opérationnel

Endpoint : `reporters/{masterId}/sensors` — champ `sensors[].state` où `crlink = CONNECTOR_1`.  
`ONLINE` → badge `--ok`. Autre → badge `--danger`, fond teinté `--danger`.

---

## Panel 04 · Historique

**Endpoint** : `reporters/{masterId}/sensors/{deviceId}/channels/{12|15}/measurements`  
**Refresh** : 30 s · **Défaut** : 2 h · fenêtres : 15 min → 84 h

**Toggle** : channel 12 (VIT. MAX.) ↔ 15 (VIT. MOY.) via bouton `#channelToggle`.

**Cache append-only** : `historyCache[${sensorIdx}_${channelIndex}]` = `{ points:[{ts,y}], earliestMs }`. Exposé via `window._p4Cache` et `window._p4Sensors` pour Panel 03.

### Datasets Chart.js

| Station | Couleur | `borderDash` |
|---------|---------|-------------|
| BIP         | `rgba(0,112,204,1)`  | `[]`    |
| CONTINENTAL | `rgba(217,119,6,1)`  | `[2,1]` |
| PALAIS 5    | `rgba(124,58,237,1)` | `[1,1]` |
| PATINOIRE   | `rgba(8,145,178,1)`  | `[4,1]` |

`tension: 0.1` · Ligne seuil 35 km/h : tirets ambre via plugin `thresholdPlugin`.

**Stats panel** (droite du graphique) : MOY + MAX par station, police 12 px / label 10 px.

---

## Panel 01 · Webcams

| Caméra | Stream |
|--------|--------|
| Grand Place  | `vTm9wYDlwkAEO8mH1746783018793.m3u8` |
| De Brouckère | `fDdnnEmqOn6Kyy3E1701416388577.m3u8` |

Flux proxifiés via le Worker (`/hls/*`, voir Infrastructure) — proxy conservé après le retrait du comptage, la lecture HLS passe par lui. HLS.js + fallback natif Safari. Refresh automatique 90 min. Mobile : 1 flux à la fois, bouton `↔️`.

### Comptage de foule — retiré (2026-07-08)

Repris et étendu par le projet dédié **`../bme_GP`** (Worker `bme-gp`, YOLO26s, historique centralisé KV) — voir son `CLAUDE.md`. L'implémentation d'origine (YOLO26n, SAHI 4×4, calibrations contre comptage manuel, RGPD, états badge) reste consultable dans l'historique git de ce repo (≤ commit `43cb028`).

---

## Panel 02 · Liens météo

Bulletin météo → `mymeteo.be` · INCA → `mymeteo.be/incaBe` (nouvel onglet).

---

## Infrastructure & sécurité

| Élément | Détail |
|---------|--------|
| Worker proxy | `rough-block-b4fe.e-jesuran.workers.dev` — injecte `CRODEON_API_KEY`, cache Cache API (10 s latest / 30 s historique) |
| Worker proxy HLS | Même Worker, route `/hls/*` → `livecam.brucity.be` (générique, sert les deux caméras, `worker.js` fonction `proxyHls`). Réécrit le manifeste `.m3u8`, ajoute CORS, supporte `Range`/`206`, cache les segments (`immutable`) sauf requêtes partielles. Sert les flux du Panel 01 |
| Clé API | Secret Worker, absente du code client |
| `worker.js` | **Non suivi par git** (`.gitignore`) — tout changement nécessite un `wrangler deploy` manuel séparé, non visible dans l'historique du repo |
| HLS | Streams publics, URLs en clair — acceptable |
| CSP / SRI | Non implémentés — backlog I4 |

---

## Fichiers

| Fichier | Rôle |
|---------|------|
| `index.html` | Application principale (tout-en-un) |
| `worker.js` | Proxy Cloudflare Worker (API Crodeon + HLS webcams) |
| `documentation/index.html` | Guide utilisateur |
| `documentation/screenshots/` | Captures (7 fichiers JPEG) — `vue-globale-annotee` et `panel-alerte` manquants |
| `logo.png` / `logo_white.png` | Logo topbar bright / dark |
| `manifest.json` | PWA manifest |

---

## Backlog

| # | État | Tâche | Panel |
|---|------|-------|-------|
| I1–I3 | ✅ | Sécurité clé API, cache Worker | — |
| U1–U9 | ✅ | Ordre cams, refresh HLS, statut, tensions, rafales réelles, toggle canal, m/s alert, cache historique | 01–04 |
| U10 | ✅ | Couleurs stations sans connotation rouge/vert (--danger/--ok séparés) | 03 |
| U11 | ✅ | Max 30' mobile paysage depuis historyCache Panel 04 | 03 |
| U12 | ✅ | Stats panel : suppression MIN, police agrandie | 04 |
| C1–C7 | ➡️ | Comptage de foule — retiré le 2026-07-08, repris par `../bme_GP` (détail : historique git ≤ `43cb028`) | 01 |
| M1 | ✅ | Mobile — veille écran (`navigator.wakeLock`) + suppression zoom double-tap (`touch-action: manipulation`) | — |
| I4 | 🟢 Basse | CSP + SRI dépendances CDN | — |
| DOC | 🟡 | Capturer `vue-globale-annotee` et `panel-alerte` | doc |
