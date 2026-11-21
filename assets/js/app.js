/* ModifTrack - app.js */

/* ---------- 0. Cadangan transisi halaman untuk browser lama ----------
   Browser yang mendukung view transition punya event "pagereveal".
   Selain itu, halaman diberi kelas .no-vt agar tampil dengan fade-in (lihat style.css). */
if (!("onpagereveal" in window)) {
  document.documentElement.classList.add("no-vt");
}

/* ---------- 1. Dialog konfirmasi kustom ----------
   Dipakai oleh elemen yang punya atribut: data-confirm="Pesan konfirmasi" */
(function () {
  var dialog = document.createElement("dialog");
  dialog.className = "confirm";
  dialog.setAttribute("aria-labelledby", "confirm-title");
  dialog.innerHTML =
    '<div class="confirm-box">' +
    '<div class="confirm-icon" aria-hidden="true">!</div>' +
    '<h2 id="confirm-title" class="confirm-title">Hapus data?</h2>' +
    '<p class="confirm-text"></p>' +
    '<div class="confirm-actions">' +
    '<button type="button" class="btn btn-ghost" data-act="cancel">Batal</button>' +
    '<button type="button" class="btn btn-danger-solid" data-act="ok">Ya, hapus</button>' +
    "</div>" +
    "</div>";
  document.body.appendChild(dialog);

  var text = dialog.querySelector(".confirm-text");
  var cancelBtn = dialog.querySelector('[data-act="cancel"]');
  var pending = null; // elemen pemicu yang sedang menunggu konfirmasi

  // Jalankan aksi asli setelah user menyetujui
  function proceed(trigger) {
    if (trigger.form) {
      trigger.form.requestSubmit(trigger); // kirim form beserta tombol pemicunya
    } else if (trigger.href) {
      window.location.href = trigger.href;
    }
  }

  document.addEventListener("click", function (ev) {
    var trigger = ev.target.closest("[data-confirm]");
    if (!trigger) return;
    ev.preventDefault();
    pending = trigger;
    text.textContent = trigger.getAttribute("data-confirm");
    dialog.showModal();
    cancelBtn.focus(); // fokus awal di Batal (lebih aman)
  });

  dialog.addEventListener("click", function (ev) {
    var act = ev.target.getAttribute("data-act");
    if (ev.target === dialog || act === "cancel") {
      // klik area gelap atau tombol Batal
      dialog.close();
    } else if (act === "ok") {
      var trigger = pending;
      dialog.close();
      proceed(trigger);
    }
  });

  dialog.addEventListener("close", function () {
    pending = null;
  });
})();

/* ---------- 2. Landing page: pilih warna kendaraan ---------- */
document.querySelectorAll(".swatch").forEach(function (btn) {
  btn.addEventListener("click", function () {
    var hero = document.querySelector(".land-hero");
    if (hero) hero.style.setProperty("--bike", btn.dataset.color);
    document.querySelectorAll(".swatch").forEach(function (b) {
      b.setAttribute("aria-pressed", b === btn ? "true" : "false");
    });
  });
});

/* ---------- 3. Dropdown model bergantung pada merk ---------- */
document.querySelectorAll("select[data-models]").forEach(function (merk) {
  var model = document.getElementById(merk.dataset.target);
  var data = JSON.parse(merk.dataset.models);

  function isiModel() {
    var terpilih = model.dataset.selected || "";
    var daftar = data[merk.value] || [];
    model.innerHTML = "";
    var awal = document.createElement("option");
    awal.value = "";
    awal.textContent = daftar.length ? "Pilih model" : "Pilih merk dulu";
    model.appendChild(awal);
    daftar.forEach(function (nama) {
      var opt = document.createElement("option");
      opt.textContent = nama; // textContent: aman dari XSS
      if (nama.toLowerCase() === terpilih.toLowerCase()) opt.selected = true;
      model.appendChild(opt);
    });
    model.disabled = !daftar.length;
    model.dataset.selected = ""; // pilihan awal hanya dipakai sekali
  }
  merk.addEventListener("change", isiModel);
  isiModel();
});

/* ---------- 4. Landing page: bayangan navbar saat halaman di-scroll ---------- */
(function () {
  var nav = document.querySelector(".site-nav");
  if (!nav) return;
  function update() {
    nav.classList.toggle("is-scrolled", window.scrollY > 8);
  }
  update();
  window.addEventListener("scroll", update, { passive: true });
})();

/* ---------- 5. About: galeri motor bergantian (maju, lalu mundur ke belakang) ----------
   Posisi awal (data-rel) sudah ditulis PHP, jadi motor pertama tampil walau JS belum jalan.
   Di sini hanya menggeser posisi tiap beberapa detik.
   Galeri sengaja tetap bergerak walau perangkat meminta "kurangi gerakan",
   karena gerakannya adalah inti tampilan bagian ini. */
(function () {
  var items = document.querySelectorAll(".fleet-item");
  var label = document.querySelector(".fleet-name");
  if (items.length < 2) return; // satu motor saja: tidak perlu bergerak

  var total = items.length;
  var now = 0;

  // data-rel: 0 = di depan, 1 = berikutnya (menunggu di belakang), lainnya = tersembunyi
  function show() {
    items.forEach(function (el, i) {
      el.dataset.rel = Math.min((i - now + total) % total, 2);
    });
    if (label) label.textContent = items[now].alt;
  }

  setInterval(function () {
    now = (now + 1) % total;
    show();
  }, 3200);
})();
