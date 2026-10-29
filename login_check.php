<?php
session_start();
require_once 'db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: login.php');
    exit;
}

$stmt = mysqli_prepare(
    $link,
    "SELECT id, username, password, avatar
     FROM users
     WHERE username = ?"
);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Przygotowanie danych do logów wizyt
$ip = $_SERVER['REMOTE_ADDR'];
$data = date('Y-m-d H:i:s');

if ($user && $user['password'] === $password) {
    session_regenerate_id(true);

    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['avatar'] = $user['avatar'] ?? 'default-avatar.svg';
    
    // Zapisanie ścieżki do katalogu MyCloud w sesji (Zadanie 5)
    $_SESSION['user_dir'] = "users_cloud/" . $user['username'];
	// NOWOŚĆ: Nadanie uprawnień admina dla menu w Zadaniu 4
    if ($user['username'] === 'sd' || $user['username'] === 'admin') {
        $_SESSION['is_admin'] = 1;
    } else {
        $_SESSION['is_admin'] = 0;
    }

    // Rejestracja SUKCESU w bazie
    $sql_log = "INSERT INTO goscieportalu (ipaddress, username, datetime, status, browser, screen_res, window_res, colors, cookies, java, language) 
                VALUES ('$ip', '$username', '$data', 'Sukces', 'Logowanie', '-', '-', 0, 0, 0, '-')";
    mysqli_query($link, $sql_log);

    mysqli_stmt_close($stmt);
    mysqli_close($link);

    header('Location: home.php');
    exit;
}

// Rejestracja BŁĘDU w bazie (jeśli hasło lub login nie pasują)
$sql_log_err = "INSERT INTO goscieportalu (ipaddress, username, datetime, status, browser, screen_res, window_res, colors, cookies, java, language) 
                VALUES ('$ip', '$username', '$data', 'Błąd', 'Logowanie', '-', '-', 0, 0, 0, '-')";
mysqli_query($link, $sql_log_err);

mysqli_stmt_close($stmt);
mysqli_close($link);

header('Location: login.php?error=1');
exit;