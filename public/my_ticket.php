<?php

declare(strict_types= 1);
require __DIR__ ."/../app/init.php";

if (!isLoggedIn()) {
    set_flash('Lütfen giriş yapın.', 'error');
    redirect('/login.php');
}
if (role() !== 'user') {
    set_flash('Bu işlem sadece kayıtlı kullanıcılar içindir', 'error');
    redirect('/login.php');
}

$pdo    = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = (string)user('id');
if (!$userId){
    set_flash('Oturum bulunamadı.','error');
    redirect('/login.php');
}

//pdf indirme
if (($_GET['op'] ??'') === 'pdf') {
    $ticketId = (string)$_GET['ticket_id']??'';
    if($ticketId===''){
        set_flash('Geçersiz bilet.','error');
        redirect('/my_ticket.php');
    }
$st = $pdo->prepare("
        SELECT 
            tk.id AS ticket_id, tk.status, tk.total_price, tk.created_at,
            t.id AS trip_id, t.departure_city, t.destination_city, 
            t.departure_time, t.arrival_time, t.price,
            GROUP_CONCAT(bs.seat_number, ', ') AS seats
        FROM Tickets tk
        JOIN Trips t ON t.id = tk.trip_id
        LEFT JOIN Booked_Seats bs ON bs.ticket_id = tk.id
        WHERE tk.id = :tid AND tk.user_id = :uid
        GROUP BY tk.id
    ");
    $st->execute([':tid' => $ticketId, ':uid' => $userId]);
    $ticket = $st->fetch(PDO::FETCH_ASSOC);
$fpdfPath = __DIR__ . '/../app/Support/fpdf.php';
if(!file_exists($fpdfPath)){
    set_flash('PDF motoru eksik.','error');
    redirect('/my_ticket.php');
}
require_once $fpdfPath;
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Kalaycı Bilet - Elektronik Bilet',0,1,'C');
$pdf->Ln(4);
$pdf->SetFont('Arial','',12);
$pdf->Cell(60,8,'Bilet No:',0,0);      $pdf->Cell(0,8,$ticket['ticket_id'],0,1);
$pdf->Cell(60,8,'Kullanici:',0,0);     $pdf->Cell(0,8,(string) user('full_name'),0,1);
$pdf->Cell(60,8,'Guzergah:',0,0);      $pdf->Cell(0,8,$ticket['departure_city'].' -> '.$ticket['destination_city'],0,1);
$pdf->Cell(60,8,'Kalkis:',0,0);        $pdf->Cell(0,8,dtfmt($ticket['departure_time']),0,1);
$pdf->Cell(60,8,'Varis:',0,0);         $pdf->Cell(0,8,dtfmt($ticket['arrival_time']),0,1);
$pdf->Cell(60,8,'Koltuk(lar):',0,0);   $pdf->Cell(0,8,($ticket['seats'] ?? '-'),0,1);
$pdf->Cell(60,8,'Durum:',0,0);         $pdf->Cell(0,8,strtoupper($ticket['status']),0,1);
$pdf->Cell(60,8,'Tutar:',0,0);         $pdf->Cell(0,8,money_tl((int)$ticket['total_price']),0,1);

$pdfName = 'bilet_'.$ticket['ticket_id'].'.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$pdfName.'"');
$pdf->Output('I', $pdfName);
exit;
}

if (($_POST['op'] ?? '') === 'cancel') {
    $ticketId = (string) ($_POST['ticket_id'] ?? '');
    if ($ticketId === '') {
        set_flash('Geçersiz bilet.', 'error');
        redirect('/my_ticket.php');
    }
    // Kalkıştan en az 1 saat önce mi?
    $now = new DateTime('now');
    $dep = new DateTime($row['departure_time']);
    $latestCancel = (clone $dep)->modify('-1 hour');
    if ($now > $latestCancel) {
        set_flash('Kalkıştan 1 saat kala iptal edilemez.', 'error');
        redirect('/my_ticket.php');
    }
    // İade + iptal + koltukları boşa çıkar (transaction)
    try {
        $pdo->beginTransaction();

        // Kullanıcı bakiyesine iade
        $st = $pdo->prepare("UPDATE User SET balance = balance + :amt WHERE id = :uid");
        $st->execute([
            ':amt' => (int)$row['total_price'],
            ':uid' => $userId
        ]);

        // Bilet durumunu iptal
        $st = $pdo->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = :tid");
        $st->execute([':tid' => $ticketId]);

        // Koltukları sil (boşa çıkar)
        $st = $pdo->prepare("DELETE FROM Booked_Seats WHERE ticket_id = :tid");
        $st->execute([':tid' => $ticketId]);

        $pdo->commit();
        set_flash('Bilet başarıyla iptal edildi. Ücret bakiyenize iade edildi.', 'success');
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash('İptal sırasında hata oluştu: ' . $e->getMessage(), 'error');
    }

    redirect('/my_ticket.php');
}

//kullanıcının biletleri :
$st = $pdo->prepare("
    SELECT 
        tk.id AS ticket_id, tk.status, tk.total_price, tk.created_at,
        t.id AS trip_id, t.departure_city, t.destination_city, 
        t.departure_time, t.arrival_time, t.price,
        GROUP_CONCAT(bs.seat_number, ', ') AS seats
    FROM Tickets tk
    JOIN Trips t ON t.id = tk.trip_id
    LEFT JOIN Booked_Seats bs ON bs.ticket_id = tk.id
    WHERE tk.user_id = :uid
    GROUP BY tk.id
    ORDER BY t.departure_time DESC
");
$st->execute([':uid' => $userId]);
$tickets = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- SAYFA ---------------- */
page_header('Biletlerim');

$flash = get_flash();
if ($flash) {
    echo "<p class='flash {$flash['type']}'>" . e($flash['msg']) . "</p>";
}
?>
<h2>Biletlerim</h2>

<?php if (!$tickets): ?>
  <p>Henüz biletiniz yok.</p>
<?php else: ?>
  <div style="display:flex; flex-direction:column; gap:14px;">
  <?php foreach ($tickets as $t): 
        $canCancel = false;
        if ($t['status'] === 'active' || $t['status'] === 'expired') {
            $now = new DateTime('now');
            $dep = new DateTime($t['departure_time']);
            $canCancel = ($now <= (clone $dep)->modify('-1 hour'));
        }
  ?>
    <div style="border:1px solid #ddd; padding:12px; border-radius:8px;">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <div>
          <strong>#<?= e($t['ticket_id']) ?></strong><br>
          <span><?= e($t['departure_city']) ?> → <?= e($t['destination_city']) ?></span><br>
          <small>Kalkış: <?= e(dtfmt($t['departure_time'])) ?> | Varış: <?= e(dtfmt($t['arrival_time'])) ?></small><br>
          <small>Koltuk(lar): <?= e($t['seats'] ?? '-') ?></small><br>
          <small>Durum: <strong><?= e($t['status']) ?></strong> | Tutar: <strong><?= e(money_tl((int)$t['total_price'])) ?></strong></small>
        </div>
        <div style="display:flex; gap:8px;">
          <a href="/my_ticket.php?op=pdf&ticket_id=<?= urlencode($t['ticket_id']) ?>" class="btn">PDF indir</a>

          <?php if ($canCancel): ?>
            <form method="post" action="/my_ticket.php" onsubmit="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?');">
              <input type="hidden" name="ticket_id" value="<?= e($t['ticket_id']) ?>">
              <button type="submit" name="op" value="cancel" class="btn" style="background:#f44336;color:#fff;">İptal Et</button>
            </form>
          <?php else: ?>
            <button class="btn" disabled title="Kalkıştan 1 saat kala iptal edilemez.">İptal Edilemez</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php page_footer(); ?>
