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
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center">
            <Head title="Iniciar Solicitud de Devolución - Tai Loy" />

            <div className="mb-8 text-center">
                {/* Logo Placeholder */}
                <h1 className="text-4xl font-bold text-tailoy-blue mb-2">Tai Loy</h1>
                <p className="text-gray-600 text-lg">Portal de Devoluciones y Garantías</p>
            </div>

            <div className="w-full sm:max-w-md mt-6 px-8 py-10 bg-white shadow-xl overflow-hidden sm:rounded-2xl border-t-4 border-tailoy-yellow">
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
                            className="mt-2 block w-full border-gray-300 focus:border-tailoy-blue focus:ring-tailoy-blue rounded-lg shadow-sm"
                            onChange={(e) => setData('order_number', e.target.value)}
                            required
                            placeholder="Ej. ORD-123456"
                        />
                        {errors.order_number && (
                            <div className="mt-2 text-sm text-tailoy-red font-medium">{errors.order_number}</div>
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
                            className="mt-2 block w-full border-gray-300 focus:border-tailoy-blue focus:ring-tailoy-blue rounded-lg shadow-sm"
                            onChange={(e) => setData('customer_dni', e.target.value)}
                            required
                            placeholder="Documento de Identidad"
                        />
                        {errors.customer_dni && (
                            <div className="mt-2 text-sm text-tailoy-red font-medium">{errors.customer_dni}</div>
                        )}
                    </div>

                    {errors.login && (
                        <div className="bg-red-50 border-l-4 border-tailoy-red p-4 rounded-md">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-tailoy-red" viewBox="0 0 20 20" fill="currentColor">
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
                            className="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-tailoy-blue bg-tailoy-yellow hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-tailoy-yellow disabled:opacity-50 transition duration-150 ease-in-out"
                        >
                            {processing ? 'Verificando...' : 'Iniciar Solicitud'}
                        </button>
                    </div>
                </form>

                <div className="mt-6 text-center text-xs text-gray-500">
                    * Solo puedes solicitar la devolución dentro de los 7 días calendario posteriores a tu compra.
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
