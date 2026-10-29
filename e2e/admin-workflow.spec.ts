import { test, expect } from '@playwright/test';
import { cleanDatabase, seedTicket } from './helpers/db';

test.describe('Backoffice Administrativo y Gobernanza RBAC (HU-08 a HU-12)', () => {
  test.beforeEach(() => {
    cleanDatabase();
  });

  test.afterAll(() => {
    cleanDatabase();
  });

  test('Escenario 1 (HU-08, HU-09): Autenticación de Operador de Soporte y visualización reactiva de tickets', async ({ page }) => {
    test.setTimeout(90000);
    const ticket = seedTicket('received');

    await page.goto('/login');
    await page.waitForLoadState('networkidle');

    // Inicio de sesión con credenciales de soporte
    await page.getByLabel('Correo Electrónico').fill('soporte@tailoy.com');
    await page.getByLabel('Contraseña').fill('password');

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/login') && resp.status() === 302),
      page.getByRole('button', { name: 'Iniciar Sesión' }).click(),
    ]);

    // Redirección al panel administrativo de tickets
    await expect(page).toHaveURL(/.*\/admin\/tickets/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: /Solicitudes de Devolución/i })).toBeVisible();

    // Verificación de la tabla de tickets con el registro sembrado (usando rol de celda para evitar ambigüedad estricta)
    await expect(page.getByText(ticket.tracking_code)).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Recibido' })).toBeVisible();

    // Filtros disponibles en pantalla
    await expect(page.getByRole('button', { name: 'Filtrar' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Limpiar' })).toBeVisible();
  });

  test('Escenario 2A (HU-12): Bloqueo RBAC - Operador de soporte recibe 403 Forbidden al intentar cerrar ticket', async ({ page }) => {
    test.setTimeout(90000);
    const ticket = seedTicket('approved');

    await page.goto('/login');
    await page.waitForLoadState('networkidle');

    await page.getByLabel('Correo Electrónico').fill('soporte@tailoy.com');
    await page.getByLabel('Contraseña').fill('password');

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/login') && resp.status() === 302),
      page.getByRole('button', { name: 'Iniciar Sesión' }).click(),
    ]);

    await expect(page).toHaveURL(/.*\/admin\/tickets/, { timeout: 15000 });

    // Navegar al detalle del ticket
    await page.goto(`/admin/tickets/${ticket.ticket_id}`);
    await expect(page).toHaveURL(new RegExp(`/admin/tickets/${ticket.ticket_id}`));
    await expect(page.getByText(ticket.tracking_code).first()).toBeVisible();

    // Gobernanza RBAC reactiva en UI: la opción 'closed' no se muestra al operador
    await expect(page.locator('#new-status option[value="closed"]')).toHaveCount(0);
    await expect(page.getByText('Solo administradores pueden cerrar solicitudes.')).toBeVisible();

    // Si el operador fuerza el valor 'closed' en el DOM, el backend responde con 403 Forbidden
    await page.evaluate(() => {
      const select = document.querySelector('#new-status');
      if (select) {
        const opt = document.createElement('option');
        opt.value = 'closed';
        opt.textContent = 'Cerrado';
        select.appendChild(opt);
      }
    });
    await page.getByLabel(/Nuevo estado/i).selectOption('closed');

    const [forbiddenResponse] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes(`/admin/tickets/${ticket.ticket_id}/status`)),
      page.getByRole('button', { name: 'Actualizar Estado' }).click(),
    ]);

    // La política RBAC debe rechazar con HTTP 403 Forbidden estricto
    expect(forbiddenResponse.status()).toBe(403);
  });

  test('Escenario 2B (HU-12): Gobernanza RBAC - Administrador ejecuta el cierre formal del ticket', async ({ page }) => {
    test.setTimeout(90000);
    const ticket = seedTicket('approved');

    await page.goto('/login');
    await page.waitForLoadState('networkidle');

    await page.getByLabel('Correo Electrónico').fill('admin@tailoy.com');
    await page.getByLabel('Contraseña').fill('password');

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/login') && resp.status() === 302),
      page.getByRole('button', { name: 'Iniciar Sesión' }).click(),
    ]);

    await expect(page).toHaveURL(/.*\/admin\/tickets/, { timeout: 15000 });

    // Navegar al detalle del ticket como Administrador
    await page.goto(`/admin/tickets/${ticket.ticket_id}`);
    await expect(page.getByText(ticket.tracking_code).first()).toBeVisible();

    // Administrador ejecuta el cierre formal del ticket
    await page.getByLabel(/Nuevo estado/i).selectOption('closed');

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes(`/admin/tickets/${ticket.ticket_id}/status`)),
      page.getByRole('button', { name: 'Actualizar Estado' }).click(),
    ]);

    // El estado del ticket se actualiza a 'Cerrado' exitosamente
    await expect(page.locator('span', { hasText: 'Cerrado' }).first()).toBeVisible({ timeout: 15000 });
  });
});
