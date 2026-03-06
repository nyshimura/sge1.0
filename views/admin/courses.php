<?php
/**
 * views/admin/courses.php
 * Course Management View (Pure PHP)
 */

$conn = get_db_connection();
$message = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $teacherId = $_POST['teacherId'] ?? 0;
        $monthlyFee = str_replace(',', '.', $_POST['monthlyFee'] ?? '0');
        $carga_horaria = trim($_POST['carga_horaria'] ?? '');
        $dayOfWeek = trim($_POST['dayOfWeek'] ?? '');
        $startTime = $_POST['startTime'] ?? '';
        $endTime = $_POST['endTime'] ?? '';
        $totalSlots = !empty($_POST['totalSlots']) ? (int)$_POST['totalSlots'] : null;

        if (empty($name) || empty($teacherId)) {
            $error = 'Nome e Professor são obrigatórios.';
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO courses (name, description, teacherId, monthlyFee, carga_horaria, dayOfWeek, startTime, endTime, totalSlots, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aberto', NOW())");
                $stmt->execute([$name, $description, $teacherId, $monthlyFee, $carga_horaria, $dayOfWeek, $startTime, $endTime, $totalSlots]);

                require_once __DIR__ . '/../../api/utils/audit.php';
                AuditLogger::log($conn, $_SESSION['user_id'], 'CREATE_COURSE', ['name' => $name]);

                $message = 'Curso criado com sucesso!';
            } catch (PDOException $e) {
                $error = 'Erro ao criar o curso: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'close') {
         $courseId = $_POST['courseId'] ?? 0;
         try {
             $stmt = $conn->prepare("UPDATE courses SET status = 'Encerrado', closed_by_admin_id = ?, closed_date = NOW() WHERE id = ?");
             $stmt->execute([$_SESSION['user_id'], $courseId]);

             require_once __DIR__ . '/../../api/utils/audit.php';
             AuditLogger::log($conn, $_SESSION['user_id'], 'CLOSE_COURSE', ['course_id' => $courseId]);

             $message = 'Curso encerrado com sucesso!';
         } catch (PDOException $e) {
             $error = 'Erro ao encerrar curso.';
         }
    }
}

// Fetch Lists
$teachers = $conn->query("SELECT id, firstName, lastName FROM users WHERE role = 'teacher' ORDER BY firstName ASC")->fetchAll();
$courses = $conn->query("
    SELECT c.*, u.firstName as teacherName, u.lastName as teacherLastName,
    (SELECT COUNT(*) FROM enrollments WHERE courseId = c.id AND status = 'Aprovada') as enrolledCount
    FROM courses c
    LEFT JOIN users u ON c.teacherId = u.id
    ORDER BY c.status ASC, c.name ASC
")->fetchAll();

?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="sm:flex sm:items-center sm:justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold leading-6 text-gray-900">Gerenciar Cursos</h1>
        <p class="mt-2 text-sm text-gray-700">Crie novas turmas, defina horários, valores e visualize os cursos existentes.</p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <!-- The button opens the modal via JS (alpine or vanilla) -->
        <button type="button" onclick="document.getElementById('createCourseModal').classList.remove('hidden')" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Adicionar Novo Curso
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="rounded-md bg-green-50 p-4 mb-6 border-l-4 border-green-400">
        <div class="flex">
            <div class="flex-shrink-0">✅</div>
            <div class="ml-3"><p class="text-sm font-medium text-green-800"><?= htmlspecialchars($message) ?></p></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="rounded-md bg-red-50 p-4 mb-6 border-l-4 border-red-400">
        <div class="flex">
            <div class="flex-shrink-0">❌</div>
            <div class="ml-3"><p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p></div>
        </div>
    </div>
<?php endif; ?>

<!-- Course List -->
<div class="mt-8 flow-root">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300 bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Nome do Curso</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Professor</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Horário</th>
                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Vagas / Ocupadas</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Valor (R$)</th>
                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Status</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                <span class="sr-only">Ações</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="7" class="py-4 text-center text-sm text-gray-500">Nenhum curso cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        <?= htmlspecialchars($course['name']) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?= htmlspecialchars(($course['teacherName'] ?? '') . ' ' . ($course['teacherLastName'] ?? 'Sem Professor')) ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?= htmlspecialchars($course['dayOfWeek'] ?? '-') ?> <br>
                                        <span class="text-xs text-gray-400"><?= htmlspecialchars($course['startTime'] ?? '') ?> às <?= htmlspecialchars($course['endTime'] ?? '') ?></span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">
                                        <span class="font-semibold text-indigo-600"><?= $course['enrolledCount'] ?></span> / <?= $course['totalSlots'] ?: '&infin;' ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?= number_format($course['monthlyFee'], 2, ',', '.') ?>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?= $course['status'] === 'Aberto' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= htmlspecialchars($course['status']) ?>
                                        </span>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <?php if ($course['status'] === 'Aberto'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja encerrar este curso? Ele não receberá novas matrículas.');">
                                                <input type="hidden" name="action" value="close">
                                                <input type="hidden" name="courseId" value="<?= $course['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">Encerrar</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="#" class="text-indigo-600 hover:text-indigo-900 ml-4">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Course Modal -->
<div id="createCourseModal" class="relative z-10 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
  <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
      <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
        <div>
          <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4" id="modal-title">Adicionar Novo Curso</h3>

          <form method="POST" action="?p=admin_courses">
              <input type="hidden" name="action" value="create">

              <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">

                  <div class="sm:col-span-4">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Nome do Curso <span class="text-red-500">*</span></label>
                      <input type="text" name="name" required class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                  </div>

                  <div class="sm:col-span-2">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Mensalidade (R$)</label>
                      <input type="number" step="0.01" name="monthlyFee" placeholder="0.00" class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                  </div>

                  <div class="col-span-full">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Professor Responsável <span class="text-red-500">*</span></label>
                      <select name="teacherId" required class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                          <option value="">Selecione...</option>
                          <?php foreach ($teachers as $teacher): ?>
                              <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['firstName'] . ' ' . $teacher['lastName']) ?></option>
                          <?php endforeach; ?>
                      </select>
                  </div>

                  <div class="sm:col-span-2">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Dia da Semana</label>
                      <select name="dayOfWeek" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                          <option value="">-</option>
                          <option value="Segunda-feira">Segunda-feira</option>
                          <option value="Terça-feira">Terça-feira</option>
                          <option value="Quarta-feira">Quarta-feira</option>
                          <option value="Quinta-feira">Quinta-feira</option>
                          <option value="Sexta-feira">Sexta-feira</option>
                          <option value="Sábado">Sábado</option>
                      </select>
                  </div>

                  <div class="sm:col-span-2">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Hora Início</label>
                      <input type="time" name="startTime" class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                  </div>

                  <div class="sm:col-span-2">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Hora Fim</label>
                      <input type="time" name="endTime" class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                  </div>

                  <div class="sm:col-span-3">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Carga Horária (Ex: 40 horas)</label>
                      <input type="text" name="carga_horaria" class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                  </div>

                  <div class="sm:col-span-3">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Vagas (Deixe vazio p/ ilimitado)</label>
                      <input type="number" name="totalSlots" class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                  </div>

                  <div class="col-span-full">
                      <label class="block text-sm font-medium leading-6 text-gray-900">Descrição</label>
                      <textarea name="description" rows="3" class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6"></textarea>
                  </div>

              </div>

              <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:col-start-2">Salvar Curso</button>
                <button type="button" onclick="document.getElementById('createCourseModal').classList.add('hidden')" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">Cancelar</button>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
