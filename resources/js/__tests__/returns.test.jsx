import React from 'react';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@inertiajs/react';
import Start from '../Pages/Returns/Start';
import Dashboard from '../Pages/Returns/Dashboard';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    useForm: vi.fn(),
}));

afterEach(() => {
    cleanup();
});

const order = {
    order_number: 'ORD-123',
    order_date: '2026-09-15',
    order_items: [
        { order_item_id: 'item-1', product_name: 'Cuaderno', product_code: 'CUA-1', unit_price: '10.00', quantity: 2 },
        { order_item_id: 'item-2', product_name: 'Lapicero', product_code: 'LAP-1', unit_price: '5.00', quantity: 1 },
    ],
};
const reasons = [{ reason_id: 'reason-1', description: 'Producto defectuoso' }];

function configureForm(initialData, overrides = {}) {
    const formState = { data: initialData, setData: vi.fn(), post: vi.fn(), processing: false, errors: {}, ...overrides };
    useForm.mockImplementation((defaults) => {
        const [data, setFormData] = React.useState(initialData ?? defaults);
        formState.setData.mockImplementation((field, value) => setFormData(current => ({ ...current, [field]: value })));
        formState.data = data;
        return { data, setData: formState.setData, post: formState.post, processing: formState.processing, errors: formState.errors };
    });
    return formState;
}

describe('Returns/Start', () => {
    beforeEach(() => vi.clearAllMocks());

    it('submits valid order and identity data through the Inertia contract', async () => {
        const user = userEvent.setup();
        const form = configureForm({ order_number: '', customer_dni: '' });
        render(<Start />);

        await user.type(screen.getByLabelText('Número de Pedido'), 'ORD-123');
        await user.type(screen.getByLabelText('DNI o CE del Comprador'), '12345678');
        await user.click(screen.getByRole('button', { name: 'Iniciar Solicitud' }));

        expect(form.post).toHaveBeenCalledWith('/route/returns.login');
    });

    it('renders the contract error when the order is not found or invalid', () => {
        configureForm({ order_number: '', customer_dni: '' }, { errors: { login: 'No se encontró el comprobante.' } });
        render(<Start />);

        expect(screen.getByText('No se encontró el comprobante.')).toBeVisible();
    });

    it('renders field-level validation errors from the login contract', () => {
        configureForm({ order_number: '', customer_dni: '' }, {
            errors: { order_number: 'El pedido es obligatorio.', customer_dni: 'El DNI es obligatorio.' },
        });
        render(<Start />);

        expect(screen.getByText('El pedido es obligatorio.')).toBeVisible();
        expect(screen.getByText('El DNI es obligatorio.')).toBeVisible();
    });

    it('disables submission while the verification request is processing', () => {
        configureForm({ order_number: '', customer_dni: '' }, { processing: true });
        render(<Start />);

        expect(screen.getByRole('button', { name: 'Verificando...' })).toBeDisabled();
    });
});

describe('Returns/Dashboard', () => {
    beforeEach(() => vi.clearAllMocks());

    it('selects multiple products and keeps their independent return data', async () => {
        const user = userEvent.setup();
        const form = configureForm({ items: [], customer_notes: '', evidences: [] });
        render(<Dashboard order={order} reasons={reasons} />);

        await user.click(screen.getByRole('checkbox', { name: /Cuaderno/ }));
        await user.click(screen.getByRole('checkbox', { name: /Lapicero/ }));
        await user.selectOptions(screen.getAllByRole('combobox', { name: 'Motivo' })[0], 'reason-1');

        expect(form.setData).toHaveBeenCalled();
        expect(form.data.items).toEqual([
            { order_item_id: 'item-1', return_reason_id: 'reason-1', quantity: 1, condition: 'sealed' },
            { order_item_id: 'item-2', return_reason_id: '', quantity: 1, condition: 'sealed' },
        ]);
    });

    it('removes a selected product when its checkbox is toggled off', async () => {
        const user = userEvent.setup();
        const form = configureForm({ items: [], customer_notes: '', evidences: [] });
        render(<Dashboard order={order} reasons={reasons} />);

        const product = screen.getByRole('checkbox', { name: /Cuaderno/ });
        await user.click(product);
        await user.click(product);

        expect(form.data.items).toEqual([]);
        expect(screen.queryByLabelText('Motivo')).not.toBeInTheDocument();
    });

    it('updates quantity, reason, and condition for a selected item', async () => {
        const user = userEvent.setup();
        const form = configureForm({ items: [], customer_notes: '', evidences: [] });
        render(<Dashboard order={order} reasons={reasons} />);

        await user.click(screen.getByRole('checkbox', { name: /Cuaderno/ }));
        await user.clear(screen.getByLabelText('Cantidad a devolver'));
        await user.type(screen.getByLabelText('Cantidad a devolver'), '2');
        await user.selectOptions(screen.getByLabelText('Motivo'), 'reason-1');
        await user.selectOptions(screen.getByLabelText('Estado del producto'), 'damaged');

        expect(form.data.items).toEqual([
            { order_item_id: 'item-1', return_reason_id: 'reason-1', quantity: 2, condition: 'damaged' },
        ]);
        expect(form.setData).toHaveBeenCalledWith('items', expect.any(Array));
    });

    it('requires reason and evidence before enabling submission', async () => {
        const user = userEvent.setup();
        configureForm({ items: [], customer_notes: '', evidences: [] });
        render(<Dashboard order={order} reasons={reasons} />);

        await user.click(screen.getByRole('checkbox', { name: /Cuaderno/ }));
        expect(screen.getByRole('button', { name: 'Enviar Solicitud' })).toBeDisabled();

        await user.selectOptions(screen.getByRole('combobox', { name: 'Motivo' }), 'reason-1');
        const evidence = new File(['valid image'], 'evidence.jpg', { type: 'image/jpeg' });
        fireEvent.change(screen.getByLabelText('Fotos o Documentos de Evidencia'), { target: { files: [evidence] } });

        await waitFor(() => expect(screen.getByRole('button', { name: 'Enviar Solicitud' })).toBeEnabled());
    });

    it('submits selected item, notes, and valid evidence through Inertia', async () => {
        const user = userEvent.setup();
        const form = configureForm({ items: [], customer_notes: '', evidences: [] });
        render(<Dashboard order={order} reasons={reasons} />);

        await user.click(screen.getByRole('checkbox', { name: /Cuaderno/ }));
        await user.selectOptions(screen.getByLabelText('Motivo'), 'reason-1');
        await user.type(screen.getByRole('textbox', { name: 'Comentarios (Opcional)' }), 'Caja dañada');
        const evidence = new File(['valid image'], 'evidence.png', { type: 'image/png' });
        fireEvent.change(screen.getByLabelText('Fotos o Documentos de Evidencia'), { target: { files: [evidence] } });
        await waitFor(() => expect(screen.getByRole('button', { name: 'Enviar Solicitud' })).toBeEnabled());
        await user.click(screen.getByRole('button', { name: 'Enviar Solicitud' }));

        expect(form.post).toHaveBeenCalledWith('/route/returns.tickets.store');
    });

    it.each([
        ['corrupto', 'El archivo no es válido.'],
        ['excedido', 'El archivo excede el máximo permitido.'],
    ])('renders the backend evidence error for un archivo %s', (_, message) => {
        const user = userEvent.setup();
        configureForm({ items: [], customer_notes: '', evidences: [] }, { errors: { 'evidences.0': message } });
        render(<Dashboard order={order} reasons={reasons} />);
        user.click(screen.getByRole('checkbox', { name: /Cuaderno/ }));

        return waitFor(() => expect(screen.getByText(message)).toBeVisible());
    });

    it('renders general item and evidence errors while ignoring unrelated error keys', async () => {
        const user = userEvent.setup();
        configureForm({ items: [], customer_notes: '', evidences: [] }, {
            errors: { items: 'Selecciona al menos un producto.', evidences: 'Adjunta una evidencia.', other: 'No debe mostrarse.' },
        });
        render(<Dashboard order={order} reasons={reasons} />);
        await user.click(screen.getByRole('checkbox', { name: /Cuaderno/ }));

        expect(screen.getByText('Selecciona al menos un producto.')).toBeVisible();
        expect(screen.getByText('Adjunta una evidencia.')).toBeVisible();
        expect(screen.queryByText('No debe mostrarse.')).not.toBeInTheDocument();
    });

    it('shows the loading state and disables submission while sending', async () => {
        const user = userEvent.setup();
        configureForm({ items: [], customer_notes: '', evidences: [] }, { processing: true });
        render(<Dashboard order={order} reasons={reasons} />);
        await user.click(screen.getByRole('checkbox', { name: /Cuaderno/ }));

        expect(screen.getByRole('button', { name: 'Enviando...' })).toBeDisabled();
    });
});