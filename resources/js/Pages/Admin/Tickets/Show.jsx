import React, { useEffect, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const ALLOWED_TRANSITIONS = {
    received:                   ['under_review', 'rejected'],
    under_review:               ['more_information_requested', 'approved', 'rejected'],
    more_information_requested: ['under_review', 'closed'],
    approved:                   ['closed'],
    rejected:                   [],
    closed:                     [],
};

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
    sellado: 'Sellado / Intacto',
    abierto: 'Abierto / Usado',
    dañado:  'Dañado de fábrica',
    danado:  'Dañado de fábrica',
};

function getConditionLabel(condition) {
    if (!condition) return 'No especificada';
    const key = String(condition).toLowerCase().trim();
    return CONDITION_LABELS[key] ?? CONDITION_LABELS[condition] ?? condition;
}

function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] ?? { label: status, bg: 'bg-gray-100', text: 'text-gray-600' };
    return (
        <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${config.bg} ${config.text}`}>
            {config.label}
        </span>
    );
}

function SectionCard({ title, children, borderColorClass = "border-t-4 border-t-[#05a060]" }) {
    return (
        <div className={`bg-white rounded-lg shadow-sm border border-gray-200 ${borderColorClass} overflow-hidden`}>
            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">{title}</h2>
            </div>
            <div className="px-6 py-5">{children}</div>
        </div>
    );
}

const ZOOM_CLASSES = {
    75:  'scale-75',
    100: 'scale-100',
    125: 'scale-125',
    150: 'scale-150',
};

const ROTATION_CLASSES = {
    0:   'rotate-0',
    90:  'rotate-90',
    180: 'rotate-180',
    270: '-rotate-90',
};

function isPdfEvidence(evidence) {
    const mime = String(evidence?.mime_type ?? '').toLowerCase();
    const name = String(evidence?.file_name ?? '').toLowerCase();
    return mime === 'application/pdf' || name.endsWith('.pdf');
}

function EvidenceModal({ evidence, onClose }) {
    const [zoomLevel, setZoomLevel] = useState(100);
    const [rotation, setRotation] = useState(0);

    useEffect(() => {
        if (!evidence) return undefined;
        setZoomLevel(100);
        setRotation(0);

        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [evidence, onClose]);

    if (!evidence) return null;

    const evidenceId = evidence.evidence_id ?? evidence.id;
    const inlineUrl = route('admin.evidences.show', evidenceId);
    const downloadUrl = `${inlineUrl}${inlineUrl.includes('?') ? '&' : '?'}download=1`;
    const isPdf = isPdfEvidence(evidence);

    const zoomIn = () => setZoomLevel(prev => (prev < 150 ? prev + 25 : 150));
    const zoomOut = () => setZoomLevel(prev => (prev > 75 ? prev - 25 : 75));
    const rotateRight = () => setRotation(prev => (prev + 90) % 360);
    const resetTransform = () => {
        setZoomLevel(100);
        setRotation(0);
    };

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={`Visor de evidencia ${evidence.file_name}`}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm p-4"
            onClick={onClose}
        >
            <div
                className="bg-white rounded-xl shadow-2xl max-w-4xl w-full overflow-hidden border-t-4 border-t-[#fbdb04] flex flex-col max-h-[90vh]"
                onClick={e => e.stopPropagation()}
            >
                <div className="px-6 py-4 bg-gray-50 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <h3 className="text-sm font-bold text-gray-900 truncate">{evidence.file_name}</h3>
                        <p className="text-xs text-gray-500">{isPdf ? 'Documento PDF' : 'Imagen de evidencia'}</p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {!isPdf && (
                            <>
                                <button
                                    type="button"
                                    onClick={zoomOut}
                                    aria-label="Alejar imagen"
                                    className="px-2.5 py-1.5 text-xs font-semibold bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100"
                                >
                                    - Zoom
                                </button>
                                <span className="text-xs font-mono text-gray-600 px-1">{zoomLevel}%</span>
                                <button
                                    type="button"
                                    onClick={zoomIn}
                                    aria-label="Acercar imagen"
                                    className="px-2.5 py-1.5 text-xs font-semibold bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100"
                                >
                                    + Zoom
                                </button>
                                <button
                                    type="button"
                                    onClick={rotateRight}
                                    aria-label="Rotar imagen"
                                    className="px-2.5 py-1.5 text-xs font-semibold bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100"
                                >
                                    Rotar 90°
                                </button>
                                <button
                                    type="button"
                                    onClick={resetTransform}
                                    aria-label="Restablecer vista"
                                    className="px-2.5 py-1.5 text-xs font-semibold bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-100"
                                >
                                    Restablecer
                                </button>
                            </>
                        )}

                        <a
                            href={downloadUrl}
                            download={evidence.file_name}
                            className="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-md text-[#002E6E] bg-[#fbdb04] hover:bg-[#05a060] hover:text-white transition-colors"
                        >
                            Descargar archivo
                        </a>

                        <button
                            type="button"
                            onClick={onClose}
                            aria-label="Cerrar visor"
                            className="px-3 py-1.5 text-xs font-semibold bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>

                <div className="p-6 bg-gray-900/95 flex-grow overflow-auto flex items-center justify-center min-h-[360px]">
                    {isPdf ? (
                        <iframe
                            src={inlineUrl}
                            title={evidence.file_name}
                            className="w-full h-[65vh] rounded bg-white border border-gray-300"
                        />
                    ) : (
                        <img
                            src={inlineUrl}
                            alt={evidence.file_name}
                            className={`max-h-[65vh] max-w-full object-contain transition-transform duration-200 transform ${ZOOM_CLASSES[zoomLevel] ?? 'scale-100'} ${ROTATION_CLASSES[rotation] ?? 'rotate-0'}`}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}

export default function Show({ ticket, statuses = [], allowed_transitions, auth }) {
    const commentRequired = ['rejected', 'more_information_requested'];
    const [selectedEvidence, setSelectedEvidence] = useState(null);
    const page = typeof usePage === 'function' ? usePage() : null;
    const currentUser = auth?.user ?? page?.props?.auth?.user;
    const isAdmin = currentUser?.role === 'admin';

    const rawAllowedTransitions = Array.isArray(allowed_transitions)
        ? allowed_transitions
        : Array.isArray(ticket?.allowed_transitions)
            ? ticket.allowed_transitions
            : (ALLOWED_TRANSITIONS[ticket?.current_status] ?? []);

    const availableStatuses = (Array.isArray(statuses) && statuses.length > 0 ? statuses : rawAllowedTransitions)
        .filter(s => rawAllowedTransitions.includes(s))
        .filter(s => isAdmin || s !== 'closed');

    const { data, setData, patch, processing, errors } = useForm({
        new_status: '',
        comment:    '',
    });

    const submitStatus = (e) => {
        e.preventDefault();
        patch(route('admin.tickets.update-status', ticket.ticket_id));
    };

    const isCommentRequired = commentRequired.includes(data.new_status);
    const isSubmitDisabled = processing || !data.new_status || (isCommentRequired && !data.comment.trim());

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            <Head title={`Ticket ${ticket.tracking_code} — Admin`} />

            {/* Header */}
            <header className="bg-[#05a060] shadow-md w-full">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
                    <img 
                        src="/logo1.webp" 
                        alt="Tai Loy" 
                        className="h-10 w-auto object-contain" 
                    />
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-bold rounded-md text-[#002E6E] bg-[#fbdb04] hover:bg-[#05a060] hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#05a060] transition-colors duration-200"
                    >
                        Cerrar Sesión
                    </Link>
                </div>
            </header>

            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 flex-grow">

                {/* Navigation/Title bar */}
                <div className="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-gray-200">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('admin.tickets.index')}
                            className="text-[#05a060] hover:text-[#04854f] text-sm font-semibold flex items-center gap-1"
                        >
                            ← Volver al listado
                        </Link>
                        <span className="text-gray-300">|</span>
                        <h1 className="text-xl font-bold text-gray-900 font-mono">{ticket.tracking_code}</h1>
                        <StatusBadge status={ticket.current_status} />
                    </div>
                </div>

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
                        <SectionCard title="Información del Pedido" borderColorClass="border-t-4 border-t-[#fbdb04]">
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
                                    <dd className="mt-1 text-sm font-mono font-semibold text-[#05a060]">{ticket.tracking_code}</dd>
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
                                                        Condición: <span className="font-medium">{getConditionLabel(item.condition)}</span>
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
                                    {ticket.evidences.map(ev => {
                                        const evId = ev.evidence_id ?? ev.id;
                                        const inlineUrl = route('admin.evidences.show', evId);
                                        const downloadUrl = `${inlineUrl}${inlineUrl.includes('?') ? '&' : '?'}download=1`;
                                        return (
                                            <li key={evId} className="flex items-center justify-between gap-2 p-2 rounded-md border border-gray-100 bg-gray-50 hover:bg-gray-100 transition">
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedEvidence(ev)}
                                                    className="flex items-center gap-2 text-sm text-[#05a060] hover:text-[#04854f] hover:underline font-medium min-w-0 text-left"
                                                >
                                                    <span className="text-lg">📎</span>
                                                    <span className="truncate">{ev.file_name}</span>
                                                </button>
                                                <a
                                                    href={downloadUrl}
                                                    download={ev.file_name}
                                                    aria-label={`Descargar ${ev.file_name}`}
                                                    className="shrink-0 text-xs font-semibold text-[#002E6E] hover:underline px-2 py-1 rounded bg-[#fbdb04]/40 hover:bg-[#fbdb04]"
                                                >
                                                    Descargar
                                                </a>
                                            </li>
                                        );
                                    })}
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
                                            <div className="absolute -left-1.5 mt-1 w-3 h-3 rounded-full bg-[#05a060] border-2 border-white" />
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
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 border-t-4 border-t-[#05a060] overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">Actualizar Estado</h2>
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
                                        className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-[#05a060] focus:border-[#05a060]"
                                    >
                                        <option value="">Seleccione...</option>
                                        {availableStatuses.map(s => (
                                            <option key={s} value={s}>{STATUS_CONFIG[s]?.label ?? s}</option>
                                        ))}
                                    </select>
                                    {!isAdmin && rawAllowedTransitions.includes('closed') && (
                                        <p className="mt-1 text-xs text-amber-700">
                                            Solo administradores pueden cerrar solicitudes.
                                        </p>
                                    )}
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
                                        className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:ring-[#05a060] focus:border-[#05a060]"
                                    />
                                    {errors.comment && (
                                        <p className="mt-1 text-xs text-red-600">{errors.comment}</p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={isSubmitDisabled}
                                    className="w-full py-2.5 px-4 bg-[#fbdb04] text-[#002E6E] text-sm font-bold rounded-md hover:bg-[#05a060] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition duration-200"
                                >
                                    {processing ? 'Guardando...' : 'Actualizar Estado'}
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </main>

            <EvidenceModal
                evidence={selectedEvidence}
                onClose={() => setSelectedEvidence(null)}
            />
        </div>
    );
}
