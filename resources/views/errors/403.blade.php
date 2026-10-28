<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 - Acceso restringido | Tai Loy</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/js/app.jsx'])
        @endif
    </head>
    <body class="font-sans antialiased min-h-screen bg-gray-50 flex flex-col">
        <header class="bg-[#05a060] shadow-md w-full">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
                <a href="{{ route('returns.start', [], false) }}">
                    <img src="/logo2.webp" alt="Logo de Tai Loy" class="h-10 w-auto object-contain" />
                </a>
            </div>
        </header>

        <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full bg-white shadow-xl rounded-2xl overflow-hidden border-t-4 border-[#fbdb04] border-b-4 border-b-[#05a060] text-center p-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 text-red-700 font-extrabold text-2xl mb-6">
                    403
                </div>

                <h1 class="text-2xl font-extrabold text-[#002E6E] mb-2">
                    Acceso no autorizado
                </h1>
                <p class="text-sm text-gray-600 mb-8">
                    No cuentas con los permisos necesarios para acceder a este recurso o realizar esta operación.
                </p>

                <div class="space-y-3">
                    <a
                        href="{{ route('returns.start', [], false) }}"
                        class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-bold rounded-lg text-[#002E6E] bg-[#fbdb04] hover:bg-[#05a060] hover:text-white transition-colors duration-200"
                    >
                        Volver al inicio
                    </a>
                    <a
                        href="{{ route('returns.tracking', [], false) }}"
                        class="w-full inline-flex justify-center py-2.5 px-4 border border-gray-300 shadow-sm text-sm font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200"
                    >
                        Consultar seguimiento
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>

