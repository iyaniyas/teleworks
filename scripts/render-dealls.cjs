// render-dealls.js
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

(async () => {
  const outPath = process.argv[2] || '/tmp/dealls-rendered.html';
  const url = process.argv[3] || 'https://dealls.com/?location=remote&sortParam=publishedAt';

  const browser = await chromium.launch({
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
    headless: true,
  });

  try {
    const context = await browser.newContext({
      userAgent: 'TeleworksBot/1.0 (+https://teleworks.id)',
      viewport: { width: 1200, height: 900 },
    });
    const page = await context.newPage();
    console.log('Navigating to', url);
    await page.goto(url, { waitUntil: 'networkidle' , timeout: 60000});
    // optionally wait for a selector that indicates job list loaded:
    // await page.waitForSelector('.job-list-item', { timeout: 15000 }).catch(() => {});
    const html = await page.content();
    fs.writeFileSync(outPath, html, 'utf8');
    console.log('Saved rendered HTML to', outPath);
    await context.close();
  } catch (err) {
    console.error('Render failed:', err);
    process.exit(2);
  } finally {
    await browser.close();
  }
})();

