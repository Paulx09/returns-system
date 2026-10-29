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
    const ticketReceived = seedTicket('received');
    const ticketApproved = seedTicket('approved', true);

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

    // Verificación de la tabla de tickets con ambos registros sembrados
    await expect(page.getByText(ticketReceived.tracking_code)).toBeVisible();
    await expect(page.getByText(ticketApproved.tracking_code)).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Recibido' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Aprobado' })).toBeVisible();

    // Filtrado reactivo por estado (TC-ADM-03) sin necesidad de presionar 'Filtrar'
    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/admin/tickets') && resp.url().includes('status=approved')),
      page.getByLabel('Estado').selectOption('approved'),
    ]);

    await expect(page.getByText(ticketApproved.tracking_code)).toBeVisible();
    await expect(page.getByText(ticketReceived.tracking_code)).toHaveCount(0);

    // Limpiar filtros restaura todos los tickets
    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/admin/tickets')),
      page.getByRole('button', { name: 'Limpiar' }).click(),
    ]);
    await expect(page.getByText(ticketReceived.tracking_code)).toBeVisible();
    await expect(page.getByText(ticketApproved.tracking_code)).toBeVisible();
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

  test('Escenario 3 (HU-10, HU-11 / TC-ADM-04, TC-ADM-05): Visor modal interactivo de evidencias y justificación obligatoria en rechazo', async ({ page }) => {
    test.setTimeout(90000);
    const ticket = seedTicket('received');

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
    await expect(page.getByText(ticket.tracking_code).first()).toBeVisible();

    // ── Parte A (TC-ADM-04): Visor modal interactivo (lightbox) de evidencia ──
    await page.getByRole('button', { name: /sample_evidence\.jpg/i }).click();

    const modal = page.getByRole('dialog', { name: /Visor de evidencia sample_evidence\.jpg/i });
    await expect(modal).toBeVisible();

    // Probar controles de zoom y rotación dentro del modal
    await expect(modal.getByText('100%')).toBeVisible();
    await modal.getByRole('button', { name: 'Acercar imagen' }).click();
    await expect(modal.getByText('125%')).toBeVisible();
    await modal.getByRole('button', { name: 'Rotar imagen' }).click();

    // Verificar enlace de descarga directa con ?download=1
    const downloadLink = modal.getByRole('link', { name: 'Descargar archivo' });
    await expect(downloadLink).toHaveAttribute('href', /download=1/);

    // Cerrar visor modal con tecla Escape
    await page.keyboard.press('Escape');
    await expect(modal).toHaveCount(0);

    // ── Parte B (TC-ADM-05): Justificación obligatoria en estado 'rejected' ──
    const submitBtn = page.getByRole('button', { name: 'Actualizar Estado' });
    await page.getByLabel(/Nuevo estado/i).selectOption('rejected');

    // Con comentario vacío, el botón de envío permanece deshabilitado
    await expect(submitBtn).toBeDisabled();

    // Al escribir la justificación, el botón se habilita y permite guardar la transición
    await page.getByLabel(/Comentario/i).fill('Producto presenta signos de uso indebido.');
    await expect(submitBtn).toBeEnabled();

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes(`/admin/tickets/${ticket.ticket_id}/status`)),
      submitBtn.click(),
    ]);

    await expect(page.locator('span', { hasText: 'Rechazado' }).first()).toBeVisible({ timeout: 15000 });
    await expect(page.getByText(/Producto presenta signos de uso indebido/i).first()).toBeVisible();
  });
});
