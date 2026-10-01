import React from 'react';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@inertiajs/react';
import Start from '../Pages/Returns/Start';
import Dashboard from '../Pages/Returns/Dashboard';
import Success from '../Pages/Returns/Success';
import Tracking from '../Pages/Returns/Tracking';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    useForm: vi.fn(),
    usePage: () => ({ props: { flash: {} } }),
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

describe('Returns/Success', () => {
    it('renders tracking code and navigation link to tracking view', () => {
        render(<Success trackingCode="RET-XYZ98765" />);

        expect(screen.getByText('RET-XYZ98765')).toBeVisible();
        expect(screen.getByRole('link', { name: 'Ver Seguimiento de mi Solicitud' })).toHaveAttribute('href', '/route/returns.tracking');
        expect(screen.getByRole('link', { name: 'Volver al Inicio' })).toHaveAttribute('href', '/route/returns.start');
    });
});

describe('Returns/Tracking', () => {
    const mockTicket = {
        ticket_id: 'ticket-1',
        tracking_code: 'RET-ABC12345',
        current_status: 'more_information_requested',
        created_at: '2026-09-25T10:00:00Z',
        customer_comment: 'El empaque vino abierto.',
        order: order,
        return_items: [
            {
                return_item_id: 'ret-item-1',
                quantity_to_return: 2,
                condition: 'opened',
                order_item: order.order_items[0],
                reason: reasons[0],
            },
        ],
        evidences: [
            {
                evidence_id: 'ev-1',
                file_name: 'foto_evidencia.jpg',
                file_size: 204800,
            },
        ],
        status_history: [
            {
                history_id: 'hist-2',
                new_status: 'more_information_requested',
                changed_at: '2026-09-26T14:30:00Z',
                comment: 'Por favor adjunta foto del código de barras.',
            },
            {
                history_id: 'hist-1',
                new_status: 'received',
                changed_at: '2026-09-25T10:00:00Z',
                comment: 'Solicitud registrada.',
            },
        ],
    };

    it('renders tracking code, order number, products, and support comments', () => {
        render(<Tracking ticket={mockTicket} order={order} />);

        expect(screen.getByRole('heading', { name: 'RET-ABC12345' })).toBeVisible();
        expect(screen.getByText('#ORD-123')).toBeVisible();
        expect(screen.getByText('Cuaderno')).toBeVisible();
        expect(screen.getByText('Cant: 2')).toBeVisible();
        expect(screen.getByText('foto_evidencia.jpg')).toBeVisible();
        expect(screen.getAllByText(/Por favor adjunta foto del código de barras/)[0]).toBeVisible();
    });

    it('renders the contextual alert when more information is requested', () => {
        render(<Tracking ticket={mockTicket} order={order} />);

        expect(screen.getByText('Se requiere información adicional')).toBeVisible();
    });

    it('renders the case closed state with action to register a new ticket', () => {
        const closedTicket = {
            ...mockTicket,
            current_status: 'closed',
            status_history: [
                {
                    history_id: 'hist-3',
                    new_status: 'closed',
                    changed_at: '2026-09-28T09:00:00Z',
                    comment: 'Caso atendido y cerrado con reembolso.',
                },
            ],
        };

        render(<Tracking ticket={closedTicket} order={order} />);

        expect(screen.getByText('Caso Finalizado y Cerrado')).toBeVisible();
        expect(screen.getByRole('link', { name: 'Registrar nueva solicitud' })).toHaveAttribute('href', '/route/returns.dashboard');
    });
});