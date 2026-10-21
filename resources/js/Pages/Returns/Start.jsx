import React, { useState } from 'react';
import { useForm, Head, Link } from '@inertiajs/react';

export default function Start() {
    const [mode, setMode] = useState('order'); // 'order' | 'tracking'
    const { data, setData, post, processing, errors } = useForm({
        order_number: '',
        customer_dni: '',
        tracking_code: '',
    });

    const submit = (e) => {
        e.preventDefault();
        if (mode === 'tracking') {
            post(route('returns.track-by-code'));
        } else {
            post(route('returns.login'));
        }
    };

    return (
        <main className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4 sm:p-6">
            <Head title="Portal de Devoluciones y Seguimiento - Tai Loy" />

            <div className="w-full max-w-4xl bg-white shadow-2xl rounded-2xl overflow-hidden flex flex-col md:flex-row border-t-8 border-t-[#fbdb04] border-l-0 md:border-l-8 md:border-l-[#05a060] border-b-8 border-b-[#05a060] md:border-b-0">
                
                {/* Left Column: Logo & Welcome Message */}
                <div className="w-full md:w-1/2 bg-gray-50 p-8 flex flex-col justify-center items-center border-b md:border-b-0 md:border-r border-gray-200">
                    <div className="text-center space-y-6 max-w-sm">
                        <img 
                            src="/logo1.webp" 
                            alt="Logo de Tai Loy" 
                            className="w-56 h-auto mx-auto object-contain drop-shadow-sm"
                        />
                        <div className="space-y-2">
                            <h1 className="text-2xl font-extrabold text-gray-800 tracking-tight">
                                Portal de Devoluciones
                            </h1>
                            <p className="text-sm text-gray-500">
                                Registra nuevas devoluciones y consulta el estado en tiempo real de tu solicitud de garantía.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Right Column: Login / Track Form */}
                <div className="w-full md:w-1/2 p-8 sm:p-10 flex flex-col justify-center">
                    {/* Mode Toggle */}
                    <div className="flex border-b border-gray-200 mb-6" role="tablist" aria-label="Método de consulta">
                        <button
                            type="button"
                            role="tab"
                            aria-selected={mode === 'order'}
                            onClick={() => setMode('order')}
                            className={`pb-3 text-sm font-bold border-b-2 mr-6 transition ${
                                mode === 'order'
                                    ? 'border-[#05a060] text-[#05a060]'
                                    : 'border-transparent text-gray-400 hover:text-gray-600'
                            }`}
                        >
                            Con Pedido y DNI
                        </button>
                        <button
                            type="button"
                            role="tab"
                            aria-selected={mode === 'tracking'}
                            onClick={() => setMode('tracking')}
                            className={`pb-3 text-sm font-bold border-b-2 transition ${
                                mode === 'tracking'
                                    ? 'border-[#05a060] text-[#05a060]'
                                    : 'border-transparent text-gray-400 hover:text-gray-600'
                            }`}
                        >
                            Con Código de Seguimiento
                        </button>
                    </div>

                    <h2 className="text-xl font-bold text-gray-800 mb-4 text-center md:text-left">
                        {mode === 'order' ? 'Iniciar o Consultar Solicitud' : 'Consultar Estado de Ticket'}
                    </h2>
                    
                    <form onSubmit={submit} className="space-y-5" noValidate>
                        {mode === 'order' ? (
                            <div>
                                <label htmlFor="order_number" className="block text-sm font-semibold text-gray-700">
                                    Número de Pedido
                                </label>
                                <input
                                    id="order_number"
                                    type="text"
                                    name="order_number"
                                    value={data.order_number}
                                    className="mt-2 block w-full border-gray-300 focus:border-[#05a060] focus:ring-[#05a060] rounded-lg shadow-sm"
                                    onChange={(e) => setData('order_number', e.target.value)}
                                    required
                                    aria-invalid={errors.order_number ? 'true' : 'false'}
                                    aria-describedby={errors.order_number ? 'error-order_number' : undefined}
                                    placeholder="Ej. ORD-123456"
                                />
                                {errors.order_number && (
                                    <div id="error-order_number" role="alert" className="mt-2 text-sm text-red-600 font-medium">
                                        {errors.order_number}
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div>
                                <label htmlFor="tracking_code" className="block text-sm font-semibold text-gray-700">
                                    Código de Seguimiento
                                </label>
                                <input
                                    id="tracking_code"
                                    type="text"
                                    name="tracking_code"
                                    value={data.tracking_code}
                                    className="mt-2 block w-full border-gray-300 focus:border-[#05a060] focus:ring-[#05a060] rounded-lg shadow-sm font-mono uppercase"
                                    onChange={(e) => setData('tracking_code', e.target.value)}
                                    required
                                    aria-invalid={errors.tracking_code ? 'true' : 'false'}
                                    aria-describedby={errors.tracking_code ? 'error-tracking_code' : undefined}
                                    placeholder="Ej. RET-8K29FA01"
                                />
                                {errors.tracking_code && (
                                    <div id="error-tracking_code" role="alert" className="mt-2 text-sm text-red-600 font-medium">
                                        {errors.tracking_code}
                                    </div>
                                )}
                            </div>
                        )}

                        <div>
                            <label htmlFor="customer_dni" className="block text-sm font-semibold text-gray-700">
                                DNI o CE del Comprador
                            </label>
                            <input
                                id="customer_dni"
                                type="text"
                                name="customer_dni"
                                value={data.customer_dni}
                                className="mt-2 block w-full border-gray-300 focus:border-[#05a060] focus:ring-[#05a060] rounded-lg shadow-sm"
                                onChange={(e) => setData('customer_dni', e.target.value)}
                                required
                                aria-invalid={errors.customer_dni ? 'true' : 'false'}
                                aria-describedby={errors.customer_dni ? 'error-customer_dni' : undefined}
                                placeholder="Documento de Identidad"
                            />
                            {errors.customer_dni && (
                                <div id="error-customer_dni" role="alert" className="mt-2 text-sm text-red-600 font-medium">
                                    {errors.customer_dni}
                                </div>
                            )}
                        </div>

                        {(errors.login || errors.tracking) && (
                            <div role="alert" aria-live="assertive" className="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm text-red-700 font-medium">
                                            {errors.login || errors.tracking}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-[#05a060] hover:bg-[#04854f] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#05a060] disabled:opacity-50 transition duration-200 ease-in-out"
                            >
                                {processing
                                    ? 'Verificando...'
                                    : (mode === 'order' ? 'Iniciar Solicitud' : 'Consultar Seguimiento')}
                            </button>
                        </div>
                    </form>

                    <div className="mt-5 text-center text-xs text-gray-500 space-y-1">
                        <p>* Solo puedes solicitar la devolución dentro de los 7 días posteriores a tu compra.</p>
                        {mode === 'order' && (
                            <p className="text-gray-400">
                                Si ya cuentas con un ticket registrado para este pedido, te mostraremos su estado de seguimiento directamente.
                            </p>
                        )}
                    </div>
                </div>
            </div>

            {/* Acceso administrativo — discreto, al pie de página */}
            <div className="mt-8 text-center">
                <Link
                    href="/admin/tickets"
                    className="text-xs text-gray-400 hover:text-gray-600 underline transition"
                >
                    Acceso Administrativo
                </Link>
            </div>
        </main>
    );
}
