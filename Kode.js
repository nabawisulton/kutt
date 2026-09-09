/**
 * KOPERASI DIGITAL KUTT SUKA MAKMUR GRATI
 * Backend Engine & Spreadsheet Database Architecture
 * Version: 1.0.0 Enterprise Production Ready
 */

const CONFIG = {
  APP_NAME: "KUTT Suka Makmur Grati",
  VERSION: "1.0.0",
  // WAJIB DIISI jika script dibuat sebagai project standalone (bukan dari menu Extensions di Sheet)
  // Cara ambil ID: buka Spreadsheet > lihat URL > .../spreadsheets/d/ID_INI_YANG_DICOPY/edit
  // Jika script dibuat dari dalam Sheet (Extensions > Apps Script), biarkan kosong ''.
  SPREADSHEET_ID: "",
  SHEET_NAMES: {
    USERS: "USERS",
    ANGGOTA: "ANGGOTA",
    COA: "COA",
    SIMPANAN: "SIMPANAN",
    PINJAMAN: "PINJAMAN",
    ANGSURAN: "ANGSURAN",
    KAS_MUTASI: "KAS_MUTASI",
    JURNAL: "JURNAL",
    SHU: "SHU_DISTRIBUSI",
    AUDIT: "AUDIT_TRAIL",
    SETTINGS: "SYSTEM_SETTINGS",
    CMS_MEDIA: "CMS_MEDIA",
  },
  // Nama folder Google Drive tempat seluruh gambar CMS (hero & produk)
  // disimpan secara permanen. Folder dibuat otomatis jika belum ada.
  CMS_MEDIA_FOLDER_NAME: "KUTT_CMS_Media_Images",
  // Batas maksimal ukuran file gambar yang diunggah lewat CMS (dalam byte).
  CMS_MAX_IMAGE_BYTES: 2 * 1024 * 1024, // 2 MB
};

function doGet(e) {
  try {
    initDatabase();
    const template = HtmlService.createTemplateFromFile("Index");
    return template
      .evaluate()
      .setTitle("Koperasi Digital - KUTT Suka Makmur Grati")
      .addMetaTag("viewport", "width=device-width, initial-scale=1.0")
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
  } catch (error) {
    return HtmlService.createHtmlOutput(
      "<h3>System Error</h3><p>" +
        error.toString() +
        "</p>" +
        "<p>Cek 2 hal ini:</p><ol>" +
        "<li>Nama file HTML di project Apps Script HARUS <b>Index</b> (Index.html)</li>" +
        "<li>Jika script standalone, isi <b>CONFIG.SPREADSHEET_ID</b> di Code.gs dengan ID Spreadsheet Anda</li>" +
        "</ol>",
    );
  }
}

function getSS() {
  if (CONFIG.SPREADSHEET_ID) {
    return SpreadsheetApp.openById(CONFIG.SPREADSHEET_ID);
  }
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  if (!ss) {
    throw new Error(
      "Spreadsheet tidak ditemukan. Isi CONFIG.SPREADSHEET_ID di Code.gs dengan ID Spreadsheet Anda.",
    );
  }
  return ss;
}

function initDatabase() {
  const ss = getSS();
  let sheetUsers = getOrCreateSheet(ss, CONFIG.SHEET_NAMES.USERS, [
    "user_id",
    "username",
    "email",
    "password_hash",
    "role",
    "full_name",
    "is_active",
    "created_at",
  ]);
  if (sheetUsers.getLastRow() <= 1) {
    sheetUsers.appendRow([
      "USR-001",
      "admin",
      "admin@kuttsukamakmur.coop",
      "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918",
      "SUPER_ADMIN",
      "Administrator Utama",
      true,
      new Date().toISOString(),
    ]);
    sheetUsers.appendRow([
      "USR-002",
      "bendahara",
      "bendahara@kuttsukamakmur.coop",
      "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918",
      "BENDAHARA",
      "Ahmad Hidayat (Bendahara)",
      true,
      new Date().toISOString(),
    ]);
    sheetUsers.appendRow([
      "USR-003",
      "ketua",
      "ketua@kuttsukamakmur.coop",
      "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918",
      "KETUA",
      "H. Sutrisno (Ketua)",
      true,
      new Date().toISOString(),
    ]);
    sheetUsers.appendRow([
      "USR-004",
      "staff",
      "staff@kuttsukamakmur.coop",
      "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918",
      "STAFF",
      "Siti Rahma (Kasir/Staff)",
      true,
      new Date().toISOString(),
    ]);
  }

  let sheetCOA = getOrCreateSheet(ss, CONFIG.SHEET_NAMES.COA, [
    "account_code",
    "account_name",
    "account_category",
    "normal_balance",
  ]);
  if (sheetCOA.getLastRow() <= 1) {
    const defaultCOA = [
      ["1101", "Kas Utama", "ASSET", "DEBIT"],
      ["1102", "Kas Bank Jatim", "ASSET", "DEBIT"],
      ["1103", "Piutang Pinjaman Anggota", "ASSET", "DEBIT"],
      ["2101", "Simpanan Pokok Anggota", "LIABILITY", "CREDIT"],
      ["2102", "Simpanan Wajib Anggota", "LIABILITY", "CREDIT"],
      ["2103", "Simpanan Sukarela Anggota", "LIABILITY", "CREDIT"],
      ["3101", "Modal Cadangan Koperasi", "EQUITY", "CREDIT"],
      ["3102", "SHU Belum Dibagi", "EQUITY", "CREDIT"],
      ["4101", "Pendapatan Jasa Bunga Pinjaman", "REVENUE", "CREDIT"],
      ["4102", "Pendapatan Operasional Unit Susu", "REVENUE", "CREDIT"],
      ["5101", "Beban Operasional & Administrasi", "EXPENSE", "DEBIT"],
      ["5102", "Beban Bunga & Simpanan", "EXPENSE", "DEBIT"],
    ];
    sheetCOA.getRange(2, 1, defaultCOA.length, 4).setValues(defaultCOA);
  }

  const sheetAnggota = getOrCreateSheet(ss, CONFIG.SHEET_NAMES.ANGGOTA, [
    "member_id",
    "nia",
    "nik",
    "nama_lengkap",
    "kelompok_tani",
    "no_hp",
    "alamat_lengkap",
    "status_anggota",
    "created_at",
  ]);
  // PENTING: NIK dan No HP berisi angka panjang (mis. 16 digit) yang oleh
  // Google Sheets sering DIDETEKSI OTOMATIS sebagai tipe Number, bukan Text.
  // Jika ini terjadi, kode frontend yang memanggil .toLowerCase() pada
  // NIK/nama akan ERROR dan menyebabkan SELURUH tabel Master Anggota gagal
  // tampil (kosong), meskipun datanya ada di spreadsheet. Kolom C (nik) dan
  // F (no_hp) dipaksa berformat Plain Text ("@") untuk seluruh baris agar
  // masalah ini tidak pernah terjadi lagi, baik untuk data lama maupun baru.
  sheetAnggota.getRange("C:C").setNumberFormat("@");
  sheetAnggota.getRange("F:F").setNumberFormat("@");
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.SIMPANAN, [
    "trans_id",
    "member_id",
    "jenis_simpanan",
    "tipe_transaksi",
    "nominal",
    "tanggal_trans",
    "operator_user",
    "keterangan",
  ]);
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.PINJAMAN, [
    "loan_id",
    "member_id",
    "pokok_pinjaman",
    "bunga_pertahun",
    "tenor_bulan",
    "sistem_bunga",
    "status_appr",
    "tanggal_pengajuan",
    "agunan",
    "keperluan",
  ]);
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.ANGSURAN, [
    "angsur_id",
    "loan_id",
    "angsuran_ke",
    "bayar_pokok",
    "bayar_bunga",
    "denda",
    "total_bayar",
    "tanggal_bayar",
    "operator_user",
  ]);
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.KAS_MUTASI, [
    "voucher_id",
    "jenis_kas",
    "account_code",
    "nominal",
    "keterangan",
    "tanggal_trans",
    "created_by",
  ]);
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.JURNAL, [
    "journal_id",
    "ref_voucher",
    "tanggal",
    "account_code",
    "debet",
    "kredit",
    "keterangan",
  ]);
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.SHU, [
    "shu_id",
    "tahun_buku",
    "member_id",
    "jasa_modal",
    "jasa_anggota",
    "total_shu",
    "status_pencairan",
  ]);
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.AUDIT, [
    "log_id",
    "timestamp",
    "user_id",
    "action",
    "details",
  ]);
  const sheetSettings = getOrCreateSheet(ss, CONFIG.SHEET_NAMES.SETTINGS, [
    "setting_key",
    "setting_value",
    "updated_at",
    "updated_by",
  ]);
  // Sheet pelacakan seluruh gambar CMS (hero & produk) yang diunggah ke
  // Google Drive, agar setiap file punya jejak audit permanen di Spreadsheet.
  getOrCreateSheet(ss, CONFIG.SHEET_NAMES.CMS_MEDIA, [
    "media_id",
    "file_id",
    "kind",
    "url",
    "uploaded_at",
    "uploaded_by",
  ]);

  // Seed DATA_VERSION counter untuk mekanisme sinkronisasi realtime.
  // Setiap kali ada perubahan data (tambah/edit di modul manapun), counter
  // ini di-increment (lihat bumpDataVersion()). Frontend melakukan polling
  // ringan ke getDataVersion() secara berkala; jika nilainya berubah,
  // frontend otomatis menarik ulang data terbaru tanpa perlu refresh halaman.
  const settingsData = sheetSettings.getDataRange().getValues();
  let hasVersionKey = false;
  for (let i = 1; i < settingsData.length; i++) {
    if (settingsData[i][0] === "DATA_VERSION") {
      hasVersionKey = true;
      break;
    }
  }
  if (!hasVersionKey) {
    sheetSettings.appendRow([
      "DATA_VERSION",
      "1",
      new Date().toISOString(),
      "SYSTEM",
    ]);
  }
}

// ========== REALTIME SYNC ENGINE ==========
// Setiap operasi tulis (create/update) WAJIB memanggil bumpDataVersion(ss)
// tepat sebelum sukses return, agar seluruh client yang sedang membuka
// aplikasi (walau berbeda perangkat/browser) bisa mendeteksi ada data baru
// dan menarik data tersebut secara otomatis (near-realtime, tanpa jeda lama).
function bumpDataVersion(ss) {
  try {
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.SETTINGS);
    if (!sheet) return;
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === "DATA_VERSION") {
        const nextVersion = (Number(data[i][1]) || 0) + 1;
        sheet
          .getRange(i + 1, 2, 1, 2)
          .setValues([[nextVersion, new Date().toISOString()]]);
        return;
      }
    }
    sheet.appendRow(["DATA_VERSION", "1", new Date().toISOString(), "SYSTEM"]);
  } catch (e) {
    // Kegagalan bump versi tidak boleh menggagalkan transaksi utama.
  }
}

// Dipanggil oleh frontend setiap beberapa detik (polling ringan & hemat
// kuota). Hanya membaca 1 baris kecil sehingga sangat cepat.
function getDataVersion() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.SETTINGS);
    if (!sheet) return { success: true, version: 0, serverTime: Date.now() };
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === "DATA_VERSION") {
        return {
          success: true,
          version: Number(data[i][1]) || 0,
          serverTime: Date.now(),
        };
      }
    }
    return { success: true, version: 0, serverTime: Date.now() };
  } catch (error) {
    return { success: false, version: 0, error: error.toString() };
  }
}

function getOrCreateSheet(ss, sheetName, headers) {
  let sheet = ss.getSheetByName(sheetName);
  if (!sheet) {
    sheet = ss.insertSheet(sheetName);
    if (headers && headers.length > 0) {
      sheet
        .getRange(1, 1, 1, headers.length)
        .setValues([headers])
        .setFontWeight("bold")
        .setBackground("#E8F8F0");
      sheet.setFrozenRows(1);
    }
  }
  return sheet;
}

// ========== AUTHENTICATION ==========
function authenticateUser(username, password) {
  try {
    if (!username || !password)
      return { success: false, message: "Username dan password wajib diisi" };
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.USERS);
    if (!sheet)
      return {
        success: false,
        message: "Sheet USERS tidak ditemukan. Jalankan initDatabase() dahulu.",
      };
    const data = sheet.getDataRange().getValues();
    const inputUser = username.toString().trim().toLowerCase();
    const hashed = computeSha256(password);

    for (let i = 1; i < data.length; i++) {
      const rowUsername = (data[i][1] || "").toString().trim().toLowerCase();
      const rowEmail = (data[i][2] || "").toString().trim().toLowerCase();
      const isActive =
        data[i][6] === true || data[i][6] === "TRUE" || data[i][6] === 1;
      if ((rowUsername === inputUser || rowEmail === inputUser) && isActive) {
        if (data[i][3] === hashed) {
          const userObj = {
            user_id: data[i][0],
            username: data[i][1],
            email: data[i][2],
            role: data[i][4],
            full_name: data[i][5],
          };
          logAuditTrail(
            userObj.user_id,
            "LOGIN",
            "User successfully logged in",
          );
          return { success: true, user: userObj, message: "Login berhasil" };
        }
        return { success: false, message: "Password salah" };
      }
    }
    return {
      success: false,
      message: "Username/Email tidak ditemukan atau akun nonaktif",
    };
  } catch (error) {
    return { success: false, message: "Error Server: " + error.toString() };
  }
}

function computeSha256(input) {
  const rawHash = Utilities.computeDigest(
    Utilities.DigestAlgorithm.SHA_256,
    input,
  );
  let txtHash = "";
  for (let j = 0; j < rawHash.length; j++) {
    let pad = (rawHash[j] < 0 ? rawHash[j] + 256 : rawHash[j]).toString(16);
    txtHash += pad.length === 1 ? "0" + pad : pad;
  }
  return txtHash;
}

// ========== DASHBOARD ==========
function getDashboardMetrics() {
  try {
    const ss = getSS();
    const sheetAnggota = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGGOTA);
    const dataAnggota = sheetAnggota
      ? sheetAnggota.getDataRange().getValues()
      : [[]];
    let totalMembers = 0;
    for (let i = 1; i < dataAnggota.length; i++) {
      if (!dataAnggota[i][0]) continue;
      const status = String(dataAnggota[i][7] || "")
        .trim()
        .toUpperCase();
      if (status === "AKTIF") totalMembers++;
    }

    const sheetSimpanan = ss.getSheetByName(CONFIG.SHEET_NAMES.SIMPANAN);
    const dataSimpanan = sheetSimpanan
      ? sheetSimpanan.getDataRange().getValues()
      : [[]];
    let totalSimpanan = 0;
    for (let i = 1; i < dataSimpanan.length; i++) {
      const tipe = dataSimpanan[i][3];
      const nominal = Number(dataSimpanan[i][4]) || 0;
      if (tipe === "SETOR") totalSimpanan += nominal;
      else if (tipe === "TARIK") totalSimpanan -= nominal;
    }

    const sheetPinjaman = ss.getSheetByName(CONFIG.SHEET_NAMES.PINJAMAN);
    const dataPinjaman = sheetPinjaman
      ? sheetPinjaman.getDataRange().getValues()
      : [[]];
    let totalPinjaman = 0;
    let totalApproved = 0;
    let pendingLoans = 0;
    for (let i = 1; i < dataPinjaman.length; i++) {
      const status = dataPinjaman[i][6];
      const nominal = Number(dataPinjaman[i][2]) || 0;
      if (status === "APPROVED" || status === "DISBURSED") {
        totalPinjaman += nominal;
        totalApproved++;
      }
      if (status === "PENDING") pendingLoans++;
    }

    const sheetJurnal = ss.getSheetByName(CONFIG.SHEET_NAMES.JURNAL);
    const dataJurnal = sheetJurnal
      ? sheetJurnal.getDataRange().getValues()
      : [[]];
    let saldoKas = 0;
    let totalRevenue = 0;
    for (let i = 1; i < dataJurnal.length; i++) {
      const coa = String(dataJurnal[i][3]);
      const debet = Number(dataJurnal[i][4]) || 0;
      const kredit = Number(dataJurnal[i][5]) || 0;
      if (coa.startsWith("110")) saldoKas += debet - kredit;
      if (coa.startsWith("41")) totalRevenue += kredit - debet;
    }

    return {
      success: true,
      metrics: {
        totalMembers,
        totalSimpanan,
        totalPinjaman,
        saldoKas,
        totalRevenue,
        pendingLoans,
      },
    };
  } catch (error) {
    return { success: false, error: error.toString() };
  }
}

function getFinancialTrend() {
  try {
    const ss = getSS();
    const dataSimpanan = ss
      .getSheetByName(CONFIG.SHEET_NAMES.SIMPANAN)
      .getDataRange()
      .getValues();
    const dataPinjaman = ss
      .getSheetByName(CONFIG.SHEET_NAMES.PINJAMAN)
      .getDataRange()
      .getValues();
    const monthLabels = [],
      monthKeys = [];
    const now = new Date();
    for (let i = 5; i >= 0; i--) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      monthLabels.push(Utilities.formatDate(d, "GMT+7", "MMM"));
      monthKeys.push(Utilities.formatDate(d, "GMT+7", "yyyy-MM"));
    }
    const simpananSeries = monthKeys.map((monthKey) => {
      const cutoff = new Date(monthKey + "-28");
      let total = 0;
      for (let i = 1; i < dataSimpanan.length; i++) {
        const tgl = new Date(dataSimpanan[i][5]);
        if (isNaN(tgl) || tgl > cutoff) continue;
        total +=
          dataSimpanan[i][3] === "SETOR"
            ? Number(dataSimpanan[i][4])
            : -Number(dataSimpanan[i][4]);
      }
      return Math.max(total, 0);
    });
    const pinjamanSeries = monthKeys.map((monthKey) => {
      const cutoff = new Date(monthKey + "-28");
      let total = 0;
      for (let i = 1; i < dataPinjaman.length; i++) {
        const tgl = new Date(dataPinjaman[i][7]);
        if (isNaN(tgl) || tgl > cutoff) continue;
        if (
          dataPinjaman[i][6] === "APPROVED" ||
          dataPinjaman[i][6] === "DISBURSED" ||
          dataPinjaman[i][6] === "LUNAS"
        ) {
          total += Number(dataPinjaman[i][2]) || 0;
        }
      }
      return total;
    });
    return {
      success: true,
      labels: monthLabels,
      simpanan: simpananSeries,
      pinjaman: pinjamanSeries,
    };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

function getSavingsPortfolio() {
  try {
    const ss = getSS();
    const data = ss
      .getSheetByName(CONFIG.SHEET_NAMES.SIMPANAN)
      .getDataRange()
      .getValues();
    let pokok = 0,
      wajib = 0,
      sukarela = 0;
    for (let i = 1; i < data.length; i++) {
      const jenis = data[i][2];
      const nominal = Number(data[i][4]) || 0;
      const delta = data[i][3] === "SETOR" ? nominal : -nominal;
      if (jenis === "POKOK") pokok += delta;
      else if (jenis === "WAJIB") wajib += delta;
      else if (jenis === "SUKARELA") sukarela += delta;
    }
    return {
      success: true,
      labels: ["Sukarela", "Wajib", "Pokok"],
      values: [Math.max(sukarela, 0), Math.max(wajib, 0), Math.max(pokok, 0)],
    };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

// ========= MEMBERS =========
function getMembersData() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGGOTA);
    if (!sheet) {
      return {
        success: false,
        error: "Sheet ANGGOTA tidak ditemukan",
        members: [],
      };
    }
    const data = sheet.getDataRange().getValues();
    if (data.length < 2) {
      return { success: true, members: [] };
    }
    const members = [];
    for (let i = 1; i < data.length; i++) {
      if (!data[i][0]) continue;
      members.push({
        member_id: String(data[i][0] || ""),
        nia: String(data[i][1] || ""),
        nik: String(data[i][2] || ""),
        nama_lengkap: String(data[i][3] || ""),
        kelompok_tani: String(data[i][4] || ""),
        no_hp: String(data[i][5] || ""),
        alamat_lengkap: String(data[i][6] || ""),
        status_anggota: String(data[i][7] || "AKTIF"),
        created_at: String(data[i][8] || ""),
      });
    }
    return { success: true, members: members };
  } catch (e) {
    return { success: false, error: e.toString(), members: [] };
  }
}

function saveMemberData(memberData, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGGOTA);
    if (!sheet)
      return { success: false, message: "Sheet ANGGOTA tidak ditemukan" };
    if (!memberData || !memberData.nama_lengkap || !memberData.nik) {
      return {
        success: false,
        message: "Nama lengkap dan NIK wajib diisi",
      };
    }
    const year = new Date().getFullYear();
    let memberId = memberData.member_id;
    let nia = memberData.nia;
    if (!memberId) {
      const count = sheet.getLastRow();
      const seq = ("0000" + count).slice(-4);
      memberId =
        "MBR-" +
        year +
        ("0" + (new Date().getMonth() + 1)).slice(-2) +
        "-" +
        seq;
      nia = "KUTT.GRATI." + year + "." + seq;
      sheet.appendRow([
        memberId,
        nia,
        memberData.nik,
        memberData.nama_lengkap,
        memberData.kelompok_tani,
        memberData.no_hp,
        memberData.alamat_lengkap,
        memberData.status_anggota || "AKTIF",
        new Date().toISOString().slice(0, 10),
      ]);
      logAuditTrail(
        userId,
        "CREATE_MEMBER",
        "Tambah Anggota Baru: " + memberData.nama_lengkap,
      );
    } else {
      const data = sheet.getDataRange().getValues();
      for (let i = 1; i < data.length; i++) {
        if (String(data[i][0]).trim() === String(memberId).trim()) {
          sheet
            .getRange(i + 1, 3, 1, 6)
            .setValues([
              [
                memberData.nik,
                memberData.nama_lengkap,
                memberData.kelompok_tani,
                memberData.no_hp,
                memberData.alamat_lengkap,
                memberData.status_anggota,
              ],
            ]);
          break;
        }
      }
      logAuditTrail(userId, "UPDATE_MEMBER", "Update Anggota: " + memberId);
    }
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message: "Data anggota berhasil disimpan",
      member_id: memberId,
      nia: nia,
    };
  } catch (error) {
    return { success: false, message: "Gagal menyimpan: " + error.toString() };
  } finally {
    lock.releaseLock();
  }
}

// ========== SIMPANAN ==========
function processSavingTransaction(transData, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheetSimp = ss.getSheetByName(CONFIG.SHEET_NAMES.SIMPANAN);
    if (!sheetSimp)
      return { success: false, message: "Sheet SIMPANAN tidak ditemukan" };
    if (
      !transData ||
      !transData.member_id ||
      !transData.jenis_simpanan ||
      !transData.tipe_transaksi ||
      !(Number(transData.nominal) > 0)
    ) {
      return {
        success: false,
        message:
          "Data transaksi simpanan tidak lengkap atau nominal tidak valid",
      };
    }
    const transId =
      "SMP-" + Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss");
    const today =
      transData.tanggal_trans ||
      Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd");
    sheetSimp.appendRow([
      transId,
      transData.member_id,
      transData.jenis_simpanan,
      transData.tipe_transaksi,
      Number(transData.nominal),
      today,
      userId || "SYSTEM",
      transData.keterangan || "",
    ]);
    // Auto Jurnal
    const sheetJurnal = ss.getSheetByName(CONFIG.SHEET_NAMES.JURNAL);
    const jrnId1 =
      "JRN-" +
      Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss") +
      "-1";
    const jrnId2 =
      "JRN-" +
      Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss") +
      "-2";
    let simpananCOA = "2103";
    if (transData.jenis_simpanan === "POKOK") simpananCOA = "2101";
    if (transData.jenis_simpanan === "WAJIB") simpananCOA = "2102";
    if (transData.tipe_transaksi === "SETOR") {
      sheetJurnal.appendRow([
        jrnId1,
        transId,
        today,
        "1101",
        Number(transData.nominal),
        0,
        "Setoran " + transData.jenis_simpanan,
      ]);
      sheetJurnal.appendRow([
        jrnId2,
        transId,
        today,
        simpananCOA,
        0,
        Number(transData.nominal),
        "Setoran " + transData.jenis_simpanan,
      ]);
    } else {
      sheetJurnal.appendRow([
        jrnId1,
        transId,
        today,
        simpananCOA,
        Number(transData.nominal),
        0,
        "Penarikan " + transData.jenis_simpanan,
      ]);
      sheetJurnal.appendRow([
        jrnId2,
        transId,
        today,
        "1101",
        0,
        Number(transData.nominal),
        "Penarikan " + transData.jenis_simpanan,
      ]);
    }
    logAuditTrail(
      userId,
      "TRANSACTION_SAVINGS",
      "Setor/Tarik " + transData.jenis_simpanan + " IDR " + transData.nominal,
    );
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message: "Transaksi simpanan berhasil diproses",
      trans_id: transId,
    };
  } catch (error) {
    return {
      success: false,
      message: "Gagal transaksi simpanan: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function getSavingsData() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.SIMPANAN);
    if (!sheet) return { success: true, items: [] };
    const data = sheet.getDataRange().getValues();
    const sheetAnggota = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGGOTA);
    const dataAnggota = sheetAnggota
      ? sheetAnggota.getDataRange().getValues()
      : [];
    const namaMap = {};
    for (let i = 1; i < dataAnggota.length; i++) {
      namaMap[dataAnggota[i][0]] = dataAnggota[i][3];
    }
    const items = [];
    for (let i = 1; i < data.length; i++) {
      items.push({
        trans_id: data[i][0],
        member_id: data[i][1],
        nama_lengkap: namaMap[data[i][1]] || data[i][1],
        jenis_simpanan: data[i][2],
        tipe_transaksi: data[i][3],
        nominal: data[i][4],
        tanggal_trans: data[i][5],
        operator_user: data[i][6],
        keterangan: data[i][7],
      });
    }
    const reversed = items.reverse();
    return { success: true, items: reversed.slice(0, 200) };
  } catch (e) {
    return { success: false, error: e.toString(), items: [] };
  }
}

// ========== KAS ==========
function saveKasVoucher(voucherData, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheetKas = ss.getSheetByName(CONFIG.SHEET_NAMES.KAS_MUTASI);
    if (!sheetKas)
      return { success: false, message: "Sheet KAS_MUTASI tidak ditemukan" };
    if (
      !voucherData ||
      !voucherData.jenis_kas ||
      !voucherData.account_code ||
      !(Number(voucherData.nominal) > 0)
    ) {
      return {
        success: false,
        message: "Lengkapi jenis kas, akun COA, dan nominal (harus > 0)",
      };
    }
    const prefix = voucherData.jenis_kas === "MASUK" ? "VKM" : "VKK";
    const voucherId =
      prefix +
      "-" +
      Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss");
    const today =
      voucherData.tanggal_trans ||
      Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd");
    const nominal = Number(voucherData.nominal);
    sheetKas.appendRow([
      voucherId,
      voucherData.jenis_kas,
      voucherData.account_code,
      nominal,
      voucherData.keterangan || "-",
      today,
      userId || "SYSTEM",
    ]);
    const sheetJurnal = ss.getSheetByName(CONFIG.SHEET_NAMES.JURNAL);
    const jrnBase =
      "JRN-" + Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss");
    if (voucherData.jenis_kas === "MASUK") {
      sheetJurnal.appendRow([
        jrnBase + "-1",
        voucherId,
        today,
        "1101",
        nominal,
        0,
        voucherData.keterangan || "Kas Masuk",
      ]);
      sheetJurnal.appendRow([
        jrnBase + "-2",
        voucherId,
        today,
        voucherData.account_code,
        0,
        nominal,
        voucherData.keterangan || "Kas Masuk",
      ]);
    } else {
      sheetJurnal.appendRow([
        jrnBase + "-1",
        voucherId,
        today,
        voucherData.account_code,
        nominal,
        0,
        voucherData.keterangan || "Kas Keluar",
      ]);
      sheetJurnal.appendRow([
        jrnBase + "-2",
        voucherId,
        today,
        "1101",
        0,
        nominal,
        voucherData.keterangan || "Kas Keluar",
      ]);
    }
    logAuditTrail(
      userId,
      "KAS_VOUCHER",
      voucherData.jenis_kas + " Rp" + nominal,
    );
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message: "Voucher kas berhasil disimpan",
      voucher_id: voucherId,
    };
  } catch (error) {
    return {
      success: false,
      message: "Gagal menyimpan voucher kas: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function getKasMutasiData() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.KAS_MUTASI);
    const data = sheet.getDataRange().getValues();
    const items = [];
    for (let i = 1; i < data.length; i++) {
      items.push({
        voucher_id: data[i][0],
        jenis_kas: data[i][1],
        account_code: data[i][2],
        nominal: data[i][3],
        keterangan: data[i][4],
        tanggal_trans: data[i][5],
        created_by: data[i][6],
      });
    }
    return { success: true, items: items.reverse() };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

// ========== PINJAMAN ==========
function getLoansData() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.PINJAMAN);
    const data = sheet.getDataRange().getValues();
    const loans = [];
    for (let i = 1; i < data.length; i++) {
      loans.push({
        loan_id: data[i][0],
        member_id: data[i][1],
        pokok_pinjaman: data[i][2],
        bunga_pertahun: data[i][3],
        tenor_bulan: data[i][4],
        sistem_bunga: data[i][5],
        status_appr: data[i][6],
        tanggal_pengajuan: data[i][7],
        agunan: data[i][8],
        keperluan: data[i][9],
      });
    }
    return { success: true, loans: loans };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

function submitLoanApplication(loanData, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.PINJAMAN);
    if (!sheet)
      return { success: false, message: "Sheet PINJAMAN tidak ditemukan" };
    if (
      !loanData ||
      !loanData.member_id ||
      !(Number(loanData.pokok_pinjaman) > 0) ||
      !(Number(loanData.tenor_bulan) > 0)
    ) {
      return {
        success: false,
        message: "Lengkapi anggota, pokok pinjaman, dan tenor dengan benar",
      };
    }
    const loanId =
      "LNM-" +
      Utilities.formatDate(new Date(), "GMT+7", "yyyyMM") +
      "-" +
      ("000" + sheet.getLastRow()).slice(-3);
    sheet.appendRow([
      loanId,
      loanData.member_id,
      Number(loanData.pokok_pinjaman),
      Number(loanData.bunga_pertahun || 12),
      Number(loanData.tenor_bulan),
      loanData.sistem_bunga || "FLAT",
      "PENDING",
      Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd"),
      loanData.agunan || "-",
      loanData.keperluan || "Modal Usaha Tani",
    ]);
    logAuditTrail(userId, "SUBMIT_LOAN", "Pengajuan Pinjaman Baru: " + loanId);
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message: "Pengajuan pinjaman berhasil diserahkan",
      loan_id: loanId,
    };
  } catch (e) {
    return { success: false, message: e.toString() };
  } finally {
    lock.releaseLock();
  }
}

function updateLoanStatus(loanId, newStatus, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.PINJAMAN);
    if (!sheet)
      return { success: false, message: "Sheet PINJAMAN tidak ditemukan" };
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === loanId) {
        if (data[i][6] === newStatus) {
          return {
            success: false,
            message: "Status pinjaman sudah " + newStatus + " sebelumnya",
          };
        }
        sheet.getRange(i + 1, 7).setValue(newStatus);
        if (newStatus === "APPROVED" || newStatus === "DISBURSED") {
          const sheetJurnal = ss.getSheetByName(CONFIG.SHEET_NAMES.JURNAL);
          const today = Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd");
          const nominal = data[i][2];
          sheetJurnal.appendRow([
            "JRN-" +
              Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss") +
              "-D",
            loanId,
            today,
            "1103",
            Number(nominal),
            0,
            "Pencairan Pinjaman",
          ]);
          sheetJurnal.appendRow([
            "JRN-" +
              Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss") +
              "-K",
            loanId,
            today,
            "1101",
            0,
            Number(nominal),
            "Pencairan Pinjaman",
          ]);
        }
        logAuditTrail(
          userId,
          "LOAN_APPROVAL",
          "Status Pinjaman " + loanId + " diubah ke " + newStatus,
        );
        SpreadsheetApp.flush();
        bumpDataVersion(ss);
        return {
          success: true,
          message: "Status pinjaman berhasil diperbarui",
        };
      }
    }
    return { success: false, message: "Pinjaman tidak ditemukan" };
  } catch (e) {
    return { success: false, message: e.toString() };
  } finally {
    lock.releaseLock();
  }
}

// ========== ANGSURAN ==========
function processInstallmentPayment(angsuranData, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheetAngsuran = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGSURAN);
    if (!sheetAngsuran)
      return { success: false, message: "Sheet ANGSURAN tidak ditemukan" };
    if (!angsuranData || !angsuranData.loan_id) {
      return { success: false, message: "ID pinjaman wajib diisi" };
    }
    const bayarPokok = Number(angsuranData.bayar_pokok) || 0;
    const bayarBunga = Number(angsuranData.bayar_bunga) || 0;
    const denda = Number(angsuranData.denda) || 0;
    const totalBayar = bayarPokok + bayarBunga + denda;
    const today =
      angsuranData.tanggal_bayar ||
      Utilities.formatDate(new Date(), "GMT+7", "yyyy-MM-dd");
    const angsurId =
      "ANG-" + Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss");
    sheetAngsuran.appendRow([
      angsurId,
      angsuranData.loan_id,
      angsuranData.angsuran_ke,
      bayarPokok,
      bayarBunga,
      denda,
      totalBayar,
      today,
      userId || "SYSTEM",
    ]);
    const sheetJurnal = ss.getSheetByName(CONFIG.SHEET_NAMES.JURNAL);
    const jrnBase =
      "JRN-" + Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss");
    sheetJurnal.appendRow([
      jrnBase + "-1",
      angsurId,
      today,
      "1101",
      totalBayar,
      0,
      "Angsuran ke-" + angsuranData.angsuran_ke,
    ]);
    if (bayarPokok > 0)
      sheetJurnal.appendRow([
        jrnBase + "-2",
        angsurId,
        today,
        "1103",
        0,
        bayarPokok,
        "Pokok Angsuran",
      ]);
    if (bayarBunga + denda > 0)
      sheetJurnal.appendRow([
        jrnBase + "-3",
        angsurId,
        today,
        "4101",
        0,
        bayarBunga + denda,
        "Bunga/Denda",
      ]);
    // Cek LUNAS
    const sheetPinjaman = ss.getSheetByName(CONFIG.SHEET_NAMES.PINJAMAN);
    const dataPinjaman = sheetPinjaman.getDataRange().getValues();
    const dataAngsuran = sheetAngsuran.getDataRange().getValues();
    for (let i = 1; i < dataPinjaman.length; i++) {
      if (dataPinjaman[i][0] === angsuranData.loan_id) {
        let totalPokokDibayar = 0;
        for (let j = 1; j < dataAngsuran.length; j++) {
          if (dataAngsuran[j][1] === angsuranData.loan_id)
            totalPokokDibayar += Number(dataAngsuran[j][3]) || 0;
        }
        if (totalPokokDibayar >= Number(dataPinjaman[i][2]))
          sheetPinjaman.getRange(i + 1, 7).setValue("LUNAS");
        break;
      }
    }
    logAuditTrail(
      userId,
      "BAYAR_ANGSURAN",
      "Angsuran ke-" +
        angsuranData.angsuran_ke +
        " pinjaman " +
        angsuranData.loan_id +
        " Rp" +
        totalBayar,
    );
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message: "Pembayaran angsuran berhasil dicatat",
      angsur_id: angsurId,
    };
  } catch (error) {
    return {
      success: false,
      message: "Gagal mencatat angsuran: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function getAngsuranData(loanId) {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGSURAN);
    const data = sheet.getDataRange().getValues();
    const items = [];
    for (let i = 1; i < data.length; i++) {
      if (!loanId || data[i][1] === loanId) {
        items.push({
          angsur_id: data[i][0],
          loan_id: data[i][1],
          angsuran_ke: data[i][2],
          bayar_pokok: data[i][3],
          bayar_bunga: data[i][4],
          denda: data[i][5],
          total_bayar: data[i][6],
          tanggal_bayar: data[i][7],
          operator_user: data[i][8],
        });
      }
    }
    return { success: true, items: items.reverse() };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

// ========== AKUNTANSI ==========
function getAccountingJournal() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.JURNAL);
    const data = sheet.getDataRange().getValues();
    const journals = [];
    for (let i = 1; i < data.length; i++) {
      journals.push({
        journal_id: data[i][0],
        ref_voucher: data[i][1],
        tanggal: data[i][2],
        account_code: data[i][3],
        debet: data[i][4],
        kredit: data[i][5],
        keterangan: data[i][6],
      });
    }
    return { success: true, journals: journals };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

function getCOAList() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.COA);
    const data = sheet.getDataRange().getValues();
    const coaList = [];
    for (let i = 1; i < data.length; i++) {
      coaList.push({
        account_code: data[i][0],
        account_name: data[i][1],
        account_category: data[i][2],
        normal_balance: data[i][3],
      });
    }
    return { success: true, coa: coaList };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

function saveCOAAccount(coaData, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.COA);
    if (!sheet) return { success: false, message: "Sheet COA tidak ditemukan" };
    if (!coaData || !coaData.account_code || !coaData.account_name) {
      return { success: false, message: "Kode akun dan nama akun wajib diisi" };
    }
    const code = String(coaData.account_code).trim();
    const name = String(coaData.account_name).trim();
    const category = String(coaData.account_category || "")
      .trim()
      .toUpperCase();
    const normalBalance = String(coaData.normal_balance || "")
      .trim()
      .toUpperCase();
    if (!/^[0-9]{3,6}$/.test(code)) {
      return {
        success: false,
        message: "Kode akun harus berupa angka (3-6 digit), contoh: 4103",
      };
    }
    if (
      !["ASSET", "LIABILITY", "EQUITY", "REVENUE", "EXPENSE"].includes(category)
    ) {
      return { success: false, message: "Kategori akun tidak valid" };
    }
    if (!["DEBIT", "CREDIT"].includes(normalBalance)) {
      return {
        success: false,
        message: "Saldo normal harus DEBIT atau CREDIT",
      };
    }
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (String(data[i][0]).trim() === code) {
        return {
          success: false,
          message: "Kode akun " + code + " sudah terdaftar",
        };
      }
    }
    sheet.appendRow([code, name, category, normalBalance]);
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    logAuditTrail(
      userId,
      "CREATE_COA",
      "Tambah Akun COA Baru: " + code + " - " + name,
    );
    return {
      success: true,
      message: "Akun COA baru berhasil ditambahkan",
      coa: {
        account_code: code,
        account_name: name,
        account_category: category,
        normal_balance: normalBalance,
      },
    };
  } catch (error) {
    return {
      success: false,
      message: "Gagal menyimpan akun COA: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

// ========== SHU ==========
function calculateSHU(
  tahunBuku,
  totalSHUBersih,
  persenModal,
  persenAnggota,
  userId,
) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    if (!tahunBuku || !(Number(totalSHUBersih) >= 0)) {
      return {
        success: false,
        message: "Tahun buku dan total SHU bersih wajib diisi dengan benar",
      };
    }
    const sheetAnggota = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGGOTA);
    const sheetSimpanan = ss.getSheetByName(CONFIG.SHEET_NAMES.SIMPANAN);
    const sheetSHU = ss.getSheetByName(CONFIG.SHEET_NAMES.SHU);
    if (!sheetAnggota || !sheetSimpanan || !sheetSHU) {
      return {
        success: false,
        message: "Sheet ANGGOTA/SIMPANAN/SHU_DISTRIBUSI tidak ditemukan",
      };
    }
    const dataAnggota = sheetAnggota.getDataRange().getValues();
    const dataSimpanan = sheetSimpanan.getDataRange().getValues();

    const saldoPerAnggota = {},
      volumeSetorPerAnggota = {};
    for (let i = 1; i < dataSimpanan.length; i++) {
      const memberId = dataSimpanan[i][1];
      const tipe = dataSimpanan[i][3];
      const nominal = Number(dataSimpanan[i][4]) || 0;
      if (!saldoPerAnggota[memberId]) saldoPerAnggota[memberId] = 0;
      if (!volumeSetorPerAnggota[memberId]) volumeSetorPerAnggota[memberId] = 0;
      if (tipe === "SETOR") {
        saldoPerAnggota[memberId] += nominal;
        volumeSetorPerAnggota[memberId] += nominal;
      } else if (tipe === "TARIK") saldoPerAnggota[memberId] -= nominal;
    }

    const totalSaldo =
      Object.values(saldoPerAnggota).reduce((a, b) => a + Math.max(b, 0), 0) ||
      1;
    const totalVolume =
      Object.values(volumeSetorPerAnggota).reduce((a, b) => a + b, 0) || 1;

    const poolModal = Number(totalSHUBersih) * (Number(persenModal) / 100);
    const poolAnggota = Number(totalSHUBersih) * (Number(persenAnggota) / 100);

    // Hapus data lama tahun ini
    const dataSHU = sheetSHU.getDataRange().getValues();
    for (let i = dataSHU.length - 1; i >= 1; i--) {
      if (String(dataSHU[i][1]) === String(tahunBuku))
        sheetSHU.deleteRow(i + 1);
    }

    const hasil = [];
    for (let i = 1; i < dataAnggota.length; i++) {
      if (dataAnggota[i][7] !== "AKTIF") continue;
      const memberId = dataAnggota[i][0];
      const saldo = Math.max(saldoPerAnggota[memberId] || 0, 0);
      const volume = volumeSetorPerAnggota[memberId] || 0;
      const jasaModal = Math.round((saldo / totalSaldo) * poolModal);
      const jasaAnggota = Math.round((volume / totalVolume) * poolAnggota);
      const totalSHUMember = jasaModal + jasaAnggota;
      const shuId = "SHU-" + tahunBuku + "-" + memberId;
      sheetSHU.appendRow([
        shuId,
        tahunBuku,
        memberId,
        jasaModal,
        jasaAnggota,
        totalSHUMember,
        "BELUM_CAIR",
      ]);
      hasil.push({
        member_id: memberId,
        nama_lengkap: dataAnggota[i][3],
        nia: dataAnggota[i][1],
        jasa_modal: jasaModal,
        jasa_anggota: jasaAnggota,
        total_shu: totalSHUMember,
        status_pencairan: "BELUM_CAIR",
      });
    }

    logAuditTrail(
      userId,
      "CALCULATE_SHU",
      "Kalkulasi SHU tahun " + tahunBuku + " total Rp" + totalSHUBersih,
    );
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message:
        "SHU tahun " +
        tahunBuku +
        " berhasil dihitung untuk " +
        hasil.length +
        " anggota",
      data: hasil,
      poolModal: poolModal,
      poolAnggota: poolAnggota,
    };
  } catch (error) {
    return {
      success: false,
      message: "Gagal menghitung SHU: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function getSHUData(tahunBuku) {
  try {
    const ss = getSS();
    const sheetSHU = ss.getSheetByName(CONFIG.SHEET_NAMES.SHU);
    const sheetAnggota = ss.getSheetByName(CONFIG.SHEET_NAMES.ANGGOTA);
    const dataSHU = sheetSHU.getDataRange().getValues();
    const dataAnggota = sheetAnggota.getDataRange().getValues();
    const namaMap = {},
      niaMap = {};
    for (let i = 1; i < dataAnggota.length; i++) {
      namaMap[dataAnggota[i][0]] = dataAnggota[i][3];
      niaMap[dataAnggota[i][0]] = dataAnggota[i][1];
    }
    const items = [];
    let poolModal = 0,
      poolAnggota = 0;
    for (let i = 1; i < dataSHU.length; i++) {
      if (String(dataSHU[i][1]) === String(tahunBuku)) {
        poolModal += Number(dataSHU[i][3]) || 0;
        poolAnggota += Number(dataSHU[i][4]) || 0;
        items.push({
          member_id: dataSHU[i][2],
          nama_lengkap: namaMap[dataSHU[i][2]] || "-",
          nia: niaMap[dataSHU[i][2]] || "-",
          jasa_modal: dataSHU[i][3],
          jasa_anggota: dataSHU[i][4],
          total_shu: dataSHU[i][5],
          status_pencairan: dataSHU[i][6],
        });
      }
    }
    return {
      success: true,
      items: items,
      poolModal: poolModal,
      poolAnggota: poolAnggota,
    };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

// ========== CMS ==========
function getCmsSettings() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.SETTINGS);
    if (!sheet) return { success: true, data: null };
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === "CMS_LANDING")
        return { success: true, data: JSON.parse(data[i][1]) };
    }
    return { success: true, data: null };
  } catch (error) {
    return {
      success: false,
      message: "Error Server: " + error.toString(),
      data: null,
    };
  }
}

function saveCmsSettings(cmsData, userId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.SETTINGS);
    if (!sheet)
      return {
        success: false,
        message: "Sheet SYSTEM_SETTINGS tidak ditemukan.",
      };
    if (!cmsData) {
      return { success: false, message: "Data CMS tidak valid" };
    }
    const data = sheet.getDataRange().getValues();
    const jsonValue = JSON.stringify(cmsData);
    const now = new Date().toISOString();
    let found = false;
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === "CMS_LANDING") {
        sheet
          .getRange(i + 1, 2, 1, 3)
          .setValues([[jsonValue, now, userId || "SYSTEM"]]);
        found = true;
        break;
      }
    }
    if (!found)
      sheet.appendRow(["CMS_LANDING", jsonValue, now, userId || "SYSTEM"]);
    logAuditTrail(
      userId,
      "UPDATE_CMS",
      "Mengubah pengaturan Landing Page (CMS)",
    );
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message: "Pengaturan Landing Page berhasil disimpan",
    };
  } catch (error) {
    return { success: false, message: "Error Server: " + error.toString() };
  } finally {
    lock.releaseLock();
  }
}

// ========== CMS MEDIA (UPLOAD GAMBAR KE GOOGLE DRIVE) ==========
function getOrCreateCmsMediaFolder_() {
  const folderName = CONFIG.CMS_MEDIA_FOLDER_NAME;
  const folders = DriveApp.getFoldersByName(folderName);
  if (folders.hasNext()) return folders.next();
  return DriveApp.createFolder(folderName);
}

function uploadCmsImage(base64Data, mimeType, kind, oldFileId, actingUserId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(15000);
    if (!base64Data) {
      return {
        success: false,
        message: "Data gambar kosong atau tidak terkirim",
      };
    }
    if (!mimeType || mimeType.toString().indexOf("image/") !== 0) {
      return {
        success: false,
        message: "File yang diunggah harus berupa gambar (JPG/PNG/WEBP)",
      };
    }
    let rawBytes;
    try {
      rawBytes = Utilities.base64Decode(base64Data);
    } catch (e) {
      return {
        success: false,
        message: "Data gambar tidak valid / rusak saat diunggah",
      };
    }
    if (rawBytes.length > CONFIG.CMS_MAX_IMAGE_BYTES) {
      return {
        success: false,
        message:
          "Ukuran gambar " +
          (rawBytes.length / (1024 * 1024)).toFixed(2) +
          " MB melebihi batas maksimal 2 MB. Silakan kompres gambar terlebih dahulu.",
      };
    }

    const ss = getSS();
    const folder = getOrCreateCmsMediaFolder_();
    const ext = (mimeType.split("/")[1] || "jpg").replace(/[^a-z0-9]/gi, "");
    const safeKind =
      (kind || "media").toString().replace(/[^a-zA-Z0-9_-]/g, "") || "media";
    const fileName =
      "CMS_" +
      safeKind +
      "_" +
      Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd_HHmmss") +
      "_" +
      Math.floor(Math.random() * 9000 + 1000) +
      "." +
      ext;
    const blob = Utilities.newBlob(rawBytes, mimeType, fileName);
    const file = folder.createFile(blob);
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
    const fileId = file.getId();
    const url = "https://lh3.googleusercontent.com/d/" + fileId;

    const sheetMedia = ss.getSheetByName(CONFIG.SHEET_NAMES.CMS_MEDIA);
    if (sheetMedia) {
      const mediaId =
        "MED-" +
        Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss") +
        "-" +
        Math.floor(Math.random() * 1000);
      sheetMedia.appendRow([
        mediaId,
        fileId,
        safeKind,
        url,
        new Date().toISOString(),
        actingUserId || "SYSTEM",
      ]);
    }

    if (oldFileId) {
      try {
        DriveApp.getFileById(oldFileId).setTrashed(true);
      } catch (e) {}
    }

    logAuditTrail(
      actingUserId,
      "UPLOAD_CMS_IMAGE",
      "Upload gambar CMS (" + safeKind + "): " + fileName,
    );
    SpreadsheetApp.flush();
    bumpDataVersion(ss);
    return {
      success: true,
      message: "Gambar berhasil diunggah & tersimpan permanen di Google Drive",
      url: url,
      fileId: fileId,
    };
  } catch (error) {
    return {
      success: false,
      message: "Gagal mengunggah gambar: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function deleteCmsImage(fileId, actingUserId) {
  try {
    if (!fileId)
      return { success: true, message: "Tidak ada file untuk dihapus" };
    DriveApp.getFileById(fileId).setTrashed(true);
    logAuditTrail(
      actingUserId,
      "DELETE_CMS_IMAGE",
      "Hapus gambar CMS, file_id: " + fileId,
    );
    return {
      success: true,
      message: "Gambar berhasil dihapus dari Google Drive",
    };
  } catch (error) {
    return {
      success: false,
      message: "Gagal menghapus gambar: " + error.toString(),
    };
  }
}

// ========== AUDIT & BACKUP ==========
function logAuditTrail(userId, action, details) {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.AUDIT);
    const logId =
      "LOG-" + Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd-HHmmss");
    sheet.appendRow([
      logId,
      new Date().toISOString(),
      userId || "ANONYMOUS",
      action,
      details,
    ]);
  } catch (e) {}
}

function getAuditLogs() {
  try {
    const ss = getSS();
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.AUDIT);
    const data = sheet.getDataRange().getValues();
    const logs = [];
    for (let i = Math.max(1, data.length - 100); i < data.length; i++) {
      logs.push({
        log_id: data[i][0],
        timestamp: data[i][1],
        user_id: data[i][2],
        action: data[i][3],
        details: data[i][4],
      });
    }
    return { success: true, logs: logs.reverse() };
  } catch (e) {
    return { success: false, error: e.toString() };
  }
}

function executeSpreadsheetBackup() {
  try {
    const ss = getSS();
    const backupName =
      "BACKUP_" +
      CONFIG.APP_NAME +
      "_" +
      Utilities.formatDate(new Date(), "GMT+7", "yyyyMMdd_HHmmss");
    const file = DriveApp.getFileById(ss.getId()).makeCopy(backupName);
    logAuditTrail(
      "SYSTEM",
      "BACKUP_DATABASE",
      "Created spreadsheet snapshot: " + backupName,
    );
    return {
      success: true,
      message: "Backup database berhasil disimpan di Google Drive",
      backup_url: file.getUrl(),
    };
  } catch (e) {
    return { success: false, message: "Gagal backup Drive: " + e.toString() };
  }
}

// ========== USER MANAGEMENT ==========
const VALID_USER_ROLES = ["SUPER_ADMIN", "BENDAHARA", "KETUA", "STAFF"];

function getUserRowInfo(ss, userId) {
  const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.USERS);
  if (!sheet) return null;
  const data = sheet.getDataRange().getValues();
  for (let i = 1; i < data.length; i++) {
    if (data[i][0] === userId) {
      return {
        rowNumber: i + 1,
        role: data[i][4],
        isActive:
          data[i][6] === true || data[i][6] === "TRUE" || data[i][6] === 1,
      };
    }
  }
  return null;
}

function requireSuperAdmin(ss, actingUserId) {
  const info = getUserRowInfo(ss, actingUserId);
  return !!(info && info.isActive && info.role === "SUPER_ADMIN");
}

function countActiveSuperAdmins(data) {
  let total = 0;
  for (let i = 1; i < data.length; i++) {
    const isActiveRow =
      data[i][6] === true || data[i][6] === "TRUE" || data[i][6] === 1;
    if (data[i][4] === "SUPER_ADMIN" && isActiveRow) total++;
  }
  return total;
}

function getUsersData(actingUserId) {
  try {
    const ss = getSS();
    if (!requireSuperAdmin(ss, actingUserId)) {
      return {
        success: false,
        message: "Akses ditolak: hanya Super Admin yang dapat mengelola user",
      };
    }
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.USERS);
    const data = sheet.getDataRange().getValues();
    const users = [];
    for (let i = 1; i < data.length; i++) {
      users.push({
        user_id: data[i][0],
        username: data[i][1],
        email: data[i][2],
        role: data[i][4],
        full_name: data[i][5],
        is_active:
          data[i][6] === true || data[i][6] === "TRUE" || data[i][6] === 1,
        created_at: data[i][7],
      });
    }
    return { success: true, users: users };
  } catch (e) {
    return { success: false, message: "Error Server: " + e.toString() };
  }
}

function saveUserData(userData, actingUserId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    if (!requireSuperAdmin(ss, actingUserId)) {
      return {
        success: false,
        message: "Akses ditolak: hanya Super Admin yang dapat mengelola user",
      };
    }
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.USERS);
    if (!sheet)
      return { success: false, message: "Sheet USERS tidak ditemukan" };
    if (
      !userData ||
      !userData.username ||
      !userData.full_name ||
      !userData.role
    ) {
      return {
        success: false,
        message: "Username, nama lengkap, dan role wajib diisi",
      };
    }
    if (VALID_USER_ROLES.indexOf(userData.role) === -1) {
      return { success: false, message: "Role tidak valid" };
    }
    const data = sheet.getDataRange().getValues();
    const usernameClean = userData.username.toString().trim().toLowerCase();
    for (let i = 1; i < data.length; i++) {
      const rowUsername = (data[i][1] || "").toString().trim().toLowerCase();
      if (rowUsername === usernameClean && data[i][0] !== userData.user_id) {
        return { success: false, message: "Username sudah dipakai user lain" };
      }
    }

    if (!userData.user_id) {
      if (!userData.password || userData.password.length < 6) {
        return {
          success: false,
          message: "Password wajib diisi, minimal 6 karakter",
        };
      }
      const existingIds = data.map(function (r) {
        return r[0];
      });
      let seq = sheet.getLastRow();
      let newId = "USR-" + ("000" + seq).slice(-3);
      while (existingIds.indexOf(newId) !== -1) {
        seq++;
        newId = "USR-" + ("000" + seq).slice(-3);
      }
      sheet.appendRow([
        newId,
        userData.username.trim(),
        userData.email || "",
        computeSha256(userData.password),
        userData.role,
        userData.full_name,
        true,
        new Date().toISOString(),
      ]);
      logAuditTrail(
        actingUserId,
        "CREATE_USER",
        "Membuat user baru: " + userData.username + " (" + userData.role + ")",
      );
      SpreadsheetApp.flush();
      bumpDataVersion(ss);
      return {
        success: true,
        message: "User baru berhasil dibuat",
        user_id: newId,
      };
    } else {
      let rowIndex = -1;
      for (let j = 1; j < data.length; j++) {
        if (data[j][0] === userData.user_id) {
          rowIndex = j;
          break;
        }
      }
      if (rowIndex === -1)
        return { success: false, message: "User tidak ditemukan" };

      if (
        data[rowIndex][4] === "SUPER_ADMIN" &&
        userData.role !== "SUPER_ADMIN" &&
        countActiveSuperAdmins(data) <= 1
      ) {
        return {
          success: false,
          message:
            "Tidak bisa mengubah role: ini adalah Super Admin aktif terakhir",
        };
      }

      sheet.getRange(rowIndex + 1, 2, 1, 1).setValue(userData.username.trim());
      sheet.getRange(rowIndex + 1, 3, 1, 1).setValue(userData.email || "");
      sheet.getRange(rowIndex + 1, 5, 1, 1).setValue(userData.role);
      sheet.getRange(rowIndex + 1, 6, 1, 1).setValue(userData.full_name);
      logAuditTrail(
        actingUserId,
        "UPDATE_USER",
        "Update user: " + userData.user_id,
      );
      SpreadsheetApp.flush();
      bumpDataVersion(ss);
      return {
        success: true,
        message: "Data user berhasil diperbarui",
        user_id: userData.user_id,
      };
    }
  } catch (error) {
    return {
      success: false,
      message: "Gagal menyimpan user: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function resetUserPassword(targetUserId, newPassword, actingUserId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    if (!requireSuperAdmin(ss, actingUserId)) {
      return {
        success: false,
        message: "Akses ditolak: hanya Super Admin yang dapat reset password",
      };
    }
    if (!newPassword || newPassword.length < 6) {
      return { success: false, message: "Password baru minimal 6 karakter" };
    }
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.USERS);
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === targetUserId) {
        sheet.getRange(i + 1, 4, 1, 1).setValue(computeSha256(newPassword));
        logAuditTrail(
          actingUserId,
          "RESET_PASSWORD",
          "Reset password user: " + targetUserId,
        );
        SpreadsheetApp.flush();
        bumpDataVersion(ss);
        return { success: true, message: "Password berhasil direset" };
      }
    }
    return { success: false, message: "User tidak ditemukan" };
  } catch (error) {
    return {
      success: false,
      message: "Gagal reset password: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function toggleUserActive(targetUserId, newActiveState, actingUserId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    if (!requireSuperAdmin(ss, actingUserId)) {
      return {
        success: false,
        message:
          "Akses ditolak: hanya Super Admin yang dapat mengaktifkan/menonaktifkan user",
      };
    }
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.USERS);
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === targetUserId) {
        if (
          data[i][4] === "SUPER_ADMIN" &&
          newActiveState === false &&
          countActiveSuperAdmins(data) <= 1
        ) {
          return {
            success: false,
            message:
              "Tidak bisa menonaktifkan: ini adalah Super Admin aktif terakhir",
          };
        }
        sheet.getRange(i + 1, 7, 1, 1).setValue(newActiveState);
        logAuditTrail(
          actingUserId,
          newActiveState ? "ACTIVATE_USER" : "DEACTIVATE_USER",
          "User: " + targetUserId,
        );
        SpreadsheetApp.flush();
        bumpDataVersion(ss);
        return {
          success: true,
          message: newActiveState
            ? "User diaktifkan kembali"
            : "User dinonaktifkan",
        };
      }
    }
    return { success: false, message: "User tidak ditemukan" };
  } catch (error) {
    return {
      success: false,
      message: "Gagal mengubah status user: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}

function deleteUserAccount(targetUserId, actingUserId) {
  const lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    const ss = getSS();
    if (!requireSuperAdmin(ss, actingUserId)) {
      return {
        success: false,
        message: "Akses ditolak: hanya Super Admin yang dapat menghapus user",
      };
    }
    if (targetUserId === actingUserId) {
      return {
        success: false,
        message:
          "Tidak bisa menghapus akun sendiri saat sedang login. Minta Super Admin lain untuk menghapus, atau nonaktifkan akun ini.",
      };
    }
    const sheet = ss.getSheetByName(CONFIG.SHEET_NAMES.USERS);
    const data = sheet.getDataRange().getValues();
    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === targetUserId) {
        if (data[i][4] === "SUPER_ADMIN" && countActiveSuperAdmins(data) <= 1) {
          return {
            success: false,
            message:
              "Tidak bisa menghapus: ini adalah Super Admin aktif terakhir",
          };
        }
        const deletedUsername = data[i][1];
        sheet.deleteRow(i + 1);
        logAuditTrail(
          actingUserId,
          "DELETE_USER",
          "Menghapus user: " + deletedUsername + " (" + targetUserId + ")",
        );
        SpreadsheetApp.flush();
        bumpDataVersion(ss);
        return { success: true, message: "User berhasil dihapus" };
      }
    }
    return { success: false, message: "User tidak ditemukan" };
  } catch (error) {
    return {
      success: false,
      message: "Gagal menghapus user: " + error.toString(),
    };
  } finally {
    lock.releaseLock();
  }
}