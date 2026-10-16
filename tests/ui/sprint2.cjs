const { chromium } = require('playwright');
const AxeBuilder = require('@axe-core/playwright').default;
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const results = [];
  fs.mkdirSync('build/sprint2-ui/screenshots', { recursive: true });
  try {
    for (const name of ['agenda', 'atendimentos', 'atendimento']) {
      for (const width of [320, 375, 390, 768, 1280]) {
        const context = await browser.newContext({ viewport: { width, height: 900 }, reducedMotion: 'reduce' });
        const page = await context.newPage();
        await page.setContent(fs.readFileSync(path.join('build/sprint2-ui', name + '.html'), 'utf8'));
        await page.locator('details').evaluateAll(nodes => nodes.forEach(node => { node.open = true; }));
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name} overflows at ${width}px`);
        await page.keyboard.press('Tab');
        assert(await page.evaluate(() => document.activeElement.tagName === 'A'), 'Navigation must be keyboard accessible');
        const report = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
        assert.equal(report.violations.length, 0, JSON.stringify(report.violations));
        const smallButtons = await page.locator('button').evaluateAll(nodes => nodes.filter(node => node.getBoundingClientRect().height < 44).length);
        assert.equal(smallButtons, 0, 'Touch buttons must be at least 44px high');
        await page.screenshot({ path: `build/sprint2-ui/screenshots/${name}-${width}.png`, fullPage: true });
        results.push({ page: name, width, overflow: false, accessibilityViolations: 0, keyboard: 'passed' });
        await context.close();
      }
    }
    fs.writeFileSync('build/sprint2-ui/results.json', JSON.stringify(results, null, 2));
    console.log(`Validated ${results.length} rendered page/viewport combinations.`);
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exit(1); });
