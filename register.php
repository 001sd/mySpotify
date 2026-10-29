<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <link rel="icon" type="image/png" href="/sd.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rejestracja</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="twoj_css.css">
</head>

<body>

<div class="container mt-4">
    <!-- Przycisk powrotu do głównego katalogu -->
    <a href="../index.php" class="btn btn-outline-secondary mb-3">
        &larr; Wróć do katalogu zadań
    </a>

    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 text-center mb-4">
                        Rejestracja
                    </h1>

                    <form method="post" action="register_save.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">Login</label>
                            <input type="text" class="form-control" id="username" name="username" maxlength="128" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Hasło</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="password2" class="form-label">Powtórz hasło</label>
                            <input type="password" class="form-control" id="password2" name="password2" required>
                        </div>

                        <div class="mb-3">
                            <label for="avatar" class="form-label">
                                Avatar <span class="text-muted">(opcjonalnie)</span>
                            </label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            Zarejestruj
                        </button>
                    </form>

                    <hr class="my-4">

                    <!-- Zmieniony link, aby poprawnie wracał do logowania w Zadaniu 4 -->
                    <a href="login.php" class="btn btn-primary w-100">
                        Powrót do logowania
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>