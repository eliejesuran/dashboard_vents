const { chromium } = require('playwright');
const path = require('path');

const BASE = 'http://localhost:5173';
const OUT  = path.join(__dirname, 'documentation/screenshots');

(async () => {
  const browser = await chromium.launch();

  async function shot(page, filename, description) {
    await page.waitForTimeout(1800); // laisse les données charger
    await page.screenshot({ path: path.join(OUT, filename), type: 'jpeg', quality: 88 });
    console.log('✓', filename, '—', description);
  }

  // ── VUE GLOBALE BRIGHT ──────────────────────────
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1440, height: 860 });
  await page.goto(BASE + '/', { waitUntil: 'networkidle' });
  await page.evaluate(() => {
    const t = localStorage.getItem('dashboard-theme');
    if (t === 'dark') document.getElementById('themeToggle').click();
  });
  await shot(page, 'vue-globale-bright.jpg', 'Vue globale thème clair');

  // ── VUE GLOBALE DARK ────────────────────────────
  await page.evaluate(() => document.getElementById('themeToggle').click());
  await page.waitForTimeout(400);
  await shot(page, 'vue-globale-dark.jpg', 'Vue globale thème sombre');

  // Repasse en bright pour les autres captures
  await page.evaluate(() => document.getElementById('themeToggle').click());
  await page.waitForTimeout(400);

  // ── PANEL ANÉMOMÈTRES ──────────────────────────
  const panel3 = await page.$('#panel3');
  await page.waitForTimeout(500);
  await panel3.screenshot({ path: path.join(OUT, 'panel-anemometres.jpg'), type: 'jpeg', quality: 88 });
  console.log('✓ panel-anemometres.jpg');

  // ── PANEL HISTORIQUE ───────────────────────────
  const panel4 = await page.$('#panel4');
  await panel4.screenshot({ path: path.join(OUT, 'panel-historique.jpg'), type: 'jpeg', quality: 88 });
  console.log('✓ panel-historique.jpg');

  // ── PANEL MÉTÉO IRM ────────────────────────────
  const panel2 = await page.$('#panel2');
  await panel2.screenshot({ path: path.join(OUT, 'panel-meteo.jpg'), type: 'jpeg', quality: 88 });
  console.log('✓ panel-meteo.jpg');

  // ── PANEL WEBCAMS ──────────────────────────────
  const panel1 = await page.$('#panel1');
  await panel1.screenshot({ path: path.join(OUT, 'panel-webcams.jpg'), type: 'jpeg', quality: 88 });
  console.log('✓ panel-webcams.jpg');

  // ── VUE MOBILE ─────────────────────────────────
  await page.setViewportSize({ width: 420, height: 900 });
  await page.waitForTimeout(600);
  await page.screenshot({ path: path.join(OUT, 'mobile-webcam.jpg'), type: 'jpeg', quality: 88, fullPage: false });
  console.log('✓ mobile-webcam.jpg');

  await browser.close();
  console.log('\nToutes les captures sont dans documentation/screenshots/');
})();
