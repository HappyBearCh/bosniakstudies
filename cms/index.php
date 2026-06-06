<?php
// ════════════════════════════════════════════════════════════════
//  Bosniak Studies — CMS  (WYSIWYG + Source editor)
//  Access: /cms/index.php
//  Default password: bosniakstudies  ← change CMS_PASS below
// ════════════════════════════════════════════════════════════════
session_start();

define('CMS_PASS',    '$2b$12$U9BB2YgYns7NMVk/CfqI6O1RrUnFtEdYPNxNFH3MBO6A3uTIYkA/O'); // bcrypt of: bosniakstudies
define('SITE_ROOT',   realpath(__DIR__ . '/..'));
define('ALLOWED_EXT', ['html', 'css', 'js']);

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !isset($_POST['content'])) {
    if (password_verify($_POST['password'], CMS_PASS)) {
        $_SESSION['cms_auth'] = true;
        $_SESSION['csrf']     = bin2hex(random_bytes(16));
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }
    $login_error = 'Incorrect password.';
}

$authed = !empty($_SESSION['cms_auth']);

// ── helpers ───────────────────────────────────────────────────────────────────
function safe_path(string $rel): string|false {
    $rel = str_replace(['\\', '..'], ['/', ''], $rel);
    $abs = realpath(SITE_ROOT . '/' . ltrim($rel, '/'));
    if (!$abs) return false;
    if (strpos($abs, SITE_ROOT . DIRECTORY_SEPARATOR) !== 0 && $abs !== SITE_ROOT) return false;
    if (!in_array(strtolower(pathinfo($abs, PATHINFO_EXTENSION)), ALLOWED_EXT)) return false;
    return $abs;
}
function rel_path(string $abs): string {
    return ltrim(str_replace('\\', '/', str_replace(SITE_ROOT, '', $abs)), '/');
}
function get_file_tree(): array {
    $tree = [];
    $it   = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SITE_ROOT, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $ext = strtolower($f->getExtension());
        if (!in_array($ext, ALLOWED_EXT)) continue;
        $rel = rel_path($f->getRealPath());
        if (preg_match('#^(cms/|lists/|\.well-known/)#', $rel)) continue;
        $dir = dirname($rel); if ($dir === '.') $dir = '';
        $tree[$dir][] = $rel;
    }
    ksort($tree);
    foreach ($tree as &$v) sort($v);
    return $tree;
}
function cm_mode(string $f): string {
    return match(strtolower(pathinfo($f, PATHINFO_EXTENSION))) {
        'css' => 'css', 'js' => 'javascript', default => 'htmlmixed' };
}
function file_icon(string $ext): string {
    return match($ext) { 'css' => '🎨', 'js' => '⚙', default => '📄' };
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function preview_url(string $rel): string {
    $site = dirname(dirname($_SERVER['SCRIPT_NAME']));
    if ($site === '/' || $site === '\\') $site = '';
    return $site . '/' . ltrim(str_replace('\\', '/', $rel), '/');
}

// ── new file ──────────────────────────────────────────────────────────────────
$msg = ''; $msg_type = ''; $redirect = '';
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'new_file'
    && isset($_POST['new_name'], $_POST['csrf'])
    && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {

    $dir_part  = trim($_POST['new_dir']  ?? '');
    $name_part = trim($_POST['new_name'] ?? '');
    $raw = ($dir_part ? $dir_part . '/' : '') . $name_part;
    // Force .html extension
    if (!str_ends_with(strtolower($raw), '.html')) $raw .= '.html';
    // Strip leading slash and sanitise
    $raw = ltrim(str_replace(['\\','..'], ['/','.'], $raw), '/');
    $abs = SITE_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $raw);
    $dir = dirname($abs);

    if (file_exists($abs)) {
        $msg = '✗ File already exists'; $msg_type = 'err';
    } elseif (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        $msg = '✗ Could not create directory'; $msg_type = 'err';
    } elseif (strpos(realpath($dir), SITE_ROOT) !== 0) {
        $msg = '✗ Path outside site root'; $msg_type = 'err';
    } else {
        // Depth-aware relative path to css/
        $depth  = substr_count($raw, '/');
        $cssrel = str_repeat('../', $depth) . 'css/';
        $title  = ucwords(str_replace(['-','_'], ' ', basename($raw, '.html')));
        $tpl    = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} — Bosniak Studies</title>
    <link rel="stylesheet" href="{$cssrel}style.css">
    <link rel="stylesheet" href="{$cssrel}responsive.css">
</head>
<body>

    <main class="container">
        <h1>{$title}</h1>
        <p>Edit this page content.</p>
    </main>

</body>
</html>
HTML;
        if (file_put_contents($abs, $tpl) !== false) {
            $redirect = '?file=' . urlencode($raw) . '&msg=created';
        } else {
            $msg = '✗ Write failed — check permissions'; $msg_type = 'err';
        }
    }
    if ($redirect) { header('Location: ' . $redirect); exit; }
}

// ── delete file ───────────────────────────────────────────────────────────────
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete_file'
    && isset($_POST['file'], $_POST['csrf'])
    && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {

    $abs = safe_path($_POST['file']);
    if ($abs && file_exists($abs)) {
        if (unlink($abs)) {
            header('Location: ?msg=deleted&deleted=' . urlencode(basename($abs))); exit;
        } else {
            $msg = '✗ Delete failed — check permissions'; $msg_type = 'err';
        }
    } else { $msg = '✗ File not found'; $msg_type = 'err'; }
}

// ── save ──────────────────────────────────────────────────────────────────────
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['content'], $_POST['file'], $_POST['csrf'])
    && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
    $abs = safe_path($_POST['file']);
    if ($abs && file_exists($abs)) {
        if (file_put_contents($abs, $_POST['content']) !== false) {
            $msg = '✓ Saved — ' . basename($abs); $msg_type = 'ok';
        } else {
            $msg = '✗ Write failed — check permissions'; $msg_type = 'err';
        }
    } else { $msg = '✗ Invalid path'; $msg_type = 'err'; }
}

// ── flash messages from redirect ──────────────────────────────────────────────
if (!$msg && isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') {
        $msg = '✓ Page created'; $msg_type = 'ok';
    } elseif ($_GET['msg'] === 'deleted') {
        $msg = '✓ Deleted — ' . basename($_GET['deleted'] ?? ''); $msg_type = 'ok';
    }
}

// ── load file ─────────────────────────────────────────────────────────────────
$current_file = ''; $file_content = ''; $file_content_js = 'null';
if ($authed && isset($_GET['file'])) {
    $abs = safe_path($_GET['file']);
    if ($abs && file_exists($abs)) {
        $current_file    = rel_path($abs);
        $file_content    = file_get_contents($abs);
        $file_content_js = json_encode($file_content);
    }
}
$file_tree = $authed ? get_file_tree() : [];
$csrf = $_SESSION['csrf'] ?? '';
$is_html = $current_file && strtolower(pathinfo($current_file, PATHINFO_EXTENSION)) === 'html';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CMS — Bosniak Studies</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#1e1e2e;--sidebar:#181825;--sidebar-hover:#313244;--sidebar-active:#45475a;
  --border:#313244;--topbar:#11111b;--text:#cdd6f4;--muted:#6c7086;
  --accent:#89b4fa;--green:#a6e3a1;--red:#f38ba8;--yellow:#f9e2af;
  --toolbar:#2a2a3d;--toolbar-btn:#3a3a55;--toolbar-hover:#4a4a6a;
  --font:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
html,body{height:100%;overflow:hidden;background:var(--bg);color:var(--text);
  font-family:var(--font);font-size:13px}

/* ── login ── */
.login-wrap{display:flex;align-items:center;justify-content:center;height:100vh}
.login-box{background:var(--sidebar);border:1px solid var(--border);border-radius:12px;
  padding:40px 48px;width:360px;text-align:center}
.login-box h1{font-size:20px;font-weight:700;margin-bottom:4px}
.login-box p{color:var(--muted);font-size:12px;margin-bottom:28px}
.login-box input{width:100%;padding:10px 14px;background:#11111b;border:1px solid var(--border);
  border-radius:8px;color:var(--text);font-size:14px;margin-bottom:12px}
.login-box input:focus{outline:none;border-color:var(--accent)}
.login-box button{width:100%;padding:10px;background:var(--accent);border:none;
  border-radius:8px;color:#1e1e2e;font-weight:700;font-size:14px;cursor:pointer}
.login-box button:hover{opacity:.9}
.login-err{color:var(--red);font-size:12px;margin-top:10px}

/* ── layout ── */
.layout{display:grid;grid-template-columns:240px 1fr;
  grid-template-rows:46px 1fr;height:100vh}

/* ── topbar ── */
.topbar{grid-column:1/-1;background:var(--topbar);border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:8px;padding:0 14px}
.topbar-logo{font-weight:700;font-size:14px;color:var(--accent);white-space:nowrap;margin-right:4px}
.topbar-file{flex:1;color:var(--muted);font-size:12px;
  font-family:'Fira Code',Consolas,monospace;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.topbar-file span{color:var(--text)}
#msg{font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap}
#msg.ok{background:rgba(166,227,161,.15);color:var(--green)}
#msg.err{background:rgba(243,139,168,.15);color:var(--red)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:5px 13px;border:none;
  border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;
  transition:opacity .15s;text-decoration:none}
.btn-save{background:var(--green);color:#1e1e2e}
.btn-save:hover{opacity:.85}
.btn-save:disabled{opacity:.35;cursor:default}
.btn-preview{background:var(--toolbar-btn);color:var(--text)}
.btn-preview:hover{opacity:.8}
.btn-logout{background:transparent;color:var(--muted);border:1px solid var(--border)}
.btn-logout:hover{color:var(--red);border-color:var(--red)}

/* ── sidebar ── */
.sidebar{background:var(--sidebar);border-right:1px solid var(--border);
  overflow-y:auto;display:flex;flex-direction:column}
.sidebar::-webkit-scrollbar{width:4px}
.sidebar::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.sidebar-search{margin:10px 10px 4px;padding:6px 10px;background:#11111b;
  border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;width:calc(100% - 20px)}
.sidebar-search:focus{outline:none;border-color:var(--accent)}
.tree-dir{padding:10px 12px 3px;font-size:10px;font-weight:700;letter-spacing:.8px;
  text-transform:uppercase;color:var(--muted)}
.tree-file{display:block;padding:5px 12px 5px 20px;color:#bac2de;text-decoration:none;
  font-family:'Fira Code',Consolas,monospace;font-size:12px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:background .1s}
.tree-file:hover{background:var(--sidebar-hover);color:var(--text)}
.tree-file.active{background:var(--sidebar-active);color:var(--accent)}

/* ── editor pane ── */
.editor-pane{display:flex;flex-direction:column;overflow:hidden;position:relative}

/* ── mode tabs ── */
.mode-tabs{display:flex;align-items:center;gap:2px;padding:6px 10px;
  background:var(--topbar);border-bottom:1px solid var(--border);flex-shrink:0}
.mode-tab{padding:4px 14px;border:1px solid transparent;border-radius:5px;
  font-size:12px;font-weight:600;cursor:pointer;background:transparent;color:var(--muted)}
.mode-tab:hover{color:var(--text)}
.mode-tab.active{background:var(--toolbar-btn);color:var(--accent);border-color:var(--border)}
.tab-spacer{flex:1}

/* ── wysiwyg toolbar ── */
.wysiwyg-toolbar{display:flex;align-items:center;gap:2px;flex-wrap:wrap;padding:5px 8px;
  background:var(--toolbar);border-bottom:1px solid var(--border);flex-shrink:0}
.tb{padding:4px 7px;border:none;border-radius:4px;background:var(--toolbar-btn);
  color:var(--text);font-size:12px;cursor:pointer;line-height:1;transition:background .1s;
  min-width:28px;text-align:center}
.tb:hover{background:var(--toolbar-hover)}
.tb.active{background:var(--accent);color:#1e1e2e}
.tb-sep{width:1px;height:18px;background:var(--border);margin:0 3px;flex-shrink:0}
.tb-select{padding:4px 6px;border:1px solid var(--border);border-radius:4px;
  background:var(--toolbar-btn);color:var(--text);font-size:12px;cursor:pointer}
.tb-select:focus{outline:none;border-color:var(--accent)}

/* ── wysiwyg iframe ── */
#wysiwyg-wrap{flex:1;overflow:hidden;display:none}
#wysiwyg-frame{width:100%;height:100%;border:none;background:#fff}

/* ── source editor ── */
#source-wrap{flex:1;overflow:hidden;display:none}
#source-wrap .CodeMirror{height:100%;font-size:13px;
  font-family:'Fira Code',Consolas,monospace;line-height:1.6}
#source-wrap .CodeMirror-scroll{height:100%}

/* ── no-file placeholder ── */
.no-file{display:flex;flex-direction:column;align-items:center;justify-content:center;
  height:100%;color:var(--muted);gap:12px}
.no-file-icon{font-size:52px;opacity:.25}

/* ── find bar ── */
.find-bar{display:none;align-items:center;gap:6px;padding:5px 10px;
  background:var(--topbar);border-top:1px solid var(--border)}
.find-bar.open{display:flex}
.find-bar input{padding:4px 8px;background:#11111b;border:1px solid var(--border);
  border-radius:4px;color:var(--text);font-size:12px;width:180px}
.find-bar input:focus{outline:none;border-color:var(--accent)}
.find-close{margin-left:auto;background:none;border:none;color:var(--muted);cursor:pointer;font-size:16px}
.find-close:hover{color:var(--red)}

/* ── modal overlay ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);
  z-index:200;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:#1e1e2e;border:1px solid var(--border);border-radius:10px;
  width:420px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.modal-head{padding:18px 20px 14px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px}
.modal-head h3{flex:1;font-size:15px;font-weight:700}
.modal-head button{background:none;border:none;color:var(--muted);
  cursor:pointer;font-size:18px;line-height:1}
.modal-head button:hover{color:var(--red)}
.modal-body{padding:20px}
.modal-body label{display:block;font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:6px}
.modal-body input,.modal-body select{width:100%;padding:8px 10px;background:#11111b;
  border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:13px;
  font-family:'Fira Code',Consolas,monospace}
.modal-body input:focus,.modal-body select:focus{outline:none;border-color:var(--accent)}
.modal-body .hint{font-size:11px;color:var(--muted);margin-top:6px}
.modal-foot{padding:14px 20px;border-top:1px solid var(--border);
  display:flex;gap:8px;justify-content:flex-end}
.btn-new{background:var(--accent);color:#1e1e2e}
.btn-new:hover{opacity:.85}
.btn-del{background:transparent;color:var(--red);border:1px solid var(--red)}
.btn-del:hover{background:var(--red);color:#fff}

/* ── sidebar footer ── */
.sidebar-footer{padding:10px;border-top:1px solid var(--border);margin-top:auto}
</style>
</head>
<body>

<?php if (!$authed): ?>
<div class="login-wrap">
  <div class="login-box">
    <h1>✦ Bosniak Studies CMS</h1>
    <p>Enter your password to continue</p>
    <form method="POST">
      <input type="password" name="password" placeholder="Password" autofocus>
      <button type="submit">Sign In →</button>
    </form>
    <?php if (!empty($login_error)): ?>
      <div class="login-err"><?= h($login_error) ?></div>
    <?php endif ?>
  </div>
</div>

<?php else: ?>
<div class="layout">

  <!-- Top bar -->
  <div class="topbar">
    <div class="topbar-logo">✦ CMS</div>
    <div class="topbar-file">
      <?php if ($current_file): ?>
        <span><?= h($current_file) ?></span>
      <?php else: ?>
        <span style="color:var(--muted)">← select a file to edit</span>
      <?php endif ?>
    </div>
    <div id="msg" class="<?= h($msg_type) ?>"><?= h($msg) ?></div>
    <?php if ($current_file): ?>
      <a class="btn btn-preview" href="<?= h(preview_url($current_file)) ?>" target="_blank">👁 Preview</a>
      <button class="btn btn-save" id="btn-save" onclick="doSave()" disabled>💾 Save</button>
      <button class="btn btn-del" onclick="openDeleteModal()" title="Delete this file">🗑</button>
    <?php endif ?>
    <a class="btn btn-logout" href="?logout">Sign out</a>
  </div>

  <!-- Sidebar -->
  <div class="sidebar">
    <input class="sidebar-search" type="text" placeholder="🔍 filter files…"
           oninput="filterTree(this.value)">
    <div id="tree">
      <?php foreach ($file_tree as $dir => $files): ?>
        <div class="tree-group">
          <div class="tree-dir"><?= h($dir ?: '/ root') ?></div>
          <?php foreach ($files as $rel): ?>
            <?php $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION)); ?>
            <a class="tree-file<?= $rel === $current_file ? ' active' : '' ?>"
               href="?file=<?= urlencode($rel) ?>"
               data-name="<?= h(strtolower(basename($rel))) ?>"
               title="<?= h($rel) ?>">
              <?= file_icon($ext) ?> <?= h(basename($rel)) ?>
            </a>
          <?php endforeach ?>
        </div>
      <?php endforeach ?>
    </div>
    <div class="sidebar-footer">
      <button class="btn btn-new" style="width:100%;justify-content:center"
              onclick="openNewModal()">＋ New Page</button>
    </div>
  </div>

  <!-- Editor pane -->
  <div class="editor-pane" id="editor-pane">

    <?php if ($current_file): ?>

    <!-- Mode tabs -->
    <div class="mode-tabs">
      <?php if ($is_html): ?>
        <button class="mode-tab active" id="tab-wysiwyg" onclick="setMode('wysiwyg')">✏ Visual</button>
        <button class="mode-tab" id="tab-source" onclick="setMode('source')">⌨ Source</button>
      <?php else: ?>
        <button class="mode-tab active" id="tab-source">⌨ Source</button>
      <?php endif ?>
      <div class="tab-spacer"></div>
      <button class="tb" onclick="toggleFind()" title="Find (Ctrl+F)" style="font-size:11px">🔍 Find</button>
    </div>

    <!-- WYSIWYG toolbar (HTML files only) -->
    <?php if ($is_html): ?>
    <div class="wysiwyg-toolbar" id="wysiwyg-toolbar">
      <button class="tb" onclick="exec('undo')"  title="Undo (Ctrl+Z)">↺</button>
      <button class="tb" onclick="exec('redo')"  title="Redo (Ctrl+Y)">↻</button>
      <div class="tb-sep"></div>
      <button class="tb" id="tb-bold"      onclick="exec('bold')"          title="Bold"><b>B</b></button>
      <button class="tb" id="tb-italic"    onclick="exec('italic')"        title="Italic"><i>I</i></button>
      <button class="tb" id="tb-underline" onclick="exec('underline')"     title="Underline"><u>U</u></button>
      <button class="tb" id="tb-strike"    onclick="exec('strikeThrough')" title="Strikethrough"><s>S</s></button>
      <div class="tb-sep"></div>
      <select class="tb-select" onchange="formatBlock(this.value); this.selectedIndex=0" title="Heading / Paragraph">
        <option value="">¶ Format…</option>
        <option value="h1">Heading 1</option>
        <option value="h2">Heading 2</option>
        <option value="h3">Heading 3</option>
        <option value="h4">Heading 4</option>
        <option value="p">Paragraph</option>
        <option value="blockquote">Blockquote</option>
        <option value="pre">Preformatted</option>
      </select>
      <div class="tb-sep"></div>
      <button class="tb" onclick="exec('justifyLeft')"   title="Align left">⬛◻◻</button>
      <button class="tb" onclick="exec('justifyCenter')" title="Center">◻⬛◻</button>
      <button class="tb" onclick="exec('justifyRight')"  title="Align right">◻◻⬛</button>
      <div class="tb-sep"></div>
      <button class="tb" onclick="exec('insertUnorderedList')" title="Bullet list">• List</button>
      <button class="tb" onclick="exec('insertOrderedList')"   title="Numbered list">1. List</button>
      <div class="tb-sep"></div>
      <button class="tb" onclick="doInsertLink()"  title="Insert link">🔗 Link</button>
      <button class="tb" onclick="exec('unlink')"  title="Remove link">✂ Unlink</button>
      <button class="tb" onclick="doInsertImage()" title="Insert image">🖼 Image</button>
      <div class="tb-sep"></div>
      <button class="tb" onclick="exec('removeFormat')" title="Clear formatting">✕ Format</button>
    </div>
    <?php endif ?>

    <!-- WYSIWYG iframe -->
    <?php if ($is_html): ?>
    <div id="wysiwyg-wrap">
      <iframe id="wysiwyg-frame" src="about:blank" sandbox="allow-same-origin allow-scripts"></iframe>
    </div>
    <?php endif ?>

    <!-- Source editor -->
    <div id="source-wrap">
      <form id="editor-form" method="POST" action="?file=<?= urlencode($current_file) ?>">
        <input type="hidden" name="file"    value="<?= h($current_file) ?>">
        <input type="hidden" name="csrf"    value="<?= h($csrf) ?>">
        <input type="hidden" name="content" id="content-field">
        <textarea id="editor-textarea"></textarea>
      </form>
    </div>

    <!-- Find bar -->
    <div class="find-bar" id="find-bar">
      <input type="text" id="find-input" placeholder="Find…" oninput="doFind()" onkeydown="findKey(event)">
      <input type="text" id="replace-input" placeholder="Replace with…">
      <button class="tb" onclick="doReplace()">Replace</button>
      <button class="tb" onclick="doReplaceAll()">All</button>
      <button class="find-close" onclick="toggleFind()">✕</button>
    </div>

    <?php else: ?>
    <!-- No file selected -->
    <div class="no-file">
      <div class="no-file-icon">📂</div>
      <p>Select a file from the sidebar to start editing.</p>
    </div>
    <?php endif ?>

  </div><!-- /editor-pane -->

</div><!-- /layout -->

<!-- ── New Page Modal ── -->
<div class="modal-overlay" id="new-modal" onclick="modalOverlayClick(event,'new-modal')">
  <div class="modal">
    <div class="modal-head">
      <h3>＋ New Page</h3>
      <button onclick="closeModal('new-modal')">✕</button>
    </div>
    <form method="POST" action="<?= h(strtok($_SERVER['REQUEST_URI'],'?')) ?>">
      <input type="hidden" name="action" value="new_file">
      <input type="hidden" name="csrf"   value="<?= h($csrf) ?>">
      <div class="modal-body">
        <label>Directory</label>
        <select name="new_dir" id="new-dir" onchange="updatePathPreview()">
          <option value="">/ (root)</option>
          <?php
            $dirs = array_unique(array_merge(['pages', 'pages/associates'], array_keys($file_tree)));
            sort($dirs);
            foreach ($dirs as $d):
              if (!$d || $d === '.') continue;
          ?>
          <option value="<?= h($d) ?>"><?= h($d) ?>/</option>
          <?php endforeach ?>
        </select>
        <br><br>
        <label>Filename <span style="color:var(--muted);font-weight:400">(without .html)</span></label>
        <input type="text" name="new_name" id="new-name" placeholder="my-new-page"
               oninput="updatePathPreview()" autocomplete="off" spellcheck="false">
        <p class="hint">Will create: <code id="path-preview" style="color:var(--accent)">—</code></p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('new-modal')">Cancel</button>
        <button type="submit" class="btn btn-new" id="new-submit" disabled>Create Page →</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="del-modal" onclick="modalOverlayClick(event,'del-modal')">
  <div class="modal">
    <div class="modal-head">
      <h3>🗑 Delete File</h3>
      <button onclick="closeModal('del-modal')">✕</button>
    </div>
    <form method="POST" action="<?= h(strtok($_SERVER['REQUEST_URI'],'?')) ?>">
      <input type="hidden" name="action" value="delete_file">
      <input type="hidden" name="csrf"   value="<?= h($csrf) ?>">
      <input type="hidden" name="file"   value="<?= h($current_file) ?>">
      <div class="modal-body">
        <p style="line-height:1.7">
          Permanently delete<br>
          <code style="color:var(--red);font-size:13px"><?= h($current_file) ?></code>?
        </p>
        <p class="hint" style="margin-top:10px">This cannot be undone.</p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="closeModal('del-modal')">Cancel</button>
        <button type="submit" class="btn btn-del">Yes, delete</button>
      </div>
    </form>
  </div>
</div>

<!-- hidden save form for WYSIWYG mode -->
<?php if ($current_file): ?>
<form id="wysiwyg-form" method="POST" action="?file=<?= urlencode($current_file) ?>" style="display:none">
  <input type="hidden" name="file"    value="<?= h($current_file) ?>">
  <input type="hidden" name="csrf"    value="<?= h($csrf) ?>">
  <input type="hidden" name="content" id="wysiwyg-content-field">
</form>
<?php endif ?>

<?php endif ?><!-- /authed -->

<!-- CodeMirror -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/selection/active-line.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/search/searchcursor.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/dialog/dialog.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/keymap/sublime.min.js"></script>

<script>
<?php if ($authed && $current_file): ?>
// ── State ────────────────────────────────────────────────────────────────────
var CURRENT_FILE   = <?= json_encode($current_file) ?>;
var IS_HTML        = <?= $is_html ? 'true' : 'false' ?>;
var INITIAL_CONTENT= <?= $file_content_js ?>;
var PREVIEW_URL    = <?= json_encode(preview_url($current_file)) ?>;
// Build a base URL so the iframe resolves assets correctly
var SITE_ORIGIN    = window.location.origin;
var FILE_BASE      = SITE_ORIGIN + '/' + CURRENT_FILE;

var cm      = null;
var dirty   = false;
var curMode = IS_HTML ? 'wysiwyg' : 'source';
var findCursor = null;

// ── CodeMirror ────────────────────────────────────────────────────────────────
(function initCM(){
  var ta = document.getElementById('editor-textarea');
  if (!ta) return;
  cm = CodeMirror.fromTextArea(ta, {
    mode:            <?= json_encode(cm_mode($current_file)) ?>,
    theme:           'dracula',
    lineNumbers:     true,
    tabSize:         2,
    indentWithTabs:  false,
    matchBrackets:   true,
    autoCloseTags:   true,
    styleActiveLine: true,
    keyMap:          'sublime',
    extraKeys: {
      'Ctrl-S': doSave,
      'Cmd-S':  doSave,
      'Ctrl-F': function(){ if (curMode==='source') toggleFind(); },
    }
  });
  cm.setValue(INITIAL_CONTENT);
  cm.clearHistory();
  cm.on('change', markDirty);
})();

// ── WYSIWYG ───────────────────────────────────────────────────────────────────
function loadFrame(html) {
  var frame = document.getElementById('wysiwyg-frame');
  if (!frame) return;

  // Inject <base> so relative assets (CSS, images) load correctly
  var baseTag = '<base href="' + FILE_BASE + '" data-cms-base>';
  if (!/<base[\s>]/i.test(html)) {
    html = html.replace(/(<head[^>]*>)/i, '$1\n  ' + baseTag);
  }

  var doc = frame.contentDocument;
  doc.open();
  doc.write(html);
  doc.close();

  // Small delay lets browser finish parsing before designMode
  setTimeout(function(){
    doc.designMode = 'on';

    // Intercept link clicks so they don't navigate the frame
    doc.addEventListener('click', function(e){
      var a = e.target.closest ? e.target.closest('a') : null;
      if (a) { e.preventDefault(); e.stopPropagation(); }
    }, true);

    // Track edits
    doc.addEventListener('input',   markDirty);
    doc.addEventListener('keydown', function(e){
      if ((e.ctrlKey||e.metaKey) && e.key==='s'){ e.preventDefault(); doSave(); }
    });

    // Keep toolbar state in sync with cursor position
    doc.addEventListener('keyup',   updateToolbarState);
    doc.addEventListener('mouseup', updateToolbarState);
  }, 60);
}

function getFrameHTML() {
  var frame = document.getElementById('wysiwyg-frame');
  if (!frame || !frame.contentDocument) return INITIAL_CONTENT;
  var doc   = frame.contentDocument;
  var clone = doc.documentElement.cloneNode(true);
  // Remove our injected base tag
  var injected = clone.querySelector('base[data-cms-base]');
  if (injected) injected.remove();
  // Get original doctype string
  var dt = doc.doctype
    ? '<!DOCTYPE ' + doc.doctype.name + '>\n'
    : '<!DOCTYPE html>\n';
  return dt + clone.outerHTML;
}

// ── Mode switching ────────────────────────────────────────────────────────────
function setMode(m) {
  if (m === curMode) return;

  if (m === 'wysiwyg') {
    // Source → WYSIWYG: push CodeMirror content into iframe
    loadFrame(cm.getValue());
    document.getElementById('source-wrap').style.display  = 'none';
    document.getElementById('wysiwyg-wrap').style.display = 'block';
    document.getElementById('wysiwyg-toolbar').style.display = 'flex';
    document.getElementById('tab-wysiwyg').classList.add('active');
    document.getElementById('tab-source').classList.remove('active');
    // Close find bar
    document.getElementById('find-bar').classList.remove('open');
  } else {
    // WYSIWYG → Source: pull iframe content into CodeMirror
    var html = getFrameHTML();
    cm.setValue(html);
    document.getElementById('wysiwyg-wrap').style.display = 'none';
    document.getElementById('source-wrap').style.display  = 'block';
    document.getElementById('wysiwyg-toolbar').style.display = 'none';
    document.getElementById('tab-source').classList.add('active');
    document.getElementById('tab-wysiwyg').classList.remove('active');
    resizeCM();
  }

  curMode = m;
}

function resizeCM() {
  if (!cm) return;
  var wrap  = document.getElementById('source-wrap');
  if (wrap) cm.setSize('100%', wrap.offsetHeight + 'px');
}

// ── Toolbar execCommand ───────────────────────────────────────────────────────
function exec(cmd, val) {
  var frame = document.getElementById('wysiwyg-frame');
  if (!frame) return;
  frame.contentDocument.execCommand(cmd, false, val || null);
  frame.contentWindow.focus();
  markDirty();
  updateToolbarState();
}

function formatBlock(tag) {
  if (!tag) return;
  exec('formatBlock', '<' + tag + '>');
}

function doInsertLink() {
  var frame = document.getElementById('wysiwyg-frame');
  var sel   = frame ? (frame.contentWindow.getSelection().toString() || '') : '';
  var url   = prompt('Link URL:', 'https://');
  if (url && url !== 'https://') {
    if (sel) exec('createLink', url);
    else {
      var text = prompt('Link text:', url);
      exec('insertHTML', '<a href="'+url+'">'+(text||url)+'</a>');
    }
  }
}

function doInsertImage() {
  var url = prompt('Image URL:', 'https://');
  if (url && url !== 'https://') {
    var alt = prompt('Alt text (optional):', '');
    exec('insertHTML', '<img src="'+url+'" alt="'+(alt||'')+'">');
  }
}

function updateToolbarState() {
  var frame = document.getElementById('wysiwyg-frame');
  if (!frame || !frame.contentDocument) return;
  var doc = frame.contentDocument;
  ['bold','italic','underline','strikeThrough'].forEach(function(cmd){
    var id = 'tb-' + cmd.toLowerCase().replace('strikethrough','strike');
    var el = document.getElementById(id);
    if (el) el.classList.toggle('active', doc.queryCommandState(cmd));
  });
}

// ── Save ──────────────────────────────────────────────────────────────────────
function doSave() {
  var content = (curMode === 'wysiwyg') ? getFrameHTML() : cm.getValue();

  if (curMode === 'wysiwyg') {
    document.getElementById('wysiwyg-content-field').value = content;
    document.getElementById('wysiwyg-form').submit();
  } else {
    document.getElementById('content-field').value = content;
    document.getElementById('editor-form').submit();
  }
}

function markDirty() {
  if (dirty) return;
  dirty = true;
  var btn = document.getElementById('btn-save');
  if (btn) btn.disabled = false;
  var m = document.getElementById('msg');
  if (m) { m.textContent = ''; m.className = ''; }
}

window.addEventListener('beforeunload', function(e){
  if (dirty){ e.preventDefault(); e.returnValue=''; }
});

// ── Find / Replace (source mode) ─────────────────────────────────────────────
function toggleFind() {
  var bar = document.getElementById('find-bar');
  bar.classList.toggle('open');
  if (bar.classList.contains('open')) document.getElementById('find-input').focus();
  else if (cm) cm.focus();
}

function doFind() {
  if (!cm) return;
  var q = document.getElementById('find-input').value;
  if (!q) return;
  findCursor = cm.getSearchCursor(q, cm.getCursor());
  if (!findCursor.find()) findCursor = cm.getSearchCursor(q, {line:0,ch:0});
  if (findCursor.find()) cm.setSelection(findCursor.from(), findCursor.to());
}

function findKey(e) {
  if (e.key === 'Enter') { e.preventDefault(); doFind(); }
  if (e.key === 'Escape') toggleFind();
}

function doReplace() {
  if (!cm || !findCursor) return;
  var r = document.getElementById('replace-input').value;
  findCursor.replace(r);
  markDirty();
  doFind();
}

function doReplaceAll() {
  if (!cm) return;
  var q = document.getElementById('find-input').value;
  var r = document.getElementById('replace-input').value;
  if (!q) return;
  var cur = cm.getSearchCursor(q);
  var n = 0;
  while (cur.findNext()) { cur.replace(r); n++; }
  if (n) markDirty();
  showMsg('Replaced ' + n + ' occurrence' + (n!==1?'s':''), 'ok');
}

// ── Init ──────────────────────────────────────────────────────────────────────
function showMsg(text, type) {
  var m = document.getElementById('msg');
  if (!m) return;
  m.textContent = text; m.className = type;
  setTimeout(function(){ m.textContent=''; m.className=''; }, 3000);
}

window.addEventListener('resize', resizeCM);

(function init(){
  if (IS_HTML) {
    // Default to WYSIWYG for HTML files
    document.getElementById('wysiwyg-wrap').style.display = 'block';
    document.getElementById('source-wrap').style.display  = 'none';
    document.getElementById('wysiwyg-toolbar').style.display = 'flex';
    loadFrame(INITIAL_CONTENT);
  } else {
    // CSS/JS: source only
    document.getElementById('source-wrap').style.display = 'block';
    resizeCM();
  }
})();

<?php if ($msg): ?>
setTimeout(function(){ showMsg('', ''); }, 3000);
<?php endif ?>

<?php else: ?>
function doSave(){}
<?php endif ?>

// ── Modals (always available) ─────────────────────────────────────────────────
function openNewModal() {
  var el = document.getElementById('new-name');
  if (el) el.value = '';
  var pp = document.getElementById('path-preview');
  if (pp) pp.textContent = '—';
  var ns = document.getElementById('new-submit');
  if (ns) ns.disabled = true;
  var m = document.getElementById('new-modal');
  if (m) { m.classList.add('open'); setTimeout(function(){ if(el) el.focus(); }, 60); }
}
function openDeleteModal() {
  var m = document.getElementById('del-modal');
  if (m) m.classList.add('open');
}
function closeModal(id) {
  var m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
function modalOverlayClick(e, id) {
  if (e.target === document.getElementById(id)) closeModal(id);
}
function updatePathPreview() {
  var dir  = document.getElementById('new-dir').value;
  var name = document.getElementById('new-name').value.trim()
    .replace(/[^a-zA-Z0-9\-_]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
  var path = (dir ? dir + '/' : '') + (name || '…') + '.html';
  document.getElementById('path-preview').textContent = path;
  document.getElementById('new-submit').disabled = !name;
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') { closeModal('new-modal'); closeModal('del-modal'); }
});

// sidebar filter (always needed)
function filterTree(q) {
  q = (q || '').toLowerCase();
  document.querySelectorAll('.tree-file').forEach(function(el) {
    el.style.display = (!q || el.dataset.name.includes(q)) ? '' : 'none';
  });
  document.querySelectorAll('.tree-group').forEach(function(g) {
    var vis = Array.from(g.querySelectorAll('.tree-file')).some(function(f) {
      return f.style.display !== 'none';
    });
    g.style.display = vis ? '' : 'none';
  });
}
</script>
</body>
</html>
