<?php
/**
 * tambah_user.php — Manajemen user (Admin).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
cek_login();

$user_role = strtolower((string) ($_SESSION['role'] ?? ''));
if ($user_role !== 'admin') {
    $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Akses ditolak. Hanya Admin yang diizinkan.'];
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? '');
    $role_valid = ['Admin', 'Kasir', 'Owner'];
    $errors = [];

    if ($username === '' || strlen($username) > 50) {
        $errors[] = 'Username wajib diisi (maks 50 karakter).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if (!in_array($role, $role_valid, true)) {
        $errors[] = 'Role tidak valid.';
    }

    if (!empty($errors)) {
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => implode(' ', $errors)];
        header('Location: tambah_user.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, :role)');
        $stmt->execute([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
        catat_aktivitas($pdo, 'Tambah user: ' . $username . ' (' . $role . ')');
        $_SESSION['flash'] = ['tipe' => 'success', 'pesan' => "User '{$username}' berhasil ditambahkan."];
    } catch (Throwable $e) {
        error_log('Error in tambah_user.php: ' . $e->getMessage());
        $_SESSION['flash'] = ['tipe' => 'danger', 'pesan' => 'Gagal menambah user. Username mungkin sudah dipakai.'];
    }
    header('Location: tambah_user.php');
    exit;
}

$judul_halaman = 'Manajemen User';
$page_aktif = 'tambah_user';
$daftar_users = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 mb-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Tambah User</h2>
    <form method="POST" action="tambah_user.php" class="grid md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm text-slate-600 mb-1">Username</label>
            <input type="text" name="username" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Password</label>
            <input type="password" name="password" minlength="6" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">Role</label>
            <select name="role" required class="w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="Admin">Admin</option>
                <option value="Kasir">Kasir</option>
                <option value="Owner">Owner</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition">Tambah</button>
        </div>
    </form>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Daftar User</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-3 py-2">ID</th>
                    <th class="text-left px-3 py-2">Username</th>
                    <th class="text-left px-3 py-2">Role</th>
                    <th class="text-left px-3 py-2">Dibuat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftar_users as $u): ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2"><?= (int) $u['id'] ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($u['role']) ?></td>
                        <td class="px-3 py-2"><?= htmlspecialchars($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
