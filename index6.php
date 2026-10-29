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
    <title>Geolokalizacja</title>

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
                <h4 class="mb-0">Geolokalizacja w oparciu o JSON</h4>
            </div>
            <div class="card-body p-4">
                <?php
                $ipaddress = $_SERVER["REMOTE_ADDR"];
                // Użycie małpy '@' wycisza błąd na wypadek gdyby serwer blokował funkcję file_get_contents dla zew. API
                $json = @file_get_contents("http://ipinfo.io/{$ipaddress}/geo");
                $details = json_decode($json);
                ?>
                <ul class="list-group list-group-flush text-start">
                    <li class="list-group-item">
                        <i class="text-muted">Badany adres IP:</i> 
                        <strong class="fs-5 ms-2"><?php echo $details->ip ?? $ipaddress; ?></strong>
                    </li>
                    <li class="list-group-item"><strong>Kraj:</strong> <?php echo $details->country ?? 'Brak danych'; ?></li>
                    <li class="list-group-item"><strong>Region:</strong> <?php echo $details->region ?? 'Brak danych'; ?></li>
                    <li class="list-group-item"><strong>Miasto:</strong> <?php echo $details->city ?? 'Brak danych'; ?></li>
                    <li class="list-group-item"><strong>Współrzędne:</strong> <?php echo $details->loc ?? 'Brak danych'; ?></li>
                </ul>
                
                <?php if(isset($details->loc)): ?>
                <div class="mt-4 text-center">
                    <a href="https://www.google.pl/maps/place/<?php echo $details->loc; ?>" target="_blank" class="btn btn-outline-primary">
                        Pokaż na mapach Google
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once 'footer.php'; ?>

</body>
</html>