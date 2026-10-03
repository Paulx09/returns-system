import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

const STATUS_CONFIG = {
    received: {
        label: 'Recibido',
        badgeBg: 'bg-blue-100 text-blue-800 border-blue-200',
        dotBg: 'bg-blue-500',
        stepIndex: 0,
    },
    under_review: {
        label: 'En Revisión',
        badgeBg: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        dotBg: 'bg-yellow-500',
        stepIndex: 1,
    },
    more_information_requested: {
        label: 'Información Solicitada',
        badgeBg: 'bg-orange-100 text-orange-800 border-orange-200',
        dotBg: 'bg-orange-500',
        stepIndex: 1,
    },
    approved: {
        label: 'Aprobado',
        badgeBg: 'bg-green-100 text-green-800 border-green-200',
        dotBg: 'bg-green-500',
        stepIndex: 2,
    },
    rejected: {
        label: 'Rechazado',
        badgeBg: 'bg-red-100 text-red-800 border-red-200',
        dotBg: 'bg-red-500',
        stepIndex: 2,
    },
    closed: {
        label: 'Cerrado',
        badgeBg: 'bg-gray-100 text-gray-700 border-gray-200',
        dotBg: 'bg-gray-500',
        stepIndex: 3,
    },
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

function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

export default function Tracking({ ticket, order }) {
    const { flash } = usePage().props;
    const [copied, setCopied] = useState(false);

    const currentStatus = ticket.current_status;
    const statusMeta = STATUS_CONFIG[currentStatus] ?? {
        label: currentStatus,
        badgeBg: 'bg-gray-100 text-gray-700 border-gray-200',
        dotBg: 'bg-gray-500',
        stepIndex: 0,
    };

    // Formulario para evidencias complementarias cuando el estado es 'more_information_requested'
    const {
        data: infoData,
        setData: setInfoData,
        post: postInfo,
        processing: infoProcessing,
        errors: infoErrors,
        reset: resetInfo,
    } = useForm({
        evidences: [],
        customer_notes: '',
    });

    const handleCopy = () => {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(ticket.tracking_code);
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);
        }
    };

    const submitAdditionalEvidence = (e) => {
        e.preventDefault();
        postInfo(route('returns.tickets.evidence', ticket.ticket_id), {
            onSuccess: () => resetInfo(),
        });
    };

    const removeNewEvidence = (idxToRemove) => {
        setInfoData('evidences', infoData.evidences.filter((_, idx) => idx !== idxToRemove));
    };

    // Stepper definition
    const steps = [
        { label: 'Recibido', desc: 'Solicitud enviada' },
        { label: 'En Evaluación', desc: 'Revisión por soporte' },
        {
            label: currentStatus === 'rejected' ? 'Rechazado' : 'Resolución',
            desc: currentStatus === 'approved' ? 'Aprobado' : (currentStatus === 'rejected' ? 'No aprobado' : 'Dictamen'),
        },
        { label: 'Finalizado', desc: 'Caso cerrado' },
    ];

    const activeStep = statusMeta.stepIndex;

    // Latest comment from support (if any in history)
    const latestComment = ticket.status_history?.find(h => !!h.comment)?.comment;

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            <Head title={`Seguimiento ${ticket.tracking_code} — Tai Loy`} />

            {/* Header */}
            <header className="bg-[#05a060] shadow-md w-full">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
                    <img
                        src="/logo2.webp"
                        alt="Logo de Tai Loy"
                        className="h-10 w-auto object-contain"
                    />
                    <Link
                        href={route('returns.logout')}
                        method="post"
                        as="button"
                        className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-bold rounded-md text-[#002E6E] bg-[#fbdb04] hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fbdb04] transition-colors duration-200"
                    >
                        Cerrar Sesión
                    </Link>
                </div>
            </header>

            <main className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 flex-grow w-full">
                {/* Flash Messages */}
                {flash?.error && (
                    <div role="alert" className="p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm font-medium">
                        {flash.error}
                    </div>
                )}
                {flash?.info && (
                    <div role="status" className="p-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-sm font-medium">
                        {flash.info}
                    </div>
                )}
                {flash?.success && (
                    <div role="status" className="p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm font-medium">
                        {flash.success}
                    </div>
                )}

                {/* Tracking Hero Card */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-200 border-t-8 border-t-[#05a060] p-6 sm:p-8">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-gray-200">
                        <div>
                            <p className="text-xs uppercase font-bold tracking-wider text-gray-500 mb-1">
                                Seguimiento de Devolución
                            </p>
                            <div className="flex flex-wrap items-center gap-3">
                                <h1 className="text-2xl sm:text-3xl font-mono font-extrabold text-gray-900 tracking-wider">
                                    {ticket.tracking_code}
                                </h1>
                                <button
                                    type="button"
                                    onClick={handleCopy}
                                    className="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-md border border-gray-300 transition"
                                    title="Copiar código al portapapeles"
                                    aria-label="Copiar código de seguimiento"
                                >
                                    {copied ? (
                                        <span className="text-green-700 font-bold">✓ ¡Copiado!</span>
                                    ) : (
                                        <>
                                            <svg className="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span>Copiar</span>
                                        </>
                                    )}
                                </button>
                            </div>
                            <p className="text-sm text-gray-500 mt-2">
                                Pedido <span className="font-semibold text-gray-800">#{order?.order_number}</span>
                                {' · '}
                                Registrado el {new Date(ticket.created_at).toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' })}
                            </p>
                        </div>

                        <div className="flex flex-col sm:items-end">
                            <span className="text-xs font-medium text-gray-500 mb-1">Estado actual</span>
                            <span className={`inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold border shadow-sm ${statusMeta.badgeBg}`}>
                                <span className={`w-2 h-2 rounded-full mr-2 ${statusMeta.dotBg}`}></span>
                                {statusMeta.label}
                            </span>
                        </div>
                    </div>

                    {/* Stepper de Progreso */}
                    <div className="pt-8 pb-4">
                        <div className="grid grid-cols-4 relative" aria-label="Progreso de la solicitud">
                            {/* Line connecting steps */}
                            <div className="absolute top-4 left-0 right-0 h-1 bg-gray-200 -z-0 mx-8">
                                <div
                                    className="h-full bg-[#05a060] transition-all duration-500"
                                    style={{
                                        width: `${(activeStep / (steps.length - 1)) * 100}%`,
                                    }}
                                />
                            </div>

                            {steps.map((step, idx) => {
                                const isCompleted = idx < activeStep;
                                const isCurrent = idx === activeStep;

                                return (
                                    <div key={step.label} className="flex flex-col items-center text-center relative z-10">
                                        <div
                                            className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition-colors duration-200 ${
                                                isCompleted
                                                    ? 'bg-[#05a060] text-white'
                                                    : isCurrent
                                                    ? 'bg-[#fbdb04] text-[#002E6E] ring-4 ring-yellow-100 font-extrabold'
                                                    : 'bg-gray-200 text-gray-500'
                                            }`}
                                        >
                                            {isCompleted ? '✓' : idx + 1}
                                        </div>
                                        <p className={`mt-2 text-xs sm:text-sm font-bold ${isCurrent ? 'text-gray-900' : 'text-gray-600'}`}>
                                            {step.label}
                                        </p>
                                        <p className="hidden sm:block text-xs text-gray-400 mt-0.5 max-w-[100px]">
                                            {step.desc}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Banner Contextual según Estado */}
                {currentStatus === 'more_information_requested' && (
                    <div className="bg-orange-50 border-l-4 border-orange-500 p-6 rounded-r-xl shadow-sm">
                        <div className="flex items-start">
                            <div className="flex-shrink-0 text-orange-500 text-2xl font-bold" aria-hidden="true">⚠️</div>
                            <div className="ml-3 flex-1">
                                <h2 className="text-base font-bold text-orange-950">
                                    Se requiere información adicional
                                </h2>
                                <p className="text-sm text-orange-900 mt-1">
                                    Nuestro equipo de atención al cliente está revisando tu caso y necesita precisiones para continuar:
                                </p>
                                {latestComment && (
                                    <div className="mt-3 p-3.5 bg-white bg-opacity-90 rounded-lg border border-orange-200 text-sm font-medium text-orange-950 italic">
                                        "{latestComment}"
                                    </div>
                                )}

                                {/* Formulario para adjuntar nuevas evidencias */}
                                <form
                                    onSubmit={submitAdditionalEvidence}
                                    className="mt-6 pt-5 border-t border-orange-200 space-y-4"
                                    encType="multipart/form-data"
                                    noValidate
                                >
                                    <h3 className="text-sm font-bold text-gray-900">
                                        Enviar información y evidencias solicitadas
                                    </h3>

                                    <div>
                                        <label htmlFor="customer_notes" className="block text-xs font-semibold text-gray-700">
                                            Comentario o Aclaración (Opcional)
                                        </label>
                                        <textarea
                                            id="customer_notes"
                                            rows={2}
                                            value={infoData.customer_notes}
                                            onChange={e => setInfoData('customer_notes', e.target.value)}
                                            placeholder="Escribe aquí cualquier observación o detalle adicional..."
                                            className="mt-1 block w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-[#05a060] focus:border-[#05a060]"
                                        />
                                        {infoErrors.customer_notes && (
                                            <p className="mt-1 text-xs text-red-600 font-medium">{infoErrors.customer_notes}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label htmlFor="additional_evidences" className="block text-xs font-semibold text-gray-700">
                                            Adjuntar Fotos o Documentos (1 a 5 archivos, máx 5MB c/u, JPG/PNG/PDF)
                                        </label>
                                        <input
                                            id="additional_evidences"
                                            type="file"
                                            multiple
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            onChange={e => setInfoData('evidences', Array.from(e.target.files))}
                                            className="mt-1 block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#05a060] file:text-white hover:file:bg-[#04854f]"
                                        />
                                        {infoErrors.evidences && (
                                            <p className="mt-1 text-xs text-red-600 font-medium">{infoErrors.evidences}</p>
                                        )}
                                        {Object.keys(infoErrors).filter(k => k.startsWith('evidences.')).map(k => (
                                            <p key={k} className="mt-1 text-xs text-red-600 font-medium">{infoErrors[k]}</p>
                                        ))}

                                        {infoData.evidences.length > 0 && (
                                            <ul className="mt-2.5 space-y-1.5" aria-label="Nuevas evidencias seleccionadas">
                                                {infoData.evidences.map((file, idx) => (
                                                    <li key={idx} className="flex items-center justify-between bg-white border border-orange-200 px-3 py-1.5 rounded-lg text-xs">
                                                        <span className="truncate max-w-xs text-gray-700">{file.name}</span>
                                                        <button
                                                            type="button"
                                                            onClick={() => removeNewEvidence(idx)}
                                                            className="text-red-500 hover:text-red-700 p-0.5 text-xs font-bold"
                                                            aria-label={`Quitar archivo ${file.name}`}
                                                        >
                                                            ✕
                                                        </button>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={infoProcessing || infoData.evidences.length === 0}
                                        className="inline-flex items-center justify-center px-4 py-2.5 bg-[#05a060] text-white text-xs font-bold rounded-lg hover:bg-[#04854f] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#05a060] disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm"
                                    >
                                        {infoProcessing ? 'Enviando información...' : 'Enviar Información Solicitada'}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                )}

                {currentStatus === 'approved' && (
                    <div className="bg-green-50 border-l-4 border-green-500 p-5 rounded-r-lg shadow-sm">
                        <div className="flex items-start">
                            <div className="flex-shrink-0 text-green-600 text-xl font-bold">✓</div>
                            <div className="ml-3">
                                <h2 className="text-base font-bold text-green-900">
                                    ¡Tu solicitud de devolución ha sido aprobada!
                                </h2>
                                <p className="text-sm text-green-800 mt-1">
                                    Hemos validado los productos y evidencias presentadas.
                                </p>
                                {latestComment && (
                                    <div className="mt-3 p-3 bg-white bg-opacity-80 rounded border border-green-200 text-sm font-medium text-green-950">
                                        Indicaciones: "{latestComment}"
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {currentStatus === 'rejected' && (
                    <div className="bg-red-50 border-l-4 border-red-500 p-5 rounded-r-lg shadow-sm">
                        <div className="flex items-start">
                            <div className="flex-shrink-0 text-red-500 text-xl font-bold">✕</div>
                            <div className="ml-3">
                                <h2 className="text-base font-bold text-red-900">
                                    Solicitud no aprobada
                                </h2>
                                <p className="text-sm text-red-800 mt-1">
                                    Luego de la evaluación técnica, la devolución no pudo ser aprobada bajo los términos de garantía.
                                </p>
                                {latestComment && (
                                    <div className="mt-3 p-3 bg-white bg-opacity-80 rounded border border-red-200 text-sm font-medium text-red-950">
                                        Motivo: "{latestComment}"
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {currentStatus === 'closed' && (
                    <div className="bg-gray-100 border-l-4 border-gray-400 p-5 rounded-r-lg shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 className="text-base font-bold text-gray-800">
                                Caso Finalizado y Cerrado
                            </h2>
                            <p className="text-sm text-gray-600 mt-0.5">
                                Este ticket ha concluido su ciclo de atención por parte del equipo administrativo.
                            </p>
                            {latestComment && (
                                <p className="text-xs text-gray-500 mt-1 italic">
                                    Observación final: "{latestComment}"
                                </p>
                            )}
                        </div>
                        <Link
                            href={route('returns.dashboard')}
                            className="inline-flex items-center justify-center px-4 py-2 bg-[#05a060] text-white text-xs font-bold rounded-lg hover:bg-[#04854f] transition shadow-sm"
                        >
                            Registrar nueva solicitud
                        </Link>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Columna Izquierda (2/3): Productos y Evidencias */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Productos a Devolver */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                                <h2 className="text-sm font-bold text-gray-800 uppercase tracking-wider">
                                    Productos en la Solicitud
                                </h2>
                                <span className="text-xs text-gray-500 font-semibold">
                                    Total: {ticket.return_items?.length ?? 0} {ticket.return_items?.length === 1 ? 'producto' : 'productos'}
                                </span>
                            </div>
                            <div className="p-6">
                                {ticket.return_items?.length > 0 ? (
                                    <ul className="divide-y divide-gray-100">
                                        {ticket.return_items.map((item) => (
                                            <li key={item.return_item_id} className="py-3.5 first:pt-0 last:pb-0">
                                                <div className="flex justify-between items-start gap-4">
                                                    <div>
                                                        <p className="text-sm font-bold text-gray-900">
                                                            {item.order_item?.product_name ?? 'Producto de la orden'}
                                                        </p>
                                                        {item.order_item?.product_code && (
                                                            <p className="text-xs font-mono text-gray-400">
                                                                Código: {item.order_item.product_code}
                                                            </p>
                                                        )}
                                                        <div className="mt-1 flex flex-wrap gap-2 text-xs">
                                                            <span className="bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-medium">
                                                                Motivo: {item.reason?.description ?? 'No especificado'}
                                                            </span>
                                                            <span className="bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-medium">
                                                                Condición: {getConditionLabel(item.condition)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="text-right flex-shrink-0">
                                                        <span className="inline-block bg-green-50 text-[#05a060] font-bold text-sm px-2.5 py-0.5 rounded-full border border-green-200">
                                                            Cant: {item.quantity_to_return}
                                                        </span>
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-gray-500">No hay productos registrados en este ticket.</p>
                                )}
                            </div>
                        </div>

                        {/* Comentario del Solicitante */}
                        {ticket.customer_comment && (
                            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h2 className="text-xs uppercase font-bold text-gray-500 tracking-wider mb-2">
                                    Tu comentario al enviar la solicitud
                                </h2>
                                <p className="text-sm text-gray-800 bg-gray-50 p-4 rounded-lg border border-gray-100">
                                    {ticket.customer_comment}
                                </p>
                            </div>
                        )}

                        {/* Evidencias Enviadas con enlace para ver/descargar */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h2 className="text-sm font-bold text-gray-800 uppercase tracking-wider">
                                    Evidencias Adjuntas
                                </h2>
                            </div>
                            <div className="p-6">
                                {ticket.evidences?.length > 0 ? (
                                    <ul className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        {ticket.evidences.map((ev) => (
                                            <li key={ev.evidence_id ?? ev.id}>
                                                <a
                                                    href={route('returns.evidences.show', ev.evidence_id ?? ev.id)}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="flex items-center gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 hover:border-[#05a060] transition group cursor-pointer"
                                                    title={`Abrir ${ev.file_name}`}
                                                    aria-label={`Abrir ${ev.file_name}`}
                                                >
                                                    <span className="text-2xl" aria-hidden="true">📎</span>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-xs font-semibold text-gray-800 group-hover:text-[#05a060] truncate">
                                                            {ev.file_name}
                                                        </p>
                                                        <p className="text-[11px] text-gray-400">
                                                            <span className="text-[#05a060] underline">Ver archivo</span>
                                                        </p>
                                                    </div>
                                                    <svg className="w-4 h-4 text-gray-400 group-hover:text-[#05a060] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-gray-500">No se adjuntaron archivos para esta solicitud.</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Columna Derecha (1/3): Línea de Tiempo e Historial */}
                    <div className="space-y-6">
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h2 className="text-sm font-bold text-gray-800 uppercase tracking-wider">
                                    Historial del Caso
                                </h2>
                            </div>
                            <div className="p-6">
                                {ticket.status_history?.length > 0 ? (
                                    <ol className="relative border-l-2 border-green-200 ml-2 space-y-6">
                                        {ticket.status_history.map((entry) => {
                                            const entryStatus = STATUS_CONFIG[entry.new_status] ?? {
                                                label: entry.new_status,
                                                badgeBg: 'bg-gray-100 text-gray-700',
                                            };
                                            return (
                                                <li key={entry.history_id} className="ml-4">
                                                    <div className="absolute -left-2 mt-1 w-3.5 h-3.5 rounded-full bg-[#05a060] border-2 border-white shadow-sm" />
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${entryStatus.badgeBg}`}>
                                                        {entryStatus.label}
                                                    </span>
                                                    <p className="text-xs text-gray-400 mt-1">
                                                        {new Date(entry.changed_at).toLocaleString('es-PE', {
                                                            day: '2-digit',
                                                            month: '2-digit',
                                                            year: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                        })}
                                                    </p>
                                                    {entry.comment && (
                                                        <div className="mt-1.5 p-2 bg-gray-50 rounded border border-gray-100 text-xs text-gray-700">
                                                            <p className="font-medium text-gray-500 text-[10px] uppercase mb-0.5">Mensaje de Soporte:</p>
                                                            "{entry.comment}"
                                                        </div>
                                                    )}
                                                </li>
                                            );
                                        })}
                                    </ol>
                                ) : (
                                    <p className="text-sm text-gray-500">Sin historial registrado.</p>
                                )}
                            </div>
                        </div>

                        {/* Ayuda y Contacto */}
                        <div className="bg-[#002E6E] text-white rounded-xl shadow-sm p-6 space-y-3">
                            <h3 className="font-bold text-sm text-[#fbdb04]">¿Tienes consultas sobre tu trámite?</h3>
                            <p className="text-xs text-gray-200 leading-relaxed">
                                Si requieres mayor detalle o presentar documentación adicional para tu caso, ten a la mano tu código de seguimiento <span className="font-mono font-bold text-white">{ticket.tracking_code}</span> y comunícate con nuestro centro de atención.
                            </p>
                            <div className="pt-2 text-xs text-gray-300 border-t border-blue-900">
                                Horario de atención: Lunes a Viernes de 9:00 a 18:00 hrs.
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
