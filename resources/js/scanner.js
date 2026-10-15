export async function startScanner() {
    const box = document.querySelector('.scanner');
    const status = document.getElementById('scanner-status');
    const video = box.querySelector('video');
    const start = box.querySelector('[data-start-scanner]');
    const stop = box.querySelector('[data-stop-scanner]');
    if (!window.BarcodeDetector || !navigator.mediaDevices?.getUserMedia) {
        start.disabled = false; status.textContent = 'Kamera pemindai tidak didukung di browser ini. Masukkan kode atau link voucher.'; return;
    }
    let stream;
    let stopped = false;
    const finish = () => { stopped = true; stream?.getTracks().forEach(track => track.stop()); video.hidden = true; stop.hidden = true; start.disabled = false; };
    try {
        window.addEventListener('pagehide', finish, { once: true });
        const formats = await BarcodeDetector.getSupportedFormats();
        if (!formats.includes('qr_code')) throw new Error('unsupported');
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        if (stopped) { finish(); return; }
        video.srcObject = stream; video.hidden = false; stop.hidden = false; start.disabled = true; await video.play();
        stop.onclick = () => { finish(); status.textContent = 'Pemindaian dihentikan. Anda dapat memasukkan kode atau membuka kamera kembali.'; };
        const detector = new BarcodeDetector({ formats: ['qr_code'] });
        status.textContent = 'Arahkan kamera ke QR voucher pelanggan.';
        const scan = async () => {
            if (stopped) return;
            try {
                const result = await detector.detect(video);
                if (result.length) {
                    document.getElementById('code').value = result[0].rawValue;
                    finish(); status.textContent = 'QR terbaca. Membuka preview voucher; belum melakukan klaim.'; document.getElementById('lookup-form').requestSubmit(); return;
                }
                setTimeout(scan, 250);
            } catch { finish(); status.textContent = 'Pemindaian gagal. Masukkan kode voucher secara manual.'; }
        };
        scan();
    } catch { finish(); status.textContent = 'Kamera tidak tersedia atau izin ditolak. Gunakan kode voucher.'; }
}
