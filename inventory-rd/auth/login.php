<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password, role
             FROM users
             WHERE email = :email AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: ../dashboard.php');
            exit;
        }

        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M | Login</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>


<body class="min-h-screen bg-[#04142f] flex items-center justify-center px-4 py-8">

    <!-- Login Card -->
    <main class="w-full max-w-md border border-gray-400 rounded-3xl
                px-6 sm:px-10 py-10 text-gray-300">

        <!-- Heading -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-3">WELCOME</h1>

            <p class="text-sm text-slate-400">
                Sign in to access your
                <span class="text-red-500">account</span>
            </p>
        </div>
        <?php if ($error !== ''): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

        <!-- Login Form -->
        <form action="login.php" method="post" class="space-y-5">
        <?= csrf_field() ?>
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm mb-2">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required
                    class="w-full rounded-xl bg-slate-500/90 border border-slate-400
                           px-4 py-3 text-white placeholder-gray-300
                           outline-none focus:border-red-500 focus:ring-2
                           focus:ring-red-500/30 transition"
                >
            </div>

            <!-- Password -->
            <div>
                <label for="password"  class="block text-sm mb-2">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="7"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-xl bg-slate-500/90 border border-slate-400
                           px-4 py-3 text-white placeholder-gray-300
                           outline-none focus:border-red-500 focus:ring-2
                           focus:ring-red-500/30 transition"
                >

                <div class="text-right mt-2">
                    <p 
                       class="text-sm text-red-500 hover:text-red-400">
                        Forgot password?
                    </p>
                </div>
            </div>

            <!-- Remember Me -->
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input
                    type="checkbox"
                    name="remember"
                    class="accent-red-600"
                >
                Remember me
            </label>

            <!-- Sign In Button -->
            <button
                type="submit"
                class="w-full bg-red-600 hover:bg-red-700
                       rounded-xl py-3 font-semibold text-white
                       transition duration-200"
            >
                Sign In
            </button>
            <button type="submit"  class="w-full bg-red-600 hover:bg-red-700
                       rounded-xl py-3 font-semibold text-white
                       transition duration-200">Log out</button>

        </form>

    </main>

</body>
</html>
