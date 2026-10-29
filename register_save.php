<?php
declare(strict_types=1);
require_once 'db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <link rel="icon" type="image/png" href="/sd.png">
    <meta charset="UTF-8">
    <title>Rejestracja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">

<?php
if ($username === '' || $password === '') {
    echo '
    <div class="alert alert-danger">
        Wszystkie wymagane pola muszą być wypełnione.
    </div>';
} elseif ($password !== $password2) {
    echo '
    <div class="alert alert-danger">
        Hasła nie są takie same.
    </div>';
} else {

    /*
     * Sprawdzenie, czy użytkownik już istnieje.
     */
    $stmt = mysqli_prepare(
        $link,
        "SELECT id
         FROM users
         WHERE username = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $username
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo '
        <div class="alert alert-danger">
            Taki użytkownik już istnieje.
        </div>';
    } else {

        /*
         * Domyślny avatar.
         */
        $avatar = 'default-avatar.svg';

        /*
         * Jeśli użytkownik przesłał avatar,
         * sprawdzamy jego plik.
         */
        if (
            isset($_FILES['avatar']) &&
            $_FILES['avatar']['error'] === UPLOAD_ERR_OK
        ) {
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp'
            ];

            $fileType = mime_content_type($_FILES['avatar']['tmp_name']);

            if (isset($allowedTypes[$fileType])) {
                $extension = $allowedTypes[$fileType];
                $newFileName = uniqid('avatar_', true) . '.' . $extension;
                $destination = __DIR__ . '/media/avatars/' . $newFileName;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                    $avatar = $newFileName;
                }
            }
        }

        /*
         * Dodanie użytkownika do bazy.
         */
        $stmt2 = mysqli_prepare(
            $link,
            "INSERT INTO users
             (username, password, avatar)
             VALUES (?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt2,
            "sss",
            $username,
            $password,
            $avatar
        );

        if (mysqli_stmt_execute($stmt2)) {
            
            // --- NOWOŚĆ Z ZADANIA 5: TWORZENIE KATALOGU W CHMURZE ---
            $sciezka = "users_cloud/" . $username;
            if (!file_exists($sciezka)) {
                mkdir($sciezka, 0777, true);
            }
            // --------------------------------------------------------

            echo '
            <div class="alert alert-success">
                <h4>Rejestracja zakończona powodzeniem.</h4>
                <p>
                    Użytkownik został dodany, a prywatny katalog został utworzony.
                </p>
                <img
                    src="media/avatars/' . htmlspecialchars($avatar) . '"
                    class="avatar mb-3">
                <p>
                    Możesz teraz się zalogować.
                </p>
            </div>';

        } else {
            echo '
            <div class="alert alert-danger">
                Wystąpił błąd podczas rejestracji.
            </div>';
        }

        mysqli_stmt_close($stmt2);
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>

<a href="login.php" class="btn btn-primary">
    Logowanie
</a>
<a href="register.php" class="btn btn-success">
    Rejestracja
</a>

</div>
</body>
</html>