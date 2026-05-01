<?php
/**
 * Shared admin sidebar.
 * Include this file from any /admin/* page.
 * Set $activeAdminPage to the current filename (e.g. 'users.php') before including.
 */
$_adminPage = $activeAdminPage ?? basename($_SERVER['PHP_SELF'] ?? '');
?>
<aside class="w-64 bg-slate-900 min-h-screen p-4 flex-shrink-0">
  <div class="text-emerald-400 font-bold text-xl mb-8">3Commas Admin</div>
  <nav class="space-y-1">
    <?php
      $links = [
        'index.php'         => 'Dashboard',
        'plans.php'         => 'Plans',
        'subscriptions.php' => 'Subscriptions',
        'addresses.php'     => 'Addresses',
        'withdrawals.php'   => 'Withdrawals',
        'kyc.php'           => 'KYC Review',
        'documents.php'     => 'Documents',
        'users.php'         => 'Users',
      ];
      foreach ($links as $file => $label):
        $isActive = $_adminPage === $file;
    ?>
    <a href="/admin/<?= $file ?>"
      class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
        <?= $isActive ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
      <?= sanitize($label) ?>
    </a>
    <?php endforeach; ?>
    <hr class="border-slate-700 my-3">
    <a href="/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-400 hover:text-red-300 transition">Logout</a>
  </nav>
</aside>
