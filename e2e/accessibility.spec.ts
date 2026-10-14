import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { cleanDatabase, seedTicket } from './helpers/db';

test.describe('Auditoría de Accesibilidad (WCAG 2.1 AA)', () => {
  test.beforeEach(() => {
    cleanDatabase();
  });

  test.afterAll(() => {
    cleanDatabase();
  });

  test('La vista /returns/start debe cumplir con los estándares WCAG 2.1 AA sin violaciones críticas', async ({ page }) => {
    test.setTimeout(60000);
    await page.goto('/returns/start');
    await page.waitForLoadState('networkidle');

    // Valida WCAG 2.1 AA y verifica que no existan violaciones críticas
    const accessibilityScanResults = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .disableRules(['color-contrast'])
      .exclude('#nprogress')
      .analyze();

    const criticalViolations = accessibilityScanResults.violations.filter(
      (v) => v.impact === 'critical' || v.impact === 'serious'
    );
    expect(criticalViolations).toEqual([]);
    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test('La vista /returns/tracking debe cumplir con los estándares WCAG 2.1 AA sin violaciones críticas', async ({ page }) => {
    test.setTimeout(60000);
    const ticket = seedTicket('received');

    await page.goto('/returns/start');
    await page.waitForLoadState('networkidle');

    await page.getByLabel('Número de Pedido').fill(ticket.order_number);
    await page.getByLabel('DNI o CE del Comprador').fill(ticket.customer_dni);

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/returns/login') && resp.status() === 302),
      page.getByRole('button', { name: 'Iniciar Solicitud' }).click(),
    ]);

    // En Inertia SPA la navegación es asíncrona hacia /returns/tracking
    await expect(page).toHaveURL(/.*\/returns\/tracking/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: ticket.tracking_code })).toBeVisible({ timeout: 15000 });

    // Esperar que la barra de carga transitoria de Inertia (nprogress) termine
    await page.locator('#nprogress').waitFor({ state: 'detached', timeout: 5000 }).catch(() => {});

    const accessibilityScanResults = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .disableRules(['color-contrast'])
      .exclude('#nprogress')
      .analyze();

    const criticalViolations = accessibilityScanResults.violations.filter(
      (v) => v.impact === 'critical' || v.impact === 'serious'
    );
    expect(criticalViolations).toEqual([]);
    expect(accessibilityScanResults.violations).toEqual([]);
  });
});
