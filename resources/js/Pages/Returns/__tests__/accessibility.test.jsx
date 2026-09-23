import React from 'react';
import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@inertiajs/react';
import { axe } from 'vitest-axe';
import Start from '../Start';
import Dashboard from '../Dashboard';
import Success from '../Success';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    useForm: vi.fn(),
}));

afterEach(() => {
    cleanup();
});

const mockOrder = {
    order_number: 'ORD-987654',
    order_date: '2026-09-20',
    order_items: [
        {
            order_item_id: 'item-101',
            product_name: 'Cuaderno Anillado A4',
            product_code: 'CUA-A4',
            unit_price: '15.50',
            quantity: 3,
        },
        {
            order_item_id: 'item-102',
            product_name: 'Caja de Plumones 12 u',
            product_code: 'PLU-12',
            unit_price: '12.00',
            quantity: 1,
        },
    ],
};

const mockReasons = [
    { reason_id: 'reason-1', description: 'Producto defectuoso de fábrica' },
    { reason_id: 'reason-2', description: 'Producto equivocado' },
];

function configureFormMock(initialData, overrides = {}) {
    const formState = {
        data: initialData,
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        errors: {},
        ...overrides,
    };

    useForm.mockImplementation((defaults) => {
        const [formData, setFormData] = React.useState(initialData ?? defaults);
        formState.setData.mockImplementation((field, value) => {
            setFormData((current) => ({ ...current, [field]: value }));
        });
        formState.data = formData;
        return {
            data: formData,
            setData: formState.setData,
            post: formState.post,
            processing: formState.processing,
            errors: formState.errors,
        };
    });

    return formState;
}

describe('Frontend Accessibility (WCAG 2.1 Level AA) Audit', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Returns/Start Component', () => {
        it('has zero accessibility violations in initial state', async () => {
            configureFormMock({ order_number: '', customer_dni: '' });
            const { container } = render(<Start />);

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });

        it('has zero accessibility violations with field-level and global validation errors', async () => {
            configureFormMock(
                { order_number: '', customer_dni: '' },
                {
                    errors: {
                        order_number: 'El número de pedido es obligatorio.',
                        customer_dni: 'El documento de identidad es inválido.',
                        login: 'No se encontró ninguna compra con las credenciales ingresadas.',
                    },
                }
            );

            const { container } = render(<Start />);

            expect(screen.getByText('No se encontró ninguna compra con las credenciales ingresadas.')).toBeInTheDocument();

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });

        it('has zero accessibility violations in loading/processing state', async () => {
            configureFormMock({ order_number: 'ORD-123456', customer_dni: '77665544' }, { processing: true });
            const { container } = render(<Start />);

            expect(screen.getByRole('button', { name: 'Verificando...' })).toBeDisabled();

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });
    });

    describe('Returns/Dashboard Component', () => {
        it('has zero accessibility violations in initial state (unselected items)', async () => {
            configureFormMock({ items: [], customer_notes: '', evidences: [] });
            const { container } = render(<Dashboard order={mockOrder} reasons={mockReasons} />);

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });

        it('has zero accessibility violations in items and reasons selection state', async () => {
            const user = userEvent.setup();
            configureFormMock({ items: [], customer_notes: '', evidences: [] });
            const { container } = render(<Dashboard order={mockOrder} reasons={mockReasons} />);

            const checkbox = screen.getByRole('checkbox', { name: /Cuaderno Anillado A4/i });
            await user.click(checkbox);

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });

        it('has zero accessibility violations with evidence files list and remove buttons', async () => {
            const mockFile = new File(['evidence image content'], 'daño_empaque.jpg', { type: 'image/jpeg' });
            configureFormMock({
                items: [
                    {
                        order_item_id: 'item-101',
                        return_reason_id: 'reason-1',
                        quantity: 2,
                        condition: 'damaged',
                    },
                ],
                customer_notes: 'Empaque roto al momento de la entrega',
                evidences: [mockFile],
            });

            const { container } = render(<Dashboard order={mockOrder} reasons={mockReasons} />);

            const removeBtn = screen.getByRole('button', { name: 'Eliminar evidencia adjunta' });
            expect(removeBtn).toBeInTheDocument();

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });

        it('has zero accessibility violations with visible validation errors', async () => {
            configureFormMock(
                { items: [], customer_notes: '', evidences: [] },
                {
                    errors: {
                        items: 'Debe seleccionar al menos un producto para la devolución.',
                        evidences: 'Debe adjuntar al menos una foto o documento comprobatorio.',
                        'evidences.0': 'El archivo adjunto excede el tamaño máximo permitido (5MB).',
                    },
                }
            );

            const { container } = render(<Dashboard order={mockOrder} reasons={mockReasons} />);

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });

        it('has zero accessibility violations in loading/processing state', async () => {
            configureFormMock(
                {
                    items: [
                        {
                            order_item_id: 'item-101',
                            return_reason_id: 'reason-1',
                            quantity: 1,
                            condition: 'sealed',
                        },
                    ],
                    customer_notes: 'Procesando devolución...',
                    evidences: [new File(['doc'], 'comprobante.pdf', { type: 'application/pdf' })],
                },
                { processing: true }
            );

            const { container } = render(<Dashboard order={mockOrder} reasons={mockReasons} />);

            expect(screen.getByRole('button', { name: 'Enviando...' })).toBeDisabled();

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });
    });

    describe('Returns/Success Component', () => {
        it('has zero accessibility violations in success confirmation state', async () => {
            const { container } = render(<Success trackingCode="DEV-2026-98765" />);

            const results = await axe(container);
            expect(results).toHaveNoViolations();
        });
    });
});
