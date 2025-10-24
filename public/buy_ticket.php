<?php 

declare(strict_types=1);

require __DIR__ . "/../app/init.php";
require __DIR__ . "/../app/Support/uuid.php";

if (!isLoggedIn()) {
    set_flash('Lütfen giriş yapın.', 'error');
    redirect('/login.php');
}
if (role() !== 'user') {
    set_flash('Bu işlem sadece kayıtlı kullanıcılar içindir', 'error');
    redirect('/login.php');
}

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tripId = (string) ($_GET['trip_id'] ?? '');
if ($tripId === '') {
    redirect('/trips.php');
}

$st = $pdo->prepare("
  SELECT 
    t.id AS trip_id,
    t.destination_city,
    t.departure_city,
    t.arrival_time,
    t.departure_time,
    t.price AS trip_price,
    t.capacity,

    tk.id AS ticket_id,
    tk.user_id AS ticket_user_id,
    tk.status AS ticket_status,
    tk.total_price AS ticket_total_price,
    tk.created_at AS ticket_created_at,

    bs.id AS seat_id,
    bs.seat_number AS seat_number,
    bs.created_at AS seat_created_at

  FROM Trips t
  LEFT JOIN Tickets tk ON tk.trip_id = t.id
  LEFT JOIN Booked_Seats bs ON bs.ticket_id = tk.id
  WHERE t.id = :id
");

$st->execute([':id' => $tripId]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$trip = $rows[0] ?? null;

if (!$trip) {
    set_flash('Sefer bulunamadı.', 'error');
    redirect('/trips.php');
}

$taken = array_column($rows, 'seat_number');

if (($_POST['op'] ?? '') === 'buy') {
    $seats = array_map('intval', $_POST['seats'] ?? []); 
    $seats = array_values(array_unique(array_filter($seats, fn($n) => $n > 0)));
    $couponCode = trim((string)($_POST['coupon'] ?? ''));

    if (!$seats) {
        set_flash('En az bir koltuk seçin.', 'error');
        redirect("/buy_ticket.php?trip_id={$tripId}");
    }

    foreach ($seats as $s) {
        if ($s < 1 || $s > (int)$trip['capacity']) {
            set_flash('Geçersiz koltuk.', 'error');
            redirect("/buy_ticket.php?trip_id={$tripId}");
        }
    }

    // Dolu koltuk kontrolü
    foreach ($seats as $s) {
        if (in_array($s, $taken)) {
            set_flash("{$s}. koltuk zaten dolu.", 'error');
            redirect("/buy_ticket.php?trip_id={$tripId}");
        }
    }

    $discount = 0;
    $discountRate = 0;
    $unit = (int)$trip['trip_price'];

    if ($couponCode !== '') {
        $st = $pdo->prepare('SELECT id, code, discount, usage_limit, expire_date, created_at
                             FROM Coupons
                             WHERE code = :code');
        $st->execute([':code' => $couponCode]);
        $coupon = $st->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            set_flash('Kupon bulunamadı ya da geçersiz.', 'error');
            redirect("/buy_ticket.php?trip_id={$tripId}");
        }

        $discountRate = (float)$coupon['discount'] / 100;
        $discount = $unit * $discountRate;
        $unit = $unit - $discount;
    }

    try {
        $pdo->beginTransaction();

        $ticketId = uuid();
        $userId = user('id');
        $totalPrice = count($seats) * $unit;

        $st = $pdo->prepare("
            INSERT INTO Tickets (id, user_id, trip_id, total_price, created_at)
            VALUES (:id, :user_id, :trip_id, :total_price, datetime('now'))
        ");
        $st->execute([
            ':id' => $ticketId,
            ':user_id' => $userId,
            ':trip_id' => $tripId,
            ':total_price' => $totalPrice
        ]);

        $seatStmt = $pdo->prepare("
            INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at)
            VALUES (:id, :ticket_id, :seat_number, datetime('now'))
        ");
        foreach ($seats as $seatNum) {
            $seatStmt->execute([
                ':id' => uuid(),
                ':ticket_id' => $ticketId,
                ':seat_number' => $seatNum
            ]);
        }

        $pdo->commit();
        set_flash('Biletiniz başarıyla alındı!', 'success');
        redirect('/my_tickets.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('Bilet alınırken hata oluştu: ' . $e->getMessage(), 'error');
        redirect("/buy_ticket.php?trip_id={$tripId}");
    }
}

/* --- Sayfa --- */
page_header('Bilet Satın Al');
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>" . e($flash['msg']) . "</p>";
?>
<h2>Sefer : <?= e($trip['departure_city']) ?> → <?= e($trip['destination_city']) ?></h2>
<p>
  Kalkış: <strong><?= e(dtfmt($trip['departure_time'])) ?></strong> |
  Varış: <?= e(dtfmt($trip['arrival_time'])) ?> |
  Fiyat: <strong><?= e(money_tl((int)$trip['trip_price'])) ?></strong> |
  Kapasite: <?= (int)$trip['capacity'] ?>
</p>

<form method="post" action="/buy_ticket.php?trip_id=<?= urlencode($tripId) ?>">
  <div style="display:grid;grid-template-columns:repeat(4,60px);gap:8px;max-width:260px;">
    <?php for ($i = 1; $i <= (int)$trip['capacity']; $i++): ?>
      <?php $disabled = in_array($i, $taken, true) ? 'disabled' : ''; ?>
      <label <?= $disabled ? 'style="opacity:.5;text-decoration:line-through;"' : '' ?>>
        <input type="checkbox" name="seats[]" value="<?= $i ?>" <?= $disabled ?>> <?= $i ?>
      </label>
    <?php endfor; ?>
  </div>

  <hr>
  <label>Kupon Kodu (opsiyonel)</label><br>
  <input name="coupon" placeholder="INDIRIM10"><br><br>

  <button type="submit" name="op" value="buy">Satın Al</button>
</form>

<?php page_footer(); ?>
