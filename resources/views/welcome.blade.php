<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ResumeAI API — Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        @keyframes pulse-ring {
            0% { transform: scale(0.9); opacity: 0.7; }
            50% { transform: scale(1.4); opacity: 0; }
            100% { transform: scale(0.9); opacity: 0; }
        }
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">

    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">

            <div class="flex items-center gap-3 mb-6">
                <div class="w-9 h-9 rounded-lg bg-blue-600 flex items-center justify-center">
                    <span class="mono text-xs font-semibold text-white">R.</span>
                </div>
                <span class="font-semibold text-gray-900">ResumeAI API</span>
            </div>

            <div class="flex items-center gap-3 mb-2">
                <div class="relative flex items-center justify-center w-3 h-3">
                    <span class="pulse-ring absolute inline-flex h-full w-full rounded-full bg-green-400"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                </div>
                <h1 class="text-lg font-semibold text-gray-900">Backend is running</h1>
            </div>
            <p class="text-sm text-gray-500 mb-6">
                API siap menerima request dari frontend.
            </p>

            <div class="space-y-2 mb-6">
                <div class="flex items-center justify-between rounded-lg bg-gray-50 border border-gray-100 px-4 py-3">
                    <span class="text-xs text-gray-500">Framework</span>
                    <span class="mono text-xs font-medium text-gray-900">
                        Laravel {{ Illuminate\Foundation\Application::VERSION }}
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 border border-gray-100 px-4 py-3">
                    <span class="text-xs text-gray-500">PHP Version</span>
                    <span class="mono text-xs font-medium text-gray-900">{{ PHP_VERSION }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-gray-50 border border-gray-100 px-4 py-3">
                    <span class="text-xs text-gray-500">Environment</span>
                    <span class="mono text-xs font-medium text-gray-900">{{ app()->environment() }}</span>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <p class="text-xs font-medium text-gray-500 mb-2">Endpoint</p>
                <div class="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3">
                    <span class="mono text-xs font-semibold text-blue-700">POST</span>
                    <span class="mono text-xs text-blue-700">/api/analyze</span>
                </div>
            </div>

        </div>

        <p class="text-center text-xs text-gray-400 mt-5">
            ResumeAI Backend &middot; {{ date('Y') }}
        </p>
    </div>

</body>
</html>