<?php
/**
 * widget_aktivitas.php — Widget riwayat aktivitas untuk Admin & Owner.
 */
if (!adalah_admin() && !adalah_owner()) {
    return;
}

$riwayat = ambil_riwayat_aktivitas($pdo, 8);
?>

<div class="modern-card">
    <div class="card-header">
        <span><i class="bi bi-clock-history"></i> Riwayat Aktivitas</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($riwayat)): ?>
            <p class="text-muted p-3 mb-0">Belum ada aktivitas tercatat.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-modern table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $log): ?>
                            <tr>
                                <td class="text-nowrap small"><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($log['username']) ?></span></td>
                                <td class="small"><?= htmlspecialchars($log['action_description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
