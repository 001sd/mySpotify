<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="/sd.png">
    <title>Rejestr wizyt i logowań</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Dodatkowy styl dla DataTables w wersji Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="twoj_css.css?v=2">
    
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Skrypty biblioteki DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>

<?php require_once 'header.php'; ?>

<main class="container-fluid mt-5 pt-5">
    <section class="sekcja1">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Rejestr wizyt i prób logowania</h4>
                <a href="home.php" class="btn btn-sm btn-light">Powrót</a>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <!-- Dodano ID "rejestrTabela", żeby skrypt wiedział, którą tabelę obsłużyć -->
                    <table id="rejestrTabela" class="table table-striped table-hover table-bordered align-middle" style="font-size: 0.85em; width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Data</th>
                                <th>IP / Host</th>
                                <th>Użytkownik</th>
                                <th>Status logowania</th>
                                <th>Geolokalizacja</th>
                                <th>Ekran</th>
                                <th>Okno</th>
                                <th>Kolory</th>
                                <th>Ciastka</th>
                                <th>Java</th>
                                <th>Język</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $link->query("SELECT * FROM goscieportalu ORDER BY datetime DESC");

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $db_ip = $row['ipaddress'];
                                    
                                    // Geolokalizacja
                                    $json = @file_get_contents("http://ipinfo.io/{$db_ip}/geo");
                                    $details = json_decode($json);
                                    $loc = $details->loc ?? '';
                                    $city = $details->city ?? 'Nieznane';
                                    $map_btn = $loc ? "<a href='https://www.google.pl/maps/place/$loc' target='_blank' class='btn btn-xs btn-outline-primary py-0' style='font-size:0.85em;'>Mapa ($city)</a>" : "<span class='text-muted'>-</span>";
                                    
                                    // Status logowania (Sukces / Błąd)
                                    $status_badge = '<span class="text-muted">-</span>';
                                    if ($row['status'] === 'Sukces') {
                                        $status_badge = '<span class="badge bg-success">Sukces</span>';
                                    } elseif ($row['status'] === 'Błąd') {
                                        $status_badge = '<span class="badge bg-danger">Błąd</span>';
                                    }

                                    // Nazwa użytkownika
                                    $user_display = !empty($row['username']) ? "<strong>{$row['username']}</strong>" : '<span class="text-muted">N/A (Gość)</span>';

                                    $cookies_str = $row['cookies'] ? "<span class='badge bg-success'>Tak</span>" : "<span class='badge bg-danger'>Nie</span>";
                                    $java_str = $row['java'] ? "<span class='badge bg-success'>Tak</span>" : "<span class='badge bg-danger'>Nie</span>";
                                    
                                    // Zapobieganie błędom, gdy log_visitor wpisał puste wartości
                                    $screen = !empty($row['screen_res']) ? $row['screen_res'] : '-';
                                    $window = !empty($row['window_res']) ? $row['window_res'] : '-';
                                    $colors = !empty($row['colors']) ? $row['colors'].' bit' : '-';
                                    
                                    echo "<tr>";
                                    echo "<td>{$row['datetime']}</td>";
                                    echo "<td><strong>{$row['ipaddress']}</strong></td>";
                                    echo "<td>{$user_display}</td>";
                                    echo "<td>{$status_badge}</td>";
                                    echo "<td>{$map_btn}</td>";
                                    echo "<td>{$screen}</td>";
                                    echo "<td>{$window}</td>";
                                    echo "<td>{$colors}</td>";
                                    echo "<td>{$cookies_str}</td>";
                                    echo "<td>{$java_str}</td>";
                                    echo "<td><span class='text-uppercase'>{$row['language']}</span></td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'footer.php'; ?>

<!-- Uruchomienie filtrowania i stronnicowania tabeli -->
<script>
$(document).ready(function() {
    $('#rejestrTabela').DataTable({
        "pageLength": 10, // Wyświetla 10 wierszy na stronę
        "order": [[ 0, "desc" ]], // Domyślnie sortuje po dacie malejąco
        "language": {
            // Polskie tłumaczenie interfejsu (szukaj, następna, poprzednia itp.)
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/pl.json"
        }
    });
});
</script>

</body>
</html>