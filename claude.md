# Dashboard Vents — Bruxelles

> Auteur : Elie JESURAN · Mis à jour : 2026-07-03  
> Fichier principal : `index.html` · Langue UI : Français

---

## Vue d'ensemble

SPA HTML/CSS/JS — monitoring temps réel de 4 anémomètres (API Crodeon) + historique graphique + webcams live + comptage de foule (Grand Place) + liens météo IRM.

**Thèmes** : Bright (défaut) / Dark — `localStorage`.  
**Fonts** : Barlow (UI), Share Tech Mono (valeurs/labels).  
**CDN** : Chart.js + chartjs-adapter-date-fns · HLS.js · onnxruntime-web · Google Fonts.

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
| Grand Place  | `vTm9wYDlwkAEO8mH1746783018793.m3u8` (proxifié via Worker, voir Comptage de foule) |
| De Brouckère | `fDdnnEmqOn6Kyy3E1701416388577.m3u8` (proxifié également depuis l'ajout du comptage sur cette caméra) |

HLS.js + fallback natif Safari. Refresh automatique 90 min. Mobile : 1 flux à la fois, bouton `↔️`.

### Comptage de foule (Grand Place + De Brouckère)

Détection de personnes en temps réel, **100 % côté navigateur** (aucun ML serveur), chiffre affiché à côté de chaque flux vidéo. Motivation : fermeture de la place en cas d'affluence excessive lors d'événements. Une seule session ONNX (un seul jeu de poids en mémoire) partagée entre les deux caméras — l'inférence est de toute façon séquentielle, pas de gain à en charger deux.

**Modèle** : YOLO26n (Ultralytics, nano, COCO 80 classes) → `models/yolo26n.onnx`, entrée **1280×1280** par tuile. Tête NMS-free (« one-to-one ») : sortie `(1,300,6)` = `[x1,y1,x2,y2,confiance,classe]` déjà dédupliquée par tuile — un NMS/dédoublonnage maison (IoU > 0.4) est refait côté JS entre tuiles, lui, pour fusionner les détections aux chevauchements. Classe personne = `0`.

**Découpage en tuiles (SAHI)** : une seule passe plein-cadre à 1280px sous-détectait massivement sur ce plan large/aérien — vérifié en direct contre un comptage manuel : ~26 détectés pour ~260 personnes réelles sur Grand Place un jour de forte affluence. Grille **4×4 (16 tuiles, chevauchement 15 %)**, chaque tuile repassée par le modèle à pleine résolution 1280px : fait remonter le rappel à ~85-90 % (vérifié : 213-232 détectés pour ~240-266 réels selon les échantillons). Le seuil de confiance a été baissé en même temps (voir ci-dessous) — les deux leviers ensemble expliquent l'essentiel du gain, la grille seule ou le seuil seul ne suffisaient pas.

**Runtime** : `onnxruntime-web` (CDN jsDelivr, version épinglée) — `executionProviders:['webgpu']` puis repli `['wasm']`. WebGPU, quand disponible, s'est révélé nettement plus rapide que WASM en test (cycles complets en quelques secondes contre ~13-15 s/caméra en WASM) — les temps ci-dessous sont le pire cas (repli WASM), à considérer comme un plafond, pas la norme.

Le script ORT (~1,5 Mo) **et** le modèle (~10 Mo) sont **chargés paresseusement** (`loadOrtScript` par injection de balise, puis `ensureModelLoaded`) : rien n'est téléchargé tant que le panel cams n'est pas entré dans le viewport — la visite « vent seul » n'embarque ni runtime ni modèle. Sur le **seul repli WASM**, `ort.env.wasm.proxy = true` déporte `session.run` dans un worker (sinon il s'exécute sur le thread UI et fige le tableau de bord par à-coups) ; le chemin WebGPU, qui calcule déjà sur le GPU, reste sans proxy. Le tenseur d'entrée (Float32Array ~20 Mo/tuile) est réutilisé d'une tuile à l'autre — un garde-fou `length` détecte le cas où le mode proxy « transfère » (détache) le buffer et réalloue alors sans planter.

**Seuil de confiance** : `0.15` (baissé de 0.25 après calibration contre un comptage manuel réel — sur une scène aussi dense, la confiance par détection est structurellement plus basse, ce n'est pas juste un problème de résolution). En dessous de ~0.10-0.15, le bruit augmente plus vite que le rappel (faux positifs constatés, ex. structure de toit fixe détectée comme personne à seuil très bas — voir masque de zone ci-dessous). Le comptage reste une **sous-estimation** par nature (détecteur par boîtes englobantes, sujets minuscules/très occlus) — à traiter comme une estimation basse, pas un chiffre exact.

**Cadence** : boucle auto-replanifiée (`setTimeout` en chaîne, pas un `setInterval` fixe) — chaque cycle complet (Grand Place puis De Brouckère, 32 tuiles au pire cas) attend sa propre fin avant de programmer le suivant, avec **45 s de battement** (`CYCLE_GAP_MS`). Valeur volontairement longue : une foule évolue sur des minutes, pas des secondes — l'ancien battement de 2 s ré-échantillonnait en continu et tenait le GPU/CPU ~90 % du temps pour rien. Aucun chevauchement possible même si un cycle dépasse largement le nominal, contrairement à un intervalle fixe. Pause automatique si onglet masqué (`visibilitychange`), **panel cams hors du viewport** (`IntersectionObserver` sur `#panel1` → `panelVisible`/`canRun`) ou tuile non visible (bascule mobile, par caméra) — vérifié par sondage `display` dans la boucle, aucune modification de `initLiveVideo()`.

**États du badge / robustesse** : `CHARGEMENT…` au chargement du modèle · un entier une fois compté · `—` en attente d'image exploitable · `N/D` **uniquement** en cas d'échec de *chargement* du modèle (CDN / ORT / création de session). Une erreur d'inférence **ponctuelle** (frame momentanément illisible quand la vidéo n'est pas activement rendue, canvas taint, pilote GPU capricieux) est journalisée (« cycle ignoré ») puis **ignorée** : elle ne fige plus le badge en N/D — le `catch` d'inférence ne pose plus `loadState='error'`, et le cycle suivant reprend normalement. (Bug corrigé : auparavant la moindre erreur d'un cycle verrouillait N/D définitivement.)

**RAM** : mesure directe (`performance.memory`, tas JS uniquement) : ~28 Mo au repos, pics à ~53 Mo pendant l'inférence — modeste, mais **ne capture ni la mémoire WASM d'onnxruntime ni les buffers WebGPU**, qui dominent probablement l'empreinte réelle (`performance.measureUserAgentSpecificMemory()` donnerait une mesure complète mais exige des headers `Cross-Origin-Isolation` non configurés ici). Pas de mesure précise du total process — à vérifier au besoin via le Gestionnaire des tâches Chrome (Shift+Échap) en conditions réelles.

**RGPD** : classe COCO « personne » uniquement — pas de reconnaissance faciale, pas d'embeddings, pas de ré-identification inter-frames. Les canvas de capture ne sont jamais sérialisés (`toDataURL`/`toBlob`) ni transmis ; seul le compte agrégé (entier) est conservé, par caméra.

**Historique** : `localStorage['dashboard-crowd-history-grandplace']` et `['dashboard-crowd-history-debrouckere']`, tableaux `{ts,count}` séparés, fenêtre de rétention 7 j (élagage à chaque écriture). **Local à l'appareil/navigateur qui fait tourner la détection** — pas d'historique centralisé entre viewers (nécessiterait un petit endpoint Worker + KV, juste des nombres, non implémenté). Exposés via `window._p1CrowdHistoryGrandplace` / `window._p1CrowdHistoryDebrouckere` (même convention que `window._p4Cache`).

**Nuit** : caméras RGB standard, pas de mode nuit. Boost contraste/luminosité canvas (`ctx.filter`) si luminance moyenne échantillonnée < 70/255 (calculé une fois sur l'image source avant découpage en tuiles, pas par tuile) — correctif limité, pas une solution : précision nocturne intrinsèquement dégradée (limite matérielle de la caméra, pas du modèle).

**Proxy vidéo requis** (bloquant, pas optionnel, pour les deux caméras) : `livecam.brucity.be` n'envoie aucun header CORS → `canvas.getImageData()` lève `SecurityError` sur un flux direct, et `crossOrigin="anonymous"` seul casse la lecture (aucun ACAO envoyé). Le Worker proxifie manifeste + segments sous `/hls/*` (générique, pas spécifique à un flux), réécrit les URIs du `.m3u8` (relatives ou absolues) pour qu'elles repassent par le proxy, et supporte les requêtes `Range`/`206` — requis par les moteurs de lecture HLS natifs, qui échouent silencieusement (`NotSupportedError`) sans ce support. Voir Infrastructure ci-dessous.

**Masque de zone (ROI) — plus juste "backlog lointain"** : constaté en test qu'à seuil bas, le modèle détecte parfois une structure de toit fixe (scène/stand) comme personne. Un masque définissant la zone réelle de la place (polygone excluant toits/façades/ciel) éliminerait ce type de faux positif quel que soit le seuil, sans jamais perdre une vraie personne — bon rapport effort/gain, mais pas encore implémenté : nécessite un outil de dessin interactif et une validation caméra par caméra (chaque caméra a ses propres structures fixes à exclure). À faire une fois que Grand Place et De Brouckère ont chacune été observées plus longtemps.

**Licence** : poids et outillage YOLO26 sous AGPL-3.0 (Ultralytics), licence Enterprise disponible en alternative — à valider selon le contexte de déploiement.

---

## Panel 02 · Liens météo

Bulletin météo → `mymeteo.be` · INCA → `mymeteo.be/incaBe` (nouvel onglet).

---

## Infrastructure & sécurité

| Élément | Détail |
|---------|--------|
| Worker proxy | `rough-block-b4fe.e-jesuran.workers.dev` — injecte `CRODEON_API_KEY`, cache Cache API (10 s latest / 30 s historique) |
| Worker proxy HLS | Même Worker, route `/hls/*` → `livecam.brucity.be` (générique, sert les deux caméras, `worker.js` fonction `proxyHls`). Réécrit le manifeste `.m3u8`, ajoute CORS, supporte `Range`/`206`, cache les segments (`immutable`) sauf requêtes partielles. Nécessaire pour le comptage de foule (Panel 01) |
| Clé API | Secret Worker, absente du code client |
| `worker.js` | **Non suivi par git** (`.gitignore`) — tout changement nécessite un `wrangler deploy` manuel séparé, non visible dans l'historique du repo |
| HLS | Streams publics, URLs en clair — acceptable |
| CSP / SRI | Non implémentés — backlog I4 |

---

## Fichiers

| Fichier | Rôle |
|---------|------|
| `index.html` | Application principale (tout-en-un) |
| `worker.js` | Proxy Cloudflare Worker (API Crodeon + HLS Grand Place) |
| `models/yolo26n.onnx` | Modèle YOLO26n (ONNX, entrée 1280px) — comptage de foule Panel 01 |
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
| C1 | ✅ | Comptage de foule Grand Place — YOLO26n/ONNX côté navigateur, proxy HLS Worker | 01 |
| C2 | 🟢 Basse | Alertes automatiques sur seuil de comptage — décliné pour v1 | 01 |
| C3 | 🟢 Basse | Masque de zone (ROI) pour exclure trottoirs/façades hors place | 01 |
| C4 | 🟢 Basse | Historique de comptage centralisé multi-appareils (Worker + KV) | 01 |
| C5 | ✅ | Allègement perf comptage — cadence 2 s→45 s, chargement paresseux ORT+modèle, proxy WASM hors thread UI, pause hors viewport, buffer tenseur réutilisé | 01 |
| C6 | 🟢 Basse | Modèle quantifié INT8 (~3 Mo au lieu de 10) — écarté pour l'instant : nécessite ré-export Ultralytics + recalibration du seuil | 01 |
| C7 | ✅ | Correction badge figé en `N/D` — les erreurs d'inférence ponctuelles ne verrouillent plus l'état ; `N/D` réservé à un échec de chargement du modèle | 01 |
| M1 | ✅ | Mobile — veille écran (`navigator.wakeLock`) + suppression zoom double-tap (`touch-action: manipulation`) | — |
| I4 | 🟢 Basse | CSP + SRI dépendances CDN | — |
| DOC | 🟡 | Capturer `vue-globale-annotee` et `panel-alerte` | doc |
