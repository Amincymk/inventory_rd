
<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_SESSION["login_error"] ?? "";
unset($_SESSION["login_error"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | StockFlow</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="flex items-center justify-center min-h-screen px-5 text-white bg-slate-950">

    <main class="w-full max-w-md p-8 border shadow-xl rounded-2xl border-slate-700 bg-slate-900">

        <a href="index.php" class="text-2xl font-bold text-sky-400">
            StockFlow
        </a>

        <h1 class="mt-8 text-3xl font-bold">Welcome Back</h1>

        <p class="mt-2 mb-6 text-slate-400">
            Sign in to access your inventory dashboard.
        </p>

        <?php if ($error !== ""): ?>
            <div role="alert"
                 class="p-3 mb-5 text-sm text-red-300 rounded-lg bg-red-950">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form action="authenticate.php" method="POST" class="space-y-5">

            <div>
                <label for="email" class="block mb-2 text-sm">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    maxlength="255"
                    autocomplete="email"
                    class="w-full p-3 border rounded-lg outline-none border-slate-700 bg-slate-800 focus:border-sky-500"
                    placeholder="you@example.com">
            </div>

            <div>
                <label for="password" class="block mb-2 text-sm">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    maxlength="72"
                    autocomplete="current-password"
                    class="w-full p-3 border rounded-lg outline-none border-slate-700 bg-slate-800 focus:border-sky-500"
                    placeholder="Enter your password">
            </div>

            <button
                type="submit"
                class="w-full py-3 font-semibold rounded-lg bg-sky-600 hover:bg-sky-700">
                Login
            </button>

        </form>

        <p class="mt-6 text-sm text-center text-slate-400">
            Don't have an account?
            <a href="index.php#register" class="text-sky-400 hover:underline">
                Create one
            </a>
        </p>

    </main>

</body>
</html>
