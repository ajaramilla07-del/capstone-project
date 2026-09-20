<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION = [];
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];

                redirectToRoleDashboard($user['role']);
            }

            $error = 'Invalid email or password.';
        } catch (Exception $e) {
            $error = 'Unable to log in right now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Member Login</title>
    <style>
        :root {
            --bg: #ffffff;
            --page: #f5f7f3;
            --panel: #f0f5ef;
            --panel-strong: #dfeee4;
            --green: #5ca66e;
            --green-dark: #3f8a56;
            --green-soft: #edf8f0;
            --text: #111111;
            --muted: #555555;
            --line: rgba(0,0,0,0.06);
            --white: #ffffff;
            --shadow: 0 18px 36px rgba(35, 60, 41, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow: hidden;
        }

        .scene {
            position: relative;
            width: min(980px, 94vw);
            min-height: 760px;
            display: grid;
            place-items: center;
            background: var(--bg);
            padding: 28px;
        }

        .login-box {
            position: relative;
            z-index: 1;
            width: min(560px, 92%);
            background: linear-gradient(180deg, #eaf4ec, #edf6ee);
            border: 1px solid rgba(17,17,17,0.06);
            border-radius: 14px;
            padding: 34px 36px 26px;
            box-shadow: var(--shadow);
        }

        h1 {
            margin: 0 0 30px;
            text-align: center;
            font-size: clamp(2.2rem, 2.4vw, 3.5rem);
            letter-spacing: 0.09em;
            font-weight: 700;
            color: var(--text);
            text-transform: uppercase;
        }

        .field {
            display: flex;
            align-items: center;
            background: linear-gradient(180deg, #f1f5f1, #edf3ee);
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 18px;
            min-height: 62px;
        }

        .field .icon {
            width: 58px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            color: var(--text);
            background: rgba(0,0,0,0.02);
            border-right: 1px solid var(--line);
        }

        input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 16px 16px 16px 14px;
            font-size: 1.08rem;
            color: var(--text);
            outline: none;
        }

        input::placeholder {
            color: var(--muted);
        }

        .login-btn,
        .register-btn {
            display: block;
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 16px 18px;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-align: center;
            text-decoration: none;
            transition: transform 0.2s ease;
            cursor: pointer;
        }

        .login-btn {
            margin-top: 8px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: var(--white);
            box-shadow: 0 10px 18px rgba(92, 166, 110, 0.18);
        }

        .register-btn {
            margin-top: 22px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: var(--white);
            box-shadow: 0 10px 18px rgba(92, 166, 110, 0.18);
        }

        .login-btn:hover,
        .register-btn:hover {
            transform: translateY(-1px);
        }

        .helper {
            text-align: center;
            margin-top: 18px;
            font-size: 1.05rem;
            color: var(--text);
        }

        .helper a {
            color: var(--text);
            text-decoration: none;
            border-bottom: 1px solid rgba(0,0,0,0.2);
        }

        .error,
        .success {
            margin-bottom: 16px;
            padding: 10px 12px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }

        .error {
            color: #5c1a1a;
            background: rgba(255, 255, 255, 0.75);
        }

        .success {
            color: #143b2d;
            background: rgba(255, 255, 255, 0.76);
        }

        @media (max-width: 640px) {
            .login-box {
                padding: 26px 20px 18px;
            }
            h1 {
                letter-spacing: 0.06em;
            }
        }
    </style>
</head>
<body>
    <div class="scene">
        <div class="login-box">
            <h1>Member Login</h1>

            <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?><div class="success">Registration successful. Please log in.</div><?php endif; ?>

            <form method="post" action="login.php">
                <div class="field">
                    <span class="icon">👤</span>
                    <input type="email" name="email" placeholder="Email Address" required />
                </div>

                <div class="field">
                    <span class="icon">🔒</span>
                    <input type="password" name="password" placeholder="Password" required />
                </div>

                <button class="login-btn" type="submit">Login</button>
            </form>

            <div class="helper">
                <a href="register.php">Forgot Password? Click Here</a>
            </div>

            <a href="register.php" class="register-btn">Register</a>
        </div>
    </div>
</body>
</html>
