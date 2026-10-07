import React from 'react';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@inertiajs/react';
import Tracking from '../Tracking';

let pageProps = { flash: {} };

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }) => null,
    Link: ({ children, href, ...props }) => <a href={href} {...props}>{children}</a>,
    useForm: vi.fn(),
    usePage: () => ({ props: pageProps }),
}));

afterEach(() => {
    cleanup();
    pageProps = { flash: {} };
});

const mockOrder = {
    order_id: 'ord-123',
    order_number: 'ORD-2026-99',
    order_date: '2026-09-15',
};

const mockBaseTicket = {
    ticket_id: 'tick-456',
    tracking_code: 'RET-TL-998877',
    current_status: 'received',
    created_at: '2026-09-20T10:00:00Z',
    customer_comment: 'El empaque llegó abierto y con fallas de fábrica.',
    return_items: [
        {
            return_item_id: 'item-1',
            quantity_to_return: 2,
            condition: 'sealed',
            order_item: {
                product_name: 'Cuaderno Anillado Tai Loy A4',
                product_code: 'CUA-TL-A4',
            },
            reason: {
                description: 'Producto defectuoso',
            },
        },
    ],
    evidences: [
        {
            evidence_id: 'ev-1',
            file_name: 'foto_caja_dañada.jpg',
        },
    ],
    status_history: [
        {
            history_id: 'hist-1',
            new_status: 'received',
            changed_at: '2026-09-20T10:00:00Z',
            comment: null,
        },
    ],
};

function configureForm(initialData = { evidences: [], customer_notes: '' }, overrides = {}) {
    const formState = {
        data: initialData,
        setData: vi.fn(),
        post: vi.fn(),
        reset: vi.fn(),
        processing: false,
        errors: {},
        ...overrides,
    };

    useForm.mockImplementation((defaults) => {
        const [data, setFormData] = React.useState(initialData ?? defaults);

        formState.setData.mockImplementation((field, value) => {
            if (typeof field === 'function') {
                setFormData(field);
            } else {
                setFormData((current) => ({ ...current, [field]: value }));
            }
        });

        formState.data = data;

        return {
            data,
            setData: formState.setData,
            post: formState.post,
            reset: formState.reset,
            processing: formState.processing,
            errors: formState.errors,
        };
    });

    return formState;
}

describe('Tracking Component (White-Box & Branch Coverage Suite)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        pageProps = { flash: {} };
    });

    describe('UI State Matrix & Stepper Timeline', () => {
        it('renders received status correctly with tracking details, items and status badge', () => {
            configureForm();
            render(<Tracking ticket={mockBaseTicket} order={mockOrder} />);

            expect(screen.getByRole('heading', { name: 'RET-TL-998877' })).toBeVisible();
            expect(screen.getByText('#ORD-2026-99')).toBeVisible();
            expect(screen.getAllByText('Recibido')[0]).toBeVisible();
            expect(screen.getByText('Cuaderno Anillado Tai Loy A4')).toBeVisible();
            expect(screen.getByText('Cant: 2')).toBeVisible();
            expect(screen.getByText('Motivo: Producto defectuoso')).toBeVisible();
            expect(screen.getByText('Condición: Sellado / Intacto')).toBeVisible();
            expect(screen.getByText('El empaque llegó abierto y con fallas de fábrica.')).toBeVisible();
        });

        it('renders under_review status with review badge and evaluation stepper active', () => {
            configureForm();
            const ticket = {
                ...mockBaseTicket,
                current_status: 'under_review',
                status_history: [
                    ...mockBaseTicket.status_history,
                    {
                        history_id: 'hist-2',
                        new_status: 'under_review',
                        changed_at: '2026-09-21T11:00:00Z',
                        comment: 'Revisión técnica iniciada',
                    },
                ],
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            expect(screen.getAllByText('En Revisión')[0]).toBeVisible();
            expect(screen.getByText('"Revisión técnica iniciada"')).toBeVisible();
        });

        it('renders approved status with approval message and indication details', () => {
            configureForm();
            const ticket = {
                ...mockBaseTicket,
                current_status: 'approved',
                status_history: [
                    ...mockBaseTicket.status_history,
                    {
                        history_id: 'hist-3',
                        new_status: 'approved',
                        changed_at: '2026-09-22T14:00:00Z',
                        comment: 'Nota de crédito N° NC-001 emitida.',
                    },
                ],
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            expect(screen.getAllByText('Aprobado')[0]).toBeVisible();
            expect(screen.getByRole('heading', { name: '¡Tu solicitud de devolución ha sido aprobada!' })).toBeVisible();
            expect(screen.getByText('Indicaciones: "Nota de crédito N° NC-001 emitida."')).toBeVisible();
        });

        it('renders rejected status with rejection indicator and support reason comment', () => {
            configureForm();
            const ticket = {
                ...mockBaseTicket,
                current_status: 'rejected',
                status_history: [
                    ...mockBaseTicket.status_history,
                    {
                        history_id: 'hist-3',
                        new_status: 'rejected',
                        changed_at: '2026-09-22T15:00:00Z',
                        comment: 'Producto presenta sellos violados y uso indebido.',
                    },
                ],
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            expect(screen.getAllByText('Rechazado')[0]).toBeVisible();
            expect(screen.getByRole('heading', { name: 'Solicitud no aprobada' })).toBeVisible();
            expect(screen.getByText('Motivo: "Producto presenta sellos violados y uso indebido."')).toBeVisible();
        });

        it('renders closed status with finalized message and dashboard action link', () => {
            configureForm();
            const ticket = {
                ...mockBaseTicket,
                current_status: 'closed',
                status_history: [
                    ...mockBaseTicket.status_history,
                    {
                        history_id: 'hist-4',
                        new_status: 'closed',
                        changed_at: '2026-09-25T10:00:00Z',
                        comment: 'Trámite finalizado por caducidad.',
                    },
                ],
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            expect(screen.getAllByText('Cerrado')[0]).toBeVisible();
            expect(screen.getByRole('heading', { name: 'Caso Finalizado y Cerrado' })).toBeVisible();
            expect(screen.getByText('Observación final: "Trámite finalizado por caducidad."')).toBeVisible();
            expect(screen.getByRole('link', { name: 'Registrar nueva solicitud' })).toHaveAttribute(
                'href',
                '/route/returns.dashboard'
            );
        });
    });

    describe('Additional Evidence Form Conditional Rendering', () => {
        it('renders form and interactive button when status is more_information_requested', () => {
            configureForm();
            const ticket = {
                ...mockBaseTicket,
                current_status: 'more_information_requested',
                status_history: [
                    ...mockBaseTicket.status_history,
                    {
                        history_id: 'hist-2',
                        new_status: 'more_information_requested',
                        changed_at: '2026-09-21T12:00:00Z',
                        comment: 'Adjuntar foto del código de barras.',
                    },
                ],
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            expect(screen.getByRole('heading', { name: 'Se requiere información adicional' })).toBeVisible();
            expect(screen.getAllByText('"Adjuntar foto del código de barras."')[0]).toBeVisible();
            expect(screen.getByLabelText('Comentario o Aclaración (Opcional)')).toBeVisible();
            expect(screen.getByLabelText(/Adjuntar Fotos o Documentos/)).toBeVisible();
            expect(
                screen.getByRole('button', { name: 'Enviar Información Solicitada' })
            ).toBeInTheDocument();
        });

        it.each(['received', 'under_review', 'approved', 'rejected', 'closed'])(
            'does NOT render evidence form or submit button when status is %s',
            (status) => {
                configureForm();
                const ticket = {
                    ...mockBaseTicket,
                    current_status: status,
                };

                render(<Tracking ticket={ticket} order={mockOrder} />);

                expect(
                    screen.queryByRole('button', { name: /enviar información solicitada|subir.*evidencia/i })
                ).toBeNull();
                expect(screen.queryByLabelText(/adjuntar fotos o documentos/i)).toBeNull();
                expect(screen.queryByLabelText(/comentario o aclaración/i)).toBeNull();
            }
        );
    });

    describe('Client File Interactions & Form Submission', () => {
        it('enables submit button when valid file is attached and allows form submission', async () => {
            const user = userEvent.setup();
            const form = configureForm({ customer_notes: '', evidences: [] });
            const ticket = {
                ...mockBaseTicket,
                current_status: 'more_information_requested',
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            const submitBtn = screen.getByRole('button', { name: 'Enviar Información Solicitada' });
            expect(submitBtn).toBeDisabled();

            await user.type(screen.getByLabelText('Comentario o Aclaración (Opcional)'), 'Foto adicional adjunta');
            const file = new File(['foto contenido'], 'boleta_adicional.png', { type: 'image/png' });
            fireEvent.change(screen.getByLabelText(/Adjuntar Fotos o Documentos/), {
                target: { files: [file] },
            });

            await waitFor(() => {
                expect(screen.getByText('boleta_adicional.png')).toBeVisible();
                expect(submitBtn).toBeEnabled();
            });

            await user.click(submitBtn);

            expect(form.post).toHaveBeenCalledWith(
                '/route/returns.tickets.evidence',
                expect.objectContaining({
                    onSuccess: expect.any(Function),
                })
            );
        });

        it('allows removing an attached file and disables submit button when empty', async () => {
            const user = userEvent.setup();
            configureForm({ customer_notes: '', evidences: [] });
            const ticket = {
                ...mockBaseTicket,
                current_status: 'more_information_requested',
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            const file = new File(['documento'], 'manual.pdf', { type: 'application/pdf' });
            fireEvent.change(screen.getByLabelText(/Adjuntar Fotos o Documentos/), {
                target: { files: [file] },
            });

            await waitFor(() => expect(screen.getByText('manual.pdf')).toBeVisible());

            const removeBtn = screen.getByRole('button', { name: 'Quitar archivo manual.pdf' });
            await user.click(removeBtn);

            await waitFor(() => {
                expect(screen.queryByText('manual.pdf')).not.toBeInTheDocument();
                expect(screen.getByRole('button', { name: 'Enviar Información Solicitada' })).toBeDisabled();
            });
        });

        it('disables submission button and renders loading label when request is processing', () => {
            configureForm({ customer_notes: '', evidences: [new File([''], 'doc.pdf')] }, { processing: true });
            const ticket = {
                ...mockBaseTicket,
                current_status: 'more_information_requested',
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            const submitBtn = screen.getByRole('button', { name: 'Enviando información...' });
            expect(submitBtn).toBeDisabled();
        });
    });

    describe('Server Error Handling & Flash Messages', () => {
        it('renders server validation errors for customer_notes and evidences', () => {
            configureForm(
                { customer_notes: '', evidences: [] },
                {
                    errors: {
                        customer_notes: 'El comentario no puede exceder 500 caracteres.',
                        evidences: 'Debe adjuntar al menos una evidencia.',
                        'evidences.0': 'El archivo excede el límite de 5MB permitible.',
                    },
                }
            );

            const ticket = {
                ...mockBaseTicket,
                current_status: 'more_information_requested',
            };

            render(<Tracking ticket={ticket} order={mockOrder} />);

            expect(screen.getByText('El comentario no puede exceder 500 caracteres.')).toBeVisible();
            expect(screen.getByText('Debe adjuntar al menos una evidencia.')).toBeVisible();
            expect(screen.getByText('El archivo excede el límite de 5MB permitible.')).toBeVisible();
        });

        it('renders flash error, info and success alert banners when present', () => {
            configureForm();
            pageProps = {
                flash: {
                    error: 'Error crítico en el servidor.',
                    info: 'Tu solicitud está en cola de procesamiento.',
                    success: 'Información complementaria enviada con éxito.',
                },
            };

            render(<Tracking ticket={mockBaseTicket} order={mockOrder} />);

            expect(screen.getByRole('alert')).toHaveTextContent('Error crítico en el servidor.');
            const statusBanners = screen.getAllByRole('status');
            expect(statusBanners[0]).toHaveTextContent('Tu solicitud está en cola de procesamiento.');
            expect(statusBanners[1]).toHaveTextContent('Información complementaria enviada con éxito.');
        });
    });

    describe('Clipboard Interaction & Evidence Links', () => {
        it('copies tracking code to clipboard when copy button is clicked', async () => {
            const user = userEvent.setup();
            const writeTextMock = vi.fn().mockResolvedValue(undefined);
            Object.defineProperty(navigator, 'clipboard', {
                value: { writeText: writeTextMock },
                writable: true,
                configurable: true,
            });

            configureForm();
            render(<Tracking ticket={mockBaseTicket} order={mockOrder} />);

            const copyBtn = screen.getByRole('button', { name: 'Copiar código de seguimiento' });
            await user.click(copyBtn);

            expect(writeTextMock).toHaveBeenCalledWith('RET-TL-998877');
            expect(screen.getByText('✓ ¡Copiado!')).toBeVisible();
        });

        it('renders accessible link to view attached evidence file', () => {
            configureForm();
            render(<Tracking ticket={mockBaseTicket} order={mockOrder} />);

            const evidenceLink = screen.getByRole('link', { name: /Abrir foto_caja_dañada\.jpg/i });
            expect(evidenceLink).toBeInTheDocument();
            expect(evidenceLink).toHaveAttribute('href', '/route/returns.evidences.show');
            expect(evidenceLink).toHaveAttribute('target', '_blank');
            expect(evidenceLink).toHaveAttribute('rel', 'noopener noreferrer');
        });
    });
});
