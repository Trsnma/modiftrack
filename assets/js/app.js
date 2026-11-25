/* ModifTrack - app.js */

/* ---------- 0. Cadangan transisi halaman untuk browser lama ----------
   Browser yang mendukung view transition punya event "pagereveal".
   Selain itu, halaman diberi kelas .no-vt agar tampil dengan fade-in (lihat style.css). */
if (!("onpagereveal" in window)) {
  document.documentElement.classList.add("no-vt");
}

/* ---------- 1. Dialog konfirmasi kustom ----------
   Dipakai oleh elemen yang punya atribut:
     data-confirm="Kalimat konfirmasi"        (wajib)
     data-confirm-title="Judul dialog"        (bawaan: Hapus data?)
     data-confirm-ok="Teks tombol setuju"     (bawaan: Ya, hapus)
     data-confirm-tone="primary"              (bawaan: merah untuk aksi menghapus) */
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

  var icon = dialog.querySelector(".confirm-icon");
  var title = dialog.querySelector(".confirm-title");
  var text = dialog.querySelector(".confirm-text");
  var cancelBtn = dialog.querySelector('[data-act="cancel"]');
  var okBtn = dialog.querySelector('[data-act="ok"]');
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

    // Form belum lengkap: biarkan browser menampilkan pesan kolom wajib, dialog belum perlu muncul
    if (trigger.form && !trigger.form.checkValidity()) {
      ev.preventDefault();
      trigger.form.reportValidity();
      return;
    }

    ev.preventDefault();
    pending = trigger;

    var d = trigger.dataset;
    var aman = d.confirmTone === "primary"; // aksi non-destruktif: tombol ungu
    icon.textContent = aman ? "?" : "!";
    title.textContent = d.confirmTitle || "Hapus data?";
    text.textContent = d.confirm; // textContent: aman dari XSS
    okBtn.textContent = d.confirmOk || "Ya, hapus";
    okBtn.className = aman ? "btn" : "btn btn-danger-solid";

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

/* ---------- 5. Galeri motor bergantian (About & Dashboard): maju, lalu mundur ke belakang ----------
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

/* ---------- 6. Dropdown kustom (menggantikan daftar pilihan bawaan browser) ----------
   Setiap <select> dibungkus tombol + panel pilihan. <select> asli tetap ada (tersembunyi),
   jadi pengiriman form dan validasi tidak berubah. Perubahan lewat kode (misalnya dropdown model
   yang mengikuti merk) dibaca lewat MutationObserver. Lewati satu select dengan atribut data-native. */
(function () {
  var panel = document.createElement("div"); // satu panel dipakai bersama, ditaruh di body
  panel.className = "dd-panel"; // agar tidak terpotong area scroll tabel
  panel.setAttribute("role", "listbox");
  panel.hidden = true;
  document.body.appendChild(panel);

  var cur = null; // dropdown yang sedang terbuka
  var active = -1; // indeks pilihan yang disorot

  // Samakan tampilan tombol dengan <select> asli
  function sync(d) {
    var o = d.native.options[d.native.selectedIndex];
    d.label.textContent = o ? o.textContent : "";
    d.btn.classList.toggle("is-placeholder", !d.native.value);
    d.btn.disabled = d.native.disabled;
  }

  // Letakkan panel di bawah tombol (atau di atas bila ruang bawah kurang)
  function place() {
    if (!cur) return;
    var r = cur.btn.getBoundingClientRect();
    var below = window.innerHeight - r.bottom - 16;
    var above = r.top - 16;
    var tinggi = Math.min(panel.scrollHeight, 260);
    var keAtas = below < tinggi && above > below;

    panel.style.width = r.width + "px";
    panel.style.left = r.left + "px";
    panel.style.maxHeight =
      Math.max(120, Math.min(260, keAtas ? above : below)) + "px";
    panel.style.top = keAtas ? "auto" : r.bottom + 6 + "px";
    panel.style.bottom = keAtas
      ? window.innerHeight - r.top + 6 + "px"
      : "auto";
  }

  function setActive(i) {
    var lama = panel.children[active];
    if (lama) lama.classList.remove("is-active");
    active = i;
    var el = panel.children[i];
    if (!el || !cur) return;
    el.classList.add("is-active");
    cur.btn.setAttribute("aria-activedescendant", el.id);
    el.scrollIntoView({ block: "nearest" });
  }

  function move(step) {
    var n = panel.children.length,
      i = active;
    for (var k = 0; k < n; k++) {
      i = (i + step + n) % n;
      if (panel.children[i].getAttribute("aria-disabled") !== "true") {
        setActive(i);
        return;
      }
    }
  }

  function open(d) {
    if (d.btn.disabled) return;
    if (cur) close();
    cur = d;
    panel.textContent = "";
    var terpilih = d.native.selectedIndex;
    Array.prototype.forEach.call(d.native.options, function (o, i) {
      var el = document.createElement("div");
      el.className = "dd-opt" + (o.value === "" ? " is-empty" : "");
      el.id = "dd-opt-" + i;
      el.dataset.i = i;
      el.setAttribute("role", "option");
      el.setAttribute("aria-selected", i === terpilih ? "true" : "false");
      if (o.disabled) el.setAttribute("aria-disabled", "true");
      el.textContent = o.textContent; // textContent: aman dari XSS
      panel.appendChild(el);
    });
    panel.hidden = false;
    d.btn.setAttribute("aria-expanded", "true");
    place();
    setActive(terpilih >= 0 ? terpilih : 0);
  }

  function close(fokus) {
    if (!cur) return;
    cur.btn.setAttribute("aria-expanded", "false");
    cur.btn.removeAttribute("aria-activedescendant");
    if (fokus) cur.btn.focus();
    cur = null;
    panel.hidden = true;
  }

  function pick(i) {
    var d = cur;
    var o = d && d.native.options[i];
    if (!o || o.disabled) return;
    var berubah = d.native.selectedIndex !== i;
    d.native.selectedIndex = i;
    close(true);
    sync(d);
    if (berubah) d.native.dispatchEvent(new Event("change", { bubbles: true })); // agar skrip lain ikut bereaksi
  }

  // Interaksi di dalam panel
  panel.addEventListener("mousedown", function (ev) {
    ev.preventDefault();
  }); // fokus tetap di tombol
  panel.addEventListener("click", function (ev) {
    var el = ev.target.closest(".dd-opt");
    if (el) pick(+el.dataset.i);
  });
  panel.addEventListener("mousemove", function (ev) {
    var el = ev.target.closest(".dd-opt");
    if (
      el &&
      +el.dataset.i !== active &&
      el.getAttribute("aria-disabled") !== "true"
    )
      setActive(+el.dataset.i);
  });

  // Tutup saat klik di luar; ikuti posisi saat layar digulir atau diubah ukurannya
  document.addEventListener("mousedown", function (ev) {
    if (cur && !panel.contains(ev.target) && !cur.wrap.contains(ev.target))
      close();
  });
  window.addEventListener("resize", place);
  window.addEventListener(
    "scroll",
    function (ev) {
      if (cur && ev.target !== panel) place();
    },
    true,
  );

  // Ubah satu <select> menjadi dropdown kustom
  function enhance(native) {
    if (
      native.dataset.dd ||
      native.multiple ||
      native.size > 1 ||
      native.hasAttribute("data-native")
    )
      return;
    native.dataset.dd = "1";

    var wrap = document.createElement("div");
    wrap.className = "dd";
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "dd-btn";
    btn.setAttribute("aria-haspopup", "listbox");
    btn.setAttribute("aria-expanded", "false");
    var label = document.createElement("span");
    label.className = "dd-label";
    btn.appendChild(label);

    // Nama aksesibel diambil dari aria-label atau <label> milik select
    var nama =
      native.getAttribute("aria-label") ||
      (native.labels && native.labels[0]
        ? native.labels[0].textContent.trim()
        : "");
    if (nama) btn.setAttribute("aria-label", nama);

    native.parentNode.insertBefore(wrap, native);
    wrap.appendChild(native);
    wrap.appendChild(btn);
    native.classList.add("dd-native");
    native.tabIndex = -1;
    native.setAttribute("aria-hidden", "true");
    native.addEventListener("focus", function () {
      btn.focus();
    }); // mis. saat validasi menyorot kolom wajib

    var d = { native: native, btn: btn, wrap: wrap, label: label };
    sync(d);

    btn.addEventListener("click", function () {
      if (cur === d) close();
      else open(d);
    });
    btn.addEventListener("keyup", function (ev) {
      if (ev.key === " ") ev.preventDefault();
    });
    btn.addEventListener("keydown", function (ev) {
      var terbuka = cur === d;
      switch (ev.key) {
        case "ArrowDown":
        case "ArrowUp":
          ev.preventDefault();
          if (!terbuka) open(d);
          else move(ev.key === "ArrowDown" ? 1 : -1);
          break;
        case "Home":
        case "End":
          if (terbuka) {
            ev.preventDefault();
            setActive(ev.key === "Home" ? 0 : panel.children.length - 1);
          }
          break;
        case "Enter":
        case " ":
          ev.preventDefault();
          if (!terbuka) open(d);
          else pick(active);
          break;
        case "Escape":
          if (terbuka) {
            ev.preventDefault();
            close();
          }
          break;
        case "Tab":
          if (terbuka) close();
          break;
      }
    });

    // Isi atau status <select> berubah lewat kode (mis. daftar model mengikuti merk)
    new MutationObserver(function () {
      if (cur === d) close();
      sync(d);
    }).observe(native, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ["disabled"],
    });
  }

  document.querySelectorAll("select").forEach(enhance);
})();
