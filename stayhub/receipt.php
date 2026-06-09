<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$reservation_id = $_GET['id'] ?? null;

if (!$reservation_id) {
    header('Location: my-rentals.php');
    exit;
}

// Fetch reservation, listing, and host details
$sql = "SELECT r.*, l.title, l.location, host.name AS host_name, host.email AS host_email, host.phone AS host_phone
        FROM reservations r
        JOIN listings l ON r.listing_id = l.id
        JOIN users host ON l.user_id = host.id
        WHERE r.id = ? AND r.user_id = ? AND r.status = 'confirmed'";

$stmt = sqlsrv_query($conn, $sql, array($reservation_id, $user_id));
$reservation = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$reservation) {
    header('Location: my-rentals.php');
    exit;
}

// Calculate dates and days
$date1 = ($reservation['check_in'] instanceof DateTime) ? $reservation['check_in'] : new DateTime($reservation['check_in']);
$date2 = ($reservation['check_out'] instanceof DateTime) ? $reservation['check_out'] : new DateTime($reservation['check_out']);
$interval = $date1->diff($date2);
$days = $interval->days > 0 ? $interval->days : 1;

$total_price = $reservation['total_price'];

$receipt_number = "REC-2025-" . str_pad($reservation['id'], 6, "0", STR_PAD_LEFT);
$invoice_number = "FACT-2025-" . str_pad($reservation['id'], 6, "0", STR_PAD_LEFT);
$receipt_date = date("d/m/Y");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu - <?php echo $receipt_number; ?> | StayHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; line-height: 1.5; color: #333; max-width: 800px; margin: 40px auto; padding: 40px; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        @media print {
            body { padding: 0; margin: 0; max-width: 100%; box-shadow: none; }
            .no-print { display: none !important; }
        }
        h1 { text-align: center; font-size: 24px; text-transform: uppercase; margin-bottom: 30px; }
        .header-info { margin-bottom: 20px; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px 15px; border-radius: 4px; margin-bottom: 30px; font-weight: 500; border: 1px solid #c3e6cb; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .box { border: 1px solid #ccc; padding: 15px; border-radius: 6px; }
        .box h3 { margin-top: 0; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .line-item { margin-bottom: 8px; font-size: 14px; }
        .section-title { font-size: 18px; border-bottom: 2px solid #333; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        table th, table td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        table th { background-color: #f8f9fa; font-weight: 600; width: 40%; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .signature-box { width: 45%; border: 1px dashed #ccc; padding: 20px; border-radius: 6px; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 20px; }
        .btn-print { background: #ff385c; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; display: block; margin: 30px auto; }
        .btn-print:hover { background: #e31c5f; }
        .checkbox-group { display: inline-flex; align-items: center; gap: 5px; margin-right: 15px; }
        .checkbox { width: 14px; height: 14px; border: 1px solid #333; display: inline-block; vertical-align: middle; }
        .checkbox.checked { background-color: #333; position: relative; }
        .checkbox.checked::after { content: '✓'; color: white; position: absolute; top: -3px; left: 1px; font-size: 12px; }
    </style>
</head>
<body>

    <button onclick="window.print()" class="btn-print no-print">Imprimer le reçu</button>

    <h1>🧾 REÇU DE PAIEMENT</h1>

    <div class="header-info">
        <div><strong>N° Reçu:</strong> <?php echo $receipt_number; ?></div>
        <div><strong>Date de paiement:</strong> <?php echo $receipt_date; ?></div>
    </div>

    <div class="alert-success">
        ✅ PAIEMENT CONFIRMÉ - Ce document atteste du paiement effectué et doit être conservé comme justificatif.
    </div>

    <div class="grid-2">
        <div class="box">
            <h3>BÉNÉFICIAIRE DU PAIEMENT</h3>
            <div class="line-item"><strong>Nom/Raison sociale:</strong> <?php echo htmlspecialchars($reservation['host_name'] ?? '_______________________________'); ?></div>
            <div class="line-item"><strong>Adresse:</strong> _______________________________</div>
            <div class="line-item"><strong>Téléphone:</strong> <?php echo htmlspecialchars($reservation['host_phone'] ?? '_______________________________'); ?></div>
            <div class="line-item"><strong>Email:</strong> <?php echo htmlspecialchars($reservation['host_email'] ?? '_______________________________'); ?></div>
        </div>

        <div class="box">
            <h3>PAYEUR</h3>
            <div class="line-item"><strong>Nom complet:</strong> <?php echo htmlspecialchars($reservation['guest_name']); ?></div>
            <div class="line-item"><strong>Adresse:</strong> _______________________________</div>
            <div class="line-item"><strong>Téléphone:</strong> <?php echo htmlspecialchars($reservation['guest_phone']); ?></div>
            <div class="line-item"><strong>Email:</strong> <?php echo htmlspecialchars($reservation['guest_email']); ?></div>
        </div>
    </div>

    <h2 class="section-title">📝 Détails du Paiement</h2>
    <table>
        <tr>
            <th>Facture N°:</th>
            <td><?php echo $invoice_number; ?></td>
        </tr>
        <tr>
            <th>Date de la facture:</th>
            <td><?php echo $receipt_date; ?></td>
        </tr>
        <tr>
            <th>Montant de la facture:</th>
            <td><?php echo number_format($total_price, 2, ',', ' '); ?> MAD</td>
        </tr>
        <tr>
            <th>Objet du paiement:</th>
            <td>
                <span class="checkbox-group"><span class="checkbox"></span> Acompte (30%)</span><br>
                <span class="checkbox-group"><span class="checkbox"></span> Solde (70%)</span><br>
                <span class="checkbox-group"><span class="checkbox checked"></span> Paiement total</span><br>
                <span class="checkbox-group"><span class="checkbox"></span> Autre: _______</span>
            </td>
        </tr>
    </table>

    <h2 class="section-title">💰 Montant Payé</h2>
    <div style="font-size: 16px; margin-bottom: 10px;">
        <strong>Montant payé:</strong> <?php echo number_format($total_price, 2, ',', ' '); ?> MAD
    </div>
    <div style="font-size: 14px; font-style: italic;">
        (En lettres: _________________________________________________ Dirhams)
    </div>

    <h2 class="section-title">💳 Mode de Paiement</h2>
    <table>
        <tr>
            <th>Méthode utilisée:</th>
            <td>
                <div><span class="checkbox-group"><span class="checkbox"></span> Espèces</span></div>
                <div><span class="checkbox-group"><span class="checkbox"></span> Virement bancaire</span></div>
                <div><span class="checkbox-group"><span class="checkbox checked"></span> Carte bancaire</span></div>
                <div><span class="checkbox-group"><span class="checkbox"></span> Chèque N°: ______________</span></div>
                <div><span class="checkbox-group"><span class="checkbox"></span> PayPal / Virement en ligne</span></div>
                <div><span class="checkbox-group"><span class="checkbox"></span> Western Union / MoneyGram</span></div>
                <div><span class="checkbox-group"><span class="checkbox"></span> Autre: ______________</span></div>
            </td>
        </tr>
    </table>
    <div style="font-size: 14px; margin-top: 10px;">
        <strong>Détails du virement bancaire (si applicable):</strong><br>
        Référence du virement: _______________________________<br>
        Banque émettrice: _______________________________<br>
        Date de virement: ___/___/______
    </div>

    <h2 class="section-title">📊 Situation du Compte</h2>
    <table>
        <tr>
            <th>Montant total de la réservation:</th>
            <td><?php echo number_format($total_price, 2, ',', ' '); ?> MAD</td>
        </tr>
        <tr>
            <th>Montant déjà payé (avant ce paiement):</th>
            <td>0,00 MAD</td>
        </tr>
        <tr>
            <th>Montant de ce paiement:</th>
            <td><?php echo number_format($total_price, 2, ',', ' '); ?> MAD</td>
        </tr>
        <tr>
            <th>Total payé à ce jour:</th>
            <td><?php echo number_format($total_price, 2, ',', ' '); ?> MAD</td>
        </tr>
        <tr>
            <th>Solde restant à payer:</th>
            <td>0,00 MAD</td>
        </tr>
        <tr>
            <th>Date d'échéance du solde:</th>
            <td>___/___/______</td>
        </tr>
    </table>

    <h2 class="section-title">🏠 Informations sur la Réservation</h2>
    <table>
        <tr>
            <th>Propriété:</th>
            <td><?php echo htmlspecialchars($reservation['title']); ?></td>
        </tr>
        <tr>
            <th>Adresse:</th>
            <td><?php echo htmlspecialchars($reservation['location']); ?></td>
        </tr>
        <tr>
            <th>Date d'arrivée:</th>
            <td><?php echo ($reservation['check_in'] instanceof DateTime) ? $reservation['check_in']->format('d/m/Y') : date('d/m/Y', strtotime($reservation['check_in'])); ?></td>
        </tr>
        <tr>
            <th>Date de départ:</th>
            <td><?php echo ($reservation['check_out'] instanceof DateTime) ? $reservation['check_out']->format('d/m/Y') : date('d/m/Y', strtotime($reservation['check_out'])); ?></td>
        </tr>
        <tr>
            <th>Nombre de nuits:</th>
            <td><?php echo $days; ?> nuits</td>
        </tr>
    </table>

    <h2 class="section-title">💰 Caution</h2>
    <table>
        <tr>
            <th>Caution versée:</th>
            <td>
                <span class="checkbox-group"><span class="checkbox"></span> Oui: _________ MAD</span>
                <span class="checkbox-group"><span class="checkbox checked"></span> Non</span>
            </td>
        </tr>
        <tr>
            <th>Mode de caution:</th>
            <td>
                <span class="checkbox-group"><span class="checkbox"></span> Espèces</span>
                <span class="checkbox-group"><span class="checkbox"></span> Empreinte CB</span>
                <span class="checkbox-group"><span class="checkbox"></span> Virement</span>
                <span class="checkbox-group"><span class="checkbox"></span> Chèque</span>
            </td>
        </tr>
        <tr>
            <th>Date de restitution prévue:</th>
            <td>___/___/______ (max 7 jours après départ)</td>
        </tr>
    </table>

    <div style="font-size: 12px; color: #555; background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 30px;">
        <strong>📌 Note importante:</strong>
        <ul style="margin-top: 5px; margin-bottom: 0; padding-left: 20px;">
            <li>Ce reçu atteste du paiement reçu à la date indiquée</li>
            <li>Il doit être conservé comme justificatif</li>
            <li>En cas de paiement partiel, un nouveau reçu sera émis pour le solde</li>
            <li>La caution sera restituée dans les 7 jours suivant le départ (sauf dégâts)</li>
        </ul>
    </div>

    <h2 class="section-title">✅ Certification</h2>
    <div style="line-height: 1.8; font-size: 14px;">
        Je soussigné(e) <strong><?php echo htmlspecialchars($reservation['host_name'] ?? '_______________________________________'); ?></strong> <br>
        certifie avoir reçu la somme de <strong><?php echo number_format($total_price, 2, ',', ' '); ?> MAD</strong> <br>
        de la part de <strong><?php echo htmlspecialchars($reservation['guest_name']); ?></strong> <br>
        au titre de paiement pour la location du logement mentionné ci-dessus.
    </div>

    <div class="signatures">
        <div class="signature-box">
            <strong>Propriétaire/Bénéficiaire</strong><br><br>
            Nom: <?php echo htmlspecialchars($reservation['host_name'] ?? '_______________________'); ?><br>
            Date: <?php echo $receipt_date; ?><br>
            Lieu: _______________________<br><br><br>
            <em style="color:#999;">Signature et cachet</em>
        </div>
        <div class="signature-box">
            <strong>Client/Payeur</strong><br><br>
            Nom: <?php echo htmlspecialchars($reservation['guest_name']); ?><br>
            Date: <?php echo $receipt_date; ?><br>
            Lieu: _______________________<br><br><br>
            <em style="color:#999;">Signature (pour reçu)</em>
        </div>
    </div>

    <div class="footer">
        <div style="margin-bottom: 10px;">
            <strong>Pour toute réclamation ou question concernant ce paiement:</strong><br>
            📧 Email: adamnaime@gmail.com | 📞 Téléphone: +212684821930
        </div>
        <div>
            Ce document est généré par la plateforme StayHub<br>
            Reçu valable - Document officiel à conserver<br>
            <strong>StayHub - Plateforme de location sécurisée</strong>
        </div>
    </div>

</body>
</html>
