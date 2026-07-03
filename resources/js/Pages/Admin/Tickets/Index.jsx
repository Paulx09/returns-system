import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_CONFIG = {
    received:                   { label: 'Recibido',            bg: 'bg-blue-100',   text: 'text-blue-800'   },
    under_review:               { label: 'En Revisión',         bg: 'bg-yellow-100', text: 'text-yellow-800' },
    approved:                   { label: 'Aprobado',            bg: 'bg-green-100',  text: 'text-green-800'  },
    rejected:                   { label: 'Rechazado',           bg: 'bg-red-100',    text: 'text-red-800'    },
    more_information_requested: { label: 'Info. Solicitada',    bg: 'bg-orange-100', text: 'text-orange-800' },
    closed:                     { label: 'Cerrado',             bg: 'bg-gray-100',   text: 'text-gray-600'   },
};

function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] ?? { label: status, bg: 'bg-gray-100', text: 'text-gray-600' };
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${config.bg} ${config.text}`}>
            {config.label}
        </span>
    );
}

export default function Index({ tickets, filters, statuses }) {
    const [form, setForm] = useState({
        status:    filters.status    ?? '',
        date_from: filters.date_from ?? '',
        date_to:   filters.date_to   ?? '',
    });

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route('admin.tickets.index'), form, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        setForm({ status: '', date_from: '', date_to: '' });
        router.get(route('admin.tickets.index'));
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            <Head title="Panel Administrativo — Tai Loy" />

            {/* Header */}
            <header className="bg-[#05a060] shadow-md w-full">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
                    <img 
                        src="/logo2.webp" 
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

            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">

                {/* Filtros */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 border-t-4 border-t-[#fbdb04] p-5 mb-6">
                    <h2 className="text-sm font-semibold text-gray-700 mb-4">Filtros de búsqueda</h2>
                    <form onSubmit={applyFilters} className="flex flex-wrap gap-4 items-end">
                        <div>
                            <label htmlFor="filter-status" className="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                            <select
                                id="filter-status"
                                value={form.status}
                                onChange={e => setForm(f => ({ ...f, status: e.target.value }))}
                                className="block w-48 rounded-md border-gray-300 text-sm shadow-sm focus:ring-[#05a060] focus:border-[#05a060]"
                            >
                                <option value="">Todos los estados</option>
                                {statuses.map(s => (
                                    <option key={s} value={s}>{STATUS_CONFIG[s]?.label ?? s}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label htmlFor="filter-date-from" className="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                            <input
                                id="filter-date-from"
                                type="date"
                                value={form.date_from}
                                onChange={e => setForm(f => ({ ...f, date_from: e.target.value }))}
                                className="block rounded-md border-gray-300 text-sm shadow-sm focus:ring-[#05a060] focus:border-[#05a060]"
                            />
                        </div>

                        <div>
                            <label htmlFor="filter-date-to" className="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                            <input
                                id="filter-date-to"
                                type="date"
                                value={form.date_to}
                                onChange={e => setForm(f => ({ ...f, date_to: e.target.value }))}
                                className="block rounded-md border-gray-300 text-sm shadow-sm focus:ring-[#05a060] focus:border-[#05a060]"
                            />
                        </div>

                        <div className="flex gap-2">
                            <button
                                type="submit"
                                className="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-bold rounded-md text-[#002E6E] bg-[#fbdb04] hover:bg-[#05a060] hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#05a060] transition-colors duration-200"
                            >
                                Filtrar
                            </button>
                            <button
                                type="button"
                                onClick={clearFilters}
                                className="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition"
                            >
                                Limpiar
                            </button>
                        </div>
                    </form>
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-200 border-t-4 border-t-[#05a060] overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 className="text-base font-semibold text-gray-900">
                            Solicitudes de Devolución
                            <span className="ml-2 text-sm font-normal text-gray-500">
                                ({tickets.total} en total)
                            </span>
                        </h2>
                    </div>

                    {tickets.data.length === 0 ? (
                        <div className="px-6 py-16 text-center text-gray-500">
                            <p className="text-lg font-medium">No hay solicitudes</p>
                            <p className="text-sm mt-1">No se encontraron tickets con los filtros actuales.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pedido</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha de solicitud</th>
                                        <th className="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {tickets.data.map(ticket => (
                                        <tr key={ticket.ticket_id} className="hover:bg-gray-50 transition">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="font-mono text-sm font-semibold text-gray-900">{ticket.tracking_code}</span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                {ticket.order?.order_number ?? '—'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <StatusBadge status={ticket.current_status} />
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {new Date(ticket.created_at).toLocaleDateString('es-PE', {
                                                    day: '2-digit', month: 'short', year: 'numeric'
                                                })}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right">
                                                <Link
                                                    href={route('admin.tickets.show', ticket.ticket_id)}
                                                    className="text-[#05a060] hover:text-[#04854f] text-sm font-medium underline"
                                                >
                                                    Ver detalle →
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Paginación */}
                    {tickets.links && tickets.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-200 flex justify-center gap-1">
                            {tickets.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url ?? '#'}
                                    preserveScroll
                                    className={`px-3 py-1 text-sm rounded-md border transition ${
                                        link.active
                                            ? 'bg-[#05a060] text-white border-[#05a060]'
                                            : link.url
                                                ? 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                                : 'bg-white text-gray-400 border-gray-200 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </main>
        </div>
    );
}
