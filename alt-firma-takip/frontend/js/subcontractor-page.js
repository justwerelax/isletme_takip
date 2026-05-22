// subcontractor-page.js — v4
if (!Auth.isAuthenticated()) { window.location.replace('login.html'); }

const params  = new URLSearchParams(window.location.search);
const subId   = parseInt(params.get('id'));
if (!subId) { window.location.replace('dashboard.html'); }

// Kullanıcı rolü
const currentUser = Auth.getUser();
const isFirma     = currentUser && currentUser.rol === 'firma';

let jobsData = [], paysData = [], subData = {};

function toast(msg, type='success') {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent  = msg;
    document.getElementById('toastIcon').textContent = type === 'success' ? '✅' : '❌';
    t.className = 'toast show ' + type;
    setTimeout(() => t.classList.remove('show'), 3000);
}

function today() { return new Date().toISOString().split('T')[0]; }

async function loadAll() {
    try {
        const res = await api.getSubcontractor(subId);
        if (!res.success) { toast('Yüklenemedi','error'); return; }

        subData  = res.data.subcontractor;
        const sum = res.data.summary || {};
        jobsData  = res.data.jobs || [];
        paysData  = res.data.payments || [];

        document.getElementById('firmName').textContent = subData.ad;
        document.getElementById('firmPhone').textContent = subData.telefon ? '📞 ' + subData.telefon : '';
        document.getElementById('firmAddr').textContent  = subData.adres   ? '📍 ' + subData.adres   : '';

        document.getElementById('btnExportJSON').onclick = () => api.exportSubJSON(subId, subData.ad);
        document.getElementById('btnExportCSV').onclick  = () => api.exportSubCSV(subId, subData.ad);

        const kendi = parseFloat(sum.kendi_isleri_toplam    || 0);
        const bizim = parseFloat(sum.bizim_islerimiz_toplam || 0);
        const odeme = parseFloat(sum.toplamOdeme            || 0);
        const borc  = parseFloat(sum.mevcut_borc            || 0);

        document.getElementById('balKendi').textContent  = formatCurrency(kendi);
        document.getElementById('balBizim').textContent  = formatCurrency(bizim);
        document.getElementById('balOdeme').textContent  = formatCurrency(odeme);
        document.getElementById('balMevcut').textContent = formatCurrency(Math.abs(borc));

        // Bakiye kart yazıları — role göre
        if (isFirma) {
            document.querySelector('[for-bal="kendi"]').textContent  = '🔵 Kendi İşlerim Bakiyesi';
            document.querySelector('[for-bal="bizim"]').textContent  = '🟢 Firma İşleri Bakiyesi';
            document.querySelector('[for-bal="odeme"]').textContent  = '💸 Yaptığım Ödemeler';
        }

        const borcCard  = document.getElementById('balMevcutCard');
        const borcLabel = document.getElementById('balMevcutLabel');
        if (borc < 0) {
            borcCard.className  = 'bal-item borc';
            borcLabel.textContent = isFirma ? '🔴 Bakiyem (Borcum Var)' : '🔴 Firma Bakiyesi (Bize Borçlu)';
        } else if (borc > 0) {
            borcCard.className  = 'bal-item alacak';
            borcLabel.textContent = isFirma ? '🟢 Bakiyem (Alacağım Var)' : '🟢 Firma Bakiyesi (Biz Borçluyuz)';
        } else {
            borcCard.className  = 'bal-item sifir';
            borcLabel.textContent = isFirma ? '⚪ Bakiyem (Dengede)' : '⚪ Firma Bakiyesi (Dengede)';
        }

        document.getElementById('stJobs').textContent = sum.bizimAdres || 0;
        document.getElementById('stM2').textContent   = parseFloat(sum.kendiM2 || 0).toFixed(2);

        // Kar: sadece teslim edilmiş (biten) işlerden hesapla
        let bitenKar = 0;
        jobsData.forEach(j => {
            if (j.is_tipi === 'bizim_isimiz' && parseInt(j.teslim_edildi) === 1) {
                if (j.odeme_tipi === 'komisyon_bazli') {
                    bitenKar += parseFloat(j.komisyon_tutari);
                } else if (j.odeme_tipi === 'm2_bazli') {
                    const mt = j.musteri_tutari != null && parseFloat(j.musteri_tutari) > 0
                        ? parseFloat(j.musteri_tutari) : parseFloat(j.toplam_tutar);
                    bitenKar += mt - parseFloat(j.toplam_tutar);
                }
            }
        });
        document.getElementById('stCom').textContent = formatCurrency(bitenKar);

        renderJobs();
        renderPays();

        // Role göre UI ayarla
        if (isFirma) {
            // Yeni iş ekle butonu gizle
            const yeniIsBtn = document.querySelector('[onclick="openJobModal()"]');
            if (yeniIsBtn) yeniIsBtn.style.display = 'none';
            // Export butonları gizle
            const btnJSON = document.getElementById('btnExportJSON');
            const btnCSV  = document.getElementById('btnExportCSV');
            if (btnJSON) btnJSON.style.display = 'none';
            if (btnCSV)  btnCSV.style.display  = 'none';
            // Ödeme butonu yazısını değiştir
            const payBtn = document.querySelector('[onclick="openPayModal()"]');
            if (payBtn) payBtn.textContent = '💸 Ödeme Yap';
            // Geri dön butonu → login'e
            const backBtn = document.querySelector('.btn-back');
            if (backBtn) { backBtn.textContent = '🚪 Çıkış Yap'; backBtn.onclick = () => { Auth.logout(); window.location.replace('login.html'); }; }
        }

        document.getElementById('loadingMsg').style.display = 'none';
        document.getElementById('content').style.display    = 'block';
    } catch(e) {
        console.error(e);
        toast('Bağlantı hatası','error');
    }
}

let bitenIsleriGoster = false;

function toggleBitenIsler() {
    bitenIsleriGoster = !bitenIsleriGoster;
    renderJobs();
}

function renderJobs() {
    const kendiIsleri = jobsData.filter(j => j.is_tipi === 'kendi_isi');
    const bizimIsler  = jobsData.filter(j => j.is_tipi === 'bizim_isimiz');
    const tb = document.getElementById('jobsTbody');

    // Biten işler sayısını bul
    const bitenSayisi = jobsData.filter(j => parseInt(j.teslim_edildi) === 1).length;

    // Göster/gizle butonunu güncelle
    const toggleBtn = document.getElementById('toggleBitenBtn');
    if (toggleBtn) {
        toggleBtn.textContent = bitenIsleriGoster
            ? `🙈 Biten İşleri Gizle`
            : `✅ Biten İşleri Göster (${bitenSayisi})`;
        toggleBtn.style.display = bitenSayisi > 0 ? 'inline-block' : 'none';
    }

    if (!jobsData.length) {
        tb.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px;color:#9ca3af;">İş kaydı yok</td></tr>';
        return;
    }

    let html = '';

    function jobRow(j, borcTutar, borcRenk, borcPrefix, teslimatText, toplamGoster) {
        const teslim    = parseInt(j.teslim_edildi) === 1;
        const rowStyle  = teslim ? 'background:#f0fdf4;' : '';
        const textStyle = teslim ? 'text-decoration:line-through;color:#9ca3af;' : '';

        let toplamStr;
        if (toplamGoster === null) {
            toplamStr = `<span style="color:#f59e0b;font-weight:600;cursor:help;" title="Müşteri sipariş tutarı girilmemiş — Düzenle ile ekleyin">⚠️ ${formatCurrency(j.toplam_tutar)}</span>`;
        } else {
            toplamStr = formatCurrency(toplamGoster);
        }

        return `<tr style="${rowStyle}">
            <td><input type="checkbox" class="teslim-check" data-id="${j.id}" ${teslim ? 'checked' : ''}
                style="width:16px;height:16px;cursor:pointer;accent-color:#10b981;"></td>
            <td style="${textStyle}">${j.aciklama || '-'}</td>
            <td style="${textStyle}">${formatDate(j.tarih)}</td>
            <td style="${textStyle}">${parseFloat(j.metrekare) > 0 ? parseFloat(j.metrekare).toFixed(2) : '-'}</td>
            <td style="${textStyle}">${parseFloat(j.birim_fiyat) > 0 ? formatCurrency(j.birim_fiyat) : '-'}</td>
            <td style="${textStyle}">${toplamStr}</td>
            <td style="${textStyle}">${teslimatText}</td>
            <td style="font-weight:700;color:${teslim ? '#9ca3af' : borcRenk};${teslim ? 'text-decoration:line-through;' : ''}">${borcPrefix}${formatCurrency(borcTutar)}</td>
            <td><div class="actions">
                ${isFirma ? '' : `<button class="btn-sm btn-edit" onclick="editJob(${j.id})">Düzenle</button>
                <button class="btn-sm btn-del"  onclick="delJob(${j.id})">Sil</button>`}
            </div></td>
        </tr>`;
    }

    // Kendi İşleri — biten işleri filtrele
    const kendiGoster = bitenIsleriGoster ? kendiIsleri : kendiIsleri.filter(j => parseInt(j.teslim_edildi) !== 1);
    if (kendiGoster.length > 0) {
        const kendiBaslik = isFirma ? '🔵 Kendi İşlerim' : '🔵 Kendi İşleri';
        html += `<tr><td colspan="9" style="background:#f3f0ff;padding:10px 14px;font-weight:700;color:#5b21b6;font-size:13px;">${kendiBaslik}</td></tr>`;
        kendiGoster.forEach(j => {
            const islemler = isFirma ? '' : `
                <button class="btn-sm btn-edit" onclick="editJob(${j.id})">Düzenle</button>
                <button class="btn-sm btn-del"  onclick="delJob(${j.id})">Sil</button>`;
            html += `<tr>
                <td></td>
                <td>${j.aciklama || '-'}</td>
                <td>${formatDate(j.tarih)}</td>
                <td>${parseFloat(j.metrekare) > 0 ? parseFloat(j.metrekare).toFixed(2) : '-'}</td>
                <td>${parseFloat(j.birim_fiyat) > 0 ? formatCurrency(j.birim_fiyat) : '-'}</td>
                <td>${formatCurrency(j.toplam_tutar)}</td>
                <td>-</td>
                <td style="font-weight:700;color:#5b21b6;">${formatCurrency(j.toplam_tutar)}</td>
                <td><div class="actions">${islemler}</div></td>
            </tr>`;
        });
    }

    // Bizim İşlerimiz — biten işleri filtrele
    const bizimGoster = bitenIsleriGoster ? bizimIsler : bizimIsler.filter(j => parseInt(j.teslim_edildi) !== 1);
    if (bizimGoster.length > 0) {
        const bizimBaslik = isFirma ? '🟢 Firma İşleri' : '🟢 Bizim İşlerimiz';
        html += `<tr><td colspan="9" style="background:#eff6ff;padding:10px 14px;font-weight:700;color:#1d4ed8;font-size:13px;">${bizimBaslik}</td></tr>`;
        bizimGoster.forEach(j => {
            const isM2  = j.odeme_tipi === 'm2_bazli';
            const isAna = j.teslimat_tipi === 'ana_firma_teslim';
            const oran  = parseFloat(j.komisyon_orani) * 100;
            let borcTutar, borcRenk, borcPrefix, teslimatText, toplamGoster;

            if (isM2) {
                borcTutar    = parseFloat(j.toplam_tutar);
                borcRenk     = '#1d4ed8';
                borcPrefix   = '';
                teslimatText = 'm² Bazlı';
                // musteri_tutari varsa göster, yoksa uyarı
                toplamGoster = (j.musteri_tutari !== null && j.musteri_tutari !== undefined && parseFloat(j.musteri_tutari) > 0)
                                ? parseFloat(j.musteri_tutari)
                                : null;
            } else if (isAna) {
                borcTutar    = parseFloat(j.komisyon_tutari);
                borcRenk     = '#ef4444';
                borcPrefix   = '-';
                teslimatText = `Ana Firma Teslim (%${oran.toFixed(0)})`;
                toplamGoster = parseFloat(j.toplam_tutar);
            } else {
                borcTutar    = parseFloat(j.toplam_tutar) - parseFloat(j.komisyon_tutari);
                borcRenk     = '#1d4ed8';
                borcPrefix   = '';
                teslimatText = `Alt Firma Teslim (%${(100-oran).toFixed(0)} bize)`;
                toplamGoster = parseFloat(j.toplam_tutar);
            }
            html += jobRow(j, borcTutar, borcRenk, borcPrefix, teslimatText, toplamGoster);
        });
    }

    tb.innerHTML = html;
}

document.addEventListener('change', async function(e) {
    if (e.target && e.target.classList.contains('teslim-check')) {
        const id = parseInt(e.target.dataset.id);

        if (isFirma) {
            // Firma: onaya gönder
            e.target.checked = !e.target.checked; // geri al, admin onaylayınca değişecek
            const res = await api.onayGonder({
                alt_firma_id: subId,
                tip: 'teslim',
                is_id: id,
                aciklama: 'Teslim edildi onayı'
            });
            if (res.success) toast('Teslim onayı admine gönderildi ✅');
            else toast('Gönderilemedi', 'error');
        } else {
            // Admin: direkt işle
            const res = await api.toggleJobTeslim(id);
            if (res.success) { loadAll(); }
            else { e.target.checked = !e.target.checked; toast('Güncellenemedi', 'error'); }
        }
    }
});

function renderPays() {
    const tb = document.getElementById('payTbody');
    if (!paysData.length) {
        tb.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#9ca3af;">Para hareketi yok</td></tr>';
        return;
    }
    tb.innerHTML = paysData.map(p => `<tr>
        <td>${formatDate(p.tarih)}</td>
        <td style="font-weight:600;">${formatCurrency(p.tutar)}</td>
        <td>${p.hareket_tipi === 'odeme' ? '💸 Ödeme' : '➕ Bakiye Ekle'}</td>
        <td>${p.aciklama || '-'}</td>
        <td><div class="actions">
            ${isFirma ? '' : `
            <button class="btn-sm btn-edit" onclick="editPay(${p.id})">Düzenle</button>
            <button class="btn-sm btn-del"  onclick="delPay(${p.id})">Sil</button>`}
        </div></td>
    </tr>`).join('');
}

function switchTab(tip) {
    document.getElementById('jIsTipi').value = tip;
    if (tip === 'kendi_isi') {
        document.getElementById('tabKendi').className = 'is-tipi-tab active kendi';
        document.getElementById('tabBizim').className = 'is-tipi-tab bizim';
        document.getElementById('secKendi').classList.add('show');
        document.getElementById('secBizim').classList.remove('show');
    } else {
        document.getElementById('tabKendi').className = 'is-tipi-tab kendi';
        document.getElementById('tabBizim').className = 'is-tipi-tab active bizim';
        document.getElementById('secKendi').classList.remove('show');
        document.getElementById('secBizim').classList.add('show');
        switchOdemeTipi(document.getElementById('jOdemeTipi').value);
        return;
    }
    updateCalc();
}

function switchOdemeTipi(tip) {
    document.getElementById('jOdemeTipi').value = tip;
    if (tip === 'm2_bazli') {
        document.getElementById('tabM2').className       = 'odeme-tab active';
        document.getElementById('tabKomisyon').className = 'odeme-tab';
        document.getElementById('secM2Bazli').style.display       = 'block';
        document.getElementById('secKomisyonBazli').style.display = 'none';
        document.getElementById('secM2Alan').style.display        = 'block';
    } else {
        document.getElementById('tabM2').className       = 'odeme-tab';
        document.getElementById('tabKomisyon').className = 'odeme-tab active';
        document.getElementById('secM2Bazli').style.display       = 'none';
        document.getElementById('secKomisyonBazli').style.display = 'block';
        document.getElementById('secM2Alan').style.display        = 'none';
    }
    updateCalc();
}

function updateCalc() {
    const m2       = parseFloat(document.getElementById('jM2').value) || 0;
    const isTip    = document.getElementById('jIsTipi').value;
    const odemeTip = document.getElementById('jOdemeTipi').value;

    if (isTip === 'kendi_isi') {
        const fiyat = parseFloat(document.getElementById('jFiyatKendi').value) || 0;
        document.getElementById('jToplamKendi').textContent = formatCurrency(m2 * fiyat);
    } else if (odemeTip === 'm2_bazli') {
        const fiyat = parseFloat(document.getElementById('jFiyatBizim').value) || 0;
        document.getElementById('jToplamBizim').textContent = formatCurrency(m2 * fiyat);
    } else {
        const siparis  = parseFloat(document.getElementById('jSiparisTutari').value) || 0;
        const teslimat = document.getElementById('jTeslimatTipi').value;
        const oran     = teslimat === 'ana_firma_teslim' ? 0.3 : 0.4;
        document.getElementById('jToplamKomisyon').textContent = formatCurrency(siparis);
        document.getElementById('jKomisyonBizim').textContent  = formatCurrency(siparis * oran);
    }
}

document.getElementById('jM2').addEventListener('input', updateCalc);
document.getElementById('jFiyatKendi').addEventListener('input', updateCalc);
document.getElementById('jFiyatBizim').addEventListener('input', updateCalc);
document.getElementById('jMusteriTutari').addEventListener('input', updateCalc);
document.getElementById('jSiparisTutari').addEventListener('input', updateCalc);
document.getElementById('jTeslimatTipi').addEventListener('change', updateCalc);

function openJobModal() {
    document.getElementById('jobMTitle').textContent = 'Yeni İş Ekle';
    document.getElementById('jobForm').reset();
    document.getElementById('jobEditId').value = '';
    document.getElementById('jTarih').value    = today();
    if (subData.birim_fiyat > 0) {
        document.getElementById('jFiyatKendi').value = subData.birim_fiyat;
        document.getElementById('jFiyatBizim').value = subData.birim_fiyat;
    }
    switchTab('kendi_isi');
    switchOdemeTipi('m2_bazli');
    document.getElementById('jobModal').classList.add('show');
}

function editJob(id) {
    const j = jobsData.find(x => x.id === id);
    if (!j) return;
    document.getElementById('jobMTitle').textContent = 'İş Düzenle';
    document.getElementById('jobEditId').value = id;
    document.getElementById('jTarih').value    = j.tarih;
    document.getElementById('jM2').value       = j.metrekare;
    document.getElementById('jAciklama').value = j.aciklama || '';

    if (j.is_tipi === 'kendi_isi') {
        document.getElementById('jFiyatKendi').value = j.birim_fiyat;
        switchTab('kendi_isi');
    } else {
        document.getElementById('jFiyatBizim').value = j.birim_fiyat;
        if (j.odeme_tipi === 'komisyon_bazli') {
            switchTab('bizim_isimiz');
            switchOdemeTipi('komisyon_bazli');
            document.getElementById('jSiparisTutari').value = j.toplam_tutar;
            document.getElementById('jTeslimatTipi').value  = j.teslimat_tipi || 'alt_firma_teslim';
        } else {
            switchTab('bizim_isimiz');
            switchOdemeTipi('m2_bazli');
            if (j.musteri_tutari) document.getElementById('jMusteriTutari').value = j.musteri_tutari;
        }
    }
    updateCalc();
    document.getElementById('jobModal').classList.add('show');
}

function closeJobModal() { document.getElementById('jobModal').classList.remove('show'); }

document.getElementById('jobForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id       = document.getElementById('jobEditId').value;
    const isTip    = document.getElementById('jIsTipi').value;
    const odemeTip = document.getElementById('jOdemeTipi').value;

    let data = {
        alt_firma_id: subId,
        is_tipi:      isTip,
        tarih:        document.getElementById('jTarih').value,
        metrekare:    parseFloat(document.getElementById('jM2').value) || 0,
        aciklama:     document.getElementById('jAciklama').value.trim()
    };

    if (isTip === 'kendi_isi') {
        data.birim_fiyat = parseFloat(document.getElementById('jFiyatKendi').value);
        data.odeme_tipi  = null;
    } else if (odemeTip === 'm2_bazli') {
        data.birim_fiyat    = parseFloat(document.getElementById('jFiyatBizim').value);
        data.musteri_tutari = parseFloat(document.getElementById('jMusteriTutari').value) || null;
        data.odeme_tipi     = 'm2_bazli';
    } else {
        const teslimat      = document.getElementById('jTeslimatTipi').value;
        data.birim_fiyat    = 0;
        data.metrekare      = 0;
        data.siparis_tutari = parseFloat(document.getElementById('jSiparisTutari').value);
        data.teslimat_tipi  = teslimat;
        data.komisyon_orani = teslimat === 'ana_firma_teslim' ? 0.3 : 0.4;
        data.odeme_tipi     = 'komisyon_bazli';
    }

    const res = id ? await api.updateJob(id, data) : await api.createJob(data);
    if (res.success) { toast(res.message || 'Kaydedildi'); closeJobModal(); loadAll(); }
    else toast(res.errors ? (Array.isArray(res.errors) ? res.errors.join(', ') : Object.values(res.errors).join(', ')) : res.error || 'Hata', 'error');
});

async function delJob(id) {
    if (!confirm('Bu iş kaydını silmek istiyor musunuz?')) return;
    const res = await api.deleteJob(id);
    if (res.success) { toast('Silindi'); loadAll(); }
    else toast(res.error || 'Hata', 'error');
}

function openPayModal() {
    if (isFirma) {
        // Firma: sadece ödeme talebi gönder
        document.getElementById('payMTitle').textContent = '💸 Ödeme Yap';
        document.getElementById('payForm').reset();
        document.getElementById('payEditId').value = '';
        document.getElementById('pTarih').value    = today();
        // Tip alanını gizle, sadece odeme olacak
        document.getElementById('pTipRow').style.display  = 'none';
        document.getElementById('pAciklamaRow').style.display = 'none';
        document.getElementById('pTip').value = 'odeme';
        document.getElementById('payBtnLabel').textContent = 'Onaya Gönder';
    } else {
        document.getElementById('payMTitle').textContent = 'Yeni Para Hareketi';
        document.getElementById('payForm').reset();
        document.getElementById('payEditId').value = '';
        document.getElementById('pTarih').value    = today();
        document.getElementById('pTipRow').style.display  = '';
        document.getElementById('pAciklamaRow').style.display = '';
        document.getElementById('payBtnLabel').textContent = 'Kaydet';
    }
    document.getElementById('payModal').classList.add('show');
}

function editPay(id) {
    const p = paysData.find(x => x.id === id);
    if (!p) return;
    document.getElementById('payMTitle').textContent = 'Para Hareketi Düzenle';
    document.getElementById('payEditId').value  = id;
    document.getElementById('pTarih').value     = p.tarih;
    document.getElementById('pTutar').value     = p.tutar;
    document.getElementById('pTip').value       = p.hareket_tipi;
    document.getElementById('pAciklama').value  = p.aciklama || '';
    document.getElementById('payModal').classList.add('show');
}

function closePayModal() { document.getElementById('payModal').classList.remove('show'); }

document.getElementById('payForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id   = document.getElementById('payEditId').value;
    const data = {
        alt_firma_id: subId,
        tarih:        document.getElementById('pTarih').value,
        tutar:        parseFloat(document.getElementById('pTutar').value),
        hareket_tipi: document.getElementById('pTip').value,
        aciklama:     document.getElementById('pAciklama').value.trim()
    };

    if (isFirma) {
        // Firma: onaya gönder
        const res = await api.onayGonder({
            alt_firma_id: subId,
            tip:    'odeme',
            tutar:  data.tutar,
            tarih:  data.tarih,
            aciklama: 'Ödeme talebi'
        });
        if (res.success) { toast('Ödeme talebiniz admine gönderildi ✅'); closePayModal(); }
        else toast(res.error || 'Hata', 'error');
    } else {
        const res = id ? await api.updatePayment(id, data) : await api.createPayment(data);
        if (res.success) { toast(res.message || 'Kaydedildi'); closePayModal(); loadAll(); }
        else toast(res.errors ? res.errors.join(', ') : res.error || 'Hata', 'error');
    }
});

async function delPay(id) {
    if (!confirm('Bu hareketi silmek istiyor musunuz?')) return;
    const res = await api.deletePayment(id);
    if (res.success) { toast('Silindi'); loadAll(); }
    else toast(res.error || 'Hata', 'error');
}

loadAll();
