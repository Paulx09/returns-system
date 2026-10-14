import { test, expect } from '@playwright/test';
import path from 'path';
import { cleanDatabase, seedTicket } from './helpers/db';

test.describe('Flujo de Devoluciones del Cliente (HU-01 a HU-06)', () => {
  test.beforeEach(() => {
    cleanDatabase();
  });

  test.afterAll(() => {
    cleanDatabase();
  });

  test('Escenario 1 (HU-01): Bloqueo y mensaje de error ante pedido con plazo vencido (> 7 días)', async ({ page }) => {
    test.setTimeout(90000);
    await page.goto('/returns/start');
    await page.waitForLoadState('networkidle');

    // Pedido fuera del plazo de garantía comercial (ORD-290146, DNI 40112710)
    await page.getByLabel('Número de Pedido').fill('ORD-290146');
    await page.getByLabel('DNI o CE del Comprador').fill('40112710');

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/returns/login') && (resp.status() === 422 || resp.status() === 302 || resp.status() === 200)),
      page.getByRole('button', { name: 'Iniciar Solicitud' }).click(),
    ]);

    // Debe mostrarse el mensaje de error de plazo vencido en pantalla
    await expect(
      page.getByText(/El plazo máximo de 7 días para devoluciones ha vencido/i)
    ).toBeVisible({ timeout: 15000 });

    // Permanece en /returns/start y no avanza al formulario de devolución
    expect(page.url()).toContain('/returns/start');
    await expect(page).not.toHaveURL(/.*\/returns\/dashboard/);
  });

  test('Escenario 2 (HU-01 a HU-05): Happy Path de Registro Completo con ítems y evidencias fotográficas', async ({ page }) => {
    test.setTimeout(90000);
    // ── Paso 1: Autenticación de cliente con pedido elegible (< 7 días) ──
    await page.goto('/returns/start');
    await page.waitForLoadState('networkidle');

    await page.getByLabel('Número de Pedido').fill('ORD-123456');
    await page.getByLabel('DNI o CE del Comprador').fill('12345678');

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/returns/login') && resp.status() === 302),
      page.getByRole('button', { name: 'Iniciar Solicitud' }).click(),
    ]);

    // Redirección al formulario de selección de ítems
    await expect(page).toHaveURL(/.*\/returns\/dashboard/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: /Pedido #ORD-123456/i })).toBeVisible();

    // ── Paso 2: Selección de producto a devolver ──
    const itemCheckbox = page.getByRole('checkbox').first();
    await itemCheckbox.check();

    // Seleccionar motivo de devolución y condición física
    const reasonSelect = page.getByLabel(/Motivo/i).first();
    await reasonSelect.selectOption({ index: 1 });

    const conditionSelect = page.getByLabel(/Estado del producto/i).first();
    await conditionSelect.selectOption('opened');

    // Comentarios opcionales del cliente
    await page.getByLabel(/Comentarios \(Opcional\)/i).fill('El producto presenta fallas de fábrica al abrirse.');

    // ── Paso 3: Carga de archivo de evidencia (multipart) ──
    const fixturePath = path.resolve(process.cwd(), 'postman/fixtures/sample_evidence.jpg');
    const fileInput = page.getByLabel(/Fotos o Documentos de Evidencia/i);
    await fileInput.setInputFiles(fixturePath);

    // Verificación de que el archivo adjunto se lista visualmente
    await expect(page.getByText('sample_evidence.jpg')).toBeVisible();

    // ── Paso 4: Envío del formulario y confirmación de creación (HU-05) ──
    const submitButton = page.getByRole('button', { name: 'Enviar Solicitud' });
    await expect(submitButton).toBeEnabled();

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/returns/tickets') && resp.status() === 302),
      submitButton.click(),
    ]);

    // Verificación de la pantalla de éxito con Tracking Code generado
    await expect(page).toHaveURL(/.*\/returns\/success/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: '¡Solicitud Registrada!' })).toBeVisible();

    // Extraer y validar el Tracking Code RET-XXXXXXXX
    const trackingCodeElement = page.locator('text=/RET-[A-Z0-9]{8}/').first();
    await expect(trackingCodeElement).toBeVisible();
    const trackingCodeText = (await trackingCodeElement.textContent())?.trim();
    expect(trackingCodeText).toMatch(/^RET-[A-Z0-9]{8}$/);
  });

  test('Escenario 3 (HU-06): Consulta de Tracking Público y verificación de estado "received"', async ({ page, isMobile }) => {
    test.setTimeout(90000);
    const ticket = seedTicket('received');

    await page.goto('/returns/start');
    await page.waitForLoadState('networkidle');

    // Cambiar a la pestaña de consulta por código de seguimiento
    await page.getByRole('tab', { name: 'Con Código de Seguimiento' }).click();
    await page.getByLabel('Código de Seguimiento').fill(ticket.tracking_code);
    await page.getByLabel('DNI o CE del Comprador').fill(ticket.customer_dni);

    await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/returns/track-by-code') && resp.status() === 302),
      page.getByRole('button', { name: 'Consultar Seguimiento' }).click(),
    ]);

    // Verificación en la vista /returns/tracking
    await expect(page).toHaveURL(/.*\/returns\/tracking/, { timeout: 15000 });
    await expect(page.getByRole('heading', { name: ticket.tracking_code })).toBeVisible();

    // Confirmar estado inicial 'received' (Recibido) en el badge y stepper
    await expect(page.getByText('Recibido').first()).toBeVisible();
    if (!isMobile) {
      await expect(page.getByText('Solicitud enviada').first()).toBeVisible();
    }
  });
});
