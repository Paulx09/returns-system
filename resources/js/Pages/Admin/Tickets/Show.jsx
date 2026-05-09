import { Head, Link, useForm } from '@inertiajs/react';

const STATUS_CONFIG = {
    received:                   { label: 'Recibido',         bg: 'bg-blue-100',   text: 'text-blue-800'   },
    under_review:               { label: 'En Revisión',      bg: 'bg-yellow-100', text: 'text-yellow-800' },
    approved:                   { label: 'Aprobado',         bg: 'bg-green-100',  text: 'text-green-800'  },
    rejected:                   { label: 'Rechazado',        bg: 'bg-red-100',    text: 'text-red-800'    },
    more_information_requested: { label: 'Info. Solicitada', bg: 'bg-orange-100', text: 'text-orange-800' },
    closed:                     { label: 'Cerrado',          bg: 'bg-gray-100',   text: 'text-gray-600'   },
};

const CONDITION_LABELS = {
    sealed:  'Sellado / Intacto',
    opened:  'Abierto / Usado',
    damaged: 'Dañado de fábrica',
};

function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] ?? { label: status, bg: 'bg-gray-100', text: 'text-gray-600' };
    return (
        <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${config.bg} ${config.text}`}>
            {config.label}
        </span>
    );
}

function SectionCard({ title, children }) {
    return (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">{title}</h2>
            </div>
            <div className="px-6 py-5">{children}</div>
        </div>
    );
}

export default function Show({ ticket, statuses }) {
    const commentRequired = ['rejected', 'more_information_requested'];

    const { data, setData, patch, processing, errors } = useForm({
        new_status: '',
        comment:    '',
    });

    const submitStatus = (e) => {
        e.preventDefault();
        patch(route('admin.tickets.update-status', ticket.ticket_id));
    };

    const isCommentRequired = commentRequired.includes(data.new_status);

    return (
        <div className="min-h-screen bg-gray-50">
            <Head title={`Ticket ${ticket.tracking_code} — Admin`} />

            {/* Header */}
            <header className="bg-white shadow-sm border-b-4 border-blue-700">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('admin.tickets.index')}
                            className="text-blue-700 hover:text-blue-900 text-sm font-medium"
                        >
                            ← Volver al listado
                        </Link>
                        <span className="text-gray-300">|</span>
                        <h1 className="text-lg font-bold text-gray-900 font-mono">{ticket.tracking_code}</h1>
                        <StatusBadge status={ticket.current_status} />
                    </div>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-sm text-gray-500 hover:text-gray-700 underline"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </header>

            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

                {/* Flash success */}
                {typeof window !== 'undefined' && new URLSearchParams(window.location.search).get('success') && (
                    <div className="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                        Estado actualizado correctamente.
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {/* ── Columna izquierda (2/3) ─────────────────────── */}
                    <div className="lg:col-span-2 space-y-6">

                        {/* Datos del pedido */}
                        <SectionCard title="Información del Pedido">
                            <dl className="grid grid-cols-2 gap-4">
                                <div>
                                    <dt className="text-xs text-gray-500 font-medium">N° de Pedido</dt>
                                    <dd className="mt-1 text-sm font-semibold text-gray-900">{ticket.order?.order_number}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-gray-500 font-medium">Fecha de Compra</dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {ticket.order?.order_date
                                            ? new Date(ticket.order.order_date).toLocaleDateString('es-PE')
                                            : '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-gray-500 font-medium">Código de Seguimiento</dt>
                                    <dd className="mt-1 text-sm font-mono font-semibold text-blue-700">{ticket.tracking_code}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-gray-500 font-medium">Fecha de Solicitud</dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {new Date(ticket.created_at).toLocaleDateString('es-PE')}
                                    </dd>
                                </div>
                            </dl>
                            {ticket.customer_comment && (
                                <div className="mt-4 p-3 bg-gray-50 rounded-md border border-gray-100">
                                    <p className="text-xs text-gray-500 font-medium mb-1">Comentario del cliente</p>
                                    <p className="text-sm text-gray-800">{ticket.customer_comment}</p>
                                </div>
                            )}
                        </SectionCard>

                        {/* Items a devolver */}
                        <SectionCard title="Productos a Devolver">
                            {ticket.return_items?.length > 0 ? (
                                <ul className="divide-y divide-gray-100">
                                    {ticket.return_items.map(item => (
                                        <li key={item.return_item_id ?? item.id} className="py-3">
                                            <div className="flex justify-between items-start">
                                                <div>
                                                    <p className="text-sm font-semibold text-gray-900">
                                                        {item.order_item?.product_name ?? 'Producto'}
                                                    </p>
                                                    <p className="text-xs text-gray-500 mt-0.5">
                                                        Motivo: <span className="font-medium">{item.reason?.description ?? '—'}</span>
                                                        {' · '}
                                                        Condición: <span className="font-medium">{CONDITION_LABELS[item.condition] ?? item.condition}</span>
                                                    </p>
                                                </div>
                                                <span className="text-sm font-semibold text-gray-700 ml-4">
                                                    × {item.quantity_to_return}
                                                </span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500">No hay items registrados.</p>
                            )}
                        </SectionCard>

                    </div>

                    {/* ── Columna derecha (1/3) ─────────────────────────── */}
                    <div className="space-y-6">

                        {/* Evidencias */}
                        <SectionCard title="Evidencias">
                            {ticket.evidences?.length > 0 ? (
                                <ul className="space-y-2">
                                    {ticket.evidences.map(ev => (
                                        <li key={ev.evidence_id ?? ev.id}>
                                            <a
                                                href={route('admin.evidences.show', ev.evidence_id ?? ev.id)}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="flex items-center gap-2 text-sm text-blue-700 hover:text-blue-900 hover:underline"
                                            >
                                                <span className="text-lg">📎</span>
                                                <span className="truncate">{ev.file_name}</span>
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500">Sin evidencias adjuntas.</p>
                            )}
                        </SectionCard>

                        {/* Historial de estados */}
                        <SectionCard title="Historial de Estados">
                            {ticket.status_history?.length > 0 ? (
                                <ol className="relative border-l border-gray-200 ml-2 space-y-4">
                                    {ticket.status_history.map(entry => (
                                        <li key={entry.history_id} className="ml-4">
                                            <div className="absolute -left-1.5 mt-1 w-3 h-3 rounded-full bg-blue-600 border-2 border-white" />
                                            <div>
                                                <StatusBadge status={entry.new_status} />
                                                <p className="text-xs text-gray-400 mt-1">
                                                    {new Date(entry.changed_at).toLocaleString('es-PE')}
                                                    {entry.changed_by && ` · ${entry.changed_by.full_name}`}
                                                </p>
                                                {entry.comment && (
                                                    <p className="text-xs text-gray-600 mt-1 italic">"{entry.comment}"</p>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            ) : (
                                <p className="text-sm text-gray-500">Sin cambios de estado registrados.</p>
                            )}
                        </SectionCard>

                        {/* Formulario de cambio de estado */}
                        <div className="bg-white rounded-lg shadow-sm border-2 border-blue-200 overflow-hidden">
                            <div className="px-6 py-4 border-b border-blue-100 bg-blue-50">
                                <h2 className="text-sm font-semibold text-blue-900 uppercase tracking-wider">Actualizar Estado</h2>
                            </div>
                            <form onSubmit={submitStatus} className="px-6 py-5 space-y-4">
                                <div>
                                    <label htmlFor="new-status" className="block text-xs font-medium text-gray-700 mb-1">
                                        Nuevo estado <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="new-status"
                                        value={data.new_status}
                                        onChange={e => setData('new_status', e.target.value)}
                                        required
                                        className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Seleccione...</option>
                                        {statuses
                                            .filter(s => s !== 'received') // received es el estado inicial, no se puede volver
                                            .map(s => (
                                                <option key={s} value={s}>{STATUS_CONFIG[s]?.label ?? s}</option>
                                            ))
                                        }
                                    </select>
                                    {errors.new_status && (
                                        <p className="mt-1 text-xs text-red-600">{errors.new_status}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="comment" className="block text-xs font-medium text-gray-700 mb-1">
                                        Comentario
                                        {isCommentRequired
                                            ? <span className="text-red-500 ml-1">* (requerido)</span>
                                            : <span className="text-gray-400 ml-1">(opcional)</span>
                                        }
                                    </label>
                                    <textarea
                                        id="comment"
                                        rows={3}
                                        value={data.comment}
                                        onChange={e => setData('comment', e.target.value)}
                                        required={isCommentRequired}
                                        placeholder={isCommentRequired ? 'Explique el motivo...' : 'Observaciones adicionales...'}
                                        className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    {errors.comment && (
                                        <p className="mt-1 text-xs text-red-600">{errors.comment}</p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing || !data.new_status}
                                    className="w-full py-2.5 px-4 bg-blue-700 text-white text-sm font-semibold rounded-md hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                >
                                    {processing ? 'Guardando...' : 'Actualizar Estado'}
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    );
}
