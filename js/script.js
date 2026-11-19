document.addEventListener("DOMContentLoaded", () => {
  // 1. Ambil Parameter Nama Tamu (?to=Nama+Tamu)
  const urlParams = new URLSearchParams(window.location.search);
  const guestParam = urlParams.get("to");
  if (guestParam) {
    document.getElementById("guest-name").textContent = guestParam;
    document.getElementById("rsvp-name").value = guestParam;
    document.getElementById("wish-name").value = guestParam;
  }

  // 2. Logika Video Pembuka -> Muncul Nama MR & MRS
  const introVideo = document.getElementById("intro-video");
  const introCard = document.getElementById("intro-card");
  const skipVideoBtn = document.getElementById("skip-video-btn");
  const introOverlay = document.getElementById("intro-overlay");
  const mainContent = document.getElementById("main-content");
  const openBtn = document.getElementById("open-invitation-btn");

  function revealNamesCard() {
    introCard.classList.add("show-names");
    skipVideoBtn.style.display = "none";
  }

  // Munculkan kartu MR & MRS setelah 3.5 detik video berputar, ATAU saat video selesai
  const introTimer = setTimeout(revealNamesCard, 3500);

  introVideo.addEventListener("ended", () => {
    clearTimeout(introTimer);
    revealNamesCard();
  });

  // Jika file video belum dimasukkan/error, langsung tampilkan kartu nama MR & MRS
  introVideo.addEventListener("error", () => {
    clearTimeout(introTimer);
    revealNamesCard();
  });

  skipVideoBtn.addEventListener("click", () => {
    clearTimeout(introTimer);
    revealNamesCard();
  });

  // 3. Buka Undangan & Jalankan IntersectionObserver untuk Animasi Scroll
  openBtn.addEventListener("click", () => {
    introOverlay.classList.add("opened");
    mainContent.classList.remove("hidden");
    initScrollAnimations();
  });

  function initScrollAnimations() {
    const observerOptions = {
      root: null,
      rootMargin: "0px 0px -60px 0px",
      threshold: 0.12
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("in-view");
        }
      });
    }, observerOptions);

    document.querySelectorAll(".scroll-animate").forEach((el) => {
      observer.observe(el);
    });
  }

  // 4. Countdown Timer (20 Desember 2026, 08:00 WIB)
  const targetDate = new Date("2026-12-20T08:00:00+07:00").getTime();

  function updateCountdown() {
    const now = new Date().getTime();
    const distance = targetDate - now;

    if (distance <= 0) {
      document.getElementById("countdown").innerHTML =
        "<p class='col-span-4 text-sm font-semibold text-[#1E3F32]'>Acara Sedang Berlangsung</p>";
      return;
    }

    document.getElementById("days").textContent = String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, "0");
    document.getElementById("hours").textContent = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, "0");
    document.getElementById("minutes").textContent = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, "0");
    document.getElementById("seconds").textContent = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, "0");
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);

  // 5. Penanganan Form RSVP
  const rsvpForm = document.getElementById("rsvp-form");
  const rsvpFeedback = document.getElementById("rsvp-feedback");

  rsvpForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const name = document.getElementById("rsvp-name").value.trim();
    const status = document.getElementById("rsvp-status").value;
    const guests = document.getElementById("rsvp-guests").value;

    rsvpFeedback.textContent = `Terima kasih, ${name}. Konfirmasi (${status}${status === "Hadir" ? ` — ${guests} Orang` : ""}) telah tersimpan.`;
    rsvpFeedback.classList.remove("hidden");
    rsvpForm.reset();
  });

  // 6. Penanganan Form Ucapan Tamu (Tersimpan di LocalStorage)
  const wishForm = document.getElementById("wish-form");
  const wishesList = document.getElementById("wishes-list");
  const STORAGE_KEY = "wedding_wishes_jerry_anzhanny_v2";

  const defaultWishes = [
    { name: "Teh Dila & Keluarga", message: "Selamat menempuh hidup baru Mr. Jerry & Mrs. Anzhanny! Samawa selalu.", time: "Baru saja" },
    { name: "Rekan Kerja Bandung", message: "Barakallah, semoga dilancarkan sampai hari H dan bahagia selamanya!", time: "2 jam lalu" }
  ];

  function getWishes() {
    const saved = localStorage.getItem(STORAGE_KEY);
    return saved ? JSON.parse(saved) : defaultWishes;
  }

  function escapeHTML(str) {
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  function renderWishes() {
    const wishes = getWishes();
    wishesList.innerHTML = wishes.map(item => `
      <div class="bg-[#F7FAF8] border border-[#D3E0D9] rounded-2xl p-4">
        <div class="flex justify-between items-center mb-1">
          <span class="text-xs font-semibold text-[#1E3F32]">${escapeHTML(item.name)}</span>
          <span class="text-[11px] text-[#556860]">${escapeHTML(item.time)}</span>
        </div>
        <p class="text-xs sm:text-sm text-[#1A2923] leading-relaxed">${escapeHTML(item.message)}</p>
      </div>
    `).join("");
  }

  wishForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const nameInput = document.getElementById("wish-name");
    const messageInput = document.getElementById("wish-message");
    const currentWishes = getWishes();

    currentWishes.unshift({
      name: nameInput.value.trim(),
      message: messageInput.value.trim(),
      time: "Baru saja"
    });

    localStorage.setItem(STORAGE_KEY, JSON.stringify(currentWishes));
    renderWishes();
    wishForm.reset();
  });

  renderWishes();
});

// Fungsi Lightbox untuk memperbesar foto galeri
function openLightbox(src) {
  const modal = document.getElementById("lightbox-modal");
  document.getElementById("lightbox-img").src = src;
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeLightbox() {
  const modal = document.getElementById("lightbox-modal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

// Fungsi Salin Nomor Rekening (Kompatibel untuk semua browser & iframe)
function copyRekening(elementId, buttonEl) {
  const rekText = document.getElementById(elementId).textContent.trim();
  const tempInput = document.createElement("textarea");
  tempInput.value = rekText;
  document.body.appendChild(tempInput);
  tempInput.select();
  document.execCommand("copy");
  document.body.removeChild(tempInput);

  const originalText = buttonEl.textContent;
  buttonEl.textContent = "✓ Berhasil Disalin!";
  buttonEl.classList.add("bg-[#1E3F32]", "text-white");

  setTimeout(() => {
    buttonEl.textContent = originalText;
    buttonEl.classList.remove("bg-[#1E3F32]", "text-white");
  }, 2000);
}