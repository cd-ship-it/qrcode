<?php
session_start();
require __DIR__ . '/../config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (ADMIN_PASSWORD !== '' && hash_equals(ADMIN_PASSWORD, $_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Incorrect password';
}

$authenticated = !empty($_SESSION['admin_authenticated']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>QR Admin</title>
<style>
  :root {
    --bg: #0f1115;
    --card: #171a21;
    --border: #2a2e38;
    --text: #e8eaed;
    --muted: #9aa0ab;
    --accent: #f7941d;
    --accent-blue: #0b5fa5;
    --danger: #c0392b;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    background: var(--bg);
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }
  .login-wrap {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }
  .login-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 36px;
    width: 100%;
    max-width: 360px;
  }
  .login-card h1 { font-size: 20px; margin: 0 0 20px; }
  input[type="password"] {
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: #0f1115;
    color: var(--text);
    font-size: 14px;
    outline: none;
  }
  input[type="password"]:focus { border-color: var(--accent-blue); }
  button {
    margin-top: 14px;
    width: 100%;
    padding: 12px 14px;
    border-radius: 10px;
    border: none;
    background: var(--accent-blue);
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
  }
  button:hover { opacity: 0.92; }
  .error {
    color: #ff8a80;
    font-size: 13px;
    margin-top: 10px;
  }
  .wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 24px;
  }
  .top-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 28px;
  }
  h1.dash { font-size: 26px; margin: 0 0 4px; }
  p.sub { color: var(--muted); margin: 0; }
  a.logout { color: var(--muted); font-size: 13px; text-decoration: none; }
  a.logout:hover { color: var(--text); }
  .stats { display: flex; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
  .stat {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 24px;
    min-width: 140px;
  }
  .stat .n { font-size: 28px; font-weight: 700; }
  .stat .l { font-size: 12px; color: var(--muted); margin-top: 4px; }
  table {
    width: 100%;
    border-collapse: collapse;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
  }
  th, td {
    text-align: left;
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    vertical-align: top;
  }
  th {
    color: var(--muted);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.04em;
  }
  tr:last-child td { border-bottom: none; }
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; }
  .pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    background: rgba(11, 95, 165, 0.15);
    color: #7db7e8;
    font-size: 11px;
  }
  a { color: #7db7e8; }
  .target { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  button.copy, button.del {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--muted);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 11px;
    cursor: pointer;
    margin: 4px 6px 0 0;
    width: auto;
  }
  button.copy:hover { border-color: var(--accent-blue); color: var(--text); }
  button.del:hover { border-color: var(--danger); color: #ff8a80; }
  .empty { color: var(--muted); text-align: center; padding: 40px; }
  #toast {
    position: fixed;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--accent-blue);
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s;
  }
  #toast.show { opacity: 1; }
  button.qr {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--muted);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 11px;
    cursor: pointer;
    margin: 4px 6px 0 0;
    width: auto;
  }
  button.qr:hover { border-color: var(--accent); color: var(--text); }
  .qr-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 100;
  }
  .qr-modal-overlay.show { display: flex; }
  .qr-modal {
    position: relative;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px;
    width: 100%;
    max-width: 360px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
  }
  .qr-modal h3 { margin: 0; font-size: 15px; text-align: center; }
  .qr-modal .qr-frame {
    background: white;
    padding: 14px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 160px;
  }
  .qr-modal .qr-frame canvas { max-width: 100%; width: 100%; height: auto; display: block; }
  .qr-modal .qr-frame p { font-size: 13px; color: var(--muted); margin: 0; }
  .qr-modal-close {
    position: absolute;
    top: 10px;
    right: 12px;
    background: transparent;
    border: none;
    color: var(--muted);
    font-size: 20px;
    line-height: 1;
    padding: 4px;
    width: auto;
    cursor: pointer;
  }
  .qr-modal-close:hover { color: var(--text); }
</style>
</head>
<body>

<?php if (!$authenticated): ?>

  <div class="login-wrap">
    <div class="login-card">
      <h1>QR Admin Login</h1>
      <form method="post">
        <input type="password" name="password" placeholder="Admin password" autofocus required />
        <button type="submit">Log in</button>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      </form>
    </div>
  </div>

<?php else: ?>

  <div class="wrap">
    <div class="top-row">
      <div>
        <h1 class="dash">QR Tracking Admin</h1>
        <p class="sub">Every tracked QR code redirects through <code>/r/:id</code> with a 303, logging each scan.</p>
      </div>
      <a class="logout" href="?logout=1">Log out</a>
    </div>

    <div class="stats">
      <div class="stat"><div class="n" id="stat-links">–</div><div class="l">Tracked links</div></div>
      <div class="stat"><div class="n" id="stat-clicks">–</div><div class="l">Total scans</div></div>
    </div>

    <table id="table">
      <thead>
        <tr>
          <th>Label</th>
          <th>Style</th>
          <th>Target</th>
          <th>Short link</th>
          <th>Created</th>
          <th>Clicks</th>
          <th>Last scan</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="rows"></tbody>
    </table>
    <div id="empty" class="empty" style="display:none;">No tracked QR codes yet.</div>
  </div>

  <div id="toast"></div>

  <div id="qr-modal-overlay" class="qr-modal-overlay">
    <div class="qr-modal">
      <button class="qr-modal-close" id="qr-modal-close">&times;</button>
      <h3 id="qr-modal-title"></h3>
      <div class="qr-frame" id="qr-modal-frame"><p>Generating…</p></div>
      <button id="qr-modal-download">Download PNG</button>
    </div>
  </div>

<script src="https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js"></script>
<script src="../assets/qrcode-gen.js"></script>
<script>
  function toast(msg) {
    const el = document.getElementById("toast");
    el.textContent = msg;
    el.classList.add("show");
    setTimeout(() => el.classList.remove("show"), 1500);
  }

  function fmt(ts) {
    if (!ts) return "—";
    return new Date(ts).toLocaleString();
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[c]));
  }

  async function load() {
    const res = await fetch("api-links.php");
    if (res.status === 401) {
      location.reload();
      return;
    }
    if (!res.ok) {
      document.body.innerHTML = "<p style='padding:40px;color:#e8eaed;'>Failed to load (" + res.status + ")</p>";
      return;
    }
    const links = await res.json();

    document.getElementById("stat-links").textContent = links.length;
    document.getElementById("stat-clicks").textContent = links.reduce((s, l) => s + (l.clickCount || 0), 0);

    const rows = document.getElementById("rows");
    rows.innerHTML = "";

    if (!links.length) {
      document.getElementById("empty").style.display = "block";
      return;
    }
    document.getElementById("empty").style.display = "none";

    for (const link of links) {
      const tr = document.createElement("tr");
      const shortUrl = `${location.origin}/r/${link.id}`;
      const lastClick = link.clicks && link.clicks.length ? link.clicks[link.clicks.length - 1].ts : null;

      tr.innerHTML = `
        <td>${link.label ? escapeHtml(link.label) : "<span style='color:var(--muted)'>—</span>"}</td>
        <td><span class="pill">${escapeHtml(link.style)}</span></td>
        <td class="target" title="${escapeHtml(link.target)}"><a href="${escapeHtml(link.target)}" target="_blank" rel="noopener">${escapeHtml(link.target)}</a></td>
        <td class="mono">${escapeHtml(shortUrl)}</td>
        <td>${fmt(link.createdAt)}</td>
        <td>${link.clickCount || 0}</td>
        <td>${fmt(lastClick)}</td>
        <td>
          <button class="qr" data-id="${link.id}">QR</button>
          <button class="copy" data-url="${escapeHtml(shortUrl)}">Copy</button>
          <button class="del" data-id="${link.id}">Delete</button>
        </td>
      `;
      rows.appendChild(tr);
    }

    rows.querySelectorAll("button.qr").forEach((btn) => {
      btn.addEventListener("click", () => {
        const link = links.find((l) => l.id === btn.dataset.id);
        if (link) openQrModal(link, `${location.origin}/r/${link.id}`);
      });
    });

    rows.querySelectorAll("button.copy").forEach((btn) => {
      btn.addEventListener("click", () => {
        navigator.clipboard.writeText(btn.dataset.url);
        toast("Copied");
      });
    });

    rows.querySelectorAll("button.del").forEach((btn) => {
      btn.addEventListener("click", async () => {
        if (!confirm("Delete this tracked link? This cannot be undone.")) return;
        const res = await fetch(`api-links.php?id=${encodeURIComponent(btn.dataset.id)}`, { method: "DELETE" });
        if (res.ok) load();
        else toast("Delete failed");
      });
    });
  }

  let modalCanvas = null;
  let modalFilename = "";
  const qrModalOverlay = document.getElementById("qr-modal-overlay");
  const qrModalFrame = document.getElementById("qr-modal-frame");
  const qrModalTitle = document.getElementById("qr-modal-title");

  async function openQrModal(link, shortUrl) {
    modalCanvas = null;
    qrModalTitle.textContent = link.label ? `${link.label} — ${link.style}` : link.style;
    qrModalFrame.innerHTML = "<p>Generating…</p>";
    qrModalOverlay.classList.add("show");

    const style = QRGen.STYLES[link.style] || QRGen.STYLES.brand;
    try {
      const canvas = await QRGen.buildStyleCanvas(style, shortUrl);
      modalCanvas = canvas;
      modalFilename = `qrcode-${link.style}-${link.id}.png`;
      qrModalFrame.innerHTML = "";
      qrModalFrame.appendChild(canvas);
    } catch (e) {
      console.error(e);
      qrModalFrame.innerHTML = `<p style='color:#ff8a80;'>${escapeHtml(e.message || "Failed to generate QR code.")}</p>`;
    }
  }

  function closeQrModal() {
    qrModalOverlay.classList.remove("show");
  }

  document.getElementById("qr-modal-close").addEventListener("click", closeQrModal);
  qrModalOverlay.addEventListener("click", (e) => {
    if (e.target === qrModalOverlay) closeQrModal();
  });
  document.getElementById("qr-modal-download").addEventListener("click", () => {
    if (modalCanvas) QRGen.downloadCanvas(modalCanvas, modalFilename);
  });

  load();
</script>

<?php endif; ?>
</body>
</html>
