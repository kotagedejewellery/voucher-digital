import QrScanner from '@agicash/qr-scanner';
import readerWasmUrl from 'zxing-wasm/reader/zxing_reader.wasm?url';

QrScanner.configureWasm({ locateFile: path => path.endsWith('.wasm') ? readerWasmUrl : path });

const cameraMessage = error => {
    if (error?.name === 'NotAllowedError' || /permission|denied/i.test(error?.message || '')) return 'Izin kamera belum diberikan. Izinkan kamera pada pengaturan browser, lalu tekan Scan dengan kamera kembali.';
    if (error?.name === 'NotFoundError' || /camera not found/i.test(error?.message || '')) return 'Kamera tidak ditemukan. Gunakan kode atau link voucher.';
    if (['NotReadableError', 'AbortError'].includes(error?.name)) return 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut, lalu tekan Scan dengan kamera kembali.';
    if (['SecurityError', 'TypeError'].includes(error?.name)) return 'Kamera memerlukan koneksi HTTPS yang aman. Periksa alamat situs, lalu coba kembali.';
    if (/wasm|worker|fetch|load/i.test(error?.message || '')) return 'Pemindai belum dapat dimuat. Periksa koneksi internet, lalu tekan Scan dengan kamera kembali.';
    return 'Kamera belum dapat dibuka. Periksa izin kamera, lalu tekan Scan dengan kamera kembali.';
};

export async function startScanner() {
    const box = document.querySelector('.scanner');
    const status = document.getElementById('scanner-status');
    const video = box.querySelector('video');
    const preview = box.querySelector('[data-scan-preview]');
    const start = box.querySelector('[data-start-scanner]');
    const stop = box.querySelector('[data-stop-scanner]');
    if (!navigator.mediaDevices?.getUserMedia) {
        start.disabled = false;
        status.textContent = 'Kamera pemindai tidak didukung di browser ini. Masukkan kode atau link voucher.';
        return;
    }

    let scanner;
    let finished = false;
    let removeLifecycleListeners = () => {};
    const release = () => {
        removeLifecycleListeners();
        scanner?.destroy();
        preview.hidden = true;
        stop.hidden = true;
        start.disabled = false;
    };
    const finish = message => {
        if (finished) return;
        finished = true;
        release();
        if (message) status.textContent = message;
    };

    try {
        preview.hidden = false;
        start.disabled = true;
        status.textContent = 'Membuka kamera belakang. Izinkan akses kamera bila diminta.';
        scanner = new QrScanner(video, result => {
            if (finished) return;
            document.getElementById('code').value = result.data;
            finish('QR terbaca. Membuka preview voucher; belum melakukan klaim.');
            document.getElementById('lookup-form').requestSubmit();
        }, {
            preferredCamera: 'environment',
            maxScansPerSecond: 10,
            onDecodeError: () => {},
        });
        const stopForPageLeave = () => finish();
        const stopWhenHidden = () => {
            if (document.visibilityState === 'hidden') finish('Pemindaian dihentikan saat halaman tidak aktif. Tekan Scan dengan kamera untuk membuka kembali.');
        };
        window.addEventListener('pagehide', stopForPageLeave);
        document.addEventListener('visibilitychange', stopWhenHidden);
        removeLifecycleListeners = () => {
            window.removeEventListener('pagehide', stopForPageLeave);
            document.removeEventListener('visibilitychange', stopWhenHidden);
        };
        await scanner.start();
        if (finished) return;
        stop.hidden = false;
        stop.onclick = () => finish('Pemindaian dihentikan. Anda dapat memasukkan kode atau membuka kamera kembali.');
        status.textContent = 'Arahkan kamera belakang ke QR voucher pelanggan.';
    } catch (error) {
        finish(cameraMessage(error));
    }
}
