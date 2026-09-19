<?php
$pageTitle = 'Landing Page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/landing.css" />
    <style>
        .auth-modal {
            position: fixed;
            inset: 0;
            background: rgba(17, 17, 17, 0.42);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
            padding: 20px;
        }

        .auth-modal.open {
            display: flex;
        }

        .auth-panel {
            width: min(560px, 100%);
            background: linear-gradient(180deg, #eaf4ec, #edf6ee);
            border: 1px solid rgba(17,17,17,0.06);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(35, 60, 41, 0.12);
            padding: 30px 32px 24px;
            position: relative;
        }

        .auth-close {
            position: absolute;
            top: 16px;
            right: 18px;
            border: none;
            background: transparent;
            color: #111;
            font-size: 1.8rem;
            cursor: pointer;
            line-height: 1;
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
        }

        .auth-panel h2 {
            margin: 0 0 24px;
            text-align: center;
            font-size: clamp(2rem, 2.4vw, 3rem);
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #111;
        }

        .auth-field {
            display: flex;
            align-items: center;
            background: linear-gradient(180deg, #f1f5f1, #edf3ee);
            border: 1px solid rgba(17,17,17,0.08);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 18px;
            min-height: 62px;
        }

        .auth-field .icon {
            width: 58px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            color: #111;
            background: rgba(0,0,0,0.02);
            border-right: 1px solid rgba(17,17,17,0.08);
        }

        .auth-field input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 16px 16px 16px 14px;
            font-size: 1.08rem;
            color: #111;
            outline: none;
        }

        .auth-btn,
        .auth-toggle-btn,
        .secondary-link-btn {
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
            cursor: pointer;
            margin-top: 6px;
            background: linear-gradient(135deg, #5ca66e, #3f8a56);
            color: #fff;
            box-shadow: 0 10px 18px rgba(92, 166, 110, 0.18);
        }

        .secondary-link-btn {
            margin-top: 22px;
            background: rgba(255,255,255,0.7);
            color: #111;
            border: 1px solid rgba(17,17,17,0.08);
            box-shadow: none;
        }

        .auth-helper {
            text-align: center;
            margin-top: 18px;
            font-size: 1rem;
            color: #111;
        }

        .auth-helper button {
            border: none;
            background: transparent;
            color: #111;
            font: inherit;
            cursor: pointer;
            padding: 0;
            border-bottom: 1px solid rgba(0,0,0,0.2);
        }

        .auth-helper a {
            color: #111;
            text-decoration: none;
            border-bottom: 1px solid rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <div class="brand">Tasty <span>Food</span></div>

            <div class="top-actions">
                <div class="top-icon" aria-label="Search">⌕</div>
                <div class="top-icon" aria-label="Cart">🛒</div>
                <a href="#" class="top-button show-login" aria-label="Sign in or sign up">
                    <span class="badge">◌</span>
                    <span>Sign Up/Sign In</span>
                </a>
            </div>
        </header>

        <main class="hero">
            <section class="hero-copy">
                <h1 class="hero-title">Order Your Favorite Meals and Experience Fast, Reliable Delivery.</h1>
              

            </section>

            <section class="hero-visual" aria-label="Food illustration">
                <div class="orbit"></div>
                <div class="food-image"></div>
                <div class="mini-bubble b1"></div>
                <div class="mini-bubble b2"></div>
                <div class="mini-food f1"></div>
                <div class="mini-food f2"></div>
                <div class="mini-food f3"></div>
                <div class="pin p1">⌖</div>
                <div class="pin p2">⌖</div>
                <div class="pin p3">⌖</div>
                <div class="pin p4">⌖</div>
            </section>
        </main>

     
        <section class="bottom-strip">
            <div class="bottom-strip-inner">
                <h2 class="bottom-title">Explore Our Delights: View the Menu Now<span class="underline"></span></h2>

                <div class="slider-controls">
                    <button class="arrow-btn" type="button" aria-label="Previous">‹</button>
                    <button class="arrow-btn" type="button" aria-label="Next">›</button>
                    <a href="#" class="see-menu show-login">See Menu</a>
                </div>
            </div>
        </section>
    </div>

    <div class="auth-modal" id="authModal" aria-hidden="true">
        <div class="auth-panel" role="dialog" aria-modal="true" aria-labelledby="authTitle">
            <button class="auth-close" type="button" aria-label="Close auth">×</button>

            <div class="auth-form active" data-form="login">
                <h2 id="authTitle">Member Login</h2>

                <form method="post" action="login.php">
                    <div class="auth-field">
                        <span class="icon">👤</span>
                        <input type="email" name="email" placeholder="Email Address" required />
                    </div>

                    <div class="auth-field">
                        <span class="icon">🔒</span>
                        <input type="password" name="password" placeholder="Password" required />
                    </div>

                    <button class="auth-btn" type="submit">Login</button>
                </form>

                <div class="auth-helper">
                    <button type="button" class="show-register">Create an account</button>
                </div>
            </div>

            <div class="auth-form" data-form="register">
                <h2>Register</h2>

                <form method="post" action="register.php">
                    <div class="auth-field">
                        <span class="icon">👤</span>
                        <input type="text" name="name" placeholder="Full Name" required />
                    </div>

                    <div class="auth-field">
                        <span class="icon">✉️</span>
                        <input type="email" name="email" placeholder="Email Address" required />
                    </div>

                    <div class="auth-field">
                        <span class="icon">🔒</span>
                        <input type="password" name="password" placeholder="Password" required />
                    </div>

                    <div class="auth-field">
                        <span class="icon">🔐</span>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
                    </div>

                    <button class="auth-btn" type="submit">Create Account</button>
                </form>

                <button type="button" class="secondary-link-btn show-login-form">Back to Login</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('authModal');
        const openButtons = document.querySelectorAll('.show-login');
        const loginForm = document.querySelector('[data-form="login"]');
        const registerForm = document.querySelector('[data-form="register"]');
        const closeButton = document.querySelector('.auth-close');
        const showRegisterBtn = document.querySelector('.show-register');
        const showLoginBtn = document.querySelector('.show-login-form');

        function openAuthModal(mode = 'login') {
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            loginForm.classList.toggle('active', mode === 'login');
            registerForm.classList.toggle('active', mode === 'register');
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                openAuthModal('login');
            });
        });

        if (showRegisterBtn) {
            showRegisterBtn.addEventListener('click', function () {
                openAuthModal('register');
            });
        }

        if (showLoginBtn) {
            showLoginBtn.addEventListener('click', function () {
                openAuthModal('login');
            });
        }

        closeButton.addEventListener('click', function () {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }
        });
    </script>
</body>
</html>
