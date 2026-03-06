<?php require __DIR__ . '/layout/header.php'; ?>

<div class="flex flex-col items-center justify-center min-h-[60vh]">
    <h1 class="text-9xl font-extrabold text-red-600 tracking-widest">403</h1>
    <div class="bg-red-600 px-2 text-sm rounded -rotate-12 absolute text-white">
        Acesso Negado
    </div>
    <div class="mt-8 text-center">
        <p class="text-2xl font-semibold md:text-3xl text-gray-800">Você não tem permissão para acessar esta página.</p>
        <p class="mt-4 mb-8 text-gray-600">Seu perfil (<?= htmlspecialchars($_SESSION['user_role'] ?? 'Desconhecido') ?>) não possui os direitos necessários.</p>
        <a href="?p=dashboard" class="px-8 py-3 font-semibold rounded bg-indigo-600 text-white hover:bg-indigo-700 transition duration-300">
            Voltar ao Painel Principal
        </a>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>