
<?php


require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';

$count = (int) $pdo->query(
    'SELECT COUNT(*) FROM users'
)->fetchColumn();

if ($count > 0) {
    http_response_code(403);
    exit('Setup is disabled because a user account already exists.');
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || strlen($name) > 150) {
        $error = 'Enter a valid full name.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 190
    ) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 12) {
        $error = 'Use a password with at least 12 characters.';
    } elseif (strlen($password) > 72) {
        $error = 'Password must not exceed 72 bytes.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the user table against concurrent first-account setup.
            $pdo->query(
                'SELECT id FROM users FOR UPDATE'
            )->fetchAll();

            $existing = (int) $pdo->query(
                'SELECT COUNT(*) FROM users'
            )->fetchColumn();

            if ($existing > 0) {
                $pdo->rollBack();
                http_response_code(403);
                exit('An account already exists. Setup is disabled.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO users
                    (full_name, email, password_hash, role, status)
                 VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'Admin',
                'active'
            ]);

            $pdo->commit();
            $success = true;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Administrator setup failed: ' . $e->getMessage());
            $error = 'Could not create the account. Check the details and server log.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Administrator Setup</title>
    <link href="./src/output.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-950 text-white flex items-center justify-center p-4">
    <main class="w-full max-w-md rounded-2xl bg-slate-900 p-8 shadow-xl">
        <h1 class="mb-2 text-2xl font-bold">Create Administrator</h1>
        <p class="mb-6 text-sm text-slate-400">
            Set up the first account for Inventory Management.
        </p>

        <?php if ($success): ?>
            <p class="mb-4 text-green-400">
                Administrator created successfully.
                Delete setup_admin.php before using the application.
            </p>
            <a href="auth/login.php"
               class="inline-block rounded-lg bg-blue-600 px-5 py-3">
                Go to Login
            </a>
        <?php else: ?>
            <?php if ($error !== ''): ?>
                <p class="mb-4 text-red-400"><?= e($error) ?></p>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token"
                       value="<?= e(csrfToken()) ?>">

                <label class="block">
                    <span class="mb-1 block">Full name</span>
                    <input name="full_name" required maxlength="150"
                           autocomplete="name"
                           class="w-full rounded-lg bg-slate-800 p-3">
                </label>

                <label class="block">
                    <span class="mb-1 block">Email</span>
                    <input type="email" name="email" required maxlength="190"
                           autocomplete="email"
                           class="w-full rounded-lg bg-slate-800 p-3">
                </label>

                <label class="block">
                    <span class="mb-1 block">Password (12+ characters)</span>
                    <input type="password" name="password" required
                           minlength="12" maxlength="72"
                           autocomplete="new-password"
                           class="w-full rounded-lg bg-slate-800 p-3">
                </label>

                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 p-3 font-semibold hover:bg-blue-500">
                    Create Administrator
                </button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
