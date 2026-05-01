<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

require_login();
$user = current_user();

$docs = [];
try {
    $docs = db()->query(
        'SELECT * FROM admin_documents WHERE public_visible = 1 ORDER BY created_at DESC'
    )->fetchAll();
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documents &amp; Reports – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen pb-24 md:pb-6">

  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center gap-3 md:hidden">
    <a href="profile.php" class="text-slate-500 hover:text-slate-700 transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <span class="text-lg font-extrabold text-emerald-400">Documents &amp; Reports</span>
  </header>

  <?php $activePage = 'profile.php'; include '_nav.php'; ?>

  <main class="max-w-xl mx-auto px-4 py-6">

    <?php if (empty($docs)): ?>
      <div class="text-center py-16">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-slate-500 font-medium">No documents available yet.</p>
        <p class="text-slate-400 text-sm mt-1">Check back later for terms, policies and reports.</p>
      </div>
    <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($docs as $doc): ?>
      <div class="bg-white border border-slate-200 rounded-2xl p-4 flex items-center justify-between gap-4 hover:border-emerald-300 transition">
        <div class="flex items-center gap-3 min-w-0">
          <?php
            $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
            $iconColor = match($ext) {
                'pdf'  => 'text-red-500',
                'doc', 'docx' => 'text-blue-500',
                'xls', 'xlsx' => 'text-emerald-500',
                default => 'text-slate-400',
            };
          ?>
          <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-slate-900 text-sm truncate"><?= sanitize($doc['title']) ?></p>
            <?php if (!empty($doc['description'])): ?>
              <p class="text-xs text-slate-500 truncate"><?= sanitize($doc['description']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-slate-400 mt-0.5"><?= date('M j, Y', strtotime($doc['created_at'])) ?></p>
          </div>
        </div>
        <a href="../<?= sanitize($doc['file_path']) ?>"
          target="_blank" rel="noopener noreferrer"
          class="flex-shrink-0 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold px-3 py-2 rounded-lg transition flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Download
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </main>

</body>
</html>
