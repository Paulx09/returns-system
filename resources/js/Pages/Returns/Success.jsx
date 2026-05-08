import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Success({ trackingCode }) {
    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
            <Head title="Solicitud Enviada - Tai Loy" />

            <div className="max-w-md w-full bg-white shadow-xl rounded-2xl overflow-hidden border-t-4 border-tailoy-yellow text-center p-8">
                <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                    <svg className="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <h2 className="text-3xl font-extrabold text-tailoy-blue mb-2">¡Solicitud Registrada!</h2>
                <p className="text-gray-600 mb-6">
                    Hemos recibido tu solicitud de devolución correctamente. Un asesor revisará tu caso a la brevedad.
                </p>

                <div className="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <p className="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-1">
                        Código de Seguimiento
                    </p>
                    <p className="text-3xl font-bold text-gray-900 tracking-widest">
                        {trackingCode}
                    </p>
                </div>

                <div className="text-sm text-gray-500 mb-8">
                    Guarda este código para hacer seguimiento a tu solicitud. Te hemos enviado una copia a tu correo electrónico registrado en la compra.
                </div>

                <Link
                    href={route('returns.start')}
                    className="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-bold rounded-md text-tailoy-blue bg-tailoy-yellow hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-tailoy-yellow"
                >
                    Volver al Inicio
                </Link>
            </div>
        </div>
    );
}
