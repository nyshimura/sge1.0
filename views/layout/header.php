<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGE - Sistema de Gestão Escolar</title>
    <!-- Tailwind CSS for modern, rapid styling without custom CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full flex flex-col">

<?php if (isset($_SESSION['user_id'])): ?>
    <nav class="bg-indigo-600 border-b border-indigo-700 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 text-white font-bold text-xl">
                        SGE
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <!-- Navigation Links based on Role -->
                            <a href="?p=dashboard" class="bg-indigo-700 text-white rounded-md px-3 py-2 text-sm font-medium" aria-current="page">Dashboard</a>

                            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'superadmin'): ?>
                                <a href="?p=legacy_app#students" class="text-indigo-100 hover:bg-indigo-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Alunos</a>
                                <a href="?p=admin_courses" class="text-indigo-100 hover:bg-indigo-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Cursos</a>
                                <a href="?p=legacy_app#financial" class="text-indigo-100 hover:bg-indigo-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Financeiro</a>
                            <?php endif; ?>

                            <?php if ($_SESSION['user_role'] === 'student'): ?>
                                <a href="?p=dashboard" class="text-indigo-100 hover:bg-indigo-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Meus Cursos</a>
                                <a href="?p=student_payments" class="text-indigo-100 hover:bg-indigo-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Pagamentos</a>
                            <?php endif; ?>

                            <?php if ($_SESSION['user_role'] === 'teacher'): ?>
                                <a href="?p=teacher_attendance" class="text-indigo-100 hover:bg-indigo-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Frequência</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <!-- Profile dropdown -->
                        <div class="relative ml-3 flex items-center gap-4 text-white text-sm">
                            <span>Olá, <?= htmlspecialchars($_SESSION['user_firstName']) ?></span>
                            <a href="?p=logout" class="bg-indigo-700 hover:bg-red-600 text-white rounded-md px-3 py-2 text-sm font-medium transition-colors">Sair</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>

    <main class="flex-1">
        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <!-- Content goes here -->
