<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>Procesy serwera</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css?v=2">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<?php require_once 'header.php'; ?>

<main class="container">
    <section class="sekcja1">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Obciążenie serwera (top)</h4>
            </div>
            <div class="card-body p-4">
                <div class="bg-dark text-light p-3 rounded shadow-inner" style="font-family: monospace; overflow-x: auto; font-size: 0.9em;">
                    <?php
                    exec('TERM=xterm /usr/bin/top -n 1 -b', $top, $error);
                    if ($error) {
                        exec('TERM=xterm /usr/bin/top -n 1 -b 2>&1', $error);
                        echo "Błąd wykonania (hosting może blokować tę komendę):<br>" . htmlspecialchars($error[0] ?? '');
                    } else {
                        echo nl2br(htmlspecialchars(implode("\n", $top)));
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'footer.php'; ?>

</body>
</html>