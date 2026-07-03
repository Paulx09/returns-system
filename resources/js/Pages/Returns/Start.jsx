import { useForm, Head, Link } from '@inertiajs/react';

export default function Start() {
    const { data, setData, post, processing, errors } = useForm({
        order_number: '',
        customer_dni: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('returns.login'));
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4 sm:p-6">
            <Head title="Iniciar Solicitud de Devolución - Tai Loy" />

            <div className="w-full max-w-4xl bg-white shadow-2xl rounded-2xl overflow-hidden flex flex-col md:flex-row border-t-8 border-t-[#fbdb04] border-l-0 md:border-l-8 md:border-l-[#05a060] border-b-8 border-b-[#05a060] md:border-b-0">
                
                {/* Left Column: Logo & Welcome Message */}
                <div className="w-full md:w-1/2 bg-gray-50 p-8 flex flex-col justify-center items-center border-b md:border-b-0 md:border-r border-gray-200">
                    <div className="text-center space-y-6 max-w-sm">
                        <img 
                            src="/logo1.webp" 
                            alt="Tai Loy Logo" 
                            className="w-56 h-auto mx-auto object-contain drop-shadow-sm"
                        />
                        <div className="space-y-2">
                            <h1 className="text-2xl font-extrabold text-gray-800 tracking-tight">
                                Portal de Devoluciones
                            </h1>
                            <p className="text-sm text-gray-500">
                                Registra y consulta el estado de tus devoluciones y garantías de forma fácil y segura.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Right Column: Login Form */}
                <div className="w-full md:w-1/2 p-8 sm:p-10 flex flex-col justify-center">
                    <h2 className="text-xl font-bold text-gray-800 mb-6 text-center md:text-left">
                        Iniciar Solicitud
                    </h2>
                    
                    <form onSubmit={submit} className="space-y-6">
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
                                placeholder="Ej. ORD-123456"
                            />
                            {errors.order_number && (
                                <div className="mt-2 text-sm text-red-600 font-medium">{errors.order_number}</div>
                            )}
                        </div>

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
                                placeholder="Documento de Identidad"
                            />
                            {errors.customer_dni && (
                                <div className="mt-2 text-sm text-red-600 font-medium">{errors.customer_dni}</div>
                            )}
                        </div>

                        {errors.login && (
                            <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm text-red-700 font-medium">
                                            {errors.login}
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
                                {processing ? 'Verificando...' : 'Iniciar Solicitud'}
                            </button>
                        </div>
                    </form>

                    <div className="mt-6 text-center text-xs text-gray-500">
                        * Solo puedes solicitar la devolución dentro de los 7 días calendario posteriores a tu compra.
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
        </div>
    );
}
