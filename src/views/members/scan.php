<?php

/**
 * Scan QR anggota (BarcodeDetector API + fallback manual).
 * Halaman terlindungi permission member.view.
 */
?>
<div class="glass-card rounded-2xl p-5 shadow-sm space-y-4">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <div class="space-y-3">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-camera mr-1 text-brand-600"></i>Pindai via Kamera</h3>
      <div class="relative rounded-xl overflow-hidden bg-slate-900 aspect-[4/3] flex items-center justify-center">
        <video id="scan-video" class="w-full h-full object-cover" muted playsinline></video>
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
          <div class="w-48 h-48 border-2 border-brand-500/80 rounded-xl"></div>
        </div>
        <p id="scan-status" class="absolute bottom-2 left-0 right-0 text-center text-[10px] text-white/80">Kamera belum aktif.</p>
      </div>
      <div class="flex gap-2">
        <button id="btn-start-cam" onclick="startCamera()" class="flex-1 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">
          <i class="fa-solid fa-play mr-1"></i>Aktifkan Kamera
        </button>
        <button onclick="stopCamera()" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold">
          <i class="fa-solid fa-stop mr-1"></i>Stop
        </button>
      </div>
      <p class="text-[10px] text-slate-400">Kamera hanya diaktifkan setelah Anda menekan tombol di atas. QR kartu anggota berisi token verifikasi, bukan data keuangan.</p>
    </div>

    <div class="space-y-3">
      <h3 class="font-display text-sm font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-keyboard mr-1 text-brand-600"></i>Input Manual</h3>
      <p class="text-xs text-slate-500">Jika kamera tidak tersedia atau izin ditolak, masukkan token dari kartu (atau No. Anggota) secara manual.</p>
      <form onsubmit="return manualLookup(event)" class="flex gap-2">
        <input type="text" id="manual-code" placeholder="Token QR / AGT-2026-0001" required
          class="flex-1 px-3 py-2 text-xs border rounded-xl bg-white dark:bg-slate-800 font-mono">
        <button class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Cari</button>
      </form>
      <div id="scan-result" class="hidden rounded-xl border border-slate-200 dark:border-slate-700 p-3 text-xs"></div>
      <div class="bg-brand-50 dark:bg-slate-800 rounded-xl p-3 text-[11px] text-slate-600 dark:text-slate-300">
        <p class="font-bold text-brand-600 mb-1"><i class="fa-solid fa-circle-info mr-1"></i>Catatan hak akses</p>
        Data keuangan anggota hanya tampil bila Anda memiliki permission <code>finance.view</code>.
      </div>
    </div>
  </div>
</div>

<script>
var _scanStream = null;

function startCamera() {
  var status = document.getElementById('scan-status');
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    status.innerText = 'Browser tidak mendukung kamera. Gunakan input manual.';
    return;
  }
  navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (stream) {
    _scanStream = stream;
    var video = document.getElementById('scan-video');
    video.srcObject = stream;
    video.play();
    status.innerText = 'Kamera aktif. Arahkan ke QR kartu anggota...';
    if ('BarcodeDetector' in window) {
      var detector = new BarcodeDetector({ formats: ['qr_code'] });
      var tick = function () {
        if (!_scanStream) return;
        detector.detect(video).then(function (codes) {
          if (codes.length) {
            var token = String(codes[0].rawValue || '');
            var m = token.match(/\/anggota\/verify\/([A-Za-z0-9]+)/);
            lookup(m ? m[1] : token);
            return;
          }
          requestAnimationFrame(tick);
        }).catch(function () { requestAnimationFrame(tick); });
      };
      tick();
    } else {
      status.innerText = 'Browser belum mendukung deteksi QR otomatis (BarcodeDetector). Gunakan input manual.';
    }
  }).catch(function (err) {
    status.innerText = 'Izin kamera ditolak / tidak tersedia. Gunakan input manual.';
  });
}

function stopCamera() {
  if (_scanStream) {
    _scanStream.getTracks().forEach(function (t) { t.stop(); });
    _scanStream = null;
    document.getElementById('scan-status').innerText = 'Kamera dihentikan.';
  }
}

function lookup(code) {
  var box = document.getElementById('scan-result');
  var xhr = new XMLHttpRequest();
  xhr.open('GET', '/members/lookup?code=' + encodeURIComponent(code));
  xhr.onload = function () {
    box.classList.remove('hidden');
    try {
      var res = JSON.parse(xhr.responseText);
      if (res.found) {
        box.innerHTML = '<p class="font-bold text-brand-600 mb-1">' + res.member_no + ' - ' + res.full_name + '</p>' +
          '<p class="text-slate-500">Status: ' + res.status + '</p>' +
          '<a href="/members/' + res.id + '/card" class="mt-2 inline-block px-3 py-1.5 rounded-lg bg-brand-600 text-white font-bold">Buka Kartu</a>';
      } else {
        box.innerHTML = '<p class="text-red-500 font-bold">Kode tidak dikenali.</p>';
      }
    } catch (e) {
      box.innerHTML = '<p class="text-red-500">Gagal membaca hasil.</p>';
    }
  };
  xhr.send();
}

function manualLookup(ev) {
  ev.preventDefault();
  var code = document.getElementById('manual-code').value.trim();
  if (code) lookup(code);
  return false;
}
</script>
