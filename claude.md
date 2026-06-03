# Dashboard Vents — Bruxelles

> Auteur : Elie JESURAN · Mis à jour : 2026-06-03  
> Fichier principal : `vents.html` · Langue UI : Français

---

## Vue d'ensemble

SPA HTML/CSS/JS — monitoring temps réel de 4 anémomètres (API Crodeon) + historique graphique + webcams live + liens météo IRM.

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

HLS.js + fallback natif Safari. Refresh automatique ~55 min. Mobile : 1 flux à la fois, bouton `↔️`.

---

## Panel 02 · Liens météo

Bulletin météo → `mymeteo.be` · INCA → `mymeteo.be/incaBe` (nouvel onglet).

---

## Infrastructure & sécurité

| Élément | Détail |
|---------|--------|
| Worker proxy | `rough-block-b4fe.e-jesuran.workers.dev` — injecte `CRODEON_API_KEY`, cache Cache API (10 s latest / 30 s historique) |
| Clé API | Secret Worker, absente du code client |
| HLS | Streams publics, URLs en clair — acceptable |
| CSP / SRI | Non implémentés — backlog I4 |

---

## Fichiers

| Fichier | Rôle |
|---------|------|
| `vents.html` | Application principale (tout-en-un) |
| `worker.js` | Proxy Cloudflare Worker |
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
| I4 | 🟢 Basse | CSP + SRI dépendances CDN | — |
| DOC | 🟡 | Capturer `vue-globale-annotee` et `panel-alerte` | doc |
