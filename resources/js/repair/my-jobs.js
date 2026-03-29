(function () {
  function $(id) { return document.getElementById(id); }

  function showLoader() { $("loaderOverlay")?.classList.add("show"); }
  function hideLoader() { $("loaderOverlay")?.classList.remove("show"); }

  function submitAcknowledge(id, ticketNo) {
    const ok = confirm(`ยืนยัน “รับทราบ” งาน #${ticketNo} ?`);
    if (!ok) return;
    showLoader();
    const form = $(`ackForm-${id}`);
    if (form) form.submit();
  }

  function submitAccept(id, ticketNo) {
    const ok = confirm(`ยืนยัน “รับเรื่อง” งาน #${ticketNo} ?`);
    if (!ok) return;
    showLoader();
    const form = $(`acceptForm-${id}`);
    if (form) form.submit();
  }

  function submitReject(id, ticketNo) {
    const remark = prompt(`ระบุเหตุผล “ไม่รับเรื่อง” งาน #${ticketNo} (ไม่กรอกก็ได้)`, "");
    if (remark === null) return;

    const ok = confirm(`ยืนยัน “ไม่รับเรื่อง” งาน #${ticketNo} ?`);
    if (!ok) return;

    const input = $(`rejectRemark-${id}`);
    if (input) input.value = (remark || "").trim();

    showLoader();
    const form = $(`rejectForm-${id}`);
    if (form) form.submit();
  }

  function renderDonut() {
    const pending = parseInt(($("stat-pending")?.textContent || "0").trim(), 10) || 0;
    const inprog  = parseInt(($("stat-in-progress")?.textContent || "0").trim(), 10) || 0;
    const comp    = parseInt(($("stat-completed")?.textContent || "0").trim(), 10) || 0;

    const total = pending + inprog + comp;
    const donut = $("donut");
    const pctEl = $("donutPct");
    if (!donut || !pctEl) return;

    const completedPct = total > 0 ? Math.round((comp / total) * 100) : 0;
    pctEl.textContent = `${completedPct}%`;

    const degPending = total > 0 ? (pending / total) * 360 : 0;
    const degInprog  = total > 0 ? (inprog  / total) * 360 : 0;
    const degComp    = total > 0 ? (comp    / total) * 360 : 0;

    const a0 = 0;
    const a1 = a0 + degPending;
    const a2 = a1 + degInprog;
    const a3 = a2 + degComp;

    donut.style.background =
      `conic-gradient(#f59e0b ${a0}deg ${a1}deg,#0ea5e9 ${a1}deg ${a2}deg,#10b981 ${a2}deg ${a3}deg,#e2e8f0 ${a3}deg 360deg)`;
  }

  const LS_KEY = "myjobs.notify.sound.enabled";

  let soundEnabled = false;
  let audioUnlocked = false;
  let pendingBeep = 0;

  function setNotifyUI(enabled) {
    const dot = $("notifyStatusDot");
    const text = $("notifyText");
    const btn = $("notifyToggleBtn");

    if (text) text.textContent = enabled ? "เปิดเสียง" : "ปิดเสียง";
    if (btn) btn.title = enabled ? "แจ้งเตือน (เปิดเสียงแล้ว)" : "แจ้งเตือน (กดเพื่อเปิดเสียง)";

    if (dot) {
      dot.classList.remove("bg-slate-300", "bg-emerald-500", "bg-amber-500", "bg-secondary");
      if (!enabled) dot.classList.add("bg-slate-300");
      else if (pendingBeep > 0) dot.classList.add("bg-amber-500"); // มีแจ้งเตือนค้าง
      else dot.classList.add("bg-emerald-500");
    }
  }

  async function unlockAudio(audioEl) {
    // ต้องเรียกจาก user gesture (onclick) เพื่อปลดล็อก autoplay policy
    if (!audioEl) return false;

    try {
      const prevVol = audioEl.volume;
      audioEl.volume = 0;          // mute ชั่วคราวเพื่อไม่ให้มีเสียงตอน unlock
      await audioEl.play();        // ลอง play
      audioEl.pause();
      audioEl.currentTime = 0;
      audioEl.volume = prevVol ?? 1;
      return true;
    } catch (e) {
      console.warn("[Notify] unlockAudio failed:", e);
      return false;
    }
  }

  function playNotifySound() {
    if (!soundEnabled) return;

    const audio = $("notifySound");
    if (!audio) {
      console.warn("[Notify] sound element not found (#notifySound)");
      return;
    }

    if (!audioUnlocked) {
      pendingBeep++;
      setNotifyUI(true);
      return;
    }

    try {
      audio.currentTime = 0;
      audio.play().catch(err => {
        console.warn("[Notify] play blocked:", err);
      });
    } catch (e) {
      console.warn("[Notify] play error:", e);
    }
  }

  function initRealtimeNotify() {
    const btn = $("notifyToggleBtn");
    const audio = $("notifySound");

    // เจ้าหน้าที่เท่านั้น (audio + btn อยู่ที่ navbar สำหรับ role !== member)
    if (!audio || !btn) return;

    // restore state
    soundEnabled = localStorage.getItem(LS_KEY) === "1";
    setNotifyUI(soundEnabled);

    // toggle click
    btn?.addEventListener("click", async () => {
      if (soundEnabled) {
        soundEnabled = false;
        localStorage.setItem(LS_KEY, "0");
        setNotifyUI(false);
        return;
      }

      const ok = await unlockAudio(audio);
      if (!ok) {
        alert("เบราว์เซอร์บล็อกเสียงอัตโนมัติ: ลองคลิกในหน้า 1 ครั้ง แล้วกดกระดิ่งอีกครั้ง");
        return;
      }

      audioUnlocked = true;
      soundEnabled = true;
      localStorage.setItem(LS_KEY, "1");

      // ถ้ามีแจ้งเตือนค้าง -> ดัง 1 ครั้งพอ แล้วล้างค้าง
      if (pendingBeep > 0 && audio) {
        pendingBeep = 0;
        try {
          audio.currentTime = 0;
          await audio.play();
        } catch (e) {
          // ถ้าโดนบล็อกอีกก็ไม่เป็นไร
        }
      }

      setNotifyUI(true);
    });

    // ต้องมี Echo
    if (!window.Echo) {
      console.warn("[MyJobs] Echo not found. ตรวจ resources/js/echo.js และ env (Pusher/Reverb)");
      return;
    }

    // subscribe + listen
    window.Echo.channel("maintenance-requests")
      .listen(".maintenance.created", (e) => {
        console.log("[MyJobs] maintenance.created", e);
        playNotifySound();
      });
  }

  // expose to inline onclick
  window.showLoader = showLoader;
  window.hideLoader = hideLoader;
  window.submitAcknowledge = submitAcknowledge;
  window.submitAccept = submitAccept;
  window.submitReject = submitReject;

  document.addEventListener("DOMContentLoaded", () => {
    hideLoader();
    if ($("donut")) renderDonut(); // เฉพาะหน้า my-jobs
    initRealtimeNotify(); // รันทุกหน้า (สำหรับเจ้าหน้าที่จะมีเสียงทั้งระบบ)
  });
})();
