import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen bg-slate-100 flex flex-col justify-center items-center p-4 sm:p-6">
            <Head title="Iniciar Sesión Administrativa - Tai Loy" />

            <div className="w-full max-w-4xl bg-white shadow-2xl rounded-2xl overflow-hidden flex flex-col md:flex-row border-t-8 border-t-[#fbdb04] border-l-0 md:border-l-8 md:border-l-[#05a060] border-b-8 border-b-[#05a060] md:border-b-0">
                
                <div className="w-full md:w-1/2 bg-[#002E6E] p-8 flex flex-col justify-center items-center text-white border-b md:border-b-0 md:border-r border-slate-700">
                    <div className="text-center space-y-6 max-w-sm">
                        <div className="bg-white p-4 rounded-xl shadow-md inline-block">
                            <img 
                                src="/logo1.webp" 
                                alt="Tai Loy Logo" 
                                className="w-44 h-auto mx-auto object-contain"
                            />
                        </div>
                        <div className="space-y-2">
                            <h1 className="text-2xl font-extrabold tracking-tight text-[#fbdb04]">
                                Módulo Administrativo
                            </h1>
                            <p className="text-sm text-slate-300">
                                Acceso exclusivo para el personal autorizado de Tai Loy. Gestión de devoluciones y garantías.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Right Column: Login Form */}
                <div className="w-full md:w-1/2 p-8 sm:p-10 flex flex-col justify-center">
                    <div className="mb-6">
                        <h2 className="text-xl font-bold text-gray-800 text-center md:text-left">
                            Acceso al Sistema
                        </h2>
                        <p className="text-xs text-gray-500 mt-1 text-center md:text-left">
                            Por seguridad, todas las acciones dentro de esta plataforma son registradas.
                        </p>
                    </div>

                    {status && (
                        <div className="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <label htmlFor="email" className="block text-sm font-semibold text-gray-700">
                                Correo Electrónico
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                className="mt-2 block w-full border-gray-300 focus:border-[#05a060] focus:ring-[#05a060] rounded-lg shadow-sm"
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                placeholder="usuario@tailoy.com.pe"
                            />
                            {errors.email && (
                                <div className="mt-2 text-sm text-red-600 font-medium">{errors.email}</div>
                            )}
                        </div>

                        <div>
                            <div className="flex justify-between items-center">
                                <label htmlFor="password" className="block text-sm font-semibold text-gray-700">
                                    Contraseña
                                </label>
                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-xs text-gray-500 hover:text-gray-800 underline transition"
                                    >
                                        ¿Olvidaste tu contraseña?
                                    </Link>
                                )}
                            </div>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                className="mt-2 block w-full border-gray-300 focus:border-[#05a060] focus:ring-[#05a060] rounded-lg shadow-sm"
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                placeholder="••••••••"
                            />
                            {errors.password && (
                                <div className="mt-2 text-sm text-red-600 font-medium">{errors.password}</div>
                            )}
                        </div>

                        <div className="flex items-center justify-between">
                            <label className="flex items-center">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded border-gray-300 text-[#05a060] focus:ring-[#05a060]"
                                />
                                <span className="ms-2 text-sm text-gray-600">
                                    Recordar sesión
                                </span>
                            </label>
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-[#05a060] hover:bg-[#04854f] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#05a060] disabled:opacity-50 transition duration-200 ease-in-out"
                            >
                                {processing ? 'Ingresando...' : 'Iniciar Sesión'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {/* Retorno al portal público */}
            <div className="mt-8 text-center">
                <Link
                    href="/"
                    className="text-xs text-gray-500 hover:text-gray-700 underline transition"
                >
                    Volver al Portal de Clientes
                </Link>
            </div>
        </div>
    );
}
